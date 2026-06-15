<?php

namespace App\Services;

use App\Models\FinancialEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BasicAccountingBackfillService
{
    public function __construct(protected BasicAccountingPostingService $posting) {}

    public function query(array $filters = []): Builder
    {
        return FinancialEntry::query()
            ->with(['category', 'journalEntry'])
            ->whereNotNull('approved_by')
            ->when($filters['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('entry_date', '>=', $date))
            ->when($filters['to'] ?? null, fn (Builder $q, $date) => $q->whereDate('entry_date', '<=', $date))
            ->when($filters['entry_type'] ?? null, fn (Builder $q, $type) => $q->where('type', $type))
            ->when($filters['category_id'] ?? null, fn (Builder $q, $id) => $q->where('category_id', $id))
            ->when($filters['entry_id'] ?? null, fn (Builder $q, $id) => $q->whereKey($id))
            ->when($filters['resume_from'] ?? null, fn (Builder $q, $id) => $q->where('id', '>=', $id))
            ->where(fn (Builder $q) => $q->whereNull('accounting_status')->orWhereIn('accounting_status', ['eligible', 'failed', 'pending']))
            ->orderBy('id');
    }

    public function preview(array $filters = [], int $limit = 200): array
    {
        $rows = $this->query($filters)->limit($limit)->get()->map(function (FinancialEntry $entry) {
            $result = $this->posting->preview($entry, audit: false);
            return ['entry' => $entry, 'result' => $result];
        });

        return [
            'rows' => $rows,
            'eligible' => $rows->where('result.eligible', true)->count(),
            'ineligible' => $rows->where('result.eligible', false)->count(),
        ];
    }

    public function execute(array $filters, User $actor, int $chunk = 100, ?callable $progress = null): array
    {
        $totals = ['selected' => 0, 'posted' => 0, 'failed' => 0, 'skipped' => 0];
        $this->query($filters)->chunkById($chunk, function ($entries) use (&$totals, $actor, $progress) {
            foreach ($entries as $entry) {
                $totals['selected']++;
                $result = $this->posting->post($entry, $actor);
                $key = $result['success'] ? 'posted' : 'failed';
                $totals[$key]++;
                if ($progress) {
                    $progress($entry, $result, $totals);
                }
            }
        });
        return $totals;
    }
}
