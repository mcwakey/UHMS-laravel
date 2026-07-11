<?php

namespace App\Services\FrontDesk;

use App\Enums\AdmissionStatus;
use App\Models\Admission;
use App\Models\FrontDeskVisitorLog;
use App\Models\Patient;
use App\Models\Ward;

/**
 * Advisory visitor rules for admitted-patient visits (Phase 18B). Every result
 * is a NON-blocking warning: authorised staff may always proceed. No clinical
 * data is read or returned — only admission status and active visitor counts.
 */
class PatientVisitorRuleService
{
    /**
     * Warnings for a set of (validated) visitor form values.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array{code: string, message: string, severity: string}>
     */
    public function warningsFor(array $data, ?int $ignoreLogId = null): array
    {
        $patient = ! empty($data['patient_id']) ? Patient::find($data['patient_id']) : null;
        $admission = ! empty($data['admission_id']) ? Admission::find($data['admission_id']) : null;

        $warnings = $this->warningsForPatient($patient, $admission);

        $phone = trim((string) ($data['visitor_phone'] ?? ''));
        if ($phone !== '') {
            $query = FrontDeskVisitorLog::query()->currentlyInside()->where('visitor_phone', $phone);
            if ($ignoreLogId) {
                $query->where('id', '!=', $ignoreLogId);
            }
            if ($query->exists()) {
                $warnings[] = $this->warn('visitor_already_inside_same_phone');
            }
        }

        return $warnings;
    }

    /**
     * Warnings for a patient (and optional admission). Falls back to the
     * patient's active admission when one is not supplied.
     *
     * @return array<int, array{code: string, message: string, severity: string}>
     */
    public function warningsForPatient(?Patient $patient, ?Admission $admission = null): array
    {
        if (! $patient) {
            return [];
        }

        $admission = $admission ?: $patient->activeAdmission()->with('bed')->first();
        $warnings = [];

        $isActive = $admission && in_array($admission->status, [AdmissionStatus::ADMITTED, AdmissionStatus::ON_LEAVE], true);

        if (! $isActive) {
            $latest = $patient->admissions()->latest('admission_date')->first();

            if ($latest && $latest->status === AdmissionStatus::DISCHARGED) {
                $sameDay = $latest->actual_discharge_date && $latest->actual_discharge_date->isToday();
                $warnings[] = $this->warn($sameDay ? 'patient_discharged_same_day' : 'patient_discharged');
            } else {
                $warnings[] = $this->warn('patient_not_admitted');
            }
        }

        $ward = $admission?->ward()->first();
        $counts = $this->activeVisitorCounts($patient, $admission, $ward);

        $patientMax = config('front_desk.visitors.max_active_visitors_per_patient');
        if ($patientMax !== null && $counts['patient'] >= (int) $patientMax) {
            $warnings[] = $this->warn('visitor_limit_for_patient_reached', ['count' => $counts['patient'], 'max' => (int) $patientMax]);
        }

        $admissionMax = config('front_desk.visitors.max_active_visitors_per_admission');
        if ($admission && $admissionMax !== null && $counts['admission'] >= (int) $admissionMax) {
            $warnings[] = $this->warn('visitor_limit_for_admission_reached', ['count' => $counts['admission'], 'max' => (int) $admissionMax]);
        }

        $wardMax = config('front_desk.visitors.max_active_visitors_per_ward');
        if ($ward && $wardMax !== null && $counts['ward'] >= (int) $wardMax) {
            $warnings[] = $this->warn('ward_visitor_limit_reached', ['count' => $counts['ward'], 'max' => (int) $wardMax]);
        }

        return $warnings;
    }

    /**
     * Count of currently-inside visitors scoped to a patient / admission / ward.
     *
     * @return array{patient: int, admission: int, ward: int}
     */
    public function activeVisitorCounts(?Patient $patient = null, ?Admission $admission = null, ?Ward $ward = null): array
    {
        return [
            'patient' => $patient ? FrontDeskVisitorLog::query()->currentlyInside()->where('patient_id', $patient->id)->count() : 0,
            'admission' => $admission ? FrontDeskVisitorLog::query()->currentlyInside()->where('admission_id', $admission->id)->count() : 0,
            'ward' => $ward ? FrontDeskVisitorLog::query()->currentlyInside()->where('ward_id', $ward->id)->count() : 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{code: string, message: string, severity: string}
     */
    private function warn(string $code, array $params = [], string $severity = 'warning'): array
    {
        return [
            'code' => $code,
            'message' => __('front_desk.warnings.' . $code, $params),
            'severity' => $severity,
        ];
    }
}
