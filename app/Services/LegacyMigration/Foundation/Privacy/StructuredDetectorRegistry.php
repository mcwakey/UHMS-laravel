<?php

namespace App\Services\LegacyMigration\Foundation\Privacy;

final class StructuredDetectorRegistry
{
    public const SCANNER_VERSION = 'P3B-PRIVACY-SCANNER-3';

    /** @var list<Detector> */
    private array $detectors;

    /** @param null|list<Detector> $detectors */
    public function __construct(?array $detectors = null)
    {
        $this->detectors = $detectors ?? [
            new RegexDetector('ghana_phone', '/\b(?:\+233|0(?:2[034567]|5[03459]))\d{7}\b/'),
            new RegexDetector('email', '/\b[A-Z0-9._%+-]+@(?!example\.(?:com|org|net)\b)[A-Z0-9.-]+\.[A-Z]{2,}\b/i'),
            new RegexDetector('assigned_opd_or_member_value', '/(?:MemberNo|OpdNo|PolicyNo|CCC|patient_number)\s*["\']?\s*[:=]\s*["\'][^"\'\r\n]+/i'),
            new RegexDetector('credential_or_secret_assignment', '/(?:DB_PASSWORD|LEGACY_DB_PASSWORD|LEGACY_MIGRATION_HMAC_KEY|password|passwd|secret|api[_-]?key|access[_-]?token)\s*["\']?\s*[:=]\s*["\'][^"\'\r\n]+/i'),
            new RegexDetector(
                'unquoted_env_secret_assignment',
                '/^[ \t]*[A-Z][A-Z0-9_]*(?:PASSWORD|SECRET|HMAC_KEY|API_?KEY|ACCESS_?TOKEN|ACCESS_KEY_ID|APP_KEY)[ \t]*=[ \t]*(?!(?:#.*)?$|null[ \t]*(?:#.*)?$)[^ \t#"\'\r\n][^\r\n]*$/im',
            ),
            new RegexDetector('private_key_material', '/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/'),
            new RegexDetector('credential_token', '/\b(?:AKIA[0-9A-Z]{16}|gh[pousr]_[A-Za-z0-9]{20,}|github_pat_[A-Za-z0-9_]{22,}|Bearer\s+[A-Za-z0-9._~+\/-]{20,})\b/'),
            new RegexDetector('raw_identifier_property', '/["\'](?:raw_patient_id|raw_target_id|raw_source_id|raw_member_number|source_patient_id|target_patient_id)["\']\s*[:=]\s*(?!null\b|false\b)["\']?[^,}\]\r\n]+/i'),
            new RegexDetector(
                'record_level_identifier_value',
                '/(?:["\'](?:MemberNo|OpdNo|PatientID)["\']\s*(?::|=>|=)\s*|\b(?:MemberNo|OpdNo|PatientID)\s*=\s*)(?:["\'][^"\'\r\n]+["\']|-?\d+)/',
            ),
            new RegexDetector(
                'scalar_identifier_value',
                '/^\s*(?:MemberNo|OpdNo|PatientID|PolicyNo)\s*[:=]\s*(?:["\'][^"\'\r\n]+["\']|-?\d+|(?=[^\r\n]*\d)[A-Za-z0-9._\/-]{3,})/im',
                '#(?:^|/)(?:reports|exports|dry-runs|evidence)(?:/|$)|\.(?:ya?ml|ini|env|log|csv|tsv)$#i',
            ),
            new RegexDetector('authorization_header', '/\bAuthorization\s*:\s*(?:Basic|Bearer|Digest)\s+[A-Za-z0-9._~+\/=:-]{8,}/i'),
            new RegexDetector('cookie_session_dump', '/\b(?:Cookie|Set-Cookie)\s*:\s*[^\r\n]*(?:session|token|auth)[^\r\n]*/i'),
            new RegexDetector('database_dsn', '/\b(?:mysql|mariadb|pgsql|postgres|sqlsrv):(?:\/\/|host=)[^\s"\'<>]+|\bDATABASE_URL\s*[:=]\s*[^\s#"\']+/i'),
            new RegexDetector('cloud_provider_credential', '/\b(?:ASIA[0-9A-Z]{16}|AIza[0-9A-Za-z_-]{30,}|sk_(?:live|test)_[0-9A-Za-z]{16,}|xox[baprs]-[0-9A-Za-z-]{10,})\b/'),
            new RegexDetector('sql_diagnostic_identifier', '/^(?:\s*\|\s*)?(?:MemberNo|OpdNo|PatientID|PolicyNo)\s*\|\s*["\']?[A-Za-z0-9._\/-]{2,}/im', '#(?:^|/)(?:reports|exports|dry-runs|evidence)(?:/|$)|\.(?:log|txt|csv|tsv)$#i'),
            new RegexDetector('log_identifier_assignment', '/\b(?:member_no|opd_no|patient_id|policy_no|insurance_number)\s*=\s*[^\s,;\]}]+/i', '#(?:^|/)(?:reports|exports|dry-runs|evidence)(?:/|$)|\.(?:log|env|ini|txt)$#i'),
            new RegexDetector(
                'real_raw_identifier_key',
                '/["\'](?:raw_(?:patient|target|source|member|insurance)_id|source_(?:patient|row)_id|target_(?:patient|row)_id|PAT_ID|INS_ID)["\']\s*(?::|=>|=)\s*(?:["\'][^"\'\r\n]+["\']|-?\d+)/i',
            ),
            new RegexDetector('row_token', '/(?:["\'](?:source_)?row_token["\']\s*[:=]\s*["\'][^"\'\r\n]+|\blmt1\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\b)/i'),
            new RegexDetector('unsafe_low_entropy_hash', '/["\'](?:patient|phone|contact|opd|member|policy)[a-z0-9_-]*(?:hash|sha256|md5)["\']\s*[:=]\s*["\'][a-f0-9]{32,64}["\']/i'),
            new RegexDetector('forbidden_structured_flag', '/["\'](?:implementation_authorized|write_authorized|patient_commit_authorized|contains_raw_phi|contains_credentials)["\']\s*:\s*true\b/i'),
            new RegexDetector(
                'non_aggregate_repository_report',
                '/["\']repository_output["\']\s*:\s*["\'](?!aggregate-only["\'])[^"\']+["\']/i',
                '#(?:^|/)(?:reports|exports|dry-runs)(?:/|$)|\.log$#i',
            ),
            new CrossDomainTokenMisuseDetector,
        ];

        $ids = array_map(static fn (Detector $detector): string => $detector->id(), $this->detectors);
        if (count($ids) !== count(array_unique($ids))) {
            throw new \InvalidArgumentException('Privacy detector IDs must be unique [LM-PRIV-REGISTRY-001].');
        }
    }

    /** @return list<Detector> */
    public function detectors(): array
    {
        return $this->detectors;
    }

    /** @return list<string> */
    public function patternClasses(): array
    {
        return array_map(static fn (Detector $detector): string => $detector->id(), $this->detectors);
    }
}
