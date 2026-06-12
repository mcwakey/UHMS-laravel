<?php

namespace App\Enums;

enum CreditNoteType: string
{
    case CREDIT_NOTE = 'credit_note';
    case WRITE_OFF = 'write_off';

    public function label(): string
    {
        return match ($this) {
            self::CREDIT_NOTE => 'Credit Note',
            self::WRITE_OFF => 'Write-Off',
        };
    }

    public function translatedLabel(): string
    {
        return __('statuses.default.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::CREDIT_NOTE => 'info',
            self::WRITE_OFF => 'dark',
        };
    }
}
