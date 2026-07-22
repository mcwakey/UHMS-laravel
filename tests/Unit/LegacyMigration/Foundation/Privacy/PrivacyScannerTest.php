<?php

namespace Tests\Unit\LegacyMigration\Foundation\Privacy;

use App\Services\LegacyMigration\Foundation\Privacy\PrivacyScanner;
use App\Services\LegacyMigration\Foundation\Privacy\SafeDiagnosticEncoder;
use App\Services\LegacyMigration\Foundation\Runtime\OperationalGuardScopeManifest;
use App\Services\LegacyMigration\Foundation\Security\CanonicalizationVersionRegistry;
use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\ConfiguredKeyProvider;
use App\Services\LegacyMigration\Foundation\Security\HmacTokenService;
use App\Services\LegacyMigration\Foundation\Security\TokenDomain;
use App\Services\LegacyMigration\Foundation\Security\TypedValue;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PrivacyScannerTest extends TestCase
{
    /** @var list<string> */
    private array $roots = [];

    protected function tearDown(): void
    {
        foreach ($this->roots as $root) {
            $this->removeTree($root);
        }
        parent::tearDown();
    }

    #[Test]
    public function clean_artifacts_have_complete_deterministic_coverage(): void
    {
        $root = $this->fixtureRoot();
        mkdir($root.'/artifacts', recursive: true);
        file_put_contents($root.'/artifacts/report.json', '{"repository_output":"aggregate-only","count":7}');
        file_put_contents($root.'/artifacts/summary.log', 'aggregate_count=7');

        $scanner = new PrivacyScanner;
        $first = $scanner->scan($root, ['artifacts']);
        $second = $scanner->scan($root, ['artifacts']);

        $this->assertFalse($first->releaseBlocked());
        $this->assertSame(0, $first->coverageDifference);
        $this->assertGreaterThanOrEqual(2, $first->fileCount);
        $this->assertSame($first->manifestHash, $second->manifestHash);
    }

    #[Test]
    public function findings_are_redacted_and_require_containment_and_rescan(): void
    {
        $root = $this->fixtureRoot();
        mkdir($root.'/reports', recursive: true);
        $sensitive = 'SYNTHETIC-'.'CREDENTIAL-MATERIAL-NEVER-PRINT';
        $assignment = 'DB_'.'PASSWORD="'.$sensitive.'"';
        file_put_contents($root.'/reports/failure.log', $assignment);

        $result = (new PrivacyScanner)->scan($root, ['reports']);
        $payload = (new SafeDiagnosticEncoder)->json($result->jsonSerialize());

        $this->assertTrue($result->releaseBlocked());
        $this->assertSame(1, $result->unallowlistedFindingCount());
        $this->assertStringNotContainsString($sensitive, $payload);
        $this->assertStringContainsString('credential_or_secret_assignment', $payload);
        $this->assertStringContainsString('contain', strtolower($payload));
        $this->assertStringContainsString('rescan', strtolower($payload));
    }

    #[Test]
    public function reused_digest_under_another_domain_is_detected_without_token_output(): void
    {
        $root = $this->fixtureRoot();
        mkdir($root.'/exports', recursive: true);
        $versions = new CanonicalizationVersionRegistry;
        $service = new HmacTokenService(
            new ConfiguredKeyProvider([
                'key_id' => 'migration-hmac',
                'key_version' => 'v1',
                'key' => '789abcdef0123456BCDEFA!@#$%^&*()-+=0123456',
                'algorithm' => 'sha256',
            ]),
            $versions,
            'testing',
        );
        $message = (new CanonicalTypedMessageEncoder($versions))->encode([TypedValue::string('SYNTHETIC-ONLY-P3::TOKEN')]);
        $token = $service->tokenize(new TokenDomain('patient_source'), $message)->encode();
        $parts = explode('.', $token);
        $header = json_decode($this->decode($parts[1]), true, flags: JSON_THROW_ON_ERROR);
        $header['domain'] = 'contact_source';
        $misused = $parts[0].'.'.$this->encode(json_encode($header, JSON_THROW_ON_ERROR)).'.'.$parts[2];
        file_put_contents($root.'/exports/one.log', $token);
        file_put_contents($root.'/exports/two.log', $misused);

        $result = (new PrivacyScanner)->scan($root, ['exports']);
        $ids = array_map(static fn ($finding): string => $finding->detectorId, $result->findings);
        $payload = json_encode($result, JSON_THROW_ON_ERROR);

        $this->assertContains('cross_domain_token_misuse', $ids);
        $this->assertStringNotContainsString($token, $payload);
        $this->assertStringNotContainsString($misused, $payload);
    }

    #[Test]
    public function requested_missing_artifact_is_a_coverage_failure(): void
    {
        $root = $this->fixtureRoot();
        $result = (new PrivacyScanner)->scan($root, [], ['reports/missing.json']);

        $this->assertSame(1, $result->coverageDifference);
        $this->assertTrue($result->releaseBlocked());
        $this->assertSame('artifact_missing', $result->findings[0]->detectorId);
    }

    #[Test]
    public function missing_configured_root_is_a_coverage_failure(): void
    {
        $root = $this->fixtureRoot();
        $result = (new PrivacyScanner)->scan($root, ['mandatory/generated-reports']);

        $this->assertSame(1, $result->coverageDifference);
        $this->assertTrue($result->releaseBlocked());
        $this->assertSame('mandatory/generated-reports', $result->findings[0]->path);
        $this->assertSame('artifact_missing', $result->findings[0]->detectorId);
    }

    #[Test]
    public function required_phase_three_scope_cannot_be_omitted_by_configuration(): void
    {
        $root = $this->fixtureRoot();
        $sensitiveFixture = 'SYNTHETIC-REQUIRED-SCOPE-'.'CREDENTIAL';
        $name = 'DB_'.'PASSWORD';
        file_put_contents(
            $root.'/app/Services/LegacyMigration/Foundation/Fixture.php',
            "<?php\n// ".$name.'="'.$sensitiveFixture.'"',
        );

        $result = (new PrivacyScanner)->scan($root, ['docs/legacy-migration']);
        $payload = json_encode($result, JSON_THROW_ON_ERROR);

        $this->assertTrue($result->releaseBlocked());
        $this->assertSame('credential_or_secret_assignment', $result->findings[0]->detectorId);
        $this->assertSame('app/Services/LegacyMigration/Foundation/Fixture.php', $result->findings[0]->path);
        $this->assertStringNotContainsString($sensitiveFixture, $payload);
    }

    #[Test]
    public function an_operational_guard_source_cannot_be_omitted_from_the_mandatory_manifest(): void
    {
        $root = $this->fixtureRoot();
        $omitted = OperationalGuardScopeManifest::sourcePaths()[0];
        unlink($root.'/'.$omitted);

        $result = (new PrivacyScanner)->scan($root, ['docs/legacy-migration']);
        $matches = array_filter(
            $result->findings,
            static fn ($finding): bool => $finding->detectorId === 'artifact_missing' && $finding->path === $omitted,
        );

        self::assertTrue($result->releaseBlocked());
        self::assertCount(1, $matches);
    }

    #[Test]
    public function unquoted_secrets_and_scalar_record_identifiers_are_detected_and_redacted(): void
    {
        $root = $this->fixtureRoot();
        mkdir($root.'/exports', recursive: true);
        $sensitiveFixture = 'SYNTHETIC-UNQUOTED-'.'CREDENTIAL-NEVER-PRINT';
        $secretName = 'LEGACY_MIGRATION_HMAC_'.'KEY';
        $memberKey = 'Member'.'No';
        $opdKey = 'Opd'.'No';
        $patientKey = 'Patient'.'ID';
        $rawKey = 'raw_'.'patient_id';
        file_put_contents($root.'/exports/runtime.env', $secretName.'='.$sensitiveFixture);
        file_put_contents($root.'/exports/rows.json', json_encode([
            $memberKey => 48291,
            $opdKey => 'SYNTHETIC-ONLY-P3::OPD',
            $patientKey => 710,
            $rawKey => 'SYNTHETIC-ONLY-P3::RAW',
        ], JSON_THROW_ON_ERROR));

        $result = (new PrivacyScanner)->scan($root, ['exports']);
        $ids = array_map(static fn ($finding): string => $finding->detectorId, $result->findings);
        $payload = json_encode($result, JSON_THROW_ON_ERROR);

        $this->assertContains('unquoted_env_secret_assignment', $ids);
        $this->assertSame(3, count(array_filter($ids, static fn (string $id): bool => $id === 'record_level_identifier_value')));
        $this->assertSame(1, count(array_filter($ids, static fn (string $id): bool => $id === 'real_raw_identifier_key')));
        $this->assertStringNotContainsString($sensitiveFixture, $payload);
        $this->assertStringNotContainsString('48291', $payload);
        $this->assertStringNotContainsString('710', $payload);
    }

    #[Test]
    public function exception_diagnostics_never_echo_the_exception_message(): void
    {
        $sensitive = 'SYNTHETIC-'.'ERROR-VALUE-NEVER-PRINT';
        $payload = (new SafeDiagnosticEncoder)->json(
            (new SafeDiagnosticEncoder)->exception(new RuntimeException($sensitive)),
        );

        $this->assertStringNotContainsString($sensitive, $payload);
        $this->assertStringContainsString('LM-PRIV-SCAN-FAILED-001', $payload);
    }

    #[Test]
    public function scalar_cli_log_dsn_header_session_github_and_cloud_formats_are_detected_without_value_output(): void
    {
        $root = $this->fixtureRoot();
        mkdir($root.'/exports', recursive: true);
        $parts = [
            'Member'.'No: 483920',
            'Opd'.'No = 93820',
            'Patient'.'ID | 76120',
            'Authorization'.': Bearer SYNTHETICLONG'.'TOKENMATERIAL123456',
            'Cookie'.': session=SYNTHETICSESSIONTOKEN123456',
            'mysql'.'://synthetic-user:synthetic-pass@'.'db.invalid/uuhms',
            'github'.'_pat_SYNTHETIC012345678901234567890123',
            'ASIA'.'SYNTHETIC01234567',
            'AIza'.'SYNTHETIC01234567890123456789012345',
            'patient'.'_id=384920',
        ];
        file_put_contents($root.'/exports/adversarial.log', implode("\n", $parts));

        $result = (new PrivacyScanner)->scan($root, ['exports']);
        $ids = array_values(array_unique(array_map(static fn ($finding): string => $finding->detectorId, $result->findings)));
        $payload = json_encode($result, JSON_THROW_ON_ERROR);

        foreach (['scalar_identifier_value', 'authorization_header', 'cookie_session_dump', 'database_dsn', 'credential_token', 'cloud_provider_credential', 'sql_diagnostic_identifier', 'log_identifier_assignment'] as $id) {
            self::assertContains($id, $ids);
        }
        foreach ($parts as $value) {
            self::assertStringNotContainsString($value, $payload);
        }
    }

    private function fixtureRoot(): string
    {
        $root = sys_get_temp_dir().'/uhms-privacy-'.bin2hex(random_bytes(6));
        mkdir($root);
        $directories = [
            'docs/legacy-migration',
            'app/Services/LegacyMigration/Foundation',
            'app/Services/LegacyMigration/Evidence',
            'app/Models/LegacyMigration',
            'app/Providers',
            'app/Console/Commands/LegacyMigration',
            'database/migrations',
            'tests/Unit/LegacyMigration/Foundation',
            'tests/Feature/LegacyMigration/Foundation',
            'config',
        ];
        foreach ($directories as $directory) {
            mkdir($root.'/'.$directory, recursive: true);
        }
        file_put_contents($root.'/.env.example', "LEGACY_MIGRATION_HMAC_KEY=\n");
        file_put_contents($root.'/config/legacy-migration.php', "<?php\nreturn [];\n");
        file_put_contents($root.'/docs/legacy-migration/README.md', 'Aggregate-only synthetic scanner fixture.');
        file_put_contents($root.'/app/Services/LegacyMigration/Foundation/Fixture.php', "<?php\n");
        file_put_contents($root.'/app/Services/LegacyMigration/Evidence/Fixture.php', "<?php\n");
        file_put_contents($root.'/app/Models/LegacyMigration/Fixture.php', "<?php\n");
        file_put_contents($root.'/app/Providers/AppServiceProvider.php', "<?php\n");
        file_put_contents($root.'/app/Console/Commands/LegacyMigration/Fixture.php', "<?php\n");
        file_put_contents($root.'/app/Console/Commands/LegacyMigrationCaptureClassicEvidenceCommand.php', "<?php\n");
        file_put_contents($root.'/app/Console/Commands/LegacyMigrationInspectTargetCommand.php', "<?php\n");
        file_put_contents($root.'/tests/Unit/LegacyMigration/Foundation/FixtureTest.php', "<?php\n");
        file_put_contents($root.'/tests/Feature/LegacyMigration/Foundation/FixtureTest.php', "<?php\n");
        foreach ([
            '2026_07_22_000110_create_legacy_migration_run_foundation_tables.php',
            '2026_07_22_000111_create_legacy_migration_protected_store_tables.php',
            '2026_07_22_000112_create_legacy_migration_recovery_tables.php',
            '2026_07_22_000113_create_legacy_migration_installation_journal.php',
            '2026_07_22_000114_create_legacy_migration_protected_lifecycle_tables.php',
            '2026_07_22_000115_create_legacy_migration_recovery_journal.php',
        ] as $migration) {
            file_put_contents($root.'/database/migrations/'.$migration, "<?php\n");
        }
        foreach (OperationalGuardScopeManifest::sourcePaths() as $path) {
            $absolute = $root.'/'.$path;
            if (! is_dir(dirname($absolute))) {
                mkdir(dirname($absolute), recursive: true);
            }
            if (! file_exists($absolute)) {
                file_put_contents($absolute, "<?php\n");
            }
        }
        $this->roots[] = $root;

        return str_replace('\\', '/', $root);
    }

    private function removeTree(string $root): void
    {
        if (! is_dir($root)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($root);
    }

    private function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function decode(string $value): string
    {
        $value .= str_repeat('=', (4 - strlen($value) % 4) % 4);

        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}
