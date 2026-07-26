<?php

namespace App\Services\Consultation\Maternity;

use App\Data\Consultation\Maternity\GynaecologyWorkspaceViewModel;
use App\Data\Consultation\Maternity\ObstetricWorkspaceViewModel;
use App\Data\Maternity\MaternityHandoffActionViewModel as Action;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\ConsultationSessionEligibilityService;
use App\Services\Maternity\Context\MaternityHandoffActionFactory;
use App\Support\Maternity\MaternityReturnContext;

/**
 * Phase 14R.5.1 — typed actions for the Obstetrics panel (14R.3.1) and the
 * Gynaecology card (14R.4.1).
 *
 * Those phases shipped their triggers with the modal bodies deferred. This
 * presenter closes that gap using the same typed contract and the same shared
 * modal shell as the 14R.5 handoffs, so a single integrity check covers every
 * O&G trigger in the workspace.
 *
 * Both view models already carry a permission-resolved `availableActions` map;
 * this class turns those booleans plus the lifecycle decision into renderable
 * actions. It performs NO permission lookups and NO queries of its own.
 */
class ConsultationMaternityModalPresenter
{
    public function __construct(
        private readonly ConsultationSessionEligibilityService $eligibility,
        private readonly MaternityHandoffActionFactory $actions,
    ) {}

    /**
     * Obstetrics panel: link / relink / unlink profile and record ANC.
     *
     * @return array<string, Action>
     */
    public function obstetrics(
        ObstetricWorkspaceViewModel $model,
        ?VisitConsultationRoute $consultation,
        ?User $user,
    ): array {
        if (! $model->workspaceEnabled || ! $model->isObstetrics || ! $consultation || ! $user) {
            return [];
        }

        $can = $model->availableActions;
        $params = ['visit' => $consultation->visit_id];
        $return = $this->returnContext($consultation, 'maternity-context');
        $candidateUrl = $this->candidateUrl($consultation);
        $lifecycle = $this->lifecycle($consultation, $user);
        $linked = $model->isExplicit && ! empty($model->pregnancy);

        return [
            'link_profile' => $this->actions->make([
                'action' => 'link_profile',
                'label' => __('consultation_maternity.actions.link_profile'),
                'description' => __('maternity_handoffs.modal.patient_scoped_search'),
                'feature_enabled' => ! $linked,
                'has_permission' => (bool) ($can['link'] ?? false),
                // Context links stay manageable after completion (14R.2 policy).
                'lifecycle_ok' => true,
                'route' => 'admin.consultations.maternity-context.link',
                'parameters' => $params,
                'modal' => 'maternityLinkProfileModal',
                'required_fields' => ['pregnancy_profile_id'],
                'source_module' => 'consultation',
                'source_record_id' => $consultation->id,
                'target_context_type' => 'pregnancy_profile',
                'context' => ['candidate_url' => $candidateUrl],
            ], $return),

            'relink_profile' => $this->actions->make([
                'action' => 'relink_profile',
                'label' => __('consultation_maternity.actions.relink_profile'),
                'feature_enabled' => $linked,
                'has_permission' => (bool) ($can['relink'] ?? $can['link'] ?? false),
                'lifecycle_ok' => true,
                'route' => 'admin.consultations.maternity-context.relink',
                'parameters' => $params,
                'modal' => 'maternityRelinkModal',
                'confirmation' => Action::CONFIRM_REASON,
                'required_fields' => ['pregnancy_profile_id', 'reason'],
                'source_module' => 'consultation',
                'source_record_id' => $consultation->id,
                'target_context_type' => 'pregnancy_profile',
                'context' => [
                    'candidate_url' => $candidateUrl,
                    'notices' => [__('maternity_handoffs.modal.history_preserved')],
                ],
            ], $return),

            'unlink_profile' => $this->actions->make([
                'action' => 'unlink_profile',
                'label' => __('consultation_maternity.actions.unlink_profile'),
                'feature_enabled' => $linked,
                'has_permission' => (bool) ($can['unlink'] ?? false),
                'lifecycle_ok' => true,
                'route' => 'admin.consultations.maternity-context.unlink',
                'parameters' => $params,
                'modal' => 'maternityUnlinkModal',
                'confirmation' => Action::CONFIRM_REASON,
                'required_fields' => ['reason'],
                'source_module' => 'consultation',
                'source_record_id' => $consultation->id,
                'target_context_type' => 'pregnancy_profile',
                'context' => ['notices' => [__('maternity_handoffs.modal.history_preserved')]],
            ], $return),

            'record_anc' => $this->actions->make([
                'action' => 'record_anc',
                'label' => __('consultation_maternity.actions.record_anc'),
                // A clinical mutation: it needs an editable consultation.
                'feature_enabled' => true,
                'has_permission' => (bool) ($can['record_anc'] ?? false),
                'lifecycle_ok' => $lifecycle['allowed'],
                'lifecycle_reason' => $lifecycle['message'],
                'context_ok' => $linked,
                'context_reason' => __('maternity_handoffs.states.explicit_link_required'),
                'route' => 'admin.consultations.maternity-context.record-anc',
                'parameters' => $params,
                'modal' => 'maternityRecordAncModal',
                'source_module' => 'consultation',
                'source_record_id' => $consultation->id,
                'target_context_type' => 'anc_visit',
            ], $return),
        ];
    }

    /**
     * Gynaecology card: link / relink / unlink profile and LMP adoption.
     * Never ANC, labor, delivery, newborn or postnatal.
     *
     * @return array<string, Action>
     */
    public function gynaecology(
        GynaecologyWorkspaceViewModel $model,
        ?VisitConsultationRoute $consultation,
        ?User $user,
    ): array {
        if (! $model->contextEnabled || ! $model->isGynaecology || ! $consultation || ! $user) {
            return [];
        }

        $can = $model->availableActions;
        $params = ['visit' => $consultation->visit_id];
        $return = $this->returnContext($consultation, 'gynaecology-maternity-context');
        $candidateUrl = $this->candidateUrl($consultation);
        $lifecycle = $this->lifecycle($consultation, $user);

        return [
            'link_profile' => $this->actions->make([
                'action' => 'link_profile',
                'label' => __('consultation_maternity.gynaecology.start_or_link'),
                'description' => __('maternity_handoffs.modal.patient_scoped_search'),
                'feature_enabled' => ! $model->hasExplicitLink,
                'has_permission' => (bool) ($can['link'] ?? false),
                'lifecycle_ok' => true,
                'route' => 'admin.consultations.maternity-context.link',
                'parameters' => $params,
                'modal' => 'gynaeLinkPregnancyModal',
                'required_fields' => ['pregnancy_profile_id'],
                'source_module' => 'consultation',
                'source_record_id' => $consultation->id,
                'target_context_type' => 'pregnancy_profile',
                'context' => [
                    'candidate_url' => $candidateUrl,
                    'notices' => [__('consultation_maternity.gynaecology.remains_gynaecology')],
                ],
            ], $return),

            'relink_profile' => $this->actions->make([
                'action' => 'relink_profile',
                'label' => __('consultation_maternity.actions.relink_profile'),
                'feature_enabled' => $model->hasExplicitLink,
                'has_permission' => (bool) ($can['relink'] ?? false),
                'lifecycle_ok' => true,
                'route' => 'admin.consultations.maternity-context.relink',
                'parameters' => $params,
                'modal' => 'gynaeRelinkModal',
                'confirmation' => Action::CONFIRM_REASON,
                'required_fields' => ['pregnancy_profile_id', 'reason'],
                'source_module' => 'consultation',
                'source_record_id' => $consultation->id,
                'target_context_type' => 'pregnancy_profile',
                'context' => ['candidate_url' => $candidateUrl],
            ], $return),

            'unlink_profile' => $this->actions->make([
                'action' => 'unlink_profile',
                'label' => __('consultation_maternity.actions.unlink_profile'),
                'feature_enabled' => $model->hasExplicitLink,
                'has_permission' => (bool) ($can['unlink'] ?? false),
                'lifecycle_ok' => true,
                'route' => 'admin.consultations.maternity-context.unlink',
                'parameters' => $params,
                'modal' => 'gynaeUnlinkModal',
                'confirmation' => Action::CONFIRM_REASON,
                'required_fields' => ['reason'],
                'source_module' => 'consultation',
                'source_record_id' => $consultation->id,
                'target_context_type' => 'pregnancy_profile',
                'context' => ['notices' => [__('maternity_handoffs.modal.history_preserved')]],
            ], $return),

            'adopt_lmp' => $this->actions->make([
                'action' => 'adopt_lmp',
                'label' => __('consultation_maternity.lmp.adopt'),
                // The 14R.4 adoption-state machine already decided this; only
                // the AVAILABLE state may render a form.
                'feature_enabled' => $model->lmpAdoptionState === GynaecologyWorkspaceViewModel::ADOPT_AVAILABLE,
                'has_permission' => (bool) ($can['adopt_lmp'] ?? false),
                'lifecycle_ok' => $lifecycle['allowed'],
                'lifecycle_reason' => $lifecycle['message'],
                'route' => 'admin.consultations.maternity-context.adopt-lmp',
                'parameters' => $params,
                'modal' => 'gynaeAdoptLmpModal',
                'source_module' => 'consultation',
                'source_record_id' => $consultation->id,
                'target_context_type' => 'pregnancy_profile',
                'context' => ['saved_lmp' => $model->savedConsultationLmp],
            ], $return),
        ];
    }

    /* ── Helpers ───────────────────────────────────────────────────────── */

    /**
     * @return array{allowed: bool, message: ?string}
     */
    private function lifecycle(VisitConsultationRoute $consultation, User $user): array
    {
        $visit = $consultation->visit;

        if (! $visit) {
            return ['allowed' => false, 'message' => __('maternity_handoffs.states.blocked_lifecycle')];
        }

        $decision = $this->eligibility->addItemDecision($visit, $consultation, $user);

        return [
            'allowed' => (bool) ($decision['allowed'] ?? false),
            'message' => ($decision['allowed'] ?? false)
                ? null
                : ($decision['message'] ?? __('maternity_handoffs.states.blocked_lifecycle')),
        ];
    }

    private function returnContext(VisitConsultationRoute $consultation, string $anchor): ?MaternityReturnContext
    {
        return MaternityReturnContext::make(
            MaternityReturnContext::MODULE_CONSULTATION,
            'admin.consultations.show',
            ['visit' => $consultation->visit_id],
            $anchor,
        );
    }

    private function candidateUrl(VisitConsultationRoute $consultation): ?string
    {
        try {
            return route('admin.consultations.maternity-context.candidates', [
                'visit' => $consultation->visit_id,
            ]);
        } catch (\Throwable) {
            return null;
        }
    }
}
