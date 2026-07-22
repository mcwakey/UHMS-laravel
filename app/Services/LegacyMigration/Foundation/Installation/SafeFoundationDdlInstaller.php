<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

use App\Services\LegacyMigration\Foundation\Environment\IdentityBoundDdlGate;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityVerification;
use RuntimeException;
use Throwable;

final readonly class SafeFoundationDdlInstaller
{
    public function __construct(
        private FoundationDdlInspector $inspector,
        private DdlRecoveryPlanner $planner,
        private DdlOperationExecutor $executor,
        private FoundationInstallationJournal $journal,
        private IdentityBoundDdlGate $ddlGate,
    ) {}

    /**
     * @param  list<DdlObjectExpectation>  $manifest
     * @param  callable(): list<DdlObjectObservation>  $observe
     */
    public function install(
        PhysicalServerIdentityVerification $identity,
        InstallationSessionCapability $session,
        string $version,
        array $manifest,
        callable $observe,
        string $auditReference,
    ): DdlRecoveryPlan {
        if ($identity->approvedIdentityReference === '' || $auditReference === '') {
            throw new RuntimeException('Verified physical identity and audit authority are required for foundation DDL.');
        }
        $this->ddlGate->assertVerified($identity);
        if (! hash_equals($session->auditReference, $auditReference)) {
            throw new RuntimeException('Installation session audit authority does not match the requested journal.');
        }
        $inspection = $this->inspector->inspect($manifest, $observe());
        foreach ($inspection as $result) {
            if ($result->state !== DdlInspectionState::Matching
                || $this->journal->hasSuccessfulVerification(
                    $version,
                    $identity->approvedIdentityReference,
                    $result->expected->coordinate(),
                    $result->expected->definitionHash,
                )) {
                continue;
            }
            $this->journal->append(new InstallationJournalRecord(
                $version,
                $identity->approvedIdentityReference,
                $result->expected->coordinate(),
                $result->expected->definitionHash,
                $result->observedDefinitionHash,
                'adopted',
                $this->journal->nextAttempt($version, $identity->approvedIdentityReference, $result->expected->coordinate()),
                $auditReference,
            ));
        }
        $plan = $this->planner->plan($version, $inspection);
        foreach ($plan->operations as $operation) {
            $attempt = $this->journal->nextAttempt(
                $version,
                $identity->approvedIdentityReference,
                $operation->coordinate(),
            );
            $this->journal->append(new InstallationJournalRecord(
                $version, $identity->approvedIdentityReference, $operation->coordinate(),
                $operation->definitionHash, null, 'started', $attempt, $auditReference,
            ));
            try {
                $this->ddlGate->execute(
                    $identity,
                    fn () => $this->executor->execute($version, $operation->installOperationId, $identity, $this->ddlGate, $session),
                );
                $after = $this->inspector->inspect($manifest, $observe());
                $verified = null;
                foreach ($after as $result) {
                    if ($result->expected->coordinate() === $operation->coordinate()) {
                        $verified = $result;
                        break;
                    }
                }
                if ($verified === null || $verified->state !== DdlInspectionState::Matching) {
                    throw new RuntimeException('Foundation DDL postcondition did not match its approved definition.');
                }
                $this->journal->append(new InstallationJournalRecord(
                    $version, $identity->approvedIdentityReference, $operation->coordinate(),
                    $operation->definitionHash, $verified->observedDefinitionHash,
                    'verified', $attempt, $auditReference,
                ));
            } catch (Throwable $exception) {
                $this->journal->append(new InstallationJournalRecord(
                    $version, $identity->approvedIdentityReference, $operation->coordinate(),
                    $operation->definitionHash, null, 'failed_closed', $attempt, $auditReference,
                ));
                throw new RuntimeException('Foundation DDL stopped after a failed or unverifiable allow-listed operation.', 0, $exception);
            }
        }

        return $plan;
    }
}
