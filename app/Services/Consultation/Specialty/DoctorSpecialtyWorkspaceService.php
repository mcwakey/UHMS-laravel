<?php

namespace App\Services\Consultation\Specialty;

use App\Data\Consultation\Specialty\DoctorSpecialtyWorkspace;
use App\Data\Consultation\Specialty\ResolvedConsultationSpecialty;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\User;
use App\Models\VisitConsultationRoute;

class DoctorSpecialtyWorkspaceService
{
    public function __construct(
        private readonly DoctorConsultationPreferenceService $preferences,
        private readonly DoctorSpecialtyWorkspaceMetricService $metrics,
        private readonly ConsultationSpecialtyQuickActionRegistry $actions,
    ) {}

    public function build(User $user, $consultation, ResolvedConsultationSpecialty|array $specialtyContext, array $workspacePayload = []): DoctorSpecialtyWorkspace
    {
        try {
            $route = $consultation instanceof VisitConsultationRoute ? $consultation : null;
            $profile = $this->profile($specialtyContext);
            $department = $route?->department
                ?? ($specialtyContext instanceof ResolvedConsultationSpecialty ? $specialtyContext->department : null)
                ?? $user->department;
            $preference = $this->preferences->getOrCreatePreference($user);
            $quickActions = $this->availableActions($profile, $specialtyContext);
            $pinnedKeys = collect($preference->pinned_actions ?? [])
                ->filter(fn ($key) => collect($quickActions)->pluck('key')->contains($key))
                ->values()
                ->all();

            return new DoctorSpecialtyWorkspace(
                doctor: [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'display_name' => 'Dr. '.$user->full_name,
                ],
                profile: $profile ? [
                    'id' => $profile->id,
                    'code' => $profile->code,
                    'name' => $profile->name,
                    'translated_name' => $profile->translatedName(),
                    'icon' => $profile->icon,
                    'color' => $profile->color,
                ] : null,
                department: $department ? [
                    'id' => $department->id,
                    'name' => $department->name,
                ] : null,
                specialty: [
                    'workspace_name' => __('consultation_specialties.workspace.doctor_workspace_name', [
                        'profile' => $profile?->translatedName() ?? __('consultation_specialties.profiles.general_medicine'),
                    ]),
                ],
                metrics: $this->metrics->metricsFor($user, $route, $profile, $department),
                quickActions: $quickActions,
                pinnedActions: $pinnedKeys,
                alerts: $this->alerts($workspacePayload, $quickActions),
                preferences: [
                    'pinned_actions' => $pinnedKeys,
                    'preferred_layout' => $preference->preferred_layout ?: 'default',
                    'compact_mode' => (bool) $preference->compact_mode,
                ],
                todayContext: [
                    'date' => today()->toDateString(),
                    'label' => __('consultation_specialties.workspace.today'),
                ],
                summaryBuilder: $workspacePayload['specialtySummaryBuilder'] ?? ['available' => false],
                readiness: $this->readinessPayload($workspacePayload['specialtyReadiness'] ?? null),
                orderSets: [
                    'available' => ! empty($workspacePayload['specialtyOrderSets'] ?? []),
                    'count' => count($workspacePayload['specialtyOrderSets'] ?? []),
                ],
                isFallback: false,
            );
        } catch (\Throwable) {
            return $this->fallback($user);
        }
    }

    private function availableActions(?ConsultationSpecialtyProfile $profile, ResolvedConsultationSpecialty|array $context): array
    {
        if (! $profile) {
            return [];
        }

        $sectionKeys = collect($context instanceof ResolvedConsultationSpecialty ? $context->sections : data_get($context, 'sections', []))
            ->map(fn ($section) => data_get($section, 'section_key', data_get($section, 'key')))
            ->filter()
            ->all();

        return collect($this->actions->actionsForProfile($profile))
            ->filter(fn ($action) => empty($action['requires_section']) || in_array($action['requires_section'], $sectionKeys, true))
            ->sortBy('priority')
            ->values()
            ->all();
    }

    private function alerts(array $payload, array $actions): array
    {
        $alerts = [];
        $readiness = $this->readinessPayload($payload['specialtyReadiness'] ?? null);
        if (($readiness['blocking_count'] ?? 0) > 0) {
            $alerts[] = $this->alert('readiness_blocks', __('consultation_specialties.workspace.readiness_blocks', ['count' => $readiness['blocking_count']]), '#completionReadinessCard', 'warning');
        } elseif (($readiness['warning_count'] ?? 0) > 0) {
            $alerts[] = $this->alert('readiness_warnings', __('consultation_specialties.workspace.readiness_warnings', ['count' => $readiness['warning_count']]), '#completionReadinessCard', 'info');
        }

        if (! empty($payload['specialtyOrderSets'] ?? [])) {
            $alerts[] = $this->alert('order_sets_available', __('consultation_specialties.workspace.order_sets_available', ['count' => count($payload['specialtyOrderSets'])]), '#order-sets-panel', 'info');
        }

        if (! empty(data_get($payload, 'specialtySummaryBuilder.available'))) {
            $alerts[] = $this->alert('summary_available', __('consultation_specialties.workspace.summary_available'), '#summary-section', 'info');
        }

        return array_slice($alerts, 0, 3);
    }

    private function alert(string $key, string $message, string $target, string $severity): array
    {
        return compact('key', 'message', 'target', 'severity');
    }

    private function readinessPayload($readiness): array
    {
        $array = is_object($readiness) && method_exists($readiness, 'toArray') ? $readiness->toArray() : (array) $readiness;

        return [
            'status' => $array['status'] ?? null,
            'blocking_count' => count($array['blockingItems'] ?? []),
            'warning_count' => count($array['warningItems'] ?? []),
        ];
    }

    private function profile(ResolvedConsultationSpecialty|array $context): ?ConsultationSpecialtyProfile
    {
        if ($context instanceof ResolvedConsultationSpecialty) {
            return $context->profile;
        }

        return ConsultationSpecialtyProfile::query()->find(data_get($context, 'profile.id'));
    }

    private function fallback(User $user): DoctorSpecialtyWorkspace
    {
        return new DoctorSpecialtyWorkspace(
            doctor: ['id' => $user->id, 'name' => $user->full_name, 'display_name' => 'Dr. '.$user->full_name],
            profile: null,
            department: null,
            specialty: null,
            metrics: [],
            quickActions: [],
            pinnedActions: [],
            alerts: [],
            preferences: ['pinned_actions' => [], 'preferred_layout' => 'default', 'compact_mode' => false],
            todayContext: ['date' => today()->toDateString(), 'label' => __('consultation_specialties.workspace.today')],
            summaryBuilder: ['available' => false],
            readiness: ['status' => null, 'blocking_count' => 0, 'warning_count' => 0],
            orderSets: ['available' => false, 'count' => 0],
            isFallback: true,
        );
    }
}
