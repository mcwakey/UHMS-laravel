<?php

namespace App\Services\LegacyMigration\Foundation\Reconciliation;

use App\Services\LegacyMigration\Foundation\Validation\VerifiedPolicyBundle;
use InvalidArgumentException;

final readonly class Phase2FMeasurementPlan
{
    /** @param array<string,string> $measurements measurement id => contract id */
    public function __construct(
        public string $bundleHash,
        public array $measurements,
        /** @var array<string,string> measurement id => closed recorder adapter kind */
        public array $adapterKinds,
    ) {}

    public static function fromBundle(VerifiedPolicyBundle $bundle): self
    {
        $paths = [
            'docs/legacy-migration/phase-2f/specifications/patient_pilot_acceptance_thresholds.json',
            'docs/legacy-migration/phase-2f/specifications/patient_pilot_dry_run_contract.json',
            'docs/legacy-migration/phase-2f/specifications/patient_pilot_reconciliation_interface.json',
        ];
        $measurements = [];
        $adapterKinds = [];
        foreach ($bundle->artifacts as $artifact) {
            if (! is_array($artifact->document)) {
                continue;
            }
            foreach ($artifact->document['records'] as $record) {
                $contractId = $record['id'];
                $measurements[$contractId.':contract_assertion'] = $contractId;
                $adapterKinds[$contractId.':contract_assertion'] = 'contract_assertion';
            }
        }
        foreach ($paths as $path) {
            $document = $bundle->artifact($path)->document;
            if (! is_array($document)) {
                throw new InvalidArgumentException('Authoritative measurement contract is invalid.');
            }
            foreach ($document['records'] as $record) {
                $contractId = $record['id'];
                foreach (['requirements', 'required_zero', 'required_inputs', 'required_outputs', 'required'] as $field) {
                    foreach (($record[$field] ?? []) as $name) {
                        if (! is_string($name) || $name === '') {
                            throw new InvalidArgumentException('Authoritative measurement contract is invalid.');
                        }
                        $measurements[$contractId.':'.$name] = $contractId;
                        $adapterKinds[$contractId.':'.$name] = $field;
                    }
                }
                foreach (($record['outcomes'] ?? []) as $name) {
                    $measurements[$contractId.':'.$name] = $contractId;
                    $adapterKinds[$contractId.':'.$name] = 'outcomes';
                }
            }
        }
        ksort($measurements, SORT_STRING);
        ksort($adapterKinds, SORT_STRING);

        return new self($bundle->bundleHash, $measurements, $adapterKinds);
    }
}
