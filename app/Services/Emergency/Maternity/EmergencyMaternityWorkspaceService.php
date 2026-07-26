<?php

namespace App\Services\Emergency\Maternity;

use App\Data\Maternity\OperationalMaternityViewModel;
use App\Models\EmergencyCase;
use App\Models\User;
use App\Services\Maternity\Context\MaternityContextCardBuilder;
use App\Services\Maternity\Context\MaternityIntegrationFlags;
use App\Support\Maternity\MaternityReturnContext;

/**
 * Phase 14R.5 — prepares the Emergency workspace maternity card.
 *
 * Called once from the real Emergency case composer. When the context flag is
 * off it returns immediately with ZERO queries: no resolver call, no link
 * lookup, no pregnancy-profile probe.
 *
 * Emergency retains ownership of triage, acuity, vitals, notes, bay, treatment,
 * tasks and disposition. None of those are duplicated into Maternity, and this
 * card never renders an Emergency clinical field.
 */
class EmergencyMaternityWorkspaceService
{
    public function __construct(
        private readonly EmergencyMaternityContextResolver $resolver,
        private readonly EmergencyMaternityHandoffService $handoffs,
        private readonly MaternityContextCardBuilder $cards,
        private readonly MaternityIntegrationFlags $flags,
    ) {}

    public function build(EmergencyCase $case, ?User $user = null): OperationalMaternityViewModel
    {
        if (! $this->flags->emergencyContextEnabled()) {
            return OperationalMaternityViewModel::disabled();
        }

        $context = $this->resolver->resolve($case);
        $handoffsEnabled = $this->flags->emergencyHandoffsEnabled();

        // The open admission request is only meaningful when handoffs are on;
        // the lookup is skipped entirely otherwise.
        $requestState = $handoffsEnabled ? $this->requestState($case) : null;

        return new OperationalMaternityViewModel(
            contextEnabled: true,
            handoffsEnabled: $handoffsEnabled,
            status: $context->status,
            resolutionSource: $context->resolutionSource,
            pregnancyProfileId: $context->pregnancyProfile?->id,
            pregnancyProfileUrl: $this->route('admin.maternity.pregnancies.show', $context->pregnancyProfile),
            laborEpisodeId: $context->laborEpisode?->id,
            laborEpisodeUrl: $this->route('admin.maternity.labor.show', $context->laborEpisode),
            postnatalCaseId: $context->postnatalCase?->id,
            cards: $this->cards->forContext($context),
            actions: $this->actions($user, $context->isExplicit() && $context->isResolved(), $handoffsEnabled),
            warnings: $context->warnings,
            returnContext: $this->returnContext($case)?->toArray(),
            requestState: $requestState,
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

    /** A safe return context pointing back at this emergency case. */
    public function returnContext(EmergencyCase $case): ?MaternityReturnContext
    {
        return MaternityReturnContext::make(
            MaternityReturnContext::MODULE_EMERGENCY,
            'admin.emergency.cases.show',
            ['emergencyCase' => $case->id],
            'maternity-context',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function requestState(EmergencyCase $case): array
    {
        $request = $this->handoffs->existingOpenRequest($case);

        return [
            'id' => $request?->id,
            'status' => $request?->status?->label(),
            'url' => $request ? $this->route('admin.admissions.requests.show', $request) : null,
        ];
    }

    /**
     * Permission-resolved actions.
     *
     * Every bridge permission is paired with the underlying domain permission —
     * the bridge never escalates access to Maternity or Admission.
     *
     * @return array<string, bool>
     */
    private function actions(?User $user, bool $hasExplicitLink, bool $handoffsEnabled): array
    {
        if (! $user) {
            return [];
        }

        $canView = $user->can('emergency.maternity_context.view');

        if (! $handoffsEnabled) {
            // Context-only mode: navigation is allowed, mutation is not.
            return ['view' => $canView];
        }

        return [
            'view' => $canView,
            'link' => $user->can('emergency.maternity_context.link')
                && $user->can('maternity.pregnancy.view'),
            'unlink' => $hasExplicitLink && $user->can('emergency.maternity_context.unlink'),
            'create_profile' => $user->can('emergency.maternity_context.create_profile')
                && $user->can('maternity.pregnancy.create'),
            'start_labor' => $hasExplicitLink
                && $user->can('emergency.maternity_context.start_labor')
                && $user->can('maternity.labor.start'),
            'create_admission_request' => $user->can('emergency.maternity_context.create_admission_request')
                && $user->can('admission.requests.create'),
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
