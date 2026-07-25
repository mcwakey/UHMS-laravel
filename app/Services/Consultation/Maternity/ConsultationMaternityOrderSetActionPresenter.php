<?php

namespace App\Services\Consultation\Maternity;

use App\Models\ConsultationSpecialtyOrderSetItem;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\User;
use App\Models\VisitConsultationRoute;

/**
 * Phase 14R.4.1 — turns a retargeted `maternity_context_action` order-set item
 * into a typed, clinician-facing CTA description.
 *
 * This is PRESENTATION ONLY. It never writes anything: no pregnancy profile,
 * no bridge link, no ANC visit, no specialty entry, no billing. The clinician
 * performs the real action through the normal controller, which re-checks
 * permissions and the mutation boundary.
 *
 * Action keys are a CLOSED set — an unknown key fails closed and renders an
 * "unsupported" state rather than being executed or ignored.
 */
class ConsultationMaternityOrderSetActionPresenter
{
    public const ACTION_CREATE_OR_LINK = 'create_or_link_pregnancy_profile';
    public const ACTION_RECORD_ANC_COUNSELLING = 'record_anc_counselling';

    /** @var list<string> */
    public const SUPPORTED_ACTIONS = [
        self::ACTION_CREATE_OR_LINK,
        self::ACTION_RECORD_ANC_COUNSELLING,
    ];

    /* Display states. */
    public const STATE_ACTION_REQUIRED = 'action_required';
    public const STATE_SATISFIED = 'satisfied';
    public const STATE_UNAVAILABLE = 'unavailable';
    public const STATE_BLOCKED = 'blocked';
    public const STATE_UNSUPPORTED = 'unsupported';
    public const STATE_LEGACY_PATCH = 'legacy_patch';

    public const OBSTETRICS = 'obstetrics';
    public const GYNAECOLOGY = 'gynecology';

    public function __construct(
        private readonly ConsultationMaternitySpecialtyWriteGuard $guard,
    ) {}

    /**
     * Describe how an order-set item should render for this consultation.
     *
     * @return array{
     *     state: string, label: string, message: string,
     *     action_key: ?string, executable: bool, item_id: int
     * }
     */
    public function present(
        ConsultationSpecialtyOrderSetItem $item,
        VisitConsultationRoute $consultation,
        ?ConsultationSpecialtyProfile $profile,
        ?User $user,
        bool $hasExplicitProfileLink,
        bool $consultationEditable = true,
    ): array {
        // Historical patch items keep their own identity — we never pretend an
        // old application used the new action.
        if ($item->apply_mode !== 'maternity_context_action') {
            return $this->result(
                $item,
                self::STATE_LEGACY_PATCH,
                __('consultation_maternity.order_sets.legacy_patch'),
                __('consultation_maternity.order_sets.legacy_patch_hint'),
            );
        }

        $actionKey = $item->payload['maternity_action'] ?? null;

        // Closed set — unknown keys fail closed.
        if (! in_array($actionKey, self::SUPPORTED_ACTIONS, true)) {
            return $this->result(
                $item,
                self::STATE_UNSUPPORTED,
                __('consultation_maternity.order_sets.unsupported_action'),
                __('consultation_maternity.order_sets.unsupported_action_hint'),
            );
        }

        $profileCode = $profile?->code;

        // The flag governs workspace visibility, not the safety semantics of
        // the retargeted action: it must never revert to patching.
        if (! $this->integrationEnabledFor($profileCode)) {
            return $this->result(
                $item,
                self::STATE_UNAVAILABLE,
                __('consultation_maternity.order_sets.integration_disabled'),
                __('consultation_maternity.order_sets.use_maternity_workspace'),
                $actionKey,
            );
        }

        return match ($actionKey) {
            self::ACTION_CREATE_OR_LINK => $this->presentCreateOrLink(
                $item, $profileCode, $user, $hasExplicitProfileLink
            ),
            self::ACTION_RECORD_ANC_COUNSELLING => $this->presentRecordAnc(
                $item, $profileCode, $user, $hasExplicitProfileLink, $consultationEditable
            ),
        };
    }

    private function presentCreateOrLink(
        ConsultationSpecialtyOrderSetItem $item,
        ?string $profileCode,
        ?User $user,
        bool $hasExplicitProfileLink,
    ): array {
        // Already linked → satisfied. Never creates a second profile and never
        // writes current_pregnancy.pregnancy_confirmed.
        if ($hasExplicitProfileLink) {
            return $this->result(
                $item,
                self::STATE_SATISFIED,
                __('consultation_maternity.order_sets.already_satisfied'),
                __('consultation_maternity.gynaecology.explicitly_linked_profile'),
                self::ACTION_CREATE_OR_LINK,
            );
        }

        if (! $user?->can('consultation.maternity_context.link')) {
            return $this->result(
                $item,
                self::STATE_BLOCKED,
                __('consultation_maternity.order_sets.action_required'),
                __('consultation_maternity.order_sets.maternity_permission_required'),
                self::ACTION_CREATE_OR_LINK,
            );
        }

        // Wording differs by specialty; Gynaecology never implies a transition.
        $label = $profileCode === self::GYNAECOLOGY
            ? __('consultation_maternity.gynaecology.start_or_link')
            : __('consultation_maternity.order_sets.create_or_link_profile');

        return $this->result(
            $item,
            self::STATE_ACTION_REQUIRED,
            $label,
            __('consultation_maternity.order_sets.no_specialty_entry_created'),
            self::ACTION_CREATE_OR_LINK,
            executable: true,
        );
    }

    private function presentRecordAnc(
        ConsultationSpecialtyOrderSetItem $item,
        ?string $profileCode,
        ?User $user,
        bool $hasExplicitProfileLink,
        bool $consultationEditable,
    ): array {
        // ANC counselling belongs to Obstetrics. If a custom Gynaecology order
        // set contains it, we render guidance and never execute it.
        if ($profileCode !== self::OBSTETRICS) {
            return $this->result(
                $item,
                self::STATE_UNAVAILABLE,
                __('consultation_maternity.order_sets.not_available_in_gynaecology'),
                __('consultation_maternity.order_sets.use_obstetrics_workflow'),
                self::ACTION_RECORD_ANC_COUNSELLING,
            );
        }

        if (! $hasExplicitProfileLink) {
            return $this->result(
                $item,
                self::STATE_ACTION_REQUIRED,
                __('consultation_maternity.order_sets.create_or_link_profile'),
                __('consultation_maternity.order_sets.confirm_profile_first'),
                self::ACTION_CREATE_OR_LINK,
                executable: true,
            );
        }

        // Clinical mutation → an editable consultation is mandatory.
        if (! $consultationEditable) {
            return $this->result(
                $item,
                self::STATE_BLOCKED,
                __('consultation_maternity.order_sets.action_unavailable'),
                __('consultation_maternity.order_sets.start_new_active_consultation'),
                self::ACTION_RECORD_ANC_COUNSELLING,
            );
        }

        // Dual permission — the bridge never escalates maternity access.
        if (! $user?->can('consultation.maternity_context.record_anc')
            || ! $user?->can('maternity.anc.record')) {
            return $this->result(
                $item,
                self::STATE_BLOCKED,
                __('consultation_maternity.order_sets.action_unavailable'),
                __('consultation_maternity.order_sets.maternity_permission_required'),
                self::ACTION_RECORD_ANC_COUNSELLING,
            );
        }

        return $this->result(
            $item,
            self::STATE_ACTION_REQUIRED,
            __('consultation_maternity.order_sets.record_anc_counselling'),
            __('consultation_maternity.order_sets.no_specialty_entry_created'),
            self::ACTION_RECORD_ANC_COUNSELLING,
            executable: true,
        );
    }

    private function integrationEnabledFor(?string $profileCode): bool
    {
        return match ($profileCode) {
            self::OBSTETRICS => $this->guard->workspaceEnabled(),
            self::GYNAECOLOGY => $this->guard->gynaecologyContextEnabled(),
            default => false,
        };
    }

    private function result(
        ConsultationSpecialtyOrderSetItem $item,
        string $state,
        string $label,
        string $message,
        ?string $actionKey = null,
        bool $executable = false,
    ): array {
        return [
            'item_id' => $item->id,
            'state' => $state,
            'label' => $label,
            'message' => $message,
            'action_key' => $actionKey,
            // Never expose a clickable action the user cannot execute.
            'executable' => $executable,
        ];
    }
}
