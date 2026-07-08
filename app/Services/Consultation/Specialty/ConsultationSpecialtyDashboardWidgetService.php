<?php

namespace App\Services\Consultation\Specialty;

use App\Models\User;

class ConsultationSpecialtyDashboardWidgetService
{
    public function __construct(private readonly ConsultationSpecialtyAnalyticsService $analytics) {}

    public function widgetPayload(array $filters = [], ?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user || ! $user->can('reports.view')) {
            return ['visible' => false];
        }

        $payload = $this->analytics->dashboardPayload($filters);

        return [
            'visible' => true,
            'filters' => $payload['filters'],
            'summary' => $payload['summary'],
            'top_specialties' => $payload['volume']['by_specialty']->take(5)->values(),
            'readiness' => $payload['readiness'],
            'billing' => $payload['billing'],
            'route' => route('admin.reports.consultation-specialties.index', array_intersect_key($payload['filters'], array_flip(['date_from', 'date_to']))),
        ];
    }
}
