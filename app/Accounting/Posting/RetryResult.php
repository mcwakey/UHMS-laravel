<?php

namespace App\Accounting\Posting;

use App\Models\JournalEntry;

class RetryResult
{
    public function __construct(
        public readonly bool $supported,
        public readonly bool $success,
        public readonly ?JournalEntry $journal = null,
        public readonly ?string $message = null,
        public readonly bool $attemptManagedByHandler = false,
    ) {}

    public static function posted(JournalEntry $journal, bool $managed = false): self
    {
        return new self(true, true, $journal, null, $managed);
    }

    public static function failed(string $message, bool $managed = false): self
    {
        return new self(true, false, null, $message, $managed);
    }

    public static function unsupported(string $message): self
    {
        return new self(false, false, null, $message);
    }
}
