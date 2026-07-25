<?php

namespace App\Http\Controllers\Doctor\Consultations;

use App\Enums\ConsultationMaternityContextType;
use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\LogModule;
use App\Data\Consultation\Maternity\GynaecologyWorkspaceViewModel;
use App\Enums\PregnancyDatingMethod;
use App\Models\LaborEpisode;
use App\Models\PregnancyProfile;
use App\Models\Visit;
use App\Services\ActivityLogService;
use App\Services\Consultation\ConsultationActionException;
use App\Services\Consultation\Maternity\ConsultationMaternityContextResolver;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkException;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkService;
use App\Services\Consultation\Maternity\GynaecologyConsultationContextService;
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
        $route = $this->mutableRoute($request, $visit);

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
        $route = $this->mutableRoute($request, $visit);

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

    /* ── Gynaecology: explicit one-way LMP adoption (Phase 14R.4) ─────── */

    /**
     * Adopt the saved Gynaecology `menstrual_history.lmp` as the pregnancy
     * profile's dating LMP. One-way and explicit:
     *
     *  - the source is read SERVER-SIDE from the persisted specialty entry;
     *    a client-submitted LMP is never trusted and an unsaved form value
     *    cannot be adopted;
     *  - the consultation entry is left completely unchanged;
     *  - a conflicting or scan/ART-dated profile is never overwritten;
     *  - the profile LMP is never synced back into the consultation.
     */
    public function adoptMenstrualLmp(
        Request $request,
        Visit $visit,
        GynaecologyConsultationContextService $gynaecology,
        PregnancyProfileService $profiles,
        ActivityLogService $activityLog,
    ) {
        $this->authorizeBridge($request, 'consultation.maternity_context.adopt_lmp');
        $this->authorizeMaternity($request, 'maternity.pregnancy.update');

        // Clinical mutation → requires an active/editable consultation.
        $route = $this->mutableRoute($request, $visit, gynaecology: true);

        $profile = $this->explicitlyLinkedProfileOrFail($route);
        $savedLmp = $gynaecology->savedConsultationLmp($route);
        $state = $gynaecology->lmpAdoptionState($savedLmp, $profile);

        $message = match ($state) {
            GynaecologyWorkspaceViewModel::ADOPT_NO_SOURCE => __('consultation_maternity.lmp.no_saved_lmp'),
            GynaecologyWorkspaceViewModel::ADOPT_CONFLICT => __('consultation_maternity.lmp.conflict'),
            GynaecologyWorkspaceViewModel::ADOPT_DATING_LOCKED => __('consultation_maternity.lmp.dating_locked'),
            default => null,
        };

        if ($message !== null) {
            return $this->failure($request, $message);
        }

        // Already the same date → idempotent success, no write.
        if ($state === GynaecologyWorkspaceViewModel::ADOPT_IDEMPOTENT) {
            return $this->success($request, __('consultation_maternity.lmp.already_matches'));
        }

        $profiles->update($profile, [
            'last_menstrual_period' => $savedLmp->toDateString(),
            'dating_method' => PregnancyDatingMethod::LMP->value,
        ], $request->user());

        $activityLog->log(
            LogModule::CONSULTATION,
            'GYNAECOLOGY_LMP_ADOPTED_FOR_PREGNANCY_DATING',
            [
                'patient_id' => $visit->patient_id,
                'visit_id' => $visit->id,
                'causer' => $request->user(),
                'metadata' => [
                    'consultation_route_id' => $route->id,
                    'pregnancy_profile_id' => $profile->id,
                    'adopted_lmp' => $savedLmp->toDateString(),
                ],
            ],
            $profile,
            'GYNAECOLOGY_LMP_ADOPTED_FOR_PREGNANCY_DATING',
        );

        return $this->success($request, __('consultation_maternity.lmp.adopted'));
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
        $route = $this->mutableRoute($request, $visit);

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
     * CONTEXT-LINK boundary (link / confirm / relink / unlink).
     *
     * Deliberately does NOT require an editable consultation. These actions
     * maintain or correct the encounter's relationship to an existing
     * longitudinal record and create no clinical data, and Phase 14R.2 requires
     * bridge links to remain manageable after consultation completion.
     */
    private function route(Request $request, Visit $visit)
    {
        $this->assertWorkspaceEnabled(eitherWorkspace: true);

        $route = app(\App\Services\ConsultationSessionService::class)
            ->resolveRouteForVisit($visit, $request->integer('consultation_route_id') ?: null);

        if (! $route) {
            abort(422, __('consultation_maternity.no_maternity_context'));
        }

        return $route;
    }

    /**
     * CLINICAL-MUTATION boundary (create profile / record ANC / start labor).
     *
     * Phase 14R.3.1 — these create or mutate longitudinal clinical records, so
     * when launched from the Consultation workspace they require an
     * active/editable consultation. Completed and cancelled sessions are
     * blocked; paused sessions follow the project's existing mutation policy.
     * Clinicians may still reach existing maternity records from a completed
     * consultation, and must start a new consultation (or work in the Maternity
     * module) to record new clinical data.
     *
     * This intentionally reuses the existing consultation mutation guard rather
     * than inventing a parallel rule — it must never be weakened here.
     */
    private function mutableRoute(Request $request, Visit $visit, bool $gynaecology = false)
    {
        $this->assertWorkspaceEnabled($gynaecology);

        try {
            return $this->consultationMutationContext(
                $request,
                $visit,
                'maternity_context.clinical_mutation',
                'consultations.create',
            )->route;
        } catch (ConsultationActionException $e) {
            // Localised explanation; no maternity record and no bridge link are
            // created, and no partial transaction is left behind because the
            // guard runs before any write.
            abort(422, $e->getMessage());
        }
    }

    /**
     * Obstetrics and Gynaecology have independent context flags. Context-link
     * actions are reachable from either workspace, so either flag suffices;
     * a Gynaecology-specific mutation requires the Gynaecology flag.
     */
    private function assertWorkspaceEnabled(bool $gynaecology = false, bool $eitherWorkspace = false): void
    {
        $obstetrics = (bool) config('consultation.maternity_context.obstetrics_workspace_enabled', false);
        $gynae = (bool) config('consultation.maternity_context.gynaecology_context_enabled', false);

        $permitted = match (true) {
            $gynaecology => $gynae,
            $eitherWorkspace => $obstetrics || $gynae,
            default => $obstetrics,
        };

        if (! $permitted) {
            abort(403, __('consultation_maternity.messages.workspace_disabled'));
        }
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
