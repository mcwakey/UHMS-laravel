<?php

namespace App\Enums\Accounting;

enum JournalEntryStatus: string
{
    case DRAFT = 'draft';
    case POSTED = 'posted';
    case REVERSED = 'reversed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function translatedLabel(): string
    {
        return __('statuses.default.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'warning',
            self::POSTED => 'success',
            self::REVERSED => 'info',
            self::CANCELLED => 'secondary',
        };
    }

    public function affectsLedger(): bool
    {
        return in_array($this, [self::POSTED, self::REVERSED], true);
    }
}
