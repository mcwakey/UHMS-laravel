<?php

namespace App\Services\LegacyMigration\Foundation\Privacy;

final class SyntheticFixtureAllowList
{
    public const VERSION = 'P2F-SYNTHETIC-ALLOWLIST-1';

    public const PATH = 'docs/legacy-migration/phase-2f/specifications/patient_pilot_scenarios.json';

    public const NAMESPACE = 'SYNTHETIC-ONLY-P2F';

    public function isValid(string $relativePath, string $content): bool
    {
        if (str_replace('\\', '/', $relativePath) !== self::PATH) {
            return false;
        }

        $document = json_decode($content, true);
        if (! is_array($document)
            || ($document['fixture_namespace'] ?? null) !== self::NAMESPACE
            || ($document['fixture_literal_contract']['copied_or_perturbed_source'] ?? null) !== false
            || ($document['privacy']['contains_raw_phi'] ?? null) !== false
            || ! is_array($document['records'] ?? null)
            || $document['records'] === []) {
            return false;
        }

        foreach ($document['records'] as $record) {
            if (! is_array($record)
                || ($record['fixture_namespace'] ?? null) !== self::NAMESPACE
                || ($record['synthetic_only_assertion'] ?? null) !== 'obviously_fictitious_independently_invented_never_copied_or_perturbed_from_classic_or_target') {
                return false;
            }
        }

        return true;
    }

    public function allows(PrivacyFinding $finding, string $content): bool
    {
        // The normative Cohort A file carries symbolic patient-number actions;
        // those are explicitly allowed only after the complete namespace and
        // per-record marker contract above validates. Credentials, contact
        // literals, raw IDs and token material are never allowlisted.
        return $finding->detectorId === 'assigned_opd_or_member_value'
            && $this->isValid($finding->path, $content);
    }
}
