<?php

namespace App\Services\FrontDesk;

use App\Enums\FrontDesk\VisitorContext;
use App\Enums\FrontDesk\VisitorStatus;
use App\Enums\LogModule;
use App\Models\FrontDeskVisitorLog;
use App\Models\Patient;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Visitor log workflow: check-in (create), edit, and check-out. All writes are
 * audit-logged through {@see ActivityLogService} so the front desk module has a
 * single logging funnel. Only safe (non-clinical) context is recorded.
 */
class VisitorLogService
{
    public function __construct(
        private ActivityLogService $activityLog,
        private VisitorBadgeNumberService $badges,
        private PatientVisitorRuleService $rules,
    ) {}

    public function create(array $data, User $actor): FrontDeskVisitorLog
    {
        $data['checked_in_by'] = $actor->id;
        $data['status'] = $data['status'] ?? VisitorStatus::CHECKED_IN->value;
        $data['time_in'] = $data['time_in'] ?? now();

        $data = $this->resolveAdmissionContext($data);
        $data = $this->applyBadgeNumber($data);
        $data = $this->snapshotWarnings($data);

        $log = FrontDeskVisitorLog::create($data);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_VISITOR_CREATED', $this->context($log), $log);

        return $log;
    }

    /**
     * When a patient is linked and admission/ward/bed weren't supplied, fill them
     * from the patient's active admission and default the context to "patient".
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolveAdmissionContext(array $data): array
    {
        if (empty($data['patient_id']) || ! empty($data['admission_id'])) {
            return $data;
        }

        $admission = Patient::with('activeAdmission.bed')->find($data['patient_id'])?->activeAdmission;
        if (! $admission) {
            return $data;
        }

        $data['admission_id'] = $admission->id;
        $data['bed_id'] = $data['bed_id'] ?? $admission->bed_id;
        $data['ward_id'] = $data['ward_id'] ?? $admission->bed?->ward_id;
        if (empty($data['visitor_context'])) {
            $data['visitor_context'] = VisitorContext::PATIENT->value;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyBadgeNumber(array $data): array
    {
        if (empty($data['badge_number']) && config('front_desk.visitors.auto_generate_badge_number', true)) {
            $data['badge_number'] = $this->badges->generate();
        }

        return $data;
    }

    /**
     * Persist the advisory-warning codes computed for this record so the show
     * page and audit trail can surface why staff were warned. Codes only —
     * never free-text or clinical data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function snapshotWarnings(array $data, ?int $ignoreLogId = null): array
    {
        $codes = array_values(array_map(
            fn (array $w) => $w['code'],
            $this->rules->warningsFor($data, $ignoreLogId),
        ));

        $metadata = $data['metadata'] ?? [];
        if ($codes !== []) {
            $metadata['visitor_warnings'] = $codes;
        } else {
            unset($metadata['visitor_warnings']);
        }
        $data['metadata'] = $metadata ?: null;

        return $data;
    }

    public function update(FrontDeskVisitorLog $log, array $data, User $actor): FrontDeskVisitorLog
    {
        $previousStatus = $log->status;

        // Never regenerate the badge on update; keep existing metadata (e.g. a
        // checkout note) and refresh the advisory-warning snapshot using the
        // values that will be in effect after this update.
        $data['metadata'] = $this->snapshotWarnings([
            'patient_id' => array_key_exists('patient_id', $data) ? $data['patient_id'] : $log->patient_id,
            'admission_id' => array_key_exists('admission_id', $data) ? $data['admission_id'] : $log->admission_id,
            'ward_id' => array_key_exists('ward_id', $data) ? $data['ward_id'] : $log->ward_id,
            'visitor_phone' => array_key_exists('visitor_phone', $data) ? $data['visitor_phone'] : $log->visitor_phone,
            'metadata' => $log->metadata ?? [],
        ], $log->id)['metadata'];

        $log->fill($data)->save();
        $log->refresh();

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_VISITOR_UPDATED', $this->context($log), $log);

        // Surface explicit denied / cancelled transitions for the audit trail.
        if ($previousStatus !== $log->status) {
            if ($log->status === VisitorStatus::DENIED) {
                $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_VISITOR_DENIED', $this->context($log), $log);
            } elseif ($log->status === VisitorStatus::CANCELLED) {
                $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_VISITOR_CANCELLED', $this->context($log), $log);
            }
        }

        return $log;
    }

    public function checkOut(
        FrontDeskVisitorLog $log,
        User $actor,
        ?Carbon $timeOut = null,
        ?string $note = null
    ): FrontDeskVisitorLog {
        if (in_array($log->status, [VisitorStatus::DENIED, VisitorStatus::CANCELLED], true)) {
            throw ValidationException::withMessages([
                'status' => __('front_desk.errors.cannot_checkout_closed'),
            ]);
        }

        if ($log->status === VisitorStatus::CHECKED_OUT || $log->time_out !== null) {
            throw ValidationException::withMessages([
                'status' => __('front_desk.errors.already_checked_out'),
            ]);
        }

        $metadata = $log->metadata ?? [];
        if ($note !== null && trim($note) !== '') {
            $metadata['checkout_note'] = trim($note);
        }

        $log->update([
            'status' => VisitorStatus::CHECKED_OUT->value,
            'time_out' => $timeOut ?? now(),
            'checked_out_by' => $actor->id,
            'metadata' => $metadata ?: null,
        ]);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_VISITOR_CHECKED_OUT', $this->context($log), $log);

        return $log;
    }

    /** Audit that a visitor pass was viewed/printed by a print-authorised user. */
    public function logPassPrinted(FrontDeskVisitorLog $log, User $actor): void
    {
        $this->activityLog->log(
            LogModule::FRONT_DESK,
            'FRONT_DESK_VISITOR_PASS_PRINTED',
            array_merge($this->context($log), ['causer' => $actor]),
            $log,
        );
    }

    /**
     * Safe audit context — ids and status only, never free-text notes.
     * patient_id / visit_id / department_id are kept top-level so the shared
     * ActivityLogService copies them onto the patient timeline; the rest is
     * nested under metadata (preserved verbatim).
     *
     * @return array<string, mixed>
     */
    private function context(FrontDeskVisitorLog $log): array
    {
        return [
            'patient_id' => $log->patient_id,
            'visit_id' => $log->visit_id,
            'department_id' => $log->department_id,
            'metadata' => array_filter([
                'front_desk_visitor_log_id' => $log->id,
                'visitor_context' => $log->visitor_context?->value,
                'status' => $log->status?->value,
            ], fn ($value) => $value !== null),
        ];
    }
}
