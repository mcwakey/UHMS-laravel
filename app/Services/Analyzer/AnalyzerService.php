<?php

namespace App\Services\Analyzer;

use App\Models\Analyzer;
use App\Models\AnalyzerRawMessage;
use App\Models\AnalyzerTestMapping;
use App\Models\LabTest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyzerService
{
    /*
    |--------------------------------------------------------------------------
    | Analyzer Device Management
    |--------------------------------------------------------------------------
    */

    public function listAnalyzers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Analyzer::withCount(['testMappings', 'rawMessages'])
            ->latest();

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->active();
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if (!empty($filters['protocol'])) {
            $query->where('protocol', $filters['protocol']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('manufacturer', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getAnalyzer(Analyzer $analyzer): Analyzer
    {
        $analyzer->load('testMappings.labTest');

        // Load the latest raw messages with a direct LIMIT. A per-relation
        // eager-load limit (->with(['rawMessages' => fn ($q) => $q->limit(50)]))
        // makes Laravel emit ROW_NUMBER() OVER(...), which MariaDB 10.1 cannot
        // parse. Querying the single parent's relation directly avoids that.
        $analyzer->setRelation(
            'rawMessages',
            $analyzer->rawMessages()->latest()->limit(50)->get()
        );

        return $analyzer;
    }

    public function createAnalyzer(array $data): Analyzer
    {
        return Analyzer::create($data);
    }

    public function updateAnalyzer(Analyzer $analyzer, array $data): Analyzer
    {
        $analyzer->update($data);
        return $analyzer;
    }

    public function toggleAnalyzer(Analyzer $analyzer): Analyzer
    {
        $analyzer->update(['is_active' => !$analyzer->is_active]);
        return $analyzer;
    }

    public function deleteAnalyzer(Analyzer $analyzer): bool
    {
        if ($analyzer->rawMessages()->where('processing_status', 'processing')->exists()) {
            return false;
        }

        $analyzer->delete();
        return true;
    }

    public function getActiveAnalyzers(): Collection
    {
        return Analyzer::active()->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Test Mappings
    |--------------------------------------------------------------------------
    */

    public function getMappings(Analyzer $analyzer): Collection
    {
        return $analyzer->testMappings()->with('labTest')->orderBy('analyzer_test_code')->get();
    }

    public function storeMapping(Analyzer $analyzer, array $data): AnalyzerTestMapping
    {
        return $analyzer->testMappings()->create($data);
    }

    public function updateMapping(AnalyzerTestMapping $mapping, array $data): AnalyzerTestMapping
    {
        $mapping->update($data);
        return $mapping;
    }

    public function deleteMapping(AnalyzerTestMapping $mapping): void
    {
        $mapping->delete();
    }

    public function getAvailableLabTests(): Collection
    {
        return LabTest::active()->orderBy('name')->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Raw Message Management
    |--------------------------------------------------------------------------
    */

    /**
     * Store a raw message from an analyzer, deduplicating by content hash.
     */
    public function storeRawMessage(
        ?int $analyzerId,
        string $protocol,
        string $content,
        string $direction = 'inbound'
    ): AnalyzerRawMessage {
        $hash = hash('sha256', $content);

        // Check for duplicate within the last hour
        $duplicate = AnalyzerRawMessage::where('content_hash', $hash)
            ->where('received_at', '>=', now()->subHour())
            ->first();

        if ($duplicate) {
            $duplicate->markDuplicate();
            return $duplicate;
        }

        return AnalyzerRawMessage::create([
            'analyzer_id' => $analyzerId,
            'protocol' => $protocol,
            'direction' => $direction,
            'content' => $content,
            'content_hash' => $hash,
            'processing_status' => 'received',
            'sample_id' => null,
            'received_at' => now(),
        ]);
    }

    /**
     * Get message log with filters.
     */
    public function getMessages(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = AnalyzerRawMessage::with('analyzer')->latest('received_at');

        if (!empty($filters['analyzer_id'])) {
            $query->where('analyzer_id', $filters['analyzer_id']);
        }

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['protocol'])) {
            $query->where('protocol', $filters['protocol']);
        }

        if (!empty($filters['sample_id'])) {
            $query->where('sample_id', 'like', "%{$filters['sample_id']}%");
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get processing statistics.
     */
    public function getMessageStats(): array
    {
        return [
            'received' => AnalyzerRawMessage::where('processing_status', 'received')->count(),
            'processing' => AnalyzerRawMessage::where('processing_status', 'processing')->count(),
            'processed' => AnalyzerRawMessage::where('processing_status', 'processed')->count(),
            'failed' => AnalyzerRawMessage::where('processing_status', 'failed')->count(),
            'today_total' => AnalyzerRawMessage::whereDate('received_at', today())->count(),
            'today_processed' => AnalyzerRawMessage::where('processing_status', 'processed')
                ->whereDate('received_at', today())->count(),
        ];
    }

    /**
     * Get analyzer-level statistics.
     */
    public function getAnalyzerStats(): array
    {
        return [
            'total' => Analyzer::count(),
            'active' => Analyzer::active()->count(),
            'hl7' => Analyzer::where('protocol', 'hl7')->count(),
            'astm' => Analyzer::where('protocol', 'astm')->count(),
            'total_mappings' => AnalyzerTestMapping::count(),
        ];
    }

    /**
     * Update last connected timestamp.
     */
    public function touchConnection(Analyzer $analyzer): void
    {
        $analyzer->update(['last_connected_at' => now()]);
    }
}
