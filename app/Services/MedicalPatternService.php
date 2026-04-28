<?php

namespace App\Services;

use App\Models\MedicalPattern;
use App\Models\MedicalPatternItem;
use App\Models\MedicalRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MedicalPatternService
{
    /**
     * List patterns with filters and pagination.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = MedicalPattern::with(['doctor', 'items']);

        if (!empty($filters['search'])) {
            $query->where('name', 'LIKE', '%' . $filters['search'] . '%');
        }

        if (isset($filters['scope'])) {
            if ($filters['scope'] === 'system') {
                $query->systemWide();
            } elseif ($filters['scope'] === 'mine') {
                $query->where('doctor_id', Auth::id());
            }
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderByDesc('usage_count')->paginate($perPage);
    }

    /**
     * Create a pattern with items.
     */
    public function create(array $data): MedicalPattern
    {
        $pattern = MedicalPattern::create([
            'name' => $data['name'],
            'doctor_id' => $data['doctor_id'] ?? null,
            'is_active' => true,
        ]);

        if (!empty($data['items'])) {
            $sortOrder = 0;
            foreach ($data['items'] as $item) {
                $pattern->items()->create([
                    'type' => $item['type'],
                    'data' => $item['data'],
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        return $pattern->load('items');
    }

    /**
     * Create a pattern from an existing medical record.
     */
    public function createFromRecord(MedicalRecord $record, string $name, ?int $doctorId = null): MedicalPattern
    {
        $record->load(['complaints', 'diagnoses', 'treatments', 'prescriptions.items']);

        $items = [];
        $sortOrder = 0;

        foreach ($record->complaints as $complaint) {
            $items[] = [
                'type' => 'complaint',
                'data' => [
                    'description' => $complaint->description,
                    'duration' => $complaint->duration,
                    'severity' => $complaint->severity,
                ],
                'sort_order' => $sortOrder++,
            ];
        }

        foreach ($record->diagnoses as $diagnosis) {
            $items[] = [
                'type' => 'diagnosis',
                'data' => [
                    'icd_code' => $diagnosis->icd_code,
                    'description' => $diagnosis->description,
                    'type' => $diagnosis->type,
                    'notes' => $diagnosis->notes,
                ],
                'sort_order' => $sortOrder++,
            ];
        }

        foreach ($record->treatments as $treatment) {
            $items[] = [
                'type' => 'treatment',
                'data' => [
                    'type' => $treatment->type,
                    'description' => $treatment->description,
                ],
                'sort_order' => $sortOrder++,
            ];
        }

        foreach ($record->prescriptions as $prescription) {
            foreach ($prescription->items as $rxItem) {
                $items[] = [
                    'type' => 'prescription_item',
                    'data' => [
                        'drug_name' => $rxItem->drug_name,
                        'dosage' => $rxItem->dosage,
                        'frequency' => $rxItem->frequency,
                        'duration' => $rxItem->duration,
                        'quantity' => $rxItem->quantity,
                        'route' => $rxItem->route,
                        'instructions' => $rxItem->instructions,
                    ],
                    'sort_order' => $sortOrder++,
                ];
            }
        }

        $pattern = MedicalPattern::create([
            'name' => $name,
            'doctor_id' => $doctorId,
            'is_active' => true,
        ]);

        foreach ($items as $item) {
            $pattern->items()->create($item);
        }

        return $pattern->load('items');
    }

    /**
     * Update a pattern.
     */
    public function update(MedicalPattern $pattern, array $data): MedicalPattern
    {
        $pattern->update([
            'name' => $data['name'] ?? $pattern->name,
            'is_active' => $data['is_active'] ?? $pattern->is_active,
        ]);

        if (isset($data['items'])) {
            $pattern->items()->delete();
            $sortOrder = 0;
            foreach ($data['items'] as $item) {
                $pattern->items()->create([
                    'type' => $item['type'],
                    'data' => $item['data'],
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        return $pattern->load('items');
    }

    /**
     * Suggest patterns based on complaint text (fuzzy match).
     */
    public function suggest(string $complaintText, int $doctorId, int $limit = 5): Collection
    {
        $keywords = array_filter(explode(' ', strtolower(trim($complaintText))));

        if (empty($keywords)) {
            return collect();
        }

        // Search patterns that have complaint items matching the text
        $query = MedicalPattern::active()
            ->forDoctor($doctorId)
            ->whereHas('items', function ($q) use ($keywords) {
                $q->where('type', 'complaint');
                foreach ($keywords as $keyword) {
                    if (strlen($keyword) >= 3) {
                        $q->where('data', 'LIKE', '%' . $keyword . '%');
                    }
                }
            })
            ->with('items')
            ->orderByDesc('usage_count')
            ->limit($limit);

        // Also search by pattern name
        $nameQuery = MedicalPattern::active()
            ->forDoctor($doctorId)
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    if (strlen($keyword) >= 3) {
                        $q->where('name', 'LIKE', '%' . $keyword . '%');
                    }
                }
            })
            ->with('items')
            ->orderByDesc('usage_count')
            ->limit($limit);

        return $query->get()
            ->merge($nameQuery->get())
            ->unique('id')
            ->take($limit);
    }

    /**
     * Apply a pattern to a medical record (creates the items).
     */
    public function applyPattern(MedicalPattern $pattern, MedicalRecord $record): array
    {
        $applied = [
            'complaints' => [],
            'diagnoses' => [],
            'treatments' => [],
            'prescription_items' => [],
        ];

        foreach ($pattern->items as $item) {
            $data = $item->data;

            switch ($item->type) {
                case 'complaint':
                    $applied['complaints'][] = $record->complaints()->create([
                        'description' => $data['description'] ?? '',
                        'duration' => $data['duration'] ?? null,
                        'severity' => $data['severity'] ?? null,
                    ]);
                    break;

                case 'diagnosis':
                    $applied['diagnoses'][] = $record->diagnoses()->create([
                        'icd_code' => $data['icd_code'] ?? null,
                        'description' => $data['description'] ?? '',
                        'type' => $data['type'] ?? 'provisional',
                        'notes' => $data['notes'] ?? null,
                    ]);
                    break;

                case 'treatment':
                    $applied['treatments'][] = $record->treatments()->create([
                        'type' => $data['type'] ?? 'medication',
                        'description' => $data['description'] ?? '',
                    ]);
                    break;

                case 'prescription_item':
                    $applied['prescription_items'][] = $data;
                    break;
            }
        }

        $pattern->incrementUsage();

        return $applied;
    }

    /**
     * Toggle pattern active status.
     */
    public function toggleActive(MedicalPattern $pattern): MedicalPattern
    {
        $pattern->update(['is_active' => !$pattern->is_active]);
        return $pattern;
    }

    /**
     * Delete a pattern.
     */
    public function delete(MedicalPattern $pattern): void
    {
        $pattern->delete();
    }
}
