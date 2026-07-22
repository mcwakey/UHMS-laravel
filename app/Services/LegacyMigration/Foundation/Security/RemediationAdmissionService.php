<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use DateTimeImmutable;

final class RemediationAdmissionService
{
    /**
     * @param  list<RemediationCandidate>  $candidates
     */
    public function admit(
        array $candidates,
        string $sourceTokenReference,
        string $fieldRule,
        int $runId,
        int $sourceSnapshotId,
        ?int $targetSnapshotId,
        DateTimeImmutable $at,
    ): RemediationCandidate {
        $eligible = [];
        foreach ($candidates as $candidate) {
            if (! $candidate instanceof RemediationCandidate) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-REMEDIATION-SHAPE-002');
            }
            if (! hash_equals($candidate->sourceTokenReference, $sourceTokenReference)
                || ! hash_equals($candidate->fieldRule, $fieldRule)
                || $candidate->runId !== $runId
                || $candidate->sourceSnapshotId !== $sourceSnapshotId
                || $candidate->targetSnapshotId !== $targetSnapshotId) {
                continue;
            }
            try {
                $candidate->integrityAuthority->assertRemediationCandidate($candidate);
            } catch (\Throwable) {
                continue;
            }
            if (! $candidate->approved || $candidate->revoked || $candidate->conflicted
                || $candidate->supersededByReference !== null
                || $candidate->approvedAt > $at
                || ($candidate->expiresAt !== null && $candidate->expiresAt < $at)) {
                continue;
            }
            $eligible[] = $candidate;
        }
        if ($eligible === []) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-REMEDIATION-NOT-ADMISSIBLE-001');
        }

        usort($eligible, static fn (RemediationCandidate $a, RemediationCandidate $b): int => [$a->precedenceOrdinal, -$a->approvedAt->getTimestamp(), $a->remediationReference]
            <=> [$b->precedenceOrdinal, -$b->approvedAt->getTimestamp(), $b->remediationReference]
        );
        $winner = $eligible[0];
        foreach (array_slice($eligible, 1) as $candidate) {
            if ($candidate->precedenceOrdinal !== $winner->precedenceOrdinal) {
                break;
            }
            if (! hash_equals($candidate->valueFingerprint, $winner->valueFingerprint)) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-REMEDIATION-CONFLICT-001');
            }
        }

        return $winner;
    }
}
