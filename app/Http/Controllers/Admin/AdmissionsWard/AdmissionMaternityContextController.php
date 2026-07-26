<?php

namespace App\Http\Controllers\Admin\AdmissionsWard;

use App\Enums\ConsultationMaternityContextType;
use App\Enums\ConsultationMaternityLinkRole;
use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\PregnancyProfile;
use App\Services\Admissions\Maternity\AdmissionMaternityLinkService;
use App\Services\Maternity\Context\MaternityIntegrationFlags;
use App\Services\Maternity\Context\MaternityLinkException;
use App\Support\Maternity\MaternityReturnContext;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Phase 14R.5 — explicit Admission ↔ Maternity context management.
 *
 * Admission may LINK, RELINK (with a reason) and UNLINK (with a reason) — and
 * nothing else. It may never:
 *   - auto-create a Pregnancy Profile because a patient was admitted;
 *   - start ANC or Labor;
 *   - create Delivery, Newborn or Postnatal records;
 *   - infer between multiple active Pregnancy Profiles.
 *
 * Relink is also the correction path for a wrongly propagated request context.
 * Bed, ward, nursing, MAR and discharge remain entirely Admission-owned and are
 * untouched here.
 */
class AdmissionMaternityContextController extends Controller
{
    public function __construct(
        private readonly AdmissionMaternityLinkService $links,
        private readonly MaternityIntegrationFlags $flags,
    ) {}

    public function link(Request $request, Admission $admission)
    {
        $this->assertEnabled();
        $this->authorizeBoth($request, 'admission.maternity_context.link', 'maternity.pregnancy.view');

        $data = $request->validate([
            'pregnancy_profile_id' => ['required', 'integer', 'exists:pregnancy_profiles,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $profile = $this->samePatientProfileOrFail($admission, (int) $data['pregnancy_profile_id']);

        try {
            $this->links->link(
                $admission,
                $profile,
                $request->user(),
                ConsultationMaternityLinkRole::PRIMARY,
                $data['reason'] ?? null,
            );
        } catch (MaternityLinkException $e) {
            return $this->failure($request, $e->getMessage(), $admission);
        }

        return $this->success($request, __('maternity_handoffs.messages.context_linked'), $admission);
    }

    /** Also the correction path for an incorrectly propagated request context. */
    public function relink(Request $request, Admission $admission)
    {
        $this->assertEnabled();
        $this->authorizeBoth($request, 'admission.maternity_context.link', 'maternity.pregnancy.view');

        $data = $request->validate([
            'pregnancy_profile_id' => ['required', 'integer', 'exists:pregnancy_profiles,id'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $profile = $this->samePatientProfileOrFail($admission, (int) $data['pregnancy_profile_id']);

        try {
            $this->links->relink($admission, $profile, $request->user(), $data['reason']);
        } catch (MaternityLinkException $e) {
            return $this->failure($request, $e->getMessage(), $admission);
        }

        return $this->success($request, __('maternity_handoffs.messages.context_relinked'), $admission);
    }

    public function unlink(Request $request, Admission $admission)
    {
        $this->assertEnabled();
        $this->authorizeBoth($request, 'admission.maternity_context.unlink', 'maternity.pregnancy.view');

        $data = $request->validate([
            'context_type' => ['nullable', 'string'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $contextType = ConsultationMaternityContextType::tryFrom(
            $data['context_type'] ?? ConsultationMaternityContextType::PREGNANCY_PROFILE->value
        ) ?? ConsultationMaternityContextType::PREGNANCY_PROFILE;

        try {
            $this->links->unlink($admission, $contextType, $request->user(), $data['reason']);
        } catch (MaternityLinkException $e) {
            return $this->failure($request, $e->getMessage(), $admission);
        }

        return $this->success($request, __('maternity_handoffs.messages.context_unlinked'), $admission);
    }

    /* ── Guards and helpers ────────────────────────────────────────────── */

    private function assertEnabled(): void
    {
        abort_unless(
            $this->flags->admissionContextEnabled(),
            403,
            __('maternity_handoffs.messages.handoff_unavailable')
        );
    }

    private function authorizeBoth(Request $request, string $bridge, string $target): void
    {
        abort_unless($request->user()?->can($bridge), 403);
        abort_unless(
            $request->user()?->can($target),
            403,
            __('consultation_maternity.messages.maternity_permission_required')
        );
    }

    private function samePatientProfileOrFail(Admission $admission, int $profileId): PregnancyProfile
    {
        $profile = PregnancyProfile::findOrFail($profileId);

        if ((int) $profile->patient_id !== (int) $admission->patient_id) {
            throw ValidationException::withMessages([
                'pregnancy_profile_id' => __('consultation_maternity.errors.patient_mismatch'),
            ]);
        }

        return $profile;
    }

    private function redirectTarget(Request $request, Admission $admission): string
    {
        $context = MaternityReturnContext::fromArray($request->all());

        return $context?->url() ?? route('admin.admissions.show', $admission);
    }

    private function success(Request $request, string $message, Admission $admission)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->to($this->redirectTarget($request, $admission))->with('success', $message);
    }

    private function failure(Request $request, string $message, Admission $admission)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return redirect()->to($this->redirectTarget($request, $admission))->with('error', $message);
    }
}
