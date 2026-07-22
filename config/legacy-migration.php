<?php

return [
    /*
    | Phase 3 foundation is inert unless every guard succeeds. Enabling this
    | file never authorizes a domain importer, Cohort B selection, or commit.
    */
    'enabled' => env('LEGACY_MIGRATION_FOUNDATION_ENABLED', false),
    'allowed_environments' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('LEGACY_MIGRATION_ALLOWED_ENVIRONMENTS', 'local,testing')),
    ))),
    'reject_production' => true,

    'source' => [
        'connection' => 'legacy_uhms',
        'database' => 'uuhms',
        'expected_version' => env('LEGACY_MIGRATION_SOURCE_VERSION', '10.4.32-MariaDB'),
        'expected_fingerprint' => env('LEGACY_MIGRATION_SOURCE_FINGERPRINT'),
        'expected_table_count' => 55,
        'expected_column_count' => 479,
        'required_account_policy' => 'select_and_metadata_only',
        'require_read_only_transaction' => true,
    ],

    'target' => [
        'connection' => env('LEGACY_MIGRATION_TARGET_CONNECTION', env('DB_CONNECTION')),
        'database' => env('LEGACY_MIGRATION_TARGET_DATABASE'),
        'expected_version' => env('LEGACY_MIGRATION_TARGET_VERSION', '10.4.32-MariaDB'),
        'expected_fingerprint' => env('LEGACY_MIGRATION_TARGET_FINGERPRINT'),
        'expected_table_count' => (int) env('LEGACY_MIGRATION_TARGET_TABLE_COUNT', 0),
        'expected_column_count' => (int) env('LEGACY_MIGRATION_TARGET_COLUMN_COUNT', 0),
    ],

    'versions' => [
        'contract_bundle' => env('LEGACY_MIGRATION_CONTRACT_BUNDLE_VERSION', 'phase-2f/2F.1.0'),
        'configuration' => 'phase-3/1.0.0',
        'canonicalization' => 'typed-length-prefix/1',
    ],

    'hmac' => [
        'key_id' => env('LEGACY_MIGRATION_HMAC_KEY_ID'),
        'key_version' => env('LEGACY_MIGRATION_HMAC_KEY_VERSION'),
        'key' => env('LEGACY_MIGRATION_HMAC_KEY'),
        'algorithm' => 'sha256',
        'domains' => [
            'patient_source', 'staff_source', 'insurance_source', 'contact_source',
            'reference_source', 'target_record', 'idempotency', 'artifact_integrity',
        ],
    ],

    'execution' => [
        'dry_run_only' => true,
        'commit_authorized' => false,
        'cohort_b_selection_enabled' => false,
        'importer_execution_enabled' => false,
        'production_enabled' => false,
    ],

    'foundation' => [
        // This authorizes only installation of the protected foundation
        // tables on the exact guarded non-production target connection.
        'schema_writes_enabled' => env('LEGACY_MIGRATION_FOUNDATION_SCHEMA_WRITES_ENABLED', false),
    ],

    'isolation' => [
        'required_subsystems' => [
            'events', 'model_observers', 'activity_log', 'notifications', 'mail',
            'sms', 'queue', 'bus', 'scheduler', 'audit_forwarding', 'payments',
            'insurance_eligibility', 'billing', 'accounting', 'stock', 'pharmacy_dispensing',
            'payment_allocation', 'bed_state', 'queue_pathway', 'appointment_reminders',
            'journey_notifications', 'file_writes', 'search_indexing', 'webhooks',
            'external_integrations',
        ],
        'outbound_deny_list' => ['mail', 'sms', 'http', 'webhook', 'queue', 'broadcast'],
    ],

    'privacy' => [
        'scan_roots' => ['docs/legacy-migration', 'storage/app/legacy-migration'],
        'synthetic_namespace' => 'SYNTHETIC-ONLY-P2F',
        'aggregate_only_reports' => true,
        'forbidden_secret_names' => [
            'LEGACY_DB_PASSWORD', 'LEGACY_MIGRATION_HMAC_KEY', 'DB_PASSWORD',
        ],
    ],

    'retention' => [
        'protected' => 'pending_approved_retention_schedule',
        'aggregate' => 'pending_approved_retention_schedule',
        'purge_requires_explicit_authority' => true,
    ],
];
