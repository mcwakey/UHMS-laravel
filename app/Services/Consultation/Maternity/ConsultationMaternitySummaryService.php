<?php

namespace App\Services\Consultation\Maternity;

use App\Data\Consultation\Maternity\ConsultationMaternitySummaryProjection as Projection;
use App\Data\Consultation\Maternity\ConsultationMaternityContext;
use App\Models\VisitConsultationRoute;
use Illuminate\Support\Carbon;

/**
 * Phase 14R.6 — builds the curated maternity projection for the consultation
 * summary and the completion snapshot.
 *
 * Consumes the EXISTING context resolver and maternity records; it recomputes
 * no gestational age, no risk score and no readiness — those stay owned by the
 * maternity domain, and this service only reads what they already decided.
 *
 * Only an EXPLICIT link produces clinical values. A suggested, ambiguous or
 * invalid context yields an advisory-only projection, so nothing the clinician
 * has not confirmed can ever reach a summary or a medico-legal snapshot.
 */
class ConsultationMaternitySummaryService
{
    /** @var array<int, Projection> request-scoped memo */
    private array $memo = [];

    public function __construct(
        private readonly ConsultationMaternityContextResolver $resolver,
    ) {}

    public function enabled(): bool
    {
        return (bool) config('consultation.maternity_context.summary_projection_enabled', false);
    }

    /**
     * Build (once per request per consultation) the projection.
     *
     * `$force` is used by snapshot capture, which is governed by its own flag
     * and must work even when the summary flag is off.
     */
    public function project(VisitConsultationRoute $consultation, bool $force = false): Projection
    {
        if (! $force && ! $this->enabled()) {
            return Projection::none($consultation->id);
        }

        return $this->memo[$consultation->id] ??= $this->build($consultation);
    }

    /* ── Internals ─────────────────────────────────────────────────────── */

    private function build(VisitConsultationRoute $consultation): Projection
    {
        // Explicit-only: the resolver's inference fallbacks are deliberately
        // not used here. A summary must never assert an unconfirmed pregnancy.
        $context = $this->resolver->resolveExplicitOnly($consultation);

        if ($context->isInvalid()) {
            return Projection::unconfirmed(
                Projection::STATUS_INVALID,
                $context->resolutionSource,
                $consultation->id,
                ['consultation_maternity_summary.warnings.context_invalid'],
            );
        }

        if ($context->isAmbiguous()) {
            return Projection::unconfirmed(
                Projection::STATUS_AMBIGUOUS,
                $context->resolutionSource,
                $consultation->id,
                ['consultation_maternity_summary.warnings.context_ambiguous'],
            );
        }

        if (! $context->isResolved() || ! $context->pregnancyProfile) {
            // Nothing explicit is linked. If the *inferring* resolver would
            // have found something, say so advisorily — but carry no values.
            return $this->unconfirmedOrNone($consultation);
        }

        return $this->explicitProjection($consultation, $context);
    }

    private function unconfirmedOrNone(VisitConsultationRoute $consultation): Projection
    {
        $inferred = $this->resolver->resolve($consultation);

        if ($inferred->isAmbiguous()) {
            return Projection::unconfirmed(
                Projection::STATUS_AMBIGUOUS,
                $inferred->resolutionSource,
                $consultation->id,
                ['consultation_maternity_summary.warnings.context_ambiguous'],
            );
        }

        if ($inferred->isResolved() && $inferred->pregnancyProfile) {
            return Projection::unconfirmed(
                Projection::STATUS_SUGGESTED,
                $inferred->resolutionSource,
                $consultation->id,
                ['consultation_maternity_summary.warnings.confirm_context_first'],
            );
        }

        return Projection::none($consultation->id, $inferred->resolutionSource);
    }

    private function explicitProjection(
        VisitConsultationRoute $consultation,
        ConsultationMaternityContext $context,
    ): Projection {
        $profile = $context->pregnancyProfile;
        $anc = $context->antenatalVisit;
        $labor = $context->laborEpisode;
        $delivery = $context->deliveryRecord;
        $postnatal = $context->postnatalCase;
        $admission = $context->admission;

        // Deterministic newborn ordering: birth order, then id.
        //
        // An EXPLICIT link resolves only the records the clinician linked, so
        // the resolver's newborn collection is empty here. One bounded, ordered
        // query loads them for the pregnancy — multiples cost no extra query
        // and there is no N+1.
        $newborns = ($context->newbornRecords ?? collect())
            ->sortBy([['birth_order', 'asc'], ['id', 'asc']])
            ->values();

        if ($newborns->isEmpty()) {
            $newborns = \App\Models\NewbornRecord::query()
                ->where('pregnancy_profile_id', $profile->id)
                ->orderBy('birth_order')
                ->orderBy('id')
                ->get();
        }

        if ($newborns->isEmpty() && $context->primaryNewbornRecord) {
            $newborns = collect([$context->primaryNewbornRecord]);
        }

        return new Projection(
            contextStatus: Projection::STATUS_EXPLICIT,
            resolutionSource: $context->resolutionSource,
            consultationRouteId: $consultation->id,
            pregnancyProfileId: $profile->id,
            pregnancy: array_filter([
                'id' => (int) $profile->id,
                'status' => $this->code($profile->profile_status),
                'gravida' => $this->int($profile->gravida),
                'para' => $this->int($profile->para),
                'abortions' => $this->int($profile->abortions),
                'living_children' => $this->int($profile->living_children),
                'previous_caesarean' => $this->bool($profile->previous_caesarean),
                'lmp' => $this->date($profile->last_menstrual_period),
                'edd' => $this->date($profile->estimated_due_date),
                'gestational_age_weeks' => $this->int($profile->gestational_age_weeks),
                'gestational_age_days' => $this->int($profile->gestational_age_days),
                // The dating method IS the gestational-age source; it is read
                // from the profile, never re-derived here.
                'dating_method' => $this->code($profile->dating_method),
                'gestational_age_source' => $this->code($profile->dating_method),
                'risk_codes' => $this->riskCodes($profile),
                'known_risk_count' => count($profile->known_risks ?? []),
            ], fn ($v) => $v !== null),
            anc: $anc ? array_filter([
                'id' => (int) $anc->id,
                'visit_date' => $this->date($anc->visit_date),
                'visit_number' => $this->int($anc->visit_number),
                'gestational_age_weeks' => $this->int($anc->gestational_age_weeks),
                'gestational_age_days' => $this->int($anc->gestational_age_days),
                'blood_pressure_systolic' => $this->int($anc->blood_pressure_systolic),
                'blood_pressure_diastolic' => $this->int($anc->blood_pressure_diastolic),
                'weight_kg' => $this->float($anc->weight_kg),
                'fundal_height_cm' => $this->float($anc->fundal_height_cm),
                'fetal_heart_rate' => $this->int($anc->fetal_heart_rate),
                'presentation' => $this->code($anc->presentation),
                'danger_sign_codes' => $this->codeList($anc->danger_signs),
                'risk_flag_codes' => $this->codeList($anc->risk_flags),
                'next_visit_date' => $this->date($anc->next_visit_date),
                'status' => $this->code($anc->status),
            ], fn ($v) => $v !== null) : [],
            labor: $labor ? array_filter([
                'id' => (int) $labor->id,
                'stage' => $this->code($labor->labor_stage),
                'status' => $this->code($labor->status),
                'risk_level' => $this->code($labor->risk_level),
                'started_at' => $this->dateTime($labor->started_at),
                'latest_observation_at' => $this->dateTime($labor->latestObservation?->observed_at),
                'cervical_dilation_cm' => $this->float($labor->latestObservation?->cervical_dilation_cm),
                'fetal_heart_rate' => $this->int($labor->latestObservation?->fetal_heart_rate),
                'blood_pressure_systolic' => $this->int($labor->latestObservation?->blood_pressure_systolic),
                'blood_pressure_diastolic' => $this->int($labor->latestObservation?->blood_pressure_diastolic),
                'theatre_escalation_required' => $this->bool($labor->theatre_escalation_required),
                'emergency_escalation_required' => $this->bool($labor->emergency_escalation_required),
            ], fn ($v) => $v !== null) : [],
            delivery: $delivery ? array_filter([
                'id' => (int) $delivery->id,
                'delivery_at' => $this->dateTime($delivery->delivery_at),
                'delivery_mode' => $this->code($delivery->delivery_mode),
                'outcome' => $this->code($delivery->delivery_outcome),
                'maternal_condition' => $this->code($delivery->maternal_condition),
                'estimated_blood_loss_ml' => $this->int($delivery->estimated_blood_loss_ml),
                'newborn_count' => $this->int($delivery->newborn_count),
                'status' => $this->code($delivery->status),
            ], fn ($v) => $v !== null) : [],
            newborns: $newborns->map(fn ($newborn) => array_filter([
                'id' => (int) $newborn->id,
                'birth_order' => $this->int($newborn->birth_order),
                'sex' => $this->code($newborn->sex),
                'birth_weight_kg' => $this->float($newborn->birth_weight_kg),
                'apgar_1_min' => $this->int($newborn->apgar_1_min),
                'apgar_5_min' => $this->int($newborn->apgar_5_min),
                'apgar_10_min' => $this->int($newborn->apgar_10_min),
                'resuscitation_required' => $this->bool($newborn->resuscitation_required),
                'outcome' => $this->code($newborn->outcome),
                'status' => $this->code($newborn->status),
            ], fn ($v) => $v !== null))->values()->all(),
            postnatal: $postnatal ? array_filter([
                'id' => (int) $postnatal->id,
                'status' => $this->code($postnatal->status),
                'mother_ready_at' => $this->dateTime($postnatal->mother_ready_at),
                'newborn_ready_at' => $this->dateTime($postnatal->newborn_ready_at),
                'ready_for_discharge_at' => $this->dateTime($postnatal->ready_for_discharge_at),
                'referral_required' => $this->bool($postnatal->referral_required),
                'follow_up_date' => $this->date($postnatal->follow_up_date),
                // Observation TIMES only — never observation values.
                'latest_mother_observation_at' => $this->dateTime($postnatal->latestMotherObservation?->observed_at),
                'latest_newborn_observation_at' => $this->dateTime($postnatal->latestNewbornObservation?->observed_at),
            ], fn ($v) => $v !== null) : [],
            // Ward/bed are ADMISSION-owned; recorded here as context, and the
            // renderer labels them as such.
            admission: $admission ? array_filter([
                'id' => (int) $admission->id,
                'status' => $this->code($admission->status),
                'ward_id' => $this->int($admission->bed?->ward_id),
                'bed_id' => $this->int($admission->bed_id),
                'owner' => 'admission',
            ], fn ($v) => $v !== null) : [],
            sourceRecordIds: array_filter([
                'pregnancy_profile_id' => (int) $profile->id,
                'maternity_case_id' => $this->int($context->maternityCase?->id),
                'antenatal_visit_id' => $this->int($anc?->id),
                'labor_episode_id' => $this->int($labor?->id),
                'delivery_record_id' => $this->int($delivery?->id),
                'newborn_record_ids' => $newborns->pluck('id')->map(fn ($id) => (int) $id)->all() ?: null,
                'postnatal_case_id' => $this->int($postnatal?->id),
                'admission_id' => $this->int($admission?->id),
            ], fn ($v) => $v !== null),
            warnings: $context->warnings === [] ? [] : ['consultation_maternity_summary.warnings.context_warnings'],
        );
    }

    /* ── Canonical scalar helpers ──────────────────────────────────────── */

    /** Enum → its backing code. Never a translated label. */
    private function code(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        return (string) $value;
    }

    /** @return list<string>|null */
    private function codeList(mixed $value): ?array
    {
        if (! is_array($value) || $value === []) {
            return null;
        }

        $codes = array_values(array_filter(array_map(
            fn ($item) => is_scalar($item) ? (string) $item : null,
            $value
        )));

        return $codes === [] ? null : $codes;
    }

    /** @return list<string>|null the profile's own boolean risk flags, as codes */
    private function riskCodes($profile): ?array
    {
        $codes = array_keys(array_filter([
            'previous_caesarean' => (bool) $profile->previous_caesarean,
            'previous_postpartum_haemorrhage' => (bool) $profile->previous_postpartum_haemorrhage,
            'hypertensive_disorder_risk' => (bool) $profile->hypertensive_disorder_risk,
            'diabetes_risk' => (bool) $profile->diabetes_risk,
            'multiple_pregnancy' => (bool) $profile->multiple_pregnancy,
        ]));

        return $codes === [] ? null : array_values($codes);
    }

    private function int(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function float(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }

    private function bool(mixed $value): ?bool
    {
        return $value === null ? null : (bool) $value;
    }

    private function date(mixed $value): ?string
    {
        return $this->carbon($value)?->toDateString();
    }

    private function dateTime(mixed $value): ?string
    {
        return $this->carbon($value)?->toIso8601String();
    }

    private function carbon(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return $value instanceof Carbon ? $value : Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
