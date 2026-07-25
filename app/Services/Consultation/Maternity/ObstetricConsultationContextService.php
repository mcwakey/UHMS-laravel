<?php

namespace App\Services\Consultation\Maternity;

use App\Data\Consultation\Maternity\ConsultationMaternityContext;
use App\Data\Consultation\Maternity\ObstetricWorkspaceViewModel;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\User;
use App\Models\VisitConsultationRoute;

/**
 * Phase 14R.3 — prepares the complete, bounded Obstetrics workspace view model.
 *
 * Orchestration only: it consumes the bridge resolver and existing maternity
 * records, and never recreates maternity business logic.
 *
 * Performance contract:
 *   - the maternity context is resolved AT MOST ONCE per request, memoised by
 *     consultation id;
 *   - all projection data is assembled here so Blade components never query;
 *   - when the workspace flag is off, the resolver is not called at all, so
 *     there is no hot-path cost for deployments that have not opted in.
 */
class ObstetricConsultationContextService
{
    public const PROFILE_CODE = 'obstetrics';

    /** @var array<int, ConsultationMaternityContext> request-scoped memo */
    private array $contextMemo = [];

    public function __construct(
        private readonly ConsultationMaternityContextResolver $resolver,
        private readonly ConsultationMaternitySpecialtyWriteGuard $writeGuard,
    ) {}

    /**
     * Resolve the maternity context once per consultation per request.
     * Public so the write guard / actions can reuse the same resolution.
     */
    public function context(VisitConsultationRoute $consultation): ConsultationMaternityContext
    {
        return $this->contextMemo[$consultation->id]
            ??= $this->resolver->resolve($consultation);
    }

    public function build(
        VisitConsultationRoute $consultation,
        ?ConsultationSpecialtyProfile $profile,
        ?User $user = null,
        ?string $returnUrl = null,
    ): ObstetricWorkspaceViewModel {
        $workspaceEnabled = $this->writeGuard->workspaceEnabled();
        $isObstetrics = $profile?->code === self::PROFILE_CODE;

        // Feature off, or a different specialty → do no work at all.
        if (! $workspaceEnabled || ! $isObstetrics) {
            return ObstetricWorkspaceViewModel::disabled($workspaceEnabled, $isObstetrics);
        }

        $context = $this->context($consultation);
        $guardApplies = $this->writeGuard->applies($consultation, $profile, $context);

        return new ObstetricWorkspaceViewModel(
            workspaceEnabled: true,
            writeGuardEnabled: $guardApplies,
            isObstetrics: true,
            status: $context->status,
            resolutionSource: $context->resolutionSource,
            isExplicit: $context->isExplicit(),
            pregnancy: $this->pregnancySection($context),
            anc: $this->ancSection($context),
            labor: $this->laborSection($context),
            delivery: $this->deliverySection($context),
            newborn: $this->newbornSection($context),
            postnatal: $this->postnatalSection($context),
            admission: $this->admissionSection($context),
            availableActions: $this->availableActions($context, $user),
            writePolicy: $guardApplies ? $this->writeGuard->matrix() : [],
            warnings: $context->warnings,
            candidateProfiles: $context->candidateProfiles,
            returnUrl: $returnUrl,
        );
    }

    /* ── Projections ──────────────────────────────────────────────────── */

    private function pregnancySection(ConsultationMaternityContext $context): array
    {
        $profile = $context->pregnancyProfile;

        if (! $profile) {
            return [];
        }

        return [
            'id' => $profile->id,
            'status' => $profile->profile_status,
            'gravida' => $profile->gravida,
            'para' => $profile->para,
            'abortions' => $profile->abortions,
            'living_children' => $profile->living_children,
            'previous_caesarean' => $profile->previous_caesarean,
            'lmp' => $profile->last_menstrual_period,
            'edd' => $profile->estimated_due_date,
            'gestational_age_weeks' => $profile->gestational_age_weeks,
            'gestational_age_days' => $profile->gestational_age_days,
            'gestational_age_source' => $context->antenatalVisit ? 'anc_visit' : 'pregnancy_profile',
            'dating_method' => $profile->dating_method,
            'risk_level' => $profile->profile_status,
            'known_risks' => $profile->known_risks ?? [],
        ];
    }

    private function ancSection(ConsultationMaternityContext $context): array
    {
        $anc = $context->antenatalVisit;

        if (! $anc) {
            return [];
        }

        return [
            'id' => $anc->id,
            'visit_date' => $anc->visit_date,
            'visit_number' => $anc->visit_number,
            'gestational_age_weeks' => $anc->gestational_age_weeks,
            'gestational_age_days' => $anc->gestational_age_days,
            'blood_pressure' => $anc->blood_pressure_systolic && $anc->blood_pressure_diastolic
                ? $anc->blood_pressure_systolic.'/'.$anc->blood_pressure_diastolic
                : null,
            'weight_kg' => $anc->weight_kg,
            'temperature' => $anc->temperature,
            'pulse' => $anc->pulse,
            'urine_protein' => $anc->urine_protein,
            'urine_glucose' => $anc->urine_glucose,
            'fundal_height_cm' => $anc->fundal_height_cm,
            'fetal_heart_rate' => $anc->fetal_heart_rate,
            'fetal_movement' => $anc->fetal_movement,
            'presentation' => $anc->presentation,
            'haemoglobin' => $anc->haemoglobin,
            'danger_signs' => $anc->danger_signs ?? [],
            'risk_flags' => $anc->risk_flags ?? [],
            'next_visit_date' => $anc->next_visit_date,
        ];
    }

    private function laborSection(ConsultationMaternityContext $context): array
    {
        $labor = $context->laborEpisode;

        if (! $labor) {
            return [];
        }

        $latest = $labor->relationLoaded('latestObservation')
            ? $labor->latestObservation
            : $labor->latestObservation()->first();

        return [
            'id' => $labor->id,
            'stage' => $labor->labor_stage,
            'status' => $labor->status,
            'started_at' => $labor->started_at,
            'theatre_escalation_required' => (bool) $labor->theatre_escalation_required,
            'emergency_escalation_required' => (bool) $labor->emergency_escalation_required,
            'latest_observation' => $latest ? [
                'observed_at' => $latest->observed_at,
                'cervical_dilation_cm' => $latest->cervical_dilation_cm,
                'fetal_heart_rate' => $latest->fetal_heart_rate,
                'maternal_pulse' => $latest->maternal_pulse,
                'blood_pressure' => $latest->blood_pressure_systolic && $latest->blood_pressure_diastolic
                    ? $latest->blood_pressure_systolic.'/'.$latest->blood_pressure_diastolic
                    : null,
                'danger_signs' => $latest->danger_signs ?? [],
            ] : null,
        ];
    }

    private function deliverySection(ConsultationMaternityContext $context): array
    {
        $delivery = $context->deliveryRecord;

        if (! $delivery) {
            return [];
        }

        return [
            'id' => $delivery->id,
            'delivery_at' => $delivery->delivery_at,
            'delivery_mode' => $delivery->delivery_mode,
            'delivery_outcome' => $delivery->delivery_outcome,
            'maternal_condition' => $delivery->maternal_condition,
            'newborn_count' => $delivery->newborn_count,
            'newborn_records_pending' => (bool) $delivery->newborn_records_pending,
            'status' => $delivery->status,
        ];
    }

    private function newbornSection(ConsultationMaternityContext $context): array
    {
        $records = $context->newbornRecords ?? collect();

        if ($records->isEmpty()) {
            return [];
        }

        return [
            'count' => $records->count(),
            'pending' => (bool) ($context->deliveryRecord?->newborn_records_pending),
            'records' => $records->map(fn ($n) => [
                'id' => $n->id,
                'birth_order' => $n->birth_order,
                'sex' => $n->sex,
                'birth_weight_kg' => $n->birth_weight_kg,
                'apgar_1_min' => $n->apgar_1_min,
                'apgar_5_min' => $n->apgar_5_min,
                'outcome' => $n->outcome ?? null,
            ])->all(),
        ];
    }

    private function postnatalSection(ConsultationMaternityContext $context): array
    {
        $postnatal = $context->postnatalCase;

        if (! $postnatal) {
            return [];
        }

        return [
            'id' => $postnatal->id,
            'status' => $postnatal->status,
            // Readiness is recorded as timestamps, not booleans.
            'mother_ready_at' => $postnatal->mother_ready_at,
            'newborn_ready_at' => $postnatal->newborn_ready_at,
            'ready_for_discharge_at' => $postnatal->ready_for_discharge_at,
            'mother_ready' => $postnatal->mother_ready_at !== null,
            'newborn_ready' => $postnatal->newborn_ready_at !== null,
            'ready_for_discharge' => $postnatal->ready_for_discharge_at !== null,
        ];
    }

    private function admissionSection(ConsultationMaternityContext $context): array
    {
        $admission = $context->admission;

        if (! $admission) {
            return [];
        }

        $bed = $admission->relationLoaded('bed') ? $admission->bed : null;

        return [
            'id' => $admission->id,
            'admission_number' => $admission->admission_number,
            'status' => $admission->status,
            'ward' => $bed?->ward?->name,
            'bed' => $bed?->bed_number,
        ];
    }

    /* ── Actions ──────────────────────────────────────────────────────── */

    /**
     * Which maternity actions the workspace may offer. Each requires BOTH the
     * bridge permission and the underlying maternity permission — the bridge
     * never escalates access.
     *
     * @return array<string, bool>
     */
    private function availableActions(ConsultationMaternityContext $context, ?User $user): array
    {
        if (! $user) {
            return [];
        }

        // An invalid explicit link disables every mutation until corrected.
        if ($context->isInvalid()) {
            return [
                'view' => $user->can('consultation.maternity_context.view'),
                'link' => false, 'unlink' => false, 'relink' => false,
                'create_profile' => false, 'record_anc' => false, 'start_labor' => false,
            ];
        }

        $explicitProfile = $context->isResolved()
            && $context->isExplicit()
            && $context->pregnancyProfile !== null;

        return [
            'view' => $user->can('consultation.maternity_context.view'),
            'link' => $user->can('consultation.maternity_context.link'),
            'unlink' => $explicitProfile && $user->can('consultation.maternity_context.unlink'),
            'relink' => $explicitProfile && $user->can('consultation.maternity_context.link'),
            'create_profile' => $user->can('consultation.maternity_context.create_profile')
                && $user->can('maternity.pregnancy.create'),
            // ANC and labor require a CONFIRMED explicit profile link.
            'record_anc' => $explicitProfile
                && $user->can('consultation.maternity_context.record_anc')
                && $user->can('maternity.anc.record'),
            'start_labor' => $explicitProfile
                && $user->can('consultation.maternity_context.start_labor')
                && $user->can('maternity.labor.start'),
        ];
    }
}
