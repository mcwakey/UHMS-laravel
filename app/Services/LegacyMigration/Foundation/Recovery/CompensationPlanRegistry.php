<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

final class CompensationPlanRegistry
{
    public function forUnit(AtomicUnit $unit): CompensationPlan
    {
        $alwaysProhibited = ['universal_hard_delete', 'delete_patient_core', 'mutate_existing_target', 'recycle_patient_number', 'allocate_replacement_number'];

        $actions = match ($unit) {
            AtomicUnit::PatientCore => ['rollback_uncommitted_unit_a', 'isolate_orphan_and_owner_review', 'repair_unit_a_metadata'],
            AtomicUnit::ExistingTargetLink => ['repair_or_compensate_metadata_only'],
            AtomicUnit::OpdAlias => ['rollback_uncommitted_alias_unit', 'withhold_alias', 'repair_alias_metadata'],
            AtomicUnit::EmergencyContact => ['rollback_uncommitted_contact_unit', 'withhold_contact', 'repair_contact_metadata'],
            AtomicUnit::InsuranceHistory => ['recover_history_row', 'hold_insurance_group', 'repair_history_metadata'],
            AtomicUnit::InsuranceCurrent => ['rollback_uncommitted_current_group', 'withhold_current_membership', 'repair_current_group_metadata'],
        };

        return new CompensationPlan($unit, $actions, $alwaysProhibited);
    }
}
