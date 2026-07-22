<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use Closure;

/** Process-local guard used only in addition to encrypted storage and DB controls. */
final class ProtectedStoreAccessSession
{
    /** @var list<ProtectedStoreOperationContext> */
    private static array $stack = [];

    /** @var list<array<string, true>> */
    private static array $verifiedRecords = [];

    private static int $envelopeLookupDepth = 0;

    public static function current(): ?ProtectedStoreOperationContext
    {
        return self::$stack[array_key_last(self::$stack)] ?? null;
    }

    public static function requireCurrent(): ProtectedStoreOperationContext
    {
        return self::current() ?? throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-SESSION-MISSING-001');
    }

    public static function run(ProtectedStoreOperationContext $context, Closure $operation): mixed
    {
        self::$stack[] = $context;
        self::$verifiedRecords[] = [];
        try {
            return $operation();
        } finally {
            array_pop(self::$verifiedRecords);
            array_pop(self::$stack);
        }
    }

    public static function markVerified(string $operation, ProtectedRecordEnvelope $envelope, ProtectedToken $verifiedToken): void
    {
        $context = self::current();
        if ($context === null || $envelope->recordId < 1) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-SESSION-MISSING-001');
        }
        $context->assertAuthorized($operation, $envelope->domain);
        $context->assertCoordinates($envelope->runId, $envelope->sourceSnapshotId, $envelope->targetSnapshotId);
        $context->assertClassifications($envelope->accessClassification, $envelope->retentionClassification);
        if (! hash_equals($envelope->encodedToken, $verifiedToken->encode())) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-SESSION-GRANT-001');
        }
        $index = array_key_last(self::$verifiedRecords);
        self::$verifiedRecords[$index][$envelope->recordType.'#'.$envelope->recordId] = true;
    }

    public static function isVerified(string $recordType, int $recordId): bool
    {
        $records = self::$verifiedRecords[array_key_last(self::$verifiedRecords)] ?? [];

        return isset($records[$recordType.'#'.$recordId]);
    }

    public static function runEnvelopeLookup(Closure $operation): mixed
    {
        if (self::current() === null) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-SESSION-MISSING-001');
        }
        self::$envelopeLookupDepth++;
        try {
            return $operation();
        } finally {
            self::$envelopeLookupDepth--;
        }
    }

    public static function envelopeLookupAllowed(): bool
    {
        return self::$envelopeLookupDepth > 0 && self::current() !== null;
    }

    public static function reset(): void
    {
        self::$stack = [];
        self::$verifiedRecords = [];
        self::$envelopeLookupDepth = 0;
    }
}
