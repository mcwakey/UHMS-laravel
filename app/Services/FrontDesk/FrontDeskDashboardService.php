<?php

namespace App\Services\FrontDesk;

use App\Enums\FrontDesk\CallDirection;
use App\Enums\FrontDesk\CourierStatus;
use App\Models\FrontDeskCallLog;
use App\Models\FrontDeskCourierHandoff;
use App\Models\FrontDeskCourierLog;
use App\Models\FrontDeskIncidentLog;
use App\Models\FrontDeskLostFoundItem;
use App\Models\FrontDeskShiftHandover;
use App\Models\FrontDeskVisitorLog;
use App\Models\User;
use App\Models\Ward;

/**
 * Aggregates the front desk dashboard cards and recent-activity panels.
 * Read-only: performs no writes and exposes no clinical data.
 */
class FrontDeskDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function metrics(?User $user = null): array
    {
        $recentLimit = (int) config('front_desk.dashboard_recent_limit', 8);

        return [
            // Visitors
            'visitors_inside_count' => FrontDeskVisitorLog::query()->currentlyInside()->count(),
            'visitors_today_count' => FrontDeskVisitorLog::query()->whereDate('time_in', today())->count(),
            'overdue_visitors_count' => FrontDeskVisitorLog::query()->overdue()->count(),

            // Patient-visitor breakdown (Phase 18B)
            'patient_visitors_inside_count' => FrontDeskVisitorLog::query()->currentlyInside()->patientLinked()->count(),
            'facility_visitors_inside_count' => FrontDeskVisitorLog::query()->currentlyInside()->facilityVisitor()->count(),
            'overdue_patient_visitors_count' => FrontDeskVisitorLog::query()->overdue()->patientLinked()->count(),

            // Calls
            'calls_today_count' => FrontDeskCallLog::query()->whereDate('started_at', today())->count(),
            'pending_call_followups_count' => FrontDeskCallLog::query()->pendingFollowUp()->count(),

            // Callback queue (Phase 18C)
            'pending_callbacks_count' => FrontDeskCallLog::query()->pendingCallback()->count(),
            'overdue_callbacks_count' => FrontDeskCallLog::query()->overdueCallback()->count(),
            'callbacks_due_today_count' => FrontDeskCallLog::query()->dueTodayCallback()->count(),
            'assigned_to_me_callbacks_count' => $user
                ? FrontDeskCallLog::query()->pendingCallback()->assignedTo($user->id)->count()
                : 0,

            // Courier workflow (Phase 18C)
            'pending_dispatch_count' => FrontDeskCourierLog::query()->pendingDispatch()->count(),
            'in_transit_couriers_count' => FrontDeskCourierLog::query()->inTransit()->count(),
            'awaiting_handover_count' => FrontDeskCourierLog::query()->awaitingHandover()->count(),
            'overdue_couriers_count' => FrontDeskCourierLog::query()->overdueCourier()->count(),

            'incoming_calls_today' => FrontDeskCallLog::query()
                ->whereDate('started_at', today())
                ->where('direction', CallDirection::INCOMING->value)
                ->count(),
            'outgoing_calls_today' => FrontDeskCallLog::query()
                ->whereDate('started_at', today())
                ->where('direction', CallDirection::OUTGOING->value)
                ->count(),

            // Couriers
            'couriers_today_count' => FrontDeskCourierLog::query()
                ->whereDate('received_or_sent_at', today())
                ->count(),
            'pending_couriers_count' => FrontDeskCourierLog::query()->pendingCourier()->count(),
            'delivered_couriers_today' => FrontDeskCourierLog::query()
                ->where('status', CourierStatus::DELIVERED->value)
                ->whereDate('delivered_at', today())
                ->count(),

            // Recent activity
            'recent_visitors' => FrontDeskVisitorLog::query()
                ->with(['patient', 'department', 'checkedInBy'])
                ->latest('time_in')
                ->limit($recentLimit)
                ->get(),
            'recent_calls' => FrontDeskCallLog::query()
                ->with(['department', 'handledBy'])
                ->latest('started_at')
                ->limit($recentLimit)
                ->get(),
            'recent_couriers' => FrontDeskCourierLog::query()
                ->with(['recipientDepartment'])
                ->latest('received_or_sent_at')
                ->limit($recentLimit)
                ->get(),

            // Ward visitor load + recent patient visitors (Phase 18B)
            'recent_patient_visitors' => FrontDeskVisitorLog::query()
                ->patientLinked()
                ->with(['patient', 'ward'])
                ->latest('time_in')
                ->limit($recentLimit)
                ->get(),

            // Callback queue + courier handoff recents (Phase 18C)
            'recent_pending_callbacks' => FrontDeskCallLog::query()
                ->pendingCallback()
                ->with(['assignedFollowUpUser', 'department'])
                ->orderByRaw('follow_up_due_at is null, follow_up_due_at asc')
                ->limit($recentLimit)
                ->get(),
            'recent_courier_handoffs' => FrontDeskCourierHandoff::query()
                ->with(['courierLog', 'createdBy'])
                ->latest('action_at')
                ->limit($recentLimit)
                ->get(),

            // Handover / lost & found / incident desk (Phase 18E)
            'pending_handovers_count' => FrontDeskShiftHandover::query()->open()->count(),
            'handovers_waiting_acceptance_count' => FrontDeskShiftHandover::query()->pendingAcceptance()->count(),
            'unclaimed_lost_found_count' => FrontDeskLostFoundItem::query()->unclaimed()->count(),
            'lost_found_reported_today_count' => FrontDeskLostFoundItem::query()->whereDate('found_or_reported_at', today())->count(),
            'open_incidents_count' => FrontDeskIncidentLog::query()->open()->count(),
            'critical_open_incidents_count' => FrontDeskIncidentLog::query()->critical()->count(),
            'incidents_reported_today_count' => FrontDeskIncidentLog::query()->whereDate('reported_at', today())->count(),
            'recent_handovers' => FrontDeskShiftHandover::query()
                ->with(['outgoingUser', 'incomingUser'])->latest('shift_date')->latest('id')
                ->limit($recentLimit)->get(),
            'recent_lost_found' => FrontDeskLostFoundItem::query()
                ->latest('found_or_reported_at')->limit($recentLimit)->get(),
            'recent_incidents' => FrontDeskIncidentLog::query()
                ->with(['assignedToUser'])->latest('reported_at')->limit($recentLimit)->get(),
        ] + $this->wardVisitorLoad();
    }

    /**
     * Currently-inside visitor counts grouped by ward. Returns zero/empty
     * gracefully if there are no ward-linked visitors.
     *
     * @return array{wards_with_visitors: int, top_wards_by_active_visitors: \Illuminate\Support\Collection}
     */
    private function wardVisitorLoad(): array
    {
        $counts = FrontDeskVisitorLog::query()
            ->currentlyInside()
            ->whereNotNull('ward_id')
            ->selectRaw('ward_id, COUNT(*) as active_count')
            ->groupBy('ward_id')
            ->orderByDesc('active_count')
            ->get();

        $wardNames = $counts->isEmpty()
            ? collect()
            : Ward::whereIn('id', $counts->pluck('ward_id'))->pluck('name', 'id');

        return [
            'wards_with_visitors' => $counts->count(),
            'top_wards_by_active_visitors' => $counts->take(5)->map(fn ($row) => [
                'ward_id' => $row->ward_id,
                'name' => $wardNames[$row->ward_id] ?? '—',
                'active_count' => (int) $row->active_count,
            ])->values(),
        ];
    }
}

