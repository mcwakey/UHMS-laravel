<?php

namespace App\Http\Controllers\Admin\Maternity;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\EmergencyCase;
use App\Models\PregnancyProfile;
use App\Models\Visit;
use App\Services\Maternity\Context\MaternityIntegrationFlags;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Phase 14R.5.1 — lazy Pregnancy Profile candidates for the handoff selectors.
 *
 * These endpoints exist so that candidate profiles are queried ONLY when a
 * clinician opens a selector, never on Emergency/Admission/Consultation page
 * render.
 *
 * Scope safety: the patient is derived from the ROUTE-BOUND source record
 * (emergency case / admission / visit) on the server. The client supplies only
 * a free-text search term, so no request parameter can widen the scope to
 * another patient's profiles.
 *
 * Read-only: these endpoints create and link nothing, and are deliberately GET
 * because they mutate no state.
 */
class MaternityContextCandidateController extends Controller
{
    /** Hard cap — a selector never needs more, and this bounds the query. */
    private const LIMIT = 20;

    public function __construct(
        private readonly MaternityIntegrationFlags $flags,
    ) {}

    public function forEmergencyCase(Request $request, EmergencyCase $emergencyCase): JsonResponse
    {
        abort_unless($this->flags->emergencyContextEnabled(), 403);
        $this->authorizeBoth($request, 'emergency.maternity_context.link');

        return $this->respond($request, (int) $emergencyCase->patient_id);
    }

    public function forAdmission(Request $request, Admission $admission): JsonResponse
    {
        abort_unless($this->flags->admissionContextEnabled(), 403);
        $this->authorizeBoth($request, 'admission.maternity_context.link');

        return $this->respond($request, (int) $admission->patient_id);
    }

    public function forVisit(Request $request, Visit $visit): JsonResponse
    {
        // Either O&G workspace flag suffices — the consultation selectors are
        // reachable from both Obstetrics and Gynaecology.
        abort_unless(
            config('consultation.maternity_context.obstetrics_workspace_enabled', false)
                || config('consultation.maternity_context.gynaecology_context_enabled', false),
            403
        );
        $this->authorizeBoth($request, 'consultation.maternity_context.link');

        return $this->respond($request, (int) $visit->patient_id);
    }

    /* ── Internals ─────────────────────────────────────────────────────── */

    /**
     * Bridge permission + the underlying maternity read permission. The bridge
     * never grants visibility of maternity data on its own.
     */
    private function authorizeBoth(Request $request, string $bridgePermission): void
    {
        abort_unless($request->user()?->can($bridgePermission), 403);
        abort_unless($request->user()?->can('maternity.pregnancy.view'), 403);
    }

    /**
     * Patient-scoped, bounded candidate list. Identifier and dating fields
     * only — no clinical narrative is returned to the browser.
     */
    private function respond(Request $request, int $patientId): JsonResponse
    {
        if ($patientId === 0) {
            return response()->json([]);
        }

        $term = trim((string) $request->query('q', ''));

        $profiles = PregnancyProfile::query()
            ->where('patient_id', $patientId)
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('id', $term)
                        ->orWhere('estimated_due_date', 'like', "%{$term}%")
                        ->orWhere('last_menstrual_period', 'like', "%{$term}%");
                });
            })
            ->orderByRaw("CASE WHEN profile_status = 'active' THEN 0 ELSE 1 END")
            ->latest('id')
            ->limit(self::LIMIT)
            ->get([
                'id', 'profile_status', 'estimated_due_date',
                'gestational_age_weeks', 'gestational_age_days',
            ]);

        return response()->json($profiles->map(fn (PregnancyProfile $profile) => [
            'id' => (string) $profile->id,
            'text' => $this->label($profile),
            'status' => $profile->profile_status?->value,
        ])->all());
    }

    private function label(PregnancyProfile $profile): string
    {
        $parts = [__('maternity_handoffs.cards.record_ref', [
            'type' => __('maternity_handoffs.cards.pregnancy'),
            'id' => $profile->id,
        ])];

        if ($profile->profile_status) {
            $parts[] = method_exists($profile->profile_status, 'label')
                ? $profile->profile_status->label()
                : $profile->profile_status->value;
        }

        if ($profile->gestational_age_weeks !== null) {
            $parts[] = sprintf(
                '%dw %dd',
                (int) $profile->gestational_age_weeks,
                (int) ($profile->gestational_age_days ?? 0)
            );
        }

        if ($profile->estimated_due_date) {
            $parts[] = __('maternity_handoffs.fields.edd').' '.$profile->estimated_due_date->format('d M Y');
        }

        return implode(' · ', $parts);
    }
}
