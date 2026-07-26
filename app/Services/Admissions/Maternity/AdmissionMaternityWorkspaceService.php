<?php

namespace App\Services\Admissions\Maternity;

use App\Data\Maternity\OperationalMaternityContext;
use App\Data\Maternity\OperationalMaternityViewModel;
use App\Models\Admission;
use App\Models\User;
use App\Services\Maternity\Context\MaternityContextCardBuilder;
use App\Services\Maternity\Context\MaternityIntegrationFlags;
use App\Support\Maternity\MaternityReturnContext;

/**
 * Phase 14R.5 — prepares the Admission workspace maternity card.
 *
 * Built once per request in the Admission composer. Flag off → returns
 * immediately with ZERO queries. A direct non-maternity admission takes the
 * resolver's fast no-context path.
 *
 * Admission continues to own bed, ward, nursing notes and tasks, MAR, transfers
 * and discharge. This card shows maternity records read-only and links out to
 * the Maternity workspace for every clinical write — it renders no ANC, labor,
 * delivery, newborn or postnatal FORM.
 */
class AdmissionMaternityWorkspaceService
{
    public function __construct(
        private readonly AdmissionMaternityContextResolver $resolver,
        private readonly MaternityContextCardBuilder $cards,
        private readonly MaternityIntegrationFlags $flags,
    ) {}

    public function build(Admission $admission, ?User $user = null): OperationalMaternityViewModel
    {
        if (! $this->flags->admissionContextEnabled()) {
            return OperationalMaternityViewModel::disabled();
        }

        $context = $this->resolver->resolve($admission);

        return new OperationalMaternityViewModel(
            contextEnabled: true,
            // Admission context management is link/unlink only — Admission
            // never starts ANC, labor, delivery or postnatal work.
            handoffsEnabled: false,
            status: $context->status,
            resolutionSource: $context->resolutionSource,
            pregnancyProfileId: $context->pregnancyProfile?->id,
            pregnancyProfileUrl: $this->route('admin.maternity.pregnancies.show', $context->pregnancyProfile),
            laborEpisodeId: $context->laborEpisode?->id,
            laborEpisodeUrl: $this->route('admin.maternity.labor.show', $context->laborEpisode),
            postnatalCaseId: $context->postnatalCase?->id,
            cards: $this->cards->forContext($context),
            actions: $this->actions($user, $context),
            warnings: $context->warnings,
            returnContext: $this->returnContext($admission)?->toArray(),
            requestState: $this->originState($admission, $context),
            candidateProfiles: $context->isAmbiguous()
                ? $context->candidateProfiles?->map(fn ($profile) => [
                    'id' => $profile->id,
                    'label' => __('maternity_handoffs.cards.record_ref', [
                        'type' => __('maternity_handoffs.cards.pregnancy'), 'id' => $profile->id,
                    ]),
                ])->values()->all()
                : null,
        );
    }

    public function returnContext(Admission $admission): ?MaternityReturnContext
    {
        return MaternityReturnContext::make(
            MaternityReturnContext::MODULE_ADMISSION,
            'admin.admissions.show',
            ['admission' => $admission->id],
            'maternity-context',
        );
    }

    /**
     * Where this admission — and its maternity context — came from.
     *
     * A legacy `maternity` request whose `source_id` is ambiguous (it may be an
     * ANC visit id OR a labor episode id, depending on which service raised it)
     * is reported as a WARNING rather than guessed at.
     *
     * @return array<string, mixed>
     */
    private function originState(Admission $admission, OperationalMaternityContext $context): array
    {
        $request = $admission->admissionRequest;

        $ambiguousLegacySource = $request
            && $request->source_type?->value === 'maternity'
            && $request->source_id !== null
            && $context->resolutionSource !== OperationalMaternityContext::SOURCE_REQUEST;

        return [
            'id' => $request?->id,
            'status' => $request?->status?->label(),
            'source' => $request?->source_type?->label(),
            'url' => $request ? $this->route('admin.admissions.requests.show', $request) : null,
            'context_from_request' => $context->resolutionSource === OperationalMaternityContext::SOURCE_REQUEST,
            'ambiguous_legacy_source' => $ambiguousLegacySource,
            'ambiguous_legacy_notice' => $ambiguousLegacySource
                ? __('maternity_handoffs.admission.ambiguous_legacy_source')
                : null,
        ];
    }

    /**
     * Admission may view, link, relink and unlink context — and nothing else.
     *
     * It may never auto-create a pregnancy profile, start ANC or labor, create
     * delivery/newborn/postnatal records, or choose between multiple profiles.
     *
     * @return array<string, bool>
     */
    private function actions(?User $user, OperationalMaternityContext $context): array
    {
        if (! $user) {
            return [];
        }

        $isExplicit = $context->isResolved() && $context->isExplicit();

        return [
            'view' => $user->can('admission.maternity_context.view'),
            'link' => $user->can('admission.maternity_context.link')
                && $user->can('maternity.pregnancy.view'),
            'relink' => $isExplicit && $user->can('admission.maternity_context.link'),
            'unlink' => $isExplicit && $user->can('admission.maternity_context.unlink'),
        ];
    }

    private function route(string $name, mixed $parameter): ?string
    {
        if ($parameter === null) {
            return null;
        }

        try {
            return route($name, $parameter);
        } catch (\Throwable) {
            return null;
        }
    }
}
