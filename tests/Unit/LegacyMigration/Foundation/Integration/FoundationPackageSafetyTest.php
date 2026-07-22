<?php

namespace Tests\Unit\LegacyMigration\Foundation\Integration;

use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FoundationPackageSafetyTest extends TestCase
{
    #[Test]
    public function configuration_defaults_are_inert_and_exactly_uuhms(): void
    {
        self::assertFalse(config('legacy-migration.enabled'));
        self::assertTrue(config('legacy-migration.reject_production'));
        self::assertSame('legacy_uhms', config('legacy-migration.source.connection'));
        self::assertSame('uuhms', config('legacy-migration.source.database'));
        self::assertTrue(config('legacy-migration.execution.dry_run_only'));
        self::assertFalse(config('legacy-migration.execution.commit_authorized'));
        self::assertFalse(config('legacy-migration.execution.cohort_b_selection_enabled'));
        self::assertFalse(config('legacy-migration.execution.importer_execution_enabled'));
        self::assertFalse(config('legacy-migration.execution.production_enabled'));
    }

    #[Test]
    public function only_the_six_approved_foundation_commands_exist(): void
    {
        $commands = array_values(array_filter(
            array_keys(Artisan::all()),
            fn (string $name): bool => str_starts_with($name, 'legacy-migration:foundation-')
                || $name === 'legacy-migration:verify-source-account'
                || $name === 'legacy-migration:privacy-scan',
        ));
        sort($commands);

        self::assertSame([
            'legacy-migration:foundation-preflight',
            'legacy-migration:foundation-reconcile',
            'legacy-migration:foundation-recovery-audit',
            'legacy-migration:foundation-status',
            'legacy-migration:privacy-scan',
            'legacy-migration:verify-source-account',
        ], $commands);
    }

    #[Test]
    public function no_domain_importer_or_business_table_migration_was_introduced(): void
    {
        $foundation = $this->contentsUnder(app_path('Services/LegacyMigration/Foundation'))
            .$this->contentsUnder(app_path('Console/Commands/LegacyMigration'));

        self::assertDoesNotMatchRegularExpression('/class\s+\w*(?:Patient|Staff|Insurance|Reference)Importer\b/i', $foundation);
        self::assertStringNotContainsString('run-patient-pilot', strtolower($foundation));
        self::assertStringNotContainsString('import-patients', strtolower($foundation));
        self::assertStringNotContainsString('migrate-patients', strtolower($foundation));

        $migrations = '';
        foreach (glob(database_path('migrations/2026_07_22_00011*.php')) ?: [] as $file) {
            $contents = (string) file_get_contents($file);
            $migrations .= $contents;
            preg_match_all("/Schema::create\('([^']+)'/", $contents, $matches);
            foreach ($matches[1] as $table) {
                self::assertStringStartsWith('legacy_migration_', $table);
            }
        }
        self::assertStringNotContainsString("Schema::create('patients'", $migrations);
        self::assertStringNotContainsString("Schema::table('patients'", $migrations);
    }

    private function contentsUnder(string $directory): string
    {
        $contents = '';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $contents .= (string) file_get_contents($file->getPathname());
            }
        }

        return $contents;
    }
}
