<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

use App\Services\LegacyMigration\Foundation\Security\ProtectedToken;

final readonly class VerifiedHmacAuthority implements VerifiedRunAuthority
{
    private function __construct(private string $reference) {}

    public static function fromSnapshots(
        SnapshotManifest $source,
        SnapshotManifest $target,
        SnapshotManifestIntegrityService $integrity,
    ): self {
        if (! $integrity->verify($source) || ! $integrity->verify($target)) {
            throw new SnapshotException('FOUNDATION_HMAC_AUTHORITY_INVALID', 'Snapshot HMAC authority is not authentic.');
        }
        $contexts = [];
        foreach ([$source, $target] as $manifest) {
            foreach (array_merge([$manifest->authoritySeal], array_values($manifest->protectedSetTokens)) as $encoded) {
                try {
                    $token = ProtectedToken::parse($encoded);
                } catch (\Throwable) {
                    throw new SnapshotException('FOUNDATION_HMAC_AUTHORITY_INVALID', 'Snapshot HMAC authority is not authentic.');
                }
                if ($token->domain() !== 'artifact_integrity') {
                    throw new SnapshotException('FOUNDATION_HMAC_AUTHORITY_INVALID', 'Snapshot HMAC authority uses an unapproved domain.');
                }
                $contexts[$token->environment().'|'.$token->keyId().'|'.$token->keyVersion().'|'.$token->canonicalizationVersion()] = true;
            }
        }
        if (count($contexts) !== 1) {
            throw new SnapshotException('FOUNDATION_HMAC_AUTHORITY_INVALID', 'Snapshot HMAC authorities do not share one pinned key context.');
        }

        return new self((new CanonicalManifestHasher)->hash([
            'authority' => 'verified_snapshot_hmac',
            'context' => array_key_first($contexts),
            'source_seal' => $source->authoritySeal,
            'target_seal' => $target->authoritySeal,
        ]));
    }

    public function reference(): string
    {
        return $this->reference;
    }
}
