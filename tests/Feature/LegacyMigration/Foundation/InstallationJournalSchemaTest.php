<?php

namespace Tests\Feature\LegacyMigration\Foundation;

use App\Services\LegacyMigration\Foundation\Installation\InstallationJournalRecord;
use App\Services\LegacyMigration\Foundation\Installation\LaravelFoundationInstallationJournal;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class InstallationJournalSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_installation_journal_is_foundation_only_append_only_and_protected(): void
    {
        $identity = hash('sha256', 'SYNTHETIC-P3B-IDENTITY');
        $journal = new LaravelFoundationInstallationJournal(DB::connection());
        $journal->append(new InstallationJournalRecord(
            'P3B-DDL-1',
            $identity,
            'table:legacy_migration_runs',
            hash('sha256', 'SYNTHETIC-P3B-DDL'),
            null,
            'started',
            1,
            hash('sha256', 'SYNTHETIC-P3B-AUDIT'),
        ));
        $this->assertDatabaseCount('legacy_migration_installation_journal', 1);
        $this->assertSame(2, $journal->nextAttempt('P3B-DDL-1', $identity, 'table:legacy_migration_runs'));

        try {
            DB::table('legacy_migration_installation_journal')->where('target_identity_reference', $identity)->update(['state' => 'verified']);
            $this->fail('Installation evidence was mutable.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        $this->expectException(QueryException::class);
        DB::table('legacy_migration_installation_journal')->where('target_identity_reference', $identity)->delete();
    }

    public function test_database_rejects_illegal_atomic_recovery_state_edges_and_counter_bypasses(): void
    {
        $digest = static fn (string $value): string => hash('sha256', 'state-ddl|'.$value);
        $bundle = DB::table('legacy_migration_contract_bundles')->insertGetId([
            'bundle_token' => $digest('bundle-token'), 'bundle_version' => 'synthetic/1',
            'bundle_hash' => $digest('bundle-hash'), 'canonicalization_version' => 'synthetic/1',
            'token_environment' => 'testing', 'hmac_key_id' => 'synthetic', 'hmac_key_version' => 'v1',
            'encrypted_manifest' => '{}', 'integrity_checksum' => $digest('bundle-integrity'),
            'access_classification' => 'synthetic_restricted', 'retention_classification' => 'synthetic_test',
        ]);
        $run = DB::table('legacy_migration_runs')->insertGetId([
            'run_token' => $digest('run-token'), 'cohort_token' => $digest('cohort-token'),
            'contract_bundle_id' => $bundle, 'mode' => 'dry_run', 'state' => 'NOT_STARTED',
            'configuration_fingerprint' => $digest('configuration'), 'source_fingerprint' => $digest('source'),
            'target_fingerprint' => $digest('target'), 'canonicalization_version' => 'synthetic/1',
            'token_environment' => 'testing', 'hmac_key_id' => 'synthetic', 'hmac_key_version' => 'v1',
            'encrypted_manifest' => '{}', 'manifest_checksum' => $digest('manifest'),
            'access_classification' => 'synthetic_restricted', 'retention_classification' => 'synthetic_test',
            'lock_version' => 0,
        ]);
        $snapshot = static function (string $kind) use ($run, $digest): int {
            return DB::table('legacy_migration_snapshots')->insertGetId([
                'run_id' => $run, 'snapshot_token' => $digest($kind.'-snapshot'), 'snapshot_kind' => $kind,
                'domain' => 'synthetic_foundation_test', 'coordinate_token' => $digest($kind.'-coordinate'),
                'database_fingerprint' => $digest($kind.'-database'), 'schema_fingerprint' => $digest($kind.'-schema'),
                'query_bundle_hash' => $digest($kind.'-query'), 'result_hash' => $digest($kind.'-result'),
                'contract_version' => 'synthetic/1', 'transformation_version' => 'synthetic/1',
                'canonicalization_version' => 'synthetic/1', 'hmac_key_version' => 'v1', 'state' => 'captured',
                'integrity_checksum' => $digest($kind.'-integrity'), 'access_classification' => 'synthetic_restricted',
                'retention_classification' => 'synthetic_test', 'captured_at' => now(),
            ]);
        };
        $source = $snapshot('source');
        $target = $snapshot('target');
        $idempotency = DB::table('legacy_migration_idempotency_records')->insertGetId([
            'run_id' => $run, 'source_snapshot_id' => $source, 'target_snapshot_id' => $target,
            'domain' => 'synthetic_foundation_test', 'idempotency_token' => $digest('idempotency'),
            'outcome_type' => 'synthetic', 'input_fingerprint' => $digest('input'),
            'contract_version' => 'synthetic/1', 'transformation_version' => 'synthetic/1',
            'canonicalization_version' => 'synthetic/1', 'hmac_key_version' => 'v1',
            'state' => 'NOT_STARTED', 'attempt_count' => 0, 'integrity_checksum' => $digest('idempotency-integrity'),
            'access_classification' => 'synthetic_restricted', 'retention_classification' => 'synthetic_test',
            'lock_version' => 0,
        ]);
        $id = DB::table('legacy_migration_atomic_intents')->insertGetId([
            'run_id' => $run,
            'idempotency_record_id' => $idempotency,
            'domain' => 'synthetic_foundation_test',
            'intent_token' => hash('sha256', 'synthetic-intent'),
            'unit_name' => 'patient_core',
            'expected_prior_state' => 'NOT_STARTED',
            'state' => 'NOT_STARTED',
            'attempt' => 1,
            'transition_attempt_count' => 0,
            'contract_version' => 'synthetic/1',
            'transformation_version' => 'synthetic/1',
            'canonicalization_version' => 'synthetic/1',
            'hmac_key_version' => 'synthetic/1',
            'integrity_checksum' => hash('sha256', 'synthetic-integrity'),
            'access_classification' => 'synthetic_restricted',
            'retention_classification' => 'synthetic_test',
            'lock_version' => 0,
        ]);

        try {
            DB::table('legacy_migration_atomic_intents')->where('id', $id)->update([
                'state' => 'COMPLETED',
                'lock_version' => 1,
                'transition_attempt_count' => 1,
            ]);
            $this->fail('Raw SQL bypassed the exact recovery transition graph.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        $this->assertSame(1, DB::table('legacy_migration_atomic_intents')->where('id', $id)->update([
            'state' => 'EXTRACTED',
            'lock_version' => 1,
            'transition_attempt_count' => 1,
        ]));

        $this->expectException(QueryException::class);
        DB::table('legacy_migration_atomic_intents')->where('id', $id)->update([
            'state' => 'CLASSIFIED',
            'lock_version' => 2,
            'transition_attempt_count' => 7,
        ]);
    }
}
