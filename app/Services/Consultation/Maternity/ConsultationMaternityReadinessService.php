<?php

namespace App\Services\Consultation\Maternity;

use App\Data\Consultation\Maternity\ConsultationMaternityReadinessResult as Result;
use App\Enums\ConsultationMaternityContextType;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\User;
use App\Models\VisitConsultationRoute;

/**
 * Phase 14R.6 — ADVISORY, stage-aware maternity readiness for Obstetrics.
 *
 * Advisory means advisory: this service returns warnings, never a blocker.
 * Consultation completion is unchanged, `ConsultationCompletionReadinessService`
 * is untouched, and Gynaecology readiness is deliberately left exactly as it is.
 *
 * The review mode is derived ONLY from explicit, closed signals — an explicit
 * link of a given context type. It is never inferred from the specialty, a
 * diagnosis, a pregnancy test, a complaint or a gestational age.
 */
class ConsultationMaternityReadinessService
{
    public const OBSTETRICS = 'obstetrics';

    /** @var array<int, Result> request-scoped memo */
    private array $memo = [];

    public function __construct(
        private readonly ConsultationMaternityLinkService $links,
        private readonly ConsultationMaternityContextResolver $resolver,
    ) {}

    public function enabled(): bool
    {
        return (bool) config('consultation.maternity_context.readiness_enabled', false);
    }

    /**
     * Evaluate advisory readiness.
     *
     * Returns an unavailable result — with ZERO queries — when the flag is off,
     * the profile is not Obstetrics, or the clinician lacks the readiness
     * permission.
     */
    public function evaluate(
        ?VisitConsultationRoute $consultation,
        ?ConsultationSpecialtyProfile $profile,
        ?User $user = null,
    ): Result {
        if (! $this->enabled()
            || ! $consultation
            || $profile?->code !== self::OBSTETRICS
            || ($user && ! $user->can('consultation.maternity_context.readiness.view'))) {
            return Result::unavailable();
        }

        return $this->memo[$consultation->id] ??= $this->build($consultation);
    }

    /* ── Internals ─────────────────────────────────────────────────────── */

    private function build(VisitConsultationRoute $consultation): Result
    {
        // Explicit links only — the closed signal set.
        $activeLinks = $this->links->getActiveLinks($consultation);

        if ($activeLinks->isEmpty()) {
            // No explicit context at all. If something is merely inferable,
            // say so advisorily; otherwise this is an ordinary Obstetrics
            // consultation and normal readiness governs it entirely.
            return $this->generalOrUnconfirmed($consultation);
        }

        $byType = $activeLinks->keyBy(fn ($link) => $link->context_type?->value);
        $context = $this->resolver->resolveExplicitOnly($consultation);

        if ($context->isInvalid()) {
            return Result::warning(Result::MODE_GENERAL, ['context_invalid']);
        }

        // Most specific explicit stage wins: postnatal → labor → ANC.
        if ($byType->has(ConsultationMaternityContextType::POSTNATAL->value)) {
            return $this->postnatalReview($byType, $context);
        }

        if ($byType->has(ConsultationMaternityContextType::LABOR->value)) {
            return $this->laborReview($byType, $context);
        }

        if ($byType->has(ConsultationMaternityContextType::ANC_VISIT->value)) {
            return $this->antenatalReview($context);
        }

        // A pregnancy profile is linked but no stage record is: an antenatal
        // review with nothing recorded yet.
        if ($context->isResolved() && $context->pregnancyProfile) {
            return $context->antenatalVisit
                ? Result::ready(Result::MODE_ANTENATAL_REVIEW)
                : Result::warning(Result::MODE_ANTENATAL_REVIEW, ['anc_visit_not_recorded']);
        }

        return Result::warning(Result::MODE_GENERAL, ['context_invalid']);
    }

    private function generalOrUnconfirmed(VisitConsultationRoute $consultation): Result
    {
        $inferred = $this->resolver->resolve($consultation);

        if ($inferred->isAmbiguous()) {
            return Result::warning(Result::MODE_GENERAL, ['context_ambiguous']);
        }

        if ($inferred->isResolved() && $inferred->pregnancyProfile) {
            return Result::warning(Result::MODE_GENERAL, ['context_confirmation_required']);
        }

        // Genuinely general: normal consultation readiness applies unchanged
        // and no maternity warning is raised.
        return Result::ready(Result::MODE_GENERAL);
    }

    private function antenatalReview($context): Result
    {
        $warnings = [];

        if (! $context->pregnancyProfile) {
            $warnings[] = 'pregnancy_profile_missing';
        }

        if (! $context->antenatalVisit) {
            $warnings[] = 'anc_visit_not_recorded';
        }

        return $warnings === []
            ? Result::ready(Result::MODE_ANTENATAL_REVIEW)
            : Result::warning(Result::MODE_ANTENATAL_REVIEW, $warnings);
    }

    private function laborReview($byType, $context): Result
    {
        $warnings = [];
        $episode = $byType->get(ConsultationMaternityContextType::LABOR->value)?->laborEpisode;

        if (! $episode) {
            $warnings[] = 'labor_episode_missing';
        } elseif ($context->pregnancyProfile
            && (int) $episode->pregnancy_profile_id !== (int) $context->pregnancyProfile->id) {
            // The linked episode no longer belongs to the linked pregnancy.
            $warnings[] = 'labor_episode_profile_mismatch';
        }

        return $warnings === []
            ? Result::ready(Result::MODE_LABOR_REVIEW)
            : Result::warning(Result::MODE_LABOR_REVIEW, $warnings);
    }

    private function postnatalReview($byType, $context): Result
    {
        $warnings = [];
        $case = $byType->get(ConsultationMaternityContextType::POSTNATAL->value)?->postnatalCase;

        if (! $case) {
            $warnings[] = 'postnatal_case_missing';
        } else {
            if ($case->referral_required) {
                $warnings[] = 'postnatal_referral_required';
            }

            if (! $case->mother_ready_at || ! $case->newborn_ready_at) {
                // Advisory only: this never blocks consultation completion, and
                // no admission discharge rule is copied into Consultation.
                $warnings[] = 'postnatal_readiness_unavailable';
            }
        }

        return $warnings === []
            ? Result::ready(Result::MODE_POSTNATAL_REVIEW)
            : Result::warning(Result::MODE_POSTNATAL_REVIEW, $warnings);
    }
}
