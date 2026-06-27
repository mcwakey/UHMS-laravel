<?php

namespace Database\Seeders\ManualTesting;

use Illuminate\Support\Facades\DB;

class ManualClinicalSeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        if (! $this->hasTable('diagnoses') || $this->countManual('diagnoses', 'notes') > 0) {
            return;
        }

        $records = DB::table('medical_records')
            ->join('visits', 'visits.id', '=', 'medical_records.visit_id')
            ->where('visits.visit_number', 'like', 'MT-VIS-%')
            ->select('medical_records.id', 'medical_records.patient_id', 'medical_records.doctor_id')
            ->limit(300)
            ->get();

        $rows = [];
        foreach ($records as $index => $record) {
            $rows[] = [
                'medical_record_id' => $record->id,
                'description' => ['Malaria', 'Hypertension', 'Upper respiratory tract infection', 'Gastritis'][$index % 4],
                'type' => $index % 5 === 0 ? 'final' : 'provisional',
                'notes' => 'MT-MANUAL clinical diagnosis seed',
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];
        }
        $this->insert('diagnoses', $rows);
    }
}
