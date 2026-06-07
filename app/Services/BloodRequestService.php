<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Models\BloodRequest;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class BloodRequestService
{
    public function __construct(
        private ActivityLogService $log,
        private NotificationService $notifier,
    ) {}

    /** Create a request tied to a facility visit/patient. */
    public function createForVisit(Visit $visit, array $data, User $user): BloodRequest
    {
        return $this->persist($visit, $data, $user);
    }

    /** Create a request for an external (referral / walk-in / not-in-attendance) recipient. */
    public function createExternal(array $data, User $user): BloodRequest
    {
        return $this->persist(null, $data, $user);
    }

    protected function persist(?Visit $visit, array $data, User $user): BloodRequest
    {
        return DB::transaction(function () use ($visit, $data, $user) {
            $isExternal = $visit === null;
            $priority = strtoupper($data['priority'] ?? $data['urgency'] ?? 'ROUTINE');
            $component = strtoupper($data['component_type'] ?? 'WHOLE_BLOOD');
            $bloodGroup = $data['blood_group'];

            $request = BloodRequest::create([
                'request_number' => BloodRequest::generateRequestNumber(),
                'visit_id' => $visit?->id,
                'patient_id' => $visit?->patient_id,
                'recipient_type' => $isExternal ? BloodRequest::RECIPIENT_EXTERNAL : BloodRequest::RECIPIENT_PATIENT,
                'admission_id' => $data['admission_id'] ?? $visit?->admission?->id,
                'emergency_case_id' => $data['emergency_case_id'] ?? $visit?->emergencyCase?->id,
                'department_id' => $data['department_id'] ?? $visit?->current_department_id,
                'requested_by' => $user->id,
                'requested_at' => $data['requested_at'] ?? now(),
                'needed_at' => $data['needed_at'] ?? null,
                'blood_group' => $bloodGroup,
                'component_type' => $component,
                'units_requested' => $data['units_requested'] ?? 1,
                'priority' => $priority,
                'status' => BloodRequest::STATUS_PENDING,
                'hb_level' => $data['hb_level'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'indication' => $data['indication'] ?? $data['clinical_indication'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->upsertRecipient($request, $visit, $data, $user);

            $who = $isExternal ? ' [external recipient]' : '';
            $this->log->log(LogModule::BLOOD_BANK, 'BLOOD_REQUEST_CREATED', [
                'description' => "Blood request {$request->request_number}: {$bloodGroup} {$component} x{$request->units_requested} ({$priority}){$who}.",
                'causer' => $user,
                'patient_id' => $request->patient_id,
                'visit_id' => $request->visit_id,
            ], $request);

            $this->notifyRequest($request);

            return $request->load('recipient');
        });
    }

    /** Create/update the recipient clinical detail record for a request. */
    public function upsertRecipient(BloodRequest $request, ?Visit $visit, array $data, User $user): void
    {
        $patient = $visit?->patient ?? $request->patient;
        $rawGroup = $data['patient_blood_group'] ?? $patient?->blood_group ?? $request->blood_group;
        $group = $rawGroup instanceof \BackedEnum ? $rawGroup->value : $rawGroup;
        [$abo, $rh] = $this->splitGroup($group);

        $before = $request->recipient?->getAttributes();
        $isExternal = $request->recipient_type === BloodRequest::RECIPIENT_EXTERNAL;

        $request->recipient()->updateOrCreate(
            ['blood_request_id' => $request->id],
            [
                'patient_id' => $request->patient_id,
                'visit_id' => $request->visit_id,
                'admission_id' => $request->admission_id,
                'emergency_case_id' => $request->emergency_case_id,
                'recipient_type' => $request->recipient_type ?? BloodRequest::RECIPIENT_PATIENT,
                'external_name' => $isExternal ? ($data['external_name'] ?? null) : null,
                'external_sex' => $isExternal ? ($data['external_sex'] ?? null) : null,
                'external_age' => $isExternal ? ($data['external_age'] ?? null) : null,
                'external_facility' => $isExternal ? ($data['external_facility'] ?? null) : null,
                'external_contact' => $isExternal ? ($data['external_contact'] ?? null) : null,
                'external_reference' => $isExternal ? ($data['external_reference'] ?? null) : null,
                'patient_blood_group' => $abo ? $abo.$rh : $group,
                'patient_rh_factor' => $data['patient_rh_factor'] ?? ($rh === '-' ? 'NEGATIVE' : 'POSITIVE'),
                'diagnosis' => $data['diagnosis'] ?? $request->diagnosis,
                'clinical_indication' => $data['clinical_indication'] ?? $data['indication'] ?? $request->indication,
                'hemoglobin_level' => $data['hemoglobin_level'] ?? $data['hb_level'] ?? $request->hb_level,
                'pregnancy_status' => $data['pregnancy_status'] ?? null,
                'previous_transfusion_reaction' => (bool) ($data['previous_transfusion_reaction'] ?? false),
                'previous_transfusion_reaction_notes' => $data['previous_transfusion_reaction_notes'] ?? null,
                'transfusion_history' => $data['transfusion_history'] ?? null,
                'special_requirements' => $data['special_requirements'] ?? null,
                'requested_component_type' => strtoupper($data['component_type'] ?? $request->component_type),
                'units_required' => $data['units_required'] ?? $request->units_requested,
                'urgency' => strtoupper($data['urgency'] ?? $request->priority),
                'requested_by' => $user->id,
            ]
        );

        if ($before !== null) {
            $this->log->log(LogModule::BLOOD_BANK, 'BLOOD_RECIPIENT_UPDATED', [
                'description' => "Recipient details updated for {$request->request_number}.",
                'causer' => $user,
                'patient_id' => $request->patient_id,
            ], $request->recipient);
        }
    }

    public function approve(BloodRequest $request, User $user): BloodRequest
    {
        $request->update([
            'status' => BloodRequest::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $this->log->log(LogModule::BLOOD_BANK, 'BLOOD_REQUEST_APPROVED', [
            'description' => "Blood request {$request->request_number} approved.",
            'causer' => $user,
            'patient_id' => $request->patient_id,
        ], $request);

        return $request->refresh();
    }

    protected function notifyRequest(BloodRequest $request): void
    {
        $urgent = in_array($request->priority, [
            BloodRequest::PRIORITY_URGENT, BloodRequest::PRIORITY_EMERGENCY, BloodRequest::PRIORITY_MASSIVE,
        ], true);

        $this->notifier->notifyPermission('blood_bank.requests.approve', [
            'module' => NotificationModule::BLOOD_BANK,
            'priority' => $urgent ? NotificationPriority::URGENT : NotificationPriority::NORMAL,
            'title' => $urgent ? 'Urgent blood request' : 'New blood request',
            'message' => "{$request->request_number}: {$request->blood_group} {$request->component_type} x{$request->units_requested} ({$request->priority}).",
            'url' => url('/admin/blood-bank/requests'),
            'source_type' => 'blood_request',
            'source_id' => $request->id,
            'patient_id' => $request->patient_id,
            'visit_id' => $request->visit_id,
        ]);
    }

    protected function splitGroup(?string $group): array
    {
        if (! $group) {
            return [null, '+'];
        }
        $group = strtoupper(trim($group));
        $rh = str_ends_with($group, '-') ? '-' : '+';
        $abo = str_replace(['+', '-', ' '], '', $group);

        return [$abo, $rh];
    }
}
