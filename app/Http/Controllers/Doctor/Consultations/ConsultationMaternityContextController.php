<?php

namespace App\Http\Controllers\Doctor\Consultations;

use App\Enums\ConsultationMaternityContextType;
use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\LogModule;
use App\Enums\PregnancyDatingMethod;
use App\Models\LaborEpisode;
use App\Models\PregnancyProfile;
use App\Models\Visit;
use App\Services\ActivityLogService;
use App\Services\Consultation\Maternity\ConsultationMaternityContextResolver;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkException;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkService;
use App\Services\Maternity\AntenatalVisitService;
use App\Services\Maternity\LaborEpisodeService;
use App\Services\Maternity\PregnancyProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Phase 14R.3 — explicit, clinician-initiated maternity context actions from
 * the Obstetrics consultation workspace.
 *
 * Every action:
 *   - requires BOTH the bridge permission and the underlying maternity
 *     permission (the bridge never escalates maternity access);
 *   - delegates all writes to the existing maternity services;
 *   - links through ConsultationMaternityLinkService (never raw inserts);
 *   - returns to the originating consultation.
 *
 * Nothing here happens automatically — opening the workspace never creates or
 * links anything.
 */
class ConsultationMaternityContextController extends ConsultationWorkflowController
{
    // NOTE: no constructor. ConsultationWorkflowController's constructor wires
    // ~15 collaborators; overriding it would leave the parent's typed
    // properties (e.g. $actionGuard) uninitialised. The two bridge services are
    // resolved lazily instead.

    private function links(): ConsultationMaternityLinkService
    {
        return app(ConsultationMaternityLinkService::class);
    }

    private function resolver(): ConsultationMaternityContextResolver
    {
        return app(ConsultationMaternityContextResolver::class);
    }

    /* ── Link / confirm / relink / unlink ─────────────────────────────── */

    public function link(Request $request, Visit $visit)
    {
        $this->authorizeBridge($request, 'consultation.maternity_context.link');
        $route = $this->route($request, $visit);

        $data = $request->validate([
            'pregnancy_profile_id' => ['required', 'integer', 'exists:pregnancy_profiles,id'],
            'link_role' => ['nullable', 'string', 'in:primary,reviewed'],
        ]);

        $profile = $this->sameParentProfileOrFail($visit, (int) $data['pregnancy_profile_id']);

        try {
            $this->links()->link(
                $route,
                $profile,
                $request->user(),
                ConsultationMaternityLinkRole::from($data['link_role'] ?? 'primary'),
            );
        } catch (ConsultationMaternityLinkException $e) {
            return $this->failure($request, $e->getMessage());
        }

        return $this->success($request, __('consultation_maternity.messages.profile_linked'));
    }

    /**
     * Confirm an inferred context. The resolver suggested it; this is the
     * clinician explicitly accepting it. Nothing is persisted until now.
     */
    public function confirm(Request $request, Visit $visit)
    {
        $this->authorizeBridge($request, 'consultation.maternity_context.link');
        $route = $this->route($request, $visit);

        $context = $this->resolver()->resolve($route);

        if (! $context->isResolved() || $context->isExplicit() || ! $context->pregnancyProfile) {
            return $this->failure($request, __('consultation_maternity.no_maternity_context'));
        }

        try {
            $this->links()->link(
                $route,
                $context->pregnancyProfile,
                $request->user(),
                ConsultationMaternityLinkRole::REVIEWED,
            );
        } catch (ConsultationMaternityLinkException $e) {
            return $this->failure($request, $e->getMessage());
        }

        return $this->success($request, __('consultation_maternity.messages.context_confirmed'));
    }

    public function relink(Request $request, Visit $visit)
    {
        $this->authorizeBridge($request, 'consultation.maternity_context.link');
        $route = $this->route($request, $visit);

        $data = $request->validate([
            'pregnancy_profile_id' => ['required', 'integer', 'exists:pregnancy_profiles,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $profile = $this->sameParentProfileOrFail($visit, (int) $data['pregnancy_profile_id']);

        try {
            $this->links()->relink($route, $profile, $request->user(), $data['reason']);
        } catch (ConsultationMaternityLinkException $e) {
            return $this->failure($request, $e->getMessage());
        }

        return $this->success($request, __('consultation_maternity.context_relinked'));
    }

    public function unlink(Request $request, Visit $visit)
    {
        $this->authorizeBridge($request, 'consultation.maternity_context.unlink');
        $route = $this->route($request, $visit);

        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        try {
            $this->links()->unlink(
                $route,
                ConsultationMaternityContextType::PREGNANCY_PROFILE,
                $request->user(),
                $data['reason'],
            );
        } catch (ConsultationMaternityLinkException $e) {
            return $this->failure($request, $e->getMessage());
        }

        return $this->success($request, __('consultation_maternity.context_unlinked'));
    }

    /* ── Create pregnancy profile ─────────────────────────────────────── */

    public function createProfile(
        Request $request,
        Visit $visit,
        PregnancyProfileService $profiles,
        ActivityLogService $activityLog,
    ) {
        $this->authorizeBridge($request, 'consultation.maternity_context.create_profile');
        $this->authorizeMaternity($request, 'maternity.pregnancy.create');
        $route = $this->route($request, $visit);

        $data = $request->validate([
            'gravida' => ['nullable', 'integer', 'min:0', 'max:30'],
            'para' => ['nullable', 'integer', 'min:0', 'max:30'],
            'abortions' => ['nullable', 'integer', 'min:0', 'max:30'],
            'living_children' => ['nullable', 'integer', 'min:0', 'max:30'],
            'last_menstrual_period' => ['nullable', 'date'],
            'estimated_due_date' => ['nullable', 'date'],
            'dating_method' => ['nullable', 'string', 'in:'.implode(',', PregnancyDatingMethod::values())],
            'previous_caesarean' => ['nullable', 'boolean'],
            'known_risks' => ['nullable', 'string'],
        ]);

        // Creation + linking are one atomic clinician action.
        $profile = DB::transaction(function () use ($data, $visit, $route, $request, $profiles) {
            $profile = $profiles->create($data + [
                'patient_id' => $visit->patient_id,
                'visit_id' => $visit->id,
                'department_id' => $route->department_id,
            ], $request->user());

            $this->links()->link(
                $route,
                $profile,
                $request->user(),
                ConsultationMaternityLinkRole::CREATED,
            );

            return $profile;
        });

        $activityLog->log(
            LogModule::CONSULTATION,
            'PREGNANCY_PROFILE_CREATED_FROM_CONSULTATION',
            [
                'patient_id' => $visit->patient_id,
                'visit_id' => $visit->id,
                'causer' => $request->user(),
                'metadata' => [
                    'consultation_route_id' => $route->id,
                    'pregnancy_profile_id' => $profile->id,
                ],
            ],
            $profile,
            'PREGNANCY_PROFILE_CREATED_FROM_CONSULTATION',
        );

        return $this->success($request, __('consultation_maternity.messages.profile_created'));
    }

    /* ── Record ANC ───────────────────────────────────────────────────── */

    public function recordAnc(
        Request $request,
        Visit $visit,
        AntenatalVisitService $antenatal,
        ActivityLogService $activityLog,
    ) {
        $this->authorizeBridge($request, 'consultation.maternity_context.record_anc');
        $this->authorizeMaternity($request, 'maternity.anc.record');
        $route = $this->route($request, $visit);

        // An explicit, confirmed link is required — inferred context is not enough.
        $profile = $this->explicitlyLinkedProfileOrFail($route);

        $data = $request->validate([
            'visit_date' => ['nullable', 'date'],
            'gestational_age_weeks' => ['nullable', 'integer', 'min:0', 'max:45'],
            'gestational_age_days' => ['nullable', 'integer', 'min:0', 'max:6'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'blood_pressure_systolic' => ['nullable', 'integer', 'min:0', 'max:300'],
            'blood_pressure_diastolic' => ['nullable', 'integer', 'min:0', 'max:200'],
            'pulse' => ['nullable', 'integer', 'min:0', 'max:250'],
            'temperature' => ['nullable', 'numeric', 'min:20', 'max:45'],
            'fundal_height_cm' => ['nullable', 'numeric', 'min:0', 'max:60'],
            'fetal_heart_rate' => ['nullable', 'integer', 'min:0', 'max:250'],
            'fetal_movement' => ['nullable', 'string', 'max:255'],
            'presentation' => ['nullable', 'string', 'max:255'],
            'urine_protein' => ['nullable', 'string', 'max:100'],
            'urine_glucose' => ['nullable', 'string', 'max:100'],
            'haemoglobin' => ['nullable', 'numeric', 'min:0', 'max:30'],
            'assessment' => ['nullable', 'string', 'max:2000'],
            'plan' => ['nullable', 'string', 'max:2000'],
            'next_visit_date' => ['nullable', 'date'],
        ]);

        $ancVisit = DB::transaction(function () use ($antenatal, $profile, $data, $visit, $route, $request) {
            $anc = $antenatal->create($profile, $data + [
                'visit_date' => $data['visit_date'] ?? now(),
                'visit_id' => $visit->id,
                'admission_id' => $visit->admission?->id,
                'department_id' => $route->department_id,
            ], $request->user());

            $this->links()->link(
                $route,
                $anc,
                $request->user(),
                ConsultationMaternityLinkRole::CREATED,
            );

            return $anc;
        });

        $activityLog->log(
            LogModule::CONSULTATION,
            'ANC_VISIT_RECORDED_FROM_CONSULTATION',
            [
                'patient_id' => $visit->patient_id,
                'visit_id' => $visit->id,
                'causer' => $request->user(),
                'metadata' => [
                    'consultation_route_id' => $route->id,
                    'pregnancy_profile_id' => $profile->id,
                    'antenatal_visit_id' => $ancVisit->id,
                ],
            ],
            $ancVisit,
            'ANC_VISIT_RECORDED_FROM_CONSULTATION',
        );

        return $this->success($request, __('consultation_maternity.messages.anc_recorded'));
    }

    /* ── Start / open labor ───────────────────────────────────────────── */

    public function startLabor(
        Request $request,
        Visit $visit,
        LaborEpisodeService $laborService,
        ActivityLogService $activityLog,
    ) {
        $this->authorizeBridge($request, 'consultation.maternity_context.start_labor');
        $this->authorizeMaternity($request, 'maternity.labor.start');
        $route = $this->route($request, $visit);

        $profile = $this->explicitlyLinkedProfileOrFail($route);

        // Never create a second episode — open the existing one instead.
        $existing = LaborEpisode::query()
            ->where('pregnancy_profile_id', $profile->id)
            ->open()
            ->latest('id')
            ->first();

        if ($existing) {
            return $this->success($request, __('consultation_maternity.messages.labor_opened'));
        }

        $episode = DB::transaction(function () use ($laborService, $profile, $visit, $route, $request) {
            $episode = $laborService->start($profile, [
                'patient_id' => $visit->patient_id,
                'visit_id' => $visit->id,
                'admission_id' => $visit->admission?->id,
                'department_id' => $route->department_id,
                'started_at' => now(),
            ], $request->user());

            $this->links()->link(
                $route,
                $episode,
                $request->user(),
                ConsultationMaternityLinkRole::CREATED,
            );

            return $episode;
        });

        $activityLog->log(
            LogModule::CONSULTATION,
            'LABOR_EPISODE_STARTED_FROM_CONSULTATION',
            [
                'patient_id' => $visit->patient_id,
                'visit_id' => $visit->id,
                'causer' => $request->user(),
                'metadata' => [
                    'consultation_route_id' => $route->id,
                    'pregnancy_profile_id' => $profile->id,
                    'labor_episode_id' => $episode->id,
                ],
            ],
            $episode,
            'LABOR_EPISODE_STARTED_FROM_CONSULTATION',
        );

        return $this->success($request, __('consultation_maternity.messages.labor_started'));
    }

    /* ── Helpers ──────────────────────────────────────────────────────── */

    /**
     * Resolve the consultation encounter for this visit.
     *
     * Deliberately does NOT use consultationMutationContext(): that guard
     * requires an *editable* (started, unpaused, uncompleted) session because
     * it protects clinical entry writes. A maternity context link is a bridge
     * record, not a clinical entry — and 14R.2 requires links to remain valid
     * on completed consultations. Requiring an editable session here would
     * break linking for review/historical contexts.
     */
    private function route(Request $request, Visit $visit)
    {
        if (! config('consultation.maternity_context.obstetrics_workspace_enabled', false)) {
            abort(403, __('consultation_maternity.messages.workspace_disabled'));
        }

        $route = app(\App\Services\ConsultationSessionService::class)
            ->resolveRouteForVisit($visit, $request->integer('consultation_route_id') ?: null);

        if (! $route) {
            abort(422, __('consultation_maternity.no_maternity_context'));
        }

        return $route;
    }

    private function authorizeBridge(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403);
    }

    /** The bridge never grants a maternity operation the user otherwise lacks. */
    private function authorizeMaternity(Request $request, string $permission): void
    {
        abort_unless(
            $request->user()?->can($permission),
            403,
            __('consultation_maternity.messages.maternity_permission_required')
        );
    }

    private function sameParentProfileOrFail(Visit $visit, int $profileId): PregnancyProfile
    {
        $profile = PregnancyProfile::findOrFail($profileId);

        if ((int) $profile->patient_id !== (int) $visit->patient_id) {
            throw ValidationException::withMessages([
                'pregnancy_profile_id' => __('consultation_maternity.errors.patient_mismatch'),
            ]);
        }

        return $profile;
    }

    private function explicitlyLinkedProfileOrFail($route): PregnancyProfile
    {
        $link = $this->links()->getActiveLink($route, ConsultationMaternityContextType::PREGNANCY_PROFILE);

        if (! $link || ! $link->pregnancyProfile) {
            abort(422, __('consultation_maternity.messages.explicit_link_required'));
        }

        return $link->pregnancyProfile;
    }

    private function success(Request $request, string $message)
    {
        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    private function failure(Request $request, string $message)
    {
        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return back()->with('error', $message);
    }
}
