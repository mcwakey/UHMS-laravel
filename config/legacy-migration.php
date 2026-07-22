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
        'physical_identity' => [
            'contract_version' => env('LEGACY_MIGRATION_TARGET_IDENTITY_CONTRACT_VERSION'),
            'connection' => env('LEGACY_MIGRATION_TARGET_CONNECTION'),
            'database' => env('LEGACY_MIGRATION_TARGET_DATABASE'),
            'driver' => env('LEGACY_MIGRATION_TARGET_DRIVER'),
            'database_version' => env('LEGACY_MIGRATION_TARGET_VERSION'),
            'host_identity_reference' => env('LEGACY_MIGRATION_TARGET_HOST_IDENTITY_REFERENCE'),
            'port' => (int) env('LEGACY_MIGRATION_TARGET_PORT', 0),
            'tls_required' => true,
            'tls_cipher_reference' => env('LEGACY_MIGRATION_TARGET_TLS_CIPHER_REFERENCE'),
            'tls_peer_identity_required' => true,
            'tls_peer_identity_reference' => env('LEGACY_MIGRATION_TARGET_TLS_PEER_IDENTITY_REFERENCE'),
            'server_identity_reference' => env('LEGACY_MIGRATION_TARGET_SERVER_IDENTITY_REFERENCE'),
            'server_role_classification' => env('LEGACY_MIGRATION_TARGET_SERVER_ROLE'),
            'network_environment_identity_reference' => env('LEGACY_MIGRATION_TARGET_NETWORK_IDENTITY_REFERENCE'),
            'environment_attestation_version' => env('LEGACY_MIGRATION_TARGET_ATTESTATION_VERSION'),
            'structural_schema_fingerprint' => env('LEGACY_MIGRATION_TARGET_FINGERPRINT'),
            'table_count' => (int) env('LEGACY_MIGRATION_TARGET_TABLE_COUNT', 0),
            'column_count' => (int) env('LEGACY_MIGRATION_TARGET_COLUMN_COUNT', 0),
            'foundation_schema_coordinate' => env('LEGACY_MIGRATION_FOUNDATION_SCHEMA_COORDINATE'),
            'configuration_fingerprint' => env('LEGACY_MIGRATION_CONFIGURATION_FINGERPRINT'),
            'owner_approval_reference' => env('LEGACY_MIGRATION_TARGET_IDENTITY_APPROVAL_REFERENCE'),
            'identity_key_reference' => env('LEGACY_MIGRATION_TARGET_IDENTITY_KEY_REFERENCE'),
            'identity_key_id' => env('LEGACY_MIGRATION_TARGET_IDENTITY_KEY_ID'),
            'identity_key_version' => env('LEGACY_MIGRATION_TARGET_IDENTITY_KEY_VERSION'),
        ],
    ],

    'versions' => [
        'contract_bundle' => env('LEGACY_MIGRATION_CONTRACT_BUNDLE_VERSION', 'phase-2f/2F.1.0'),
        'configuration' => 'phase-3/1.0.0',
        'canonicalization' => 'typed-length-prefix/1',
    ],

    'hmac' => [
        'key_id' => env('LEGACY_MIGRATION_HMAC_KEY_ID'),
        'key_version' => env('LEGACY_MIGRATION_HMAC_KEY_VERSION'),
        // Compatibility only for isolated tests. Deployed Phase 3B must use
        // key_provider external references; key bytes must not enter config.
        'key' => null,
        'algorithm' => 'sha256',
        'domains' => [
            'patient_source', 'staff_source', 'insurance_source', 'contact_source',
            'reference_source', 'target_record', 'idempotency', 'artifact_integrity',
            'migration_run', 'migration_audit', 'source_snapshot', 'target_snapshot',
            'target_collision', 'reconciliation', 'recovery', 'number_reservation',
        ],
    ],

    'key_provider' => [
        'provider' => 'external_reference',
        'references' => [],
        'reference_manifest' => env('LEGACY_MIGRATION_KEY_REFERENCE_MANIFEST'),
        'reference_manifest_hash' => env('LEGACY_MIGRATION_KEY_REFERENCE_MANIFEST_HASH'),
        'rotation_authority_reference' => env('LEGACY_MIGRATION_KEY_ROTATION_AUTHORITY_REFERENCE'),
        'approved_rotation_reasons' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('LEGACY_MIGRATION_KEY_ROTATION_REASONS', '')),
        ))),
    ],

    'protected_store' => [
        'population_enabled' => false,
        'full_envelope_required' => true,
        'keyed_integrity_required' => true,
        'purpose_scoped_access_required' => true,
        'direct_model_access_allowed' => false,
        'access_policy_reference' => env('LEGACY_MIGRATION_PROTECTED_ACCESS_POLICY_REFERENCE'),
        'access_classification' => env('LEGACY_MIGRATION_PROTECTED_ACCESS_CLASSIFICATION'),
    ],

    'phase2f_policy' => [
        'artifacts' => [],
        'artifact_manifest' => env('LEGACY_MIGRATION_PHASE2F_POLICY_MANIFEST'),
        'expected_bundle_hash' => env('LEGACY_MIGRATION_PHASE2F_POLICY_BUNDLE_HASH'),
        'approval_reference' => env('LEGACY_MIGRATION_PHASE2F_POLICY_APPROVAL_REFERENCE'),
    ],

    'snapshots' => [
        'authoritative_capture_enabled' => false,
        'source_query_manifest_reference' => env('LEGACY_MIGRATION_SOURCE_QUERY_MANIFEST_REFERENCE'),
        'target_query_manifest_reference' => env('LEGACY_MIGRATION_TARGET_QUERY_MANIFEST_REFERENCE'),
        'persistence_authority_reference' => env('LEGACY_MIGRATION_SNAPSHOT_PERSISTENCE_AUTHORITY_REFERENCE'),
        'tool_version' => env('LEGACY_MIGRATION_CAPTURE_TOOL_VERSION'),
        'physical_target_authority_required' => true,
        'protected_persistence_required' => true,
    ],

    'recovery' => [
        'persistent_journal_enabled' => false,
        'connection' => env('LEGACY_MIGRATION_RECOVERY_CONNECTION'),
        'journal_version' => 'phase-3b/recovery-journal/1',
        'compare_and_set_required' => true,
        'authority_verified' => false,
        'authority_reference' => env('LEGACY_MIGRATION_RECOVERY_AUTHORITY_REFERENCE'),
        'access_classification' => env('LEGACY_MIGRATION_RECOVERY_ACCESS_CLASSIFICATION'),
        'retention_classification' => env('LEGACY_MIGRATION_RECOVERY_RETENTION_CLASSIFICATION'),
        'transformation_version' => env('LEGACY_MIGRATION_RECOVERY_TRANSFORMATION_VERSION'),
    ],

    'allocation' => [
        'protected_reservations_enabled' => false,
        'authority_verified' => false,
        'connection' => env('LEGACY_MIGRATION_ALLOCATION_CONNECTION'),
        'authority_reference' => env('LEGACY_MIGRATION_ALLOCATION_AUTHORITY_REFERENCE'),
        'access_classification' => env('LEGACY_MIGRATION_ALLOCATION_ACCESS_CLASSIFICATION'),
        'retention_classification' => env('LEGACY_MIGRATION_ALLOCATION_RETENTION_CLASSIFICATION'),
        'transformation_version' => env('LEGACY_MIGRATION_ALLOCATION_TRANSFORMATION_VERSION'),
    ],

    'runtime_audit' => [
        'enabled' => false,
        'authority_reference' => env('LEGACY_MIGRATION_RUNTIME_AUDIT_AUTHORITY_REFERENCE'),
        'access_classification' => env('LEGACY_MIGRATION_RUNTIME_AUDIT_ACCESS_CLASSIFICATION'),
        'retention_classification' => env('LEGACY_MIGRATION_RUNTIME_AUDIT_RETENTION_CLASSIFICATION'),
    ],

    'evaluator' => [
        'bundle_version' => env('LEGACY_MIGRATION_EVALUATOR_BUNDLE_VERSION'),
        'authoritative_recorders_bound' => false,
        'source_recorder_reference' => env('LEGACY_MIGRATION_SOURCE_RECORDER_REFERENCE'),
        'target_recorder_reference' => env('LEGACY_MIGRATION_TARGET_RECORDER_REFERENCE'),
        'side_effect_recorder_reference' => env('LEGACY_MIGRATION_SIDE_EFFECT_RECORDER_REFERENCE'),
        'repository_recorder_reference' => env('LEGACY_MIGRATION_REPOSITORY_RECORDER_REFERENCE'),
        'missing_measurement_invalid' => true,
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
        'installation_journal_enabled' => false,
        'installation_manifest_version' => 'phase-3b/foundation-ddl/1',
        'partial_repair_authorized' => false,
    ],

    'disposable_verification' => [
        'enabled' => false,
        'identity_verified' => false,
        'environment_reference' => '',
        'connection' => '',
        'database' => '',
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
        'application_bindings' => [
            'bindings_verified' => false,
            'queue_workers_paused' => false,
            'scheduler_paused' => false,
            'external_integrations_sink_verified' => false,
            'authority_reference' => env('LEGACY_MIGRATION_ISOLATION_AUTHORITY_REFERENCE'),
            'queue_worker_barrier_reference' => env('LEGACY_MIGRATION_QUEUE_BARRIER_REFERENCE'),
            'scheduler_barrier_reference' => env('LEGACY_MIGRATION_SCHEDULER_BARRIER_REFERENCE'),
            'null_sink_reference' => env('LEGACY_MIGRATION_NULL_SINK_REFERENCE'),
        ],
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
        'policy_reference' => env('LEGACY_MIGRATION_RETENTION_POLICY_REFERENCE'),
        'schedule_resolved' => false,
        'purge_execution_enabled' => false,
    ],
];
