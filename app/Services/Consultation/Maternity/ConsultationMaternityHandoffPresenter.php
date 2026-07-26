<?php

namespace App\Services\Consultation\Maternity;

use App\Models\ConsultationSpecialtyProfile;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Services\Maternity\Context\MaternityIntegrationFlags;

/**
 * Phase 14R.5 — prepares the Consultation maternity handoff panel.
 *
 * Built once in HandlesConsultationWorkspace. When
 * MATERNITY_CONSULTATION_HANDOFFS_ENABLED is off it returns `['enabled' => false]`
 * immediately with ZERO queries — no resolver call, no request lookup, no
 * postnatal lookup.
 *
 * Availability rules:
 *   - Create Admission Request → Obstetrics only, never Gynaecology;
 *   - Refer to Obstetrics/Maternity → Gynaecology only;
 *   - Postnatal review → either, and read-only.
 *
 * Every action also requires the target module's own permission; the bridge
 * never escalates access.
 */
class ConsultationMaternityHandoffPresenter
{
    public const OBSTETRICS = 'obstetrics';
    public const GYNAECOLOGY = 'gynecology';

    public function __construct(
        private readonly ConsultationMaternityContextResolver $resolver,
        private readonly ConsultationMaternityAdmissionRequestService $admissionRequests,
        private readonly ConsultationPostnatalReviewService $postnatal,
        private readonly MaternityIntegrationFlags $flags,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(
        ?VisitConsultationRoute $consultation,
        ?ConsultationSpecialtyProfile $profile,
        ?User $user,
    ): array {
        $code = $profile?->code;
        $isObstetrics = $code === self::OBSTETRICS;
        $isGynaecology = $code === self::GYNAECOLOGY;

        if (! $this->flags->consultationHandoffsEnabled()
            || ! $consultation
            || ! $user
            || (! $isObstetrics && ! $isGynaecology)) {
            return ['enabled' => false];
        }

        // Explicit context only: a handoff must never carry a guessed record.
        $context = $this->resolver->resolveExplicitOnly($consultation);
        $hasExplicitLink = $context->isResolved() && $context->pregnancyProfile !== null;

        $canCreateRequest = $isObstetrics
            && $user->can('consultation.maternity_context.create_admission_request')
            && $user->can('admission.requests.create');

        $canRefer = $isGynaecology
            && $user->can('consultation.maternity_context.refer_obstetrics')
            && $user->can('consultations.create');

        $canOpenPostnatal = $user->can('consultation.maternity_context.open_postnatal')
            && $user->can('maternity.postnatal.view');

        // Only look up the open request when the button could actually show.
        $openRequest = $hasExplicitLink && $canCreateRequest
            ? $this->admissionRequests->existingOpenRequest($consultation, $context)
            : null;

        $postnatalCase = $canOpenPostnatal ? $context->postnatalCase : null;

        return [
            'enabled' => true,
            'can_view' => $user->can('consultation.maternity_context.view'),
            'is_obstetrics' => $isObstetrics,
            'is_gynaecology' => $isGynaecology,
            'has_explicit_link' => $hasExplicitLink,
            'pregnancy_profile_id' => $context->pregnancyProfile?->id,
            'can_create_admission_request' => $canCreateRequest,
            'can_refer_obstetrics' => $canRefer,
            'can_open_postnatal' => $canOpenPostnatal,
            'open_admission_request' => $openRequest ? [
                'id' => $openRequest->id,
                'status' => $openRequest->status?->label(),
                'url' => $this->route('admin.admissions.requests.show', $openRequest),
            ] : null,
            'postnatal' => $postnatalCase ? $this->postnatal->projection($postnatalCase) : null,
        ];
    }

    private function route(string $name, mixed $parameter): ?string
    {
        try {
            return route($name, $parameter);
        } catch (\Throwable) {
            return null;
        }
    }
}
