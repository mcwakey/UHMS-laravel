<?php

namespace Database\Seeders\ManualTesting;

use Illuminate\Support\Facades\DB;

class ManualPharmacySeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        if (! $this->hasTable('prescriptions')) {
            return;
        }

        $existing = $this->countManual('prescriptions', 'prescription_number');
        $target = $this->target('prescriptions');
        if ($existing >= $target) {
            return;
        }

        $visits = DB::table('visits')->where('visit_number', 'like', 'MT-VIS-%')->select('id', 'patient_id', 'created_by')->limit($target)->get()->values();
        if ($visits->isEmpty()) {
            return;
        }

        $records = [];
        $statuses = ['pending', 'accepted', 'partially_dispensed', 'dispensed', 'rejected', 'cancelled'];
        foreach ($visits as $index => $visit) {
            if ($index >= $target) {
                break;
            }
            $medicalRecordId = $this->ensureMedicalRecord($visit);
            if (! $medicalRecordId) {
                continue;
            }
            $records[] = [
                'medical_record_id' => $medicalRecordId,
                'visit_id' => $visit->id,
                'patient_id' => $visit->patient_id,
                'doctor_id' => $visit->created_by,
                'prescription_number' => $this->ref('RX', $existing + $index + 1),
                'status' => $statuses[$index % count($statuses)],
                'notes' => $this->metadata(['pharmacy' => true]),
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];
        }
        $this->insert('prescriptions', $records);
        $this->seedItems();
    }

    private function ensureMedicalRecord(object $visit): ?int
    {
        if (! $this->hasTable('medical_records')) {
            return null;
        }

        $existing = DB::table('medical_records')->where('visit_id', $visit->id)->value('id');
        if ($existing) {
            return $existing;
        }

        return DB::table('medical_records')->insertGetId([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'doctor_id' => $visit->created_by,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);
    }

    private function seedItems(): void
    {
        if (! $this->hasTable('prescription_items') || $this->countManual('prescription_items', 'drug_name') > 0) {
            return;
        }

        $drugs = ['Paracetamol', 'Amoxicillin', 'Artemether Lumefantrine', 'Amlodipine', 'ORS', 'Ceftriaxone'];
        $rows = [];
        $prescriptions = DB::table('prescriptions')->where('prescription_number', 'like', 'MT-RX-%')->select('id', 'status')->get();
        foreach ($prescriptions as $index => $prescription) {
            $rows[] = [
                'prescription_id' => $prescription->id,
                'drug_name' => $this->ref('DRUG', $index + 1).' '.$drugs[$index % count($drugs)],
                'dosage' => ['500mg', '250mg', '10mg'][$index % 3],
                'frequency' => ['BD', 'TDS', 'Daily'][$index % 3],
                'duration' => (3 + ($index % 7)).' days',
                'quantity' => 6 + ($index % 20),
                'route' => 'oral',
                'instructions' => 'Manual prescription item for dispensing tests.',
                'is_dispensed' => in_array($prescription->status, ['dispensed', 'partially_dispensed'], true),
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];
        }
        $this->insert('prescription_items', $rows);
    }
}
