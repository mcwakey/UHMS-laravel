<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

final readonly class DdlObjectObservation
{
    public function __construct(
        public DdlObjectType $type,
        public string $name,
        public string $definitionHash,
    ) {}

    public function coordinate(): string
    {
        return $this->type->value.':'.$this->name;
    }
}
