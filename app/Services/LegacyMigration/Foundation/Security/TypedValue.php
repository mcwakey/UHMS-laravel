<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use InvalidArgumentException;

final class TypedValue
{
    private function __construct(
        private readonly string $type,
        private readonly ?string $payload,
    ) {}

    public static function null(): self
    {
        return new self('null', null);
    }

    public static function string(string $value): self
    {
        self::assertUtf8($value);

        return new self('string', $value);
    }

    public static function integer(int $value): self
    {
        return new self('integer', (string) $value);
    }

    public static function boolean(bool $value): self
    {
        return new self('boolean', $value ? '1' : '0');
    }

    public static function decimal(string $value): self
    {
        if (preg_match('/^-?(?:0|[1-9]\d*)(?:\.\d+)?$/D', $value) !== 1) {
            throw new InvalidArgumentException('Canonical decimal is invalid [LM-SEC-CANON-DECIMAL-001].');
        }

        return new self('decimal', $value);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function payload(): ?string
    {
        return $this->payload;
    }

    private static function assertUtf8(string $value): void
    {
        if (preg_match('//u', $value) !== 1) {
            throw new InvalidArgumentException('Canonical string is not valid UTF-8 [LM-SEC-CANON-UTF8-001].');
        }
    }
}
