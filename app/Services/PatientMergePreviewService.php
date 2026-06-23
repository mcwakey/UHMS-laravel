<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PatientMergePreviewService
{
    private const RECORD_TABLES = [
        ['table' => 'visits', 'label' => 'Visits'],
        ['table' => 'medical_records', 'label' => 'Medical records'],
        ['table' => 'vitals', 'label' => 'Vitals'],
        ['table' => 'prescriptions', 'label' => 'Prescriptions'],
        ['table' => 'invoices', 'label' => 'Invoices'],
        ['table' => 'invoice_items', 'label' => 'Invoice items'],
        ['table' => 'payments', 'label' => 'Payments'],
        ['table' => 'appointments', 'label' => 'Appointments'],
        ['table' => 'admissions', 'label' => 'Admissions'],
        ['table' => 'lab_requests', 'label' => 'Lab requests'],
        ['table' => 'triages', 'label' => 'Triages'],
        ['table' => 'claims', 'label' => 'Claims'],
        ['table' => 'claim_items', 'label' => 'Claim items'],
        ['table' => 'dispensing_records', 'label' => 'Dispensing records'],
        ['table' => 'patient_procedures', 'label' => 'Patient procedures'],
        ['table' => 'procedure_requests', 'label' => 'Procedure requests'],
        ['table' => 'consumable_usages', 'label' => 'Consumable usages'],
        ['table' => 'visit_consultation_routes', 'label' => 'Consultation routes'],
        ['table' => 'complaints', 'label' => 'Complaints'],
        ['table' => 'diagnoses', 'label' => 'Diagnoses'],
        ['table' => 'investigations', 'label' => 'Investigations'],
        ['table' => 'treatments', 'label' => 'Treatments'],
        ['table' => 'consultation_tasks', 'label' => 'Consultation tasks'],
        ['table' => 'history_of_presenting_complaints', 'label' => 'History of presenting complaints'],
        ['table' => 'physical_examinations', 'label' => 'Physical examinations'],
        ['table' => 'medication_orders', 'label' => 'Medication orders'],
        ['table' => 'clinical_tasks', 'label' => 'Clinical tasks'],
        ['table' => 'medication_administration_schedules', 'label' => 'Medication administration schedules'],
        ['table' => 'medication_administrations', 'label' => 'Medication administrations'],
        ['table' => 'emergency_cases', 'label' => 'Emergency cases'],
        ['table' => 'emergency_case_logs', 'label' => 'Emergency case logs'],
        ['table' => 'emergency_notes', 'label' => 'Emergency notes'],
        ['table' => 'pharmacy_billing_selections', 'label' => 'Pharmacy billing selections'],
        ['table' => 'emergency_contacts', 'label' => 'Emergency contacts'],
        ['table' => 'patient_insurances', 'label' => 'Patient insurances', 'handler' => 'insurance'],
    ];

    public const DEMOGRAPHIC_FIELDS = [
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'other_names' => 'Other names',
        'date_of_birth' => 'Date of birth',
        'gender' => 'Gender',
        'blood_group' => 'Blood group',
        'marital_status' => 'Marital status',
        'religion' => 'Religion',
        'occupation' => 'Occupation',
        'phone' => 'Phone',
        'phone_secondary' => 'Secondary phone',
        'email' => 'Email',
        'ghana_card_number' => 'ID Card',
        'address' => 'Address',
        'city' => 'City',
        'town' => 'Town',
        'region' => 'Region',
        'digital_address' => 'Digital address',
        'allergies' => 'Allergies',
        'chronic_conditions' => 'Chronic conditions',
    ];

    public function preview(Patient $mainPatient, Patient $duplicatePatient): array
    {
        $counts = [];
        $total = 0;

        foreach (self::RECORD_TABLES as $definition) {
            if (! $this->tableColumnExists($definition['table'], 'patient_id')) {
                continue;
            }

            $count = DB::table($definition['table'])
                ->where('patient_id', $duplicatePatient->id)
                ->count();

            $counts[] = [
                'table' => $definition['table'],
                'label' => $this->recordLabel($definition['table'], $definition['label']),
                'count' => $count,
                'handler' => $definition['handler'] ?? 'generic',
            ];

            $total += $count;
        }

        return [
            'main_patient_id' => $mainPatient->id,
            'duplicate_patient_id' => $duplicatePatient->id,
            'record_counts' => $counts,
            'total_records' => $total,
            'demographic_fields' => $this->demographicComparison($mainPatient, $duplicatePatient),
            'warnings' => $this->warnings($mainPatient, $duplicatePatient),
        ];
    }

    public function recordTables(): array
    {
        return array_map(function (array $definition) {
            $definition['label'] = $this->recordLabel($definition['table'], $definition['label']);

            return $definition;
        }, self::RECORD_TABLES);
    }

    public function demographicFields(): array
    {
        return collect(self::DEMOGRAPHIC_FIELDS)
            ->mapWithKeys(fn (string $label, string $field) => [$field => $this->demographicLabel($field, $label)])
            ->all();
    }

    public function tableExists(string $table): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasTable($table);
        }

        $quotedTable = DB::getPdo()->quote($table);

        return ! empty(DB::select("SHOW TABLES LIKE {$quotedTable}"));
    }

    public function tableColumnExists(string $table, string $column): bool
    {
        if (! $this->tableExists($table)) {
            return false;
        }

        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasColumn($table, $column);
        }

        $quotedColumn = DB::getPdo()->quote($column);

        return ! empty(DB::select("SHOW COLUMNS FROM `{$table}` LIKE {$quotedColumn}"));
    }

    private function demographicComparison(Patient $mainPatient, Patient $duplicatePatient): array
    {
        $comparison = [];

        foreach (self::DEMOGRAPHIC_FIELDS as $field => $label) {
            $mainValue = $mainPatient->getRawOriginal($field);
            $duplicateValue = $duplicatePatient->getRawOriginal($field);

            $comparison[] = [
                'field' => $field,
                'label' => $this->demographicLabel($field, $label),
                'main' => $mainValue,
                'duplicate' => $duplicateValue,
                'differs' => (string) $mainValue !== (string) $duplicateValue,
                'suggested_source' => blank($mainValue) && filled($duplicateValue) ? 'duplicate' : 'main',
            ];
        }

        return $comparison;
    }

    private function warnings(Patient $mainPatient, Patient $duplicatePatient): array
    {
        $warnings = [];

        if ($mainPatient->isMerged()) {
            $warnings[] = __('patients.merge_preview.warnings.main_already_merged');
        }

        if ($duplicatePatient->isMerged()) {
            $warnings[] = __('patients.merge_preview.warnings.duplicate_already_merged');
        }

        if ($duplicatePatient->is_temporary) {
            $warnings[] = __('patients.merge_preview.warnings.temporary_emergency_identity');
        }

        return $warnings;
    }

    private function recordLabel(string $table, string $fallback): string
    {
        return __("patients.merge_preview.records.{$table}", [], app()->getLocale()) !== "patients.merge_preview.records.{$table}"
            ? __("patients.merge_preview.records.{$table}")
            : $fallback;
    }

    private function demographicLabel(string $field, string $fallback): string
    {
        return __("patients.merge_preview.demographics.{$field}", [], app()->getLocale()) !== "patients.merge_preview.demographics.{$field}"
            ? __("patients.merge_preview.demographics.{$field}")
            : $fallback;
    }
}
