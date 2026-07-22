<?php

namespace App\Services\LegacyMigration\Foundation\Validation;

use JsonException;

final class AuthoritativePolicyBundleLoader
{
    public const VERSION = 'phase2f-authoritative-policy/1.0.0';

    /** @var list<string> */
    public const REQUIRED_ARTIFACTS = [
        'docs/legacy-migration/phase-2f/specifications/insurance_pilot_initialization_rules.json',
        'docs/legacy-migration/phase-2f/specifications/patient_pilot_acceptance_thresholds.json',
        'docs/legacy-migration/phase-2f/specifications/patient_pilot_atomicity_rules.json',
        'docs/legacy-migration/phase-2f/specifications/patient_pilot_cohort_rules.json',
        'docs/legacy-migration/phase-2f/specifications/patient_pilot_composed_column_mappings.json',
        'docs/legacy-migration/phase-2f/specifications/patient_pilot_crosswalk_interface.json',
        'docs/legacy-migration/phase-2f/specifications/patient_pilot_dependency_sequence.json',
        'docs/legacy-migration/phase-2f/specifications/patient_pilot_dry_run_contract.json',
        'docs/legacy-migration/phase-2f/specifications/patient_pilot_exception_precedence.json',
        'docs/legacy-migration/phase-2f/specifications/patient_pilot_idempotency_rules.json',
        'docs/legacy-migration/phase-2f/specifications/patient_pilot_privacy_contract.json',
        'docs/legacy-migration/phase-2f/specifications/patient_pilot_provenance_interface.json',
        'docs/legacy-migration/phase-2f/specifications/patient_pilot_quarantine_interface.json',
        'docs/legacy-migration/phase-2f/specifications/patient_pilot_reconciliation_interface.json',
        'docs/legacy-migration/phase-2f/specifications/patient_pilot_remediation_input_rules.json',
        'docs/legacy-migration/phase-2f/specifications/patient_pilot_rollback_resume_rules.json',
        'docs/legacy-migration/phase-2f/specifications/patient_pilot_scenarios.json',
        'docs/legacy-migration/phase-2f/specifications/patient_pilot_source_selection_rules.json',
        'docs/legacy-migration/phase-2f/specifications/patient_pilot_state_matrix.json',
        'docs/legacy-migration/phase-2f/specifications/patient_pilot_target_collision_rules.json',
        'docs/legacy-migration/phase-2f/specifications/phase3_foundation_requirements.json',
        'docs/legacy-migration/APPROVED_DECISION_SPECIFICATIONS.md',
        'docs/legacy-migration/DECISIONS.md',
    ];

    /**
     * @param  array<string, array{sha256:string,specification_version?:string,contract_ids?:list<string>,approval_reference:string}>  $expected
     */
    public function load(string $repositoryRoot, array $expected): VerifiedPolicyBundle
    {
        $root = realpath($repositoryRoot);
        if ($root === false) {
            throw $this->failure('FOUNDATION_POLICY_ROOT_INVALID');
        }
        $expectedPaths = array_keys($expected);
        sort($expectedPaths, SORT_STRING);
        $required = self::REQUIRED_ARTIFACTS;
        sort($required, SORT_STRING);
        if ($expectedPaths !== $required) {
            throw $this->failure('FOUNDATION_POLICY_ARTIFACT_SET_INVALID');
        }

        $artifacts = [];
        $seenContractIds = [];
        foreach ($required as $path) {
            $definition = $expected[$path];
            $absolute = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
            $real = realpath($absolute);
            if ($real === false || ! is_file($real)
                || ! str_starts_with(strtolower($real), strtolower($root.DIRECTORY_SEPARATOR))) {
                throw $this->failure('FOUNDATION_POLICY_ARTIFACT_MISSING');
            }
            $bytes = file_get_contents($real);
            if (! is_string($bytes)) {
                throw $this->failure('FOUNDATION_POLICY_ARTIFACT_UNREADABLE');
            }
            $actualHash = hash('sha256', $bytes);
            $expectedHash = strtolower((string) ($definition['sha256'] ?? ''));
            $approval = trim((string) ($definition['approval_reference'] ?? ''));
            if (preg_match('/\A[a-f0-9]{64}\z/', $expectedHash) !== 1
                || ! hash_equals($expectedHash, $actualHash)
                || $approval === '') {
                throw $this->failure('FOUNDATION_POLICY_ARTIFACT_AUTHORITY_INVALID');
            }

            $version = null;
            $contractIds = [];
            $document = $bytes;
            if (str_ends_with($path, '.json')) {
                try {
                    $document = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException) {
                    throw $this->failure('FOUNDATION_POLICY_ARTIFACT_SHAPE_INVALID');
                }
                if (! is_array($document)
                    || ! is_string($document['specification_version'] ?? null)
                    || ! is_array($document['records'] ?? null)
                    || (int) ($document['record_count'] ?? -1) !== count($document['records'])) {
                    throw $this->failure('FOUNDATION_POLICY_ARTIFACT_SHAPE_INVALID');
                }
                $version = $document['specification_version'];
                if (! hash_equals((string) ($definition['specification_version'] ?? ''), $version)) {
                    throw $this->failure('FOUNDATION_POLICY_VERSION_MISMATCH');
                }
                foreach ($document['records'] as $record) {
                    $id = is_array($record) ? ($record['id'] ?? null) : null;
                    if (! is_string($id) || $id === '' || isset($seenContractIds[$id])) {
                        throw $this->failure('FOUNDATION_POLICY_CONTRACT_ID_INVALID');
                    }
                    $seenContractIds[$id] = $path;
                    $contractIds[] = $id;
                }
                $configuredIds = $definition['contract_ids'] ?? [];
                sort($configuredIds, SORT_STRING);
                $sortedActual = $contractIds;
                sort($sortedActual, SORT_STRING);
                if ($configuredIds !== $sortedActual) {
                    throw $this->failure('FOUNDATION_POLICY_CONTRACT_SET_MISMATCH');
                }
            } elseif (trim($bytes) === '') {
                throw $this->failure('FOUNDATION_POLICY_ARTIFACT_SHAPE_INVALID');
            }

            $artifacts[$path] = new VerifiedPolicyArtifact($path, $actualHash, $version, $contractIds, $approval, $document);
        }

        ksort($artifacts, SORT_STRING);
        $identity = array_map(static fn (VerifiedPolicyArtifact $artifact): array => [
            'path' => $artifact->path,
            'sha256' => $artifact->sha256,
            'specification_version' => $artifact->specificationVersion,
            'contract_ids' => $artifact->contractIds,
            'approval_reference' => $artifact->approvalReference,
        ], $artifacts);

        return new VerifiedPolicyBundle(
            self::VERSION,
            hash('sha256', json_encode($identity, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)),
            $artifacts,
        );
    }

    private function failure(string $code): ValidationException
    {
        return new ValidationException($code, [], 'The authoritative Phase 2F policy bundle failed closed.');
    }
}
