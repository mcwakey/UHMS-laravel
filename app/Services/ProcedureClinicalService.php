<?php

namespace App\Services;

use App\Enums\ProcedureStatus;
use App\Models\AnaesthesiaNote;
use App\Models\OperativeNote;
use App\Models\PostOpNote;
use App\Models\ProcedureChecklist;
use App\Models\ProcedureRequest;
use App\Models\ProcedureVital;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProcedureClinicalService
{
    public function __construct(
        protected ProcedureWorkflowService $workflow,
    ) {}

    /**
     * Record pre-op vitals + checklist (SCHEDULED → PRE_OP).
     */
    public function recordPreOp(ProcedureRequest $request, array $data, User $user): ProcedureRequest
    {
        if ($request->status !== ProcedureStatus::SCHEDULED) {
            throw new \RuntimeException("Cannot record pre-op: status must be SCHEDULED (currently '{$request->status->value}').");
        }

        return DB::transaction(function () use ($request, $data, $user) {
            $vitals = $data['vitals'] ?? [];
            if (! empty(array_filter($vitals, fn ($v) => $v !== null && $v !== ''))) {
                ProcedureVital::create([
                    'procedure_request_id' => $request->id,
                    'stage'                => 'pre_op',
                    'temperature'          => $vitals['temperature'] ?? null,
                    'blood_pressure'       => $vitals['blood_pressure'] ?? null,
                    'pulse'                => $vitals['pulse'] ?? null,
                    'respiratory_rate'     => $vitals['respiratory_rate'] ?? null,
                    'oxygen_saturation'    => $vitals['oxygen_saturation'] ?? null,
                    'weight'               => $vitals['weight'] ?? null,
                    'pain_score'           => $vitals['pain_score'] ?? null,
                    'notes'                => $vitals['notes'] ?? null,
                    'recorded_by'          => $user->id,
                    'recorded_at'          => now(),
                ]);
            }

            ProcedureChecklist::updateOrCreate(
                ['procedure_request_id' => $request->id],
                [
                    'consent_signed'          => (bool) ($data['consent_signed'] ?? false),
                    'fasting_confirmed'       => (bool) ($data['fasting_confirmed'] ?? false),
                    'allergies_checked'       => (bool) ($data['allergies_checked'] ?? false),
                    'blood_available'         => (bool) ($data['blood_available'] ?? false),
                    'site_marked'             => (bool) ($data['site_marked'] ?? false),
                    'equipment_ready'         => (bool) ($data['equipment_ready'] ?? false),
                    'anaesthesia_review_done' => (bool) ($data['anaesthesia_review_done'] ?? false),
                    'pre_op_diagnosis'        => $data['pre_op_diagnosis'] ?? null,
                    'notes'                   => $data['checklist_notes'] ?? null,
                    'completed_by'            => $user->id,
                    'completed_at'            => now(),
                ]
            );

            $this->workflow->transition($request, ProcedureStatus::PRE_OP, $user, 'Pre-op vitals and checklist recorded.');

            return $request->fresh(['vitals', 'checklist']);
        });
    }

    public function recordAnaesthesiaNote(ProcedureRequest $request, array $data, User $user): AnaesthesiaNote
    {
        if ($request->status !== ProcedureStatus::PRE_OP) {
            throw new \RuntimeException("Cannot record anaesthesia note: status must be PRE_OP (currently '{$request->status->value}').");
        }
        if (empty($data['anaesthesia_type'])) {
            throw new \InvalidArgumentException('Anaesthesia type is required.');
        }
        if (! in_array($data['anaesthesia_type'], AnaesthesiaNote::TYPES, true)) {
            throw new \InvalidArgumentException('Invalid anaesthesia type.');
        }

        return DB::transaction(function () use ($request, $data, $user) {
            $note = AnaesthesiaNote::updateOrCreate(
                ['procedure_request_id' => $request->id],
                [
                    'anaesthetist_id'   => $data['anaesthetist_id'] ?? $user->id,
                    'anaesthesia_type'  => $data['anaesthesia_type'],
                    'pre_assessment'    => $data['pre_assessment'] ?? null,
                    'drugs_used'        => $data['drugs_used'] ?? null,
                    'dosage_notes'      => $data['dosage_notes'] ?? null,
                    'airway_management' => $data['airway_management'] ?? null,
                    'monitoring_notes'  => $data['monitoring_notes'] ?? null,
                    'complications'     => $data['complications'] ?? null,
                    'start_time'        => $data['start_time'] ?? null,
                    'end_time'          => $data['end_time'] ?? null,
                    'notes'             => $data['notes'] ?? null,
                ]
            );

            $this->workflow->transition($request, ProcedureStatus::ANAESTHESIA, $user, 'Anaesthesia note recorded.');

            return $note->fresh();
        });
    }

    public function startSurgery(ProcedureRequest $request, User $user): ProcedureRequest
    {
        if ($request->status !== ProcedureStatus::ANAESTHESIA) {
            throw new \RuntimeException("Cannot start surgery: status must be ANAESTHESIA (currently '{$request->status->value}').");
        }
        $this->workflow->transition($request, ProcedureStatus::IN_SURGERY, $user, 'Surgery started.');
        return $request->fresh();
    }

    public function recordOperativeNote(ProcedureRequest $request, array $data, User $user): OperativeNote
    {
        if (! in_array($request->status, [ProcedureStatus::ANAESTHESIA, ProcedureStatus::IN_SURGERY], true)) {
            throw new \RuntimeException("Cannot record operative note: status must be ANAESTHESIA or IN_SURGERY (currently '{$request->status->value}').");
        }
        if (empty($data['procedure_performed'])) {
            throw new \InvalidArgumentException('Procedure performed is required.');
        }

        return DB::transaction(function () use ($request, $data, $user) {
            $note = OperativeNote::updateOrCreate(
                ['procedure_request_id' => $request->id],
                [
                    'surgeon_id'           => $data['surgeon_id'] ?? $user->id,
                    'assistant_surgeon_id' => $data['assistant_surgeon_id'] ?? null,
                    'procedure_performed'  => $data['procedure_performed'],
                    'pre_op_diagnosis'     => $data['pre_op_diagnosis'] ?? null,
                    'post_op_diagnosis'    => $data['post_op_diagnosis'] ?? null,
                    'findings'             => $data['findings'] ?? null,
                    'incision'             => $data['incision'] ?? null,
                    'technique'            => $data['technique'] ?? null,
                    'blood_loss'           => $data['blood_loss'] ?? null,
                    'complications'        => $data['complications'] ?? null,
                    'specimens'            => $data['specimens'] ?? null,
                    'implants'             => $data['implants'] ?? null,
                    'start_time'           => $data['start_time'] ?? null,
                    'end_time'             => $data['end_time'] ?? null,
                    'outcome'              => $data['outcome'] ?? null,
                    'notes'                => $data['notes'] ?? null,
                ]
            );

            // If we're still in ANAESTHESIA, jump to IN_SURGERY first then SURGERY_DONE.
            if ($request->fresh()->status === ProcedureStatus::ANAESTHESIA) {
                $this->workflow->transition($request, ProcedureStatus::IN_SURGERY, $user, 'Operative note started.');
            }

            // Only mark SURGERY_DONE if both procedure_performed and end_time are set or completion is explicit.
            if (! empty($data['completed'])) {
                $this->workflow->transition($request, ProcedureStatus::SURGERY_DONE, $user, 'Operative note completed.');
            }

            return $note->fresh();
        });
    }

    public function completeSurgery(ProcedureRequest $request, User $user): ProcedureRequest
    {
        if ($request->status !== ProcedureStatus::IN_SURGERY) {
            throw new \RuntimeException("Cannot complete surgery: status must be IN_SURGERY (currently '{$request->status->value}').");
        }
        $request->loadMissing('operativeNote');
        if (! $request->operativeNote) {
            throw new \RuntimeException('Cannot complete surgery: operative note must be recorded first.');
        }
        $this->workflow->transition($request, ProcedureStatus::SURGERY_DONE, $user, 'Surgery completed.');
        return $request->fresh();
    }

    public function recordPostOp(ProcedureRequest $request, array $data, User $user): PostOpNote
    {
        if ($request->status !== ProcedureStatus::SURGERY_DONE) {
            throw new \RuntimeException("Cannot record post-op note: status must be SURGERY_DONE (currently '{$request->status->value}').");
        }

        return DB::transaction(function () use ($request, $data, $user) {
            $note = PostOpNote::updateOrCreate(
                ['procedure_request_id' => $request->id],
                [
                    'recorded_by'          => $user->id,
                    'recovery_status'      => $data['recovery_status'] ?? null,
                    'pain_score'           => $data['pain_score'] ?? null,
                    'consciousness_level'  => $data['consciousness_level'] ?? null,
                    'post_op_instructions' => $data['post_op_instructions'] ?? null,
                    'medications'          => $data['medications'] ?? null,
                    'complications'        => $data['complications'] ?? null,
                    'transfer_destination' => $data['transfer_destination'] ?? null,
                    'notes'                => $data['notes'] ?? null,
                ]
            );

            // Optionally also record a post_op vitals snapshot.
            $vitals = $data['vitals'] ?? [];
            if (! empty(array_filter($vitals, fn ($v) => $v !== null && $v !== ''))) {
                ProcedureVital::create([
                    'procedure_request_id' => $request->id,
                    'stage'                => 'post_op',
                    'temperature'          => $vitals['temperature'] ?? null,
                    'blood_pressure'       => $vitals['blood_pressure'] ?? null,
                    'pulse'                => $vitals['pulse'] ?? null,
                    'respiratory_rate'     => $vitals['respiratory_rate'] ?? null,
                    'oxygen_saturation'    => $vitals['oxygen_saturation'] ?? null,
                    'weight'               => $vitals['weight'] ?? null,
                    'pain_score'           => $data['pain_score'] ?? null,
                    'notes'                => $vitals['notes'] ?? null,
                    'recorded_by'          => $user->id,
                    'recorded_at'          => now(),
                ]);
            }

            $this->workflow->transition($request, ProcedureStatus::POST_OP, $user, 'Post-op note recorded.');

            return $note->fresh();
        });
    }
}
