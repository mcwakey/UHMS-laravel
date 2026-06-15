<?php

namespace App\Services;

use App\Models\AccountingPostingAttempt;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AccountingIdempotencyService
{
    public function sourceType(Model|string $source): string
    {
        $type = is_string($source) ? $source : $source::class;

        return Str::of($type)->replace('\\', '.')->lower()->toString();
    }

    public function key(Model|string $source, int|string $sourceId, string $postingType, int $version = 1): string
    {
        $key = implode(':', [
            $this->sourceType($source),
            $sourceId,
            Str::of($postingType)->snake()->lower()->toString(),
            'v'.max(1, $version),
        ]);

        if (strlen($key) <= 191) {
            return $key;
        }

        return substr($key, 0, 126).':'.hash('sha256', $key);
    }

    public function postedJournal(string $idempotencyKey): ?JournalEntry
    {
        $attempt = AccountingPostingAttempt::query()
            ->with('journalEntry')
            ->where('idempotency_key', $idempotencyKey)
            ->where('status', 'posted')
            ->first();

        return $attempt?->journalEntry
            ?? JournalEntry::query()->where('idempotency_key', $idempotencyKey)->first();
    }
}
