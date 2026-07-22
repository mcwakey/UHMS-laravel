<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

interface MariaDbFoundationDdlManifestContract
{
    public function version(): string;

    public function operations(): array;

    public function expectations(): array;

    public function operation(string $operationId): MariaDbDdlOperation;

    public function payloadHash(): string;
}
