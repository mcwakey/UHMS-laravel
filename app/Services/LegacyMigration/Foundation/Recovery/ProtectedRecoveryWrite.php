<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;

final readonly class ProtectedRecoveryWrite
{
    /**
     * @param  array<string,mixed>  $attributes
     * @param  array<string,array{encoded_token:string,domain:string}>  $tokenSet
     */
    public function __construct(
        public ProtectedStoreOperationContext $context,
        public array $attributes,
        public array $tokenSet,
    ) {
        if ($attributes === [] || $tokenSet === []) {
            throw RecoveryException::failClosed('RECOVERY-PROTECTED-WRITE-INCOMPLETE');
        }
    }
}
