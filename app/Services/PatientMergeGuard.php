<?php

namespace App\Services;

use App\Models\Patient;

class PatientMergeGuard
{
    public function assertCanReceiveNewRecords(Patient|int|null $patient, string $context = 'record'): Patient
    {
        $patient = $patient instanceof Patient ? $patient : Patient::find($patient);

        if (! $patient) {
            throw new \InvalidArgumentException('The selected patient does not exist.');
        }

        $patient->assertCanReceiveNewRecords($context);

        return $patient;
    }
}
