<?php

namespace App\Services;

use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Model;

class AccountingPostingService
{
    public function __construct(protected JournalEntryService $journalEntryService) {}

    public function postFromSource(string $sourceModule, Model $source, array $lines, array $meta = []): JournalEntry
    {
        $entry = $this->journalEntryService->createDraft(array_merge($meta, [
            'entry_date' => $meta['entry_date'] ?? now()->toDateString(),
            'description' => $meta['description'] ?? class_basename($source) . ' posting',
            'reference_type' => $source::class,
            'reference_id' => $source->getKey(),
            'source_module' => $sourceModule,
            'lines' => $lines,
        ]));

        return $this->journalEntryService->post($entry, auth()->user());
    }
}
