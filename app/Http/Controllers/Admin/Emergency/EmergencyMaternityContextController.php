<?php

namespace App\Http\Controllers\Admin\Emergency;

use App\Enums\ConsultationMaternityContextType;
use App\Http\Controllers\Controller;
use App\Models\EmergencyCase;
use App\Models\PregnancyProfile;
use App\Services\Emergency\Maternity\EmergencyMaternityHandoffService;
use App\Services\Emergency\Maternity\EmergencyMaternityLinkService;
use App\Services\Maternity\Context\MaternityIntegrationFlags;
use App\Services\Maternity\Context\MaternityLinkException;
use App\Support\Maternity\MaternityReturnContext;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Phase 14R.5 — explicit Emergency ↔ Maternity actions.
 *
 * Every action requires BOTH the emergency bridge permission and the underlying
 * Maternity/Admission permission — the bridge never escalates access to another
 * module. Nothing here runs automatically; each method is a clinician-pressed
 * button.
 *
 * Emergency keeps ownership of triage, bay, notes, vitals, treatment, tasks and
 * disposition throughout: this controller never touches any of them.
 */
class EmergencyMaternityContextController extends Controller
{
    public function __construct(
        private readonly EmergencyMaternityLinkService $links,
        private readonly EmergencyMaternityHandoffService $handoffs,
        private readonly MaternityIntegrationFlags $flags,
    ) {}

    /* ── Context management ────────────────────────────────────────────── */

    public function link(Request $request, EmergencyCase $emergencyCase)
    {
        $this->assertContextEnabled();
        $this->authorizeBoth($request, 'emergency.maternity_context.link', 'maternity.pregnancy.view');

        $data = $request->validate([
            'pregnancy_profile_id' => ['required', 'integer', 'exists:pregnancy_profiles,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $profile = $this->samePatientProfileOrFail($emergencyCase, (int) $data['pregnancy_profile_id']);

        try {
            $this->handoffs->linkPregnancyProfile(
                $emergencyCase, $profile, $request->user(), $data['reason'] ?? null
            );
        } catch (MaternityLinkException $e) {
            return $this->failure($request, $e->getMessage(), $emergencyCase);
        }

        return $this->success($request, __('maternity_handoffs.messages.context_linked'), $emergencyCase);
    }

    public function relink(Request $request, EmergencyCase $emergencyCase)
    {
        $this->assertContextEnabled();
        $this->authorizeBoth($request, 'emergency.maternity_context.link', 'maternity.pregnancy.view');

        $data = $request->validate([
            'pregnancy_profile_id' => ['required', 'integer', 'exists:pregnancy_profiles,id'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $profile = $this->samePatientProfileOrFail($emergencyCase, (int) $data['pregnancy_profile_id']);

        try {
            $this->links->relink($emergencyCase, $profile, $request->user(), $data['reason']);
        } catch (MaternityLinkException $e) {
            return $this->failure($request, $e->getMessage(), $emergencyCase);
        }

        return $this->success($request, __('maternity_handoffs.messages.context_relinked'), $emergencyCase);
    }

    public function unlink(Request $request, EmergencyCase $emergencyCase)
    {
        $this->assertContextEnabled();
        $this->authorizeBoth($request, 'emergency.maternity_context.unlink', 'maternity.pregnancy.view');

        $data = $request->validate([
            'context_type' => ['nullable', 'string'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $contextType = ConsultationMaternityContextType::tryFrom(
            $data['context_type'] ?? ConsultationMaternityContextType::PREGNANCY_PROFILE->value
        ) ?? ConsultationMaternityContextType::PREGNANCY_PROFILE;

        try {
            $this->links->unlink($emergencyCase, $contextType, $request->user(), $data['reason']);
        } catch (MaternityLinkException $e) {
            return $this->failure($request, $e->getMessage(), $emergencyCase);
        }

        return $this->success($request, __('maternity_handoffs.messages.context_unlinked'), $emergencyCase);
    }

    /* ── A. Create Pregnancy Profile ───────────────────────────────────── */

    public function createProfile(Request $request, EmergencyCase $emergencyCase)
    {
        $this->assertHandoffsEnabled();
        $this->authorizeBoth($request, 'emergency.maternity_context.create_profile', 'maternity.pregnancy.create');

        $data = $request->validate([
            'last_menstrual_period' => ['nullable', 'date'],
            'estimated_due_date' => ['nullable', 'date'],
            'gravida' => ['nullable', 'integer', 'min:0', 'max:30'],
            'para' => ['nullable', 'integer', 'min:0', 'max:30'],
        ]);

        $result = $this->handoffs->createPregnancyProfile($emergencyCase, $data, $request->user());

        return $this->success(
            $request,
            __('maternity_handoffs.messages.profile_created', ['id' => $result['profile']->id]),
            $emergencyCase
        );
    }

    /* ── B. Start / re-use Labor Episode ───────────────────────────────── */

    public function startLabor(Request $request, EmergencyCase $emergencyCase)
    {
        $this->assertHandoffsEnabled();
        $this->authorizeBoth($request, 'emergency.maternity_context.start_labor', 'maternity.labor.start');

        $data = $request->validate([
            'pregnancy_profile_id' => ['required', 'integer', 'exists:pregnancy_profiles,id'],
            'labor_onset_at' => ['nullable', 'date'],
            'presentation' => ['nullable', 'string', 'max:60'],
        ]);

        $profile = $this->samePatientProfileOrFail($emergencyCase, (int) $data['pregnancy_profile_id']);

        try {
            $result = $this->handoffs->startOrReuseLabor($emergencyCase, $profile, $data, $request->user());
        } catch (MaternityLinkException $e) {
            return $this->failure($request, $e->getMessage(), $emergencyCase);
        }

        return $this->success($request, $result['reused']
            ? __('maternity_handoffs.messages.labor_reused', ['id' => $result['episode']->id])
            : __('maternity_handoffs.messages.labor_started', ['id' => $result['episode']->id]),
            $emergencyCase);
    }

    /* ── C. Create / re-use Admission Request ──────────────────────────── */

    public function createAdmissionRequest(Request $request, EmergencyCase $emergencyCase)
    {
        $this->assertHandoffsEnabled();
        $this->authorizeBoth(
            $request,
            'emergency.maternity_context.create_admission_request',
            'admission.requests.create'
        );

        $data = $request->validate([
            'priority' => ['nullable', 'string', 'max:40'],
            'requested_ward_id' => ['nullable', 'integer', 'exists:wards,id'],
            'provisional_diagnosis' => ['nullable', 'string', 'max:1000'],
            'clinical_summary' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = $this->handoffs->createOrReuseAdmissionRequest($emergencyCase, $data, $request->user());

        return $this->success($request, $result['reused']
            ? __('maternity_handoffs.messages.admission_request_exists', ['id' => $result['request']->id])
            : __('maternity_handoffs.messages.admission_request_created', ['id' => $result['request']->id]),
            $emergencyCase);
    }

    /* ── Guards and helpers ────────────────────────────────────────────── */

    private function assertContextEnabled(): void
    {
        abort_unless(
            $this->flags->emergencyContextEnabled(),
            403,
            __('maternity_handoffs.messages.handoff_unavailable')
        );
    }

    private function assertHandoffsEnabled(): void
    {
        abort_unless(
            $this->flags->emergencyHandoffsEnabled(),
            403,
            __('maternity_handoffs.messages.handoff_unavailable')
        );
    }

    /**
     * The bridge permission is never sufficient on its own: the underlying
     * Maternity or Admission permission must also pass.
     */
    private function authorizeBoth(Request $request, string $bridge, string $target): void
    {
        abort_unless($request->user()?->can($bridge), 403);
        abort_unless(
            $request->user()?->can($target),
            403,
            __('consultation_maternity.messages.maternity_permission_required')
        );
    }

    private function samePatientProfileOrFail(EmergencyCase $case, int $profileId): PregnancyProfile
    {
        $profile = PregnancyProfile::findOrFail($profileId);

        if ((int) $profile->patient_id !== (int) $case->patient_id) {
            throw ValidationException::withMessages([
                'pregnancy_profile_id' => __('consultation_maternity.errors.patient_mismatch'),
            ]);
        }

        return $profile;
    }

    /**
     * Return to where the clinician came from, validated as an internal named
     * route. An invalid or missing context falls back to this emergency case.
     */
    private function redirectTarget(Request $request, EmergencyCase $case): string
    {
        $context = MaternityReturnContext::fromArray($request->all());

        return $context?->url() ?? route('admin.emergency.cases.show', $case);
    }

    private function success(Request $request, string $message, EmergencyCase $case)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->to($this->redirectTarget($request, $case))->with('success', $message);
    }

    private function failure(Request $request, string $message, EmergencyCase $case)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return redirect()->to($this->redirectTarget($request, $case))->with('error', $message);
    }
}
