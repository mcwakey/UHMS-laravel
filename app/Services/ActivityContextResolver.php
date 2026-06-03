<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Model;

/**
 * Derives the patient/visit context for an activity log from its subject model
 * (and any explicit values the caller passed). This is what makes module actions
 * — logged against a Visit, Invoice, EmergencyCase, MedicalRecord, … — show up
 * on the patient's profile timeline without every caller remembering to pass
 * patient_id.
 *
 * Explicit values always win; the subject only fills the gaps.
 */
class ActivityContextResolver
{
    /**
     * @return array{patient_id: int|null, visit_id: int|null}
     */
    public function resolve(?Model $subject, array $data = []): array
    {
        $patientId = $data['patient_id'] ?? null;
        $visitId = $data['visit_id'] ?? null;

        if ($subject !== null) {
            [$p, $v] = $this->fromSubject($subject);
            $patientId ??= $p;
            $visitId ??= $v;
        }

        // Have a visit but no patient → derive the patient from the visit.
        if ($patientId === null && $visitId !== null) {
            $patientId = Visit::query()->whereKey($visitId)->value('patient_id');
        }

        return [
            'patient_id' => $patientId !== null ? (int) $patientId : null,
            'visit_id' => $visitId !== null ? (int) $visitId : null,
        ];
    }

    /**
     * @return array{0: int|null, 1: int|null} [patient_id, visit_id]
     */
    private function fromSubject(Model $subject): array
    {
        if ($subject instanceof Patient) {
            return [$subject->getKey(), null];
        }
        if ($subject instanceof Visit) {
            return [$subject->patient_id, $subject->getKey()];
        }

        $attrs = $subject->getAttributes();
        $patientId = $attrs['patient_id'] ?? null;
        $visitId = $attrs['visit_id'] ?? null;

        // Models that link to the patient only through an invoice (e.g. Payment).
        if (($patientId === null || $visitId === null) && array_key_exists('invoice_id', $attrs) && $attrs['invoice_id']) {
            $invoice = Invoice::query()->whereKey($attrs['invoice_id'])->first(['patient_id', 'visit_id']);
            if ($invoice) {
                $patientId ??= $invoice->patient_id;
                $visitId ??= $invoice->visit_id;
            }
        }

        return [$patientId, $visitId];
    }
}
