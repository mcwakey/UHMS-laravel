<?php

namespace App\Models\LegacyMigration;

use App\Models\LegacyMigration\Concerns\AppendOnly;

final class RecoveryJournalEntry extends ProtectedFoundationModel
{
    use AppendOnly;

    public const UPDATED_AT = null;

    protected $table = 'legacy_migration_recovery_journal_entries';

    protected $hidden = [
        'journal_token',
        'encrypted_evidence',
        'integrity_checksum',
        'created_by_token',
        'input_fingerprint',
        'source_snapshot_fingerprint',
        'target_snapshot_fingerprint',
        'contract_bundle_hash',
        'transaction_evidence_hash',
        'write_set_hash',
        'crosswalk_evidence_hash',
        'provenance_evidence_hash',
        'reconciliation_evidence_hash',
        'checkpoint_evidence_hash',
        'compensation_evidence_hash',
    ];

    protected function casts(): array
    {
        return parent::casts() + [
            'encrypted_evidence' => 'encrypted:array',
            'target_writes_permitted' => 'boolean',
            'operator_review_required' => 'boolean',
        ];
    }
}
