<?php

namespace App\Enums;

enum LogSeverity: string
{
    case DEBUG = 'DEBUG';
    case INFO = 'INFO';
    case NOTICE = 'NOTICE';
    case WARNING = 'WARNING';
    case ERROR = 'ERROR';
    case CRITICAL = 'CRITICAL';
    case SECURITY = 'SECURITY';

    public function label(): string
    {
        return ucfirst(strtolower($this->value));
    }

    public function color(): string
    {
        return match ($this) {
            self::DEBUG => 'light',
            self::INFO => 'secondary',
            self::NOTICE => 'info',
            self::WARNING => 'warning',
            self::ERROR => 'danger',
            self::CRITICAL => 'danger',
            self::SECURITY => 'dark',
        };
    }
}
