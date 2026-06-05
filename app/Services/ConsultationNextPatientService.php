<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\QueueEntry;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Services\Billing\BillingPolicyService;
use Illuminate\Support\Facades\DB;

class ConsultationNextPatientService
{
    public function __construct(
        private ConsultationRouteService $routes,
        private BillingPolicyService $billingPolicy,
        private ActivityLogService $logger,
    ) {}

    public function preview(VisitConsultationRoute $currentRoute, User $user): ?array
    {
        $candidate = $this->findCandidate($currentRoute, $user);
        if (! $candidate) {
            return null;
        }

        $entry = $candidate['entry'];
        $route = $candidate['route'];
        $visit = $route->visit;
        $patient = $visit?->patient;
        $payment = $this->paymentReadiness($route, $user);
        $priority = $entry->priority ?? $visit?->priority;
        $priorityValue = $priority instanceof \BackedEnum ? $priority->value : (string) $priority;

        return [
            'entry_id' => $entry->id,
            'route_id' => $route->id,
            'visit_id' => $visit?->id,
            'patient_name' => $patient?->full_name ?? 'Patient #'.$route->patient_id,
            'patient_number' => $patient?->patient_number,
            'visit_number' => $visit?->visit_number,
            'age' => $visit?->patient_age ?? $patient?->age,
            'gender' => $patient?->gender instanceof \BackedEnum ? $patient->gender->value : $patient?->gender,
            'waiting_minutes' => $entry->created_at ? max(0, (int) $entry->created_at->diffInMinutes(now())) : 0,
            'queue_number' => $entry->queue_number,
            'priority' => is_object($priority) && method_exists($priority, 'label') ? $priority->label() : ucfirst($priorityValue ?: 'normal'),
            'priority_color' => is_object($priority) && method_exists($priority, 'color') ? $priority->color() : 'secondary',
            'department' => $route->department?->name,
            'services' => $route->routeServices
                ->map(fn ($routeService) => $routeService->service?->name)
                ->filter()
                ->values()
                ->all(),
            'doctor' => $route->doctor?->full_name,
            'payment_allowed' => $payment['allowed'],
            'payment_message' => $payment['message'],
        ];
    }

    public function openNext(VisitConsultationRoute $currentRoute, User $user, bool $completeCurrent = false): VisitConsultationRoute
    {
        $currentRoute->loadMissing(['visit', 'department']);

        return DB::transaction(function () use ($currentRoute, $user, $completeCurrent) {
            $candidate = $this->findCandidate($currentRoute, $user);
            if (! $candidate) {
                throw new \RuntimeException('The next patient is no longer available. Please refresh the queue.');
            }

            $entry = QueueEntry::whereKey($candidate['entry']->id)->lockForUpdate()->first();
            if (! $entry || $entry->status !== 'waiting') {
                throw new \RuntimeException('The next patient is no longer available. Please refresh the queue.');
            }

            /** @var VisitConsultationRoute|null $nextRoute */
            $nextRoute = VisitConsultationRoute::query()
                ->whereKey($candidate['route']->id)
                ->lockForUpdate()
                ->first();

            if (! $nextRoute || ! in_array($nextRoute->status, [VisitConsultationRoute::STATUS_PENDING, VisitConsultationRoute::STATUS_ACTIVE], true)) {
                throw new \RuntimeException('The next patient is no longer available. Please refresh the queue.');
            }

            $nextRoute->loadMissing('visit');
            if (! $this->routeHasWaitingOutpatientVisit($nextRoute)) {
                throw new \RuntimeException('The next patient is no longer available. Please refresh the queue.');
            }

            if (! $this->routeAssignedToUserOrOpen($nextRoute, $user)) {
                throw new \RuntimeException('The next patient is assigned to another doctor.');
            }

            $payment = $this->paymentReadiness($nextRoute, $user);
            if (! $payment['allowed']) {
                throw new \RuntimeException($payment['message']);
            }

            if ($completeCurrent) {
                $this->routes->completeRoute($currentRoute, $user, 'Completed before opening next patient.');
            }

            $activatedRoute = $this->routes->activateRoute($nextRoute, $user);

            $this->logger->log(
                LogModule::CONSULTATION,
                'NEXT_PATIENT_OPENED',
                [
                    'patient_id' => $activatedRoute->patient_id,
                    'visit_id' => $activatedRoute->visit_id,
                    'consultation_route_id' => $activatedRoute->id,
                    'department_id' => $activatedRoute->department_id,
                    'metadata' => [
                        'previous_visit_id' => $currentRoute->visit_id,
                        'previous_consultation_route_id' => $currentRoute->id,
                        'completed_previous' => $completeCurrent,
                    ],
                ],
                $activatedRoute,
                'Next patient opened from consultation queue.',
            );

            return $activatedRoute->fresh(['visit', 'department', 'routeServices.service', 'doctor']);
        });
    }

    private function findCandidate(VisitConsultationRoute $currentRoute, User $user): ?array
    {
        $currentRoute->loadMissing('department');
        if (! $currentRoute->department_id) {
            return null;
        }

        $routeConstraint = function ($routeQuery) use ($currentRoute, $user) {
            $routeQuery
                ->where('department_id', $currentRoute->department_id)
                ->whereIn('status', [VisitConsultationRoute::STATUS_PENDING, VisitConsultationRoute::STATUS_ACTIVE])
                ->where('visit_id', '!=', $currentRoute->visit_id)
                ->where(function ($doctorQuery) use ($user) {
                    $doctorQuery->whereNull('doctor_id')
                        ->orWhere('doctor_id', $user->id);
                })
                ->orderBy('id');
        };

        $entries = QueueEntry::query()
            ->with([
                'visit.patient',
                'visit.admission',
                'visit.emergencyCase',
                'visit.consultationRoutes' => $routeConstraint,
                'visit.consultationRoutes.department',
                'visit.consultationRoutes.doctor',
                'visit.consultationRoutes.routeServices.service',
                'visit.consultationRoutes.routeServices.invoiceItem.visit.admission',
                'visit.consultationRoutes.routeServices.invoiceItem.visit.emergencyCase',
            ])
            ->where('department_id', $currentRoute->department_id)
            ->where('status', 'waiting')
            ->whereHas('visit', function ($visitQuery) use ($currentRoute) {
                $visitQuery
                    ->where('id', '!=', $currentRoute->visit_id)
                    ->where('visit_type', VisitType::OUTPATIENT->value)
                    ->where('status', VisitStatus::WAITING_CONSULTATION->value);
            })
            ->whereHas('visit.consultationRoutes', $routeConstraint)
            ->orderBy('queue_number')
            ->orderBy('created_at')
            ->limit(50)
            ->get();

        foreach ($entries as $entry) {
            $route = $entry->visit?->consultationRoutes?->first();
            if ($route) {
                return ['entry' => $entry, 'route' => $route];
            }
        }

        return null;
    }

    private function routeHasWaitingOutpatientVisit(VisitConsultationRoute $route): bool
    {
        $visit = $route->visit;

        return $visit
            && $visit->visit_type === VisitType::OUTPATIENT
            && $visit->status === VisitStatus::WAITING_CONSULTATION;
    }

    private function paymentReadiness(VisitConsultationRoute $route, User $user): array
    {
        $route->loadMissing([
            'visit.admission',
            'visit.emergencyCase',
            'routeServices.invoiceItem.visit.admission',
            'routeServices.invoiceItem.visit.emergencyCase',
        ]);

        foreach ($route->routeServices as $routeService) {
            if (! $routeService->invoiceItem) {
                continue;
            }

            $decision = $this->billingPolicy->getInvoiceItemPolicy($routeService->invoiceItem, $user);
            if (! $decision->allowed) {
                return [
                    'allowed' => false,
                    'message' => $decision->message,
                ];
            }
        }

        return [
            'allowed' => true,
            'message' => 'Payment ready',
        ];
    }

    private function routeAssignedToUserOrOpen(VisitConsultationRoute $route, User $user): bool
    {
        return ! $route->doctor_id || (int) $route->doctor_id === (int) $user->id;
    }
}
