<?php

namespace App\Services\FrontDesk;

use App\Enums\FrontDesk\CourierStatus;
use App\Models\FrontDeskCallLog;
use App\Models\FrontDeskCourierHandoff;
use App\Models\FrontDeskCourierLog;
use App\Models\FrontDeskIncidentLog;
use App\Models\FrontDeskLostFoundItem;
use App\Models\FrontDeskShiftHandover;
use App\Models\FrontDeskVisitorLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Front Desk operational reporting (Phase 18D). Aggregate-only and privacy-safe:
 * it reads no clinical data and returns counts / safe identifiers. Every metric
 * degrades gracefully to zero / empty. Default window is the last 30 days.
 */
class FrontDeskReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function normaliseFilters(array $filters): array
    {
        $from = ! empty($filters['date_from']) ? Carbon::parse($filters['date_from'])->startOfDay() : now()->subDays(30)->startOfDay();
        $to = ! empty($filters['date_to']) ? Carbon::parse($filters['date_to'])->endOfDay() : now()->endOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'department_id' => $filters['department_id'] ?? null,
            'ward_id' => $filters['ward_id'] ?? null,
            'visitor_context' => $filters['visitor_context'] ?? null,
            'visitor_status' => $filters['visitor_status'] ?? null,
            'call_direction' => $filters['call_direction'] ?? null,
            'call_category' => $filters['call_category'] ?? null,
            'call_outcome' => $filters['call_outcome'] ?? null,
            'follow_up_status' => $filters['follow_up_status'] ?? null,
            'courier_direction' => $filters['courier_direction'] ?? null,
            'courier_type' => $filters['courier_type'] ?? null,
            'courier_status' => $filters['courier_status'] ?? null,
            'handover_status' => $filters['handover_status'] ?? null,
        ];
    }

    /* ── Base queries (date + shared filters applied) ────────────── */

    private function visitors(array $f): Builder
    {
        return FrontDeskVisitorLog::query()
            ->whereBetween('time_in', [$f['date_from'] . ' 00:00:00', $f['date_to'] . ' 23:59:59'])
            ->when($f['department_id'], fn ($q, $v) => $q->where('department_id', $v))
            ->when($f['ward_id'], fn ($q, $v) => $q->where('ward_id', $v))
            ->when($f['visitor_context'], fn ($q, $v) => $q->where('visitor_context', $v))
            ->when($f['visitor_status'], fn ($q, $v) => $q->where('status', $v));
    }

    private function calls(array $f): Builder
    {
        return FrontDeskCallLog::query()
            ->whereBetween('started_at', [$f['date_from'] . ' 00:00:00', $f['date_to'] . ' 23:59:59'])
            ->when($f['department_id'], fn ($q, $v) => $q->where('department_id', $v))
            ->when($f['call_direction'], fn ($q, $v) => $q->where('direction', $v))
            ->when($f['call_category'], fn ($q, $v) => $q->where('category', $v))
            ->when($f['call_outcome'], fn ($q, $v) => $q->where('outcome', $v))
            ->when($f['follow_up_status'], fn ($q, $v) => $q->where('follow_up_status', $v));
    }

    private function couriers(array $f): Builder
    {
        return FrontDeskCourierLog::query()
            ->whereBetween('received_or_sent_at', [$f['date_from'] . ' 00:00:00', $f['date_to'] . ' 23:59:59'])
            ->when($f['department_id'], fn ($q, $v) => $q->where('recipient_department_id', $v))
            ->when($f['courier_direction'], fn ($q, $v) => $q->where('direction', $v))
            ->when($f['courier_type'], fn ($q, $v) => $q->where('courier_type', $v))
            ->when($f['courier_status'], fn ($q, $v) => $q->where('status', $v))
            ->when($f['handover_status'], fn ($q, $v) => $q->where('handover_status', $v));
    }

    /* ── Summary ─────────────────────────────────────────────────── */

    public function summary(array $filters = []): array
    {
        $f = $this->normaliseFilters($filters);

        return [
            'total_visitors' => (clone $this->visitors($f))->count(),
            'patient_visitors' => (clone $this->visitors($f))->whereNotNull('patient_id')->count(),
            'facility_visitors' => (clone $this->visitors($f))->whereNull('patient_id')->count(),
            'currently_inside' => FrontDeskVisitorLog::query()->currentlyInside()->count(),
            'overdue_visitors' => FrontDeskVisitorLog::query()->overdue()->count(),
            'total_calls' => (clone $this->calls($f))->count(),
            'incoming_calls' => (clone $this->calls($f))->where('direction', 'incoming')->count(),
            'outgoing_calls' => (clone $this->calls($f))->where('direction', 'outgoing')->count(),
            'pending_callbacks' => FrontDeskCallLog::query()->pendingCallback()->count(),
            'overdue_callbacks' => FrontDeskCallLog::query()->overdueCallback()->count(),
            'total_couriers' => (clone $this->couriers($f))->count(),
            'pending_couriers' => (clone $this->couriers($f))->pendingCourier()->count(),
            'in_transit_couriers' => (clone $this->couriers($f))->inTransit()->count(),
            'delivered_couriers' => (clone $this->couriers($f))->where('status', CourierStatus::DELIVERED->value)->count(),
            'returned_couriers' => (clone $this->couriers($f))->where('status', CourierStatus::RETURNED->value)->count(),
        ];
    }

    /* ── Visitor report ──────────────────────────────────────────── */

    public function visitorMetrics(array $filters = []): array
    {
        $f = $this->normaliseFilters($filters);

        return [
            'visitors_by_context' => $this->countBy($this->visitors($f), 'visitor_context', $this->enumLabel(\App\Enums\FrontDesk\VisitorContext::class)),
            'visitors_by_status' => $this->countBy($this->visitors($f), 'status', $this->enumLabel(\App\Enums\FrontDesk\VisitorStatus::class)),
            'visitors_by_ward' => $this->countByRelation($this->visitors($f)->whereNotNull('ward_id'), 'ward_id', \App\Models\Ward::class, 'name'),
            'visitors_by_department' => $this->countByRelation($this->visitors($f)->whereNotNull('department_id'), 'department_id', \App\Models\Department::class, 'name'),
            'daily_visitor_trend' => $this->dailyTrend($this->visitors($f), 'time_in'),
            'current_visitors' => FrontDeskVisitorLog::query()->currentlyInside()->count(),
            'overdue_visitors' => FrontDeskVisitorLog::query()->overdue()->count(),
            'top_patients_by_visitor_count' => $this->topPatientsByVisitors($f),
        ];
    }

    /* ── Call report ─────────────────────────────────────────────── */

    public function callMetrics(array $filters = []): array
    {
        $f = $this->normaliseFilters($filters);

        return [
            'calls_by_direction' => $this->countBy($this->calls($f), 'direction', $this->enumLabel(\App\Enums\FrontDesk\CallDirection::class)),
            'calls_by_category' => $this->countBy($this->calls($f), 'category', $this->enumLabel(\App\Enums\FrontDesk\CallCategory::class)),
            'calls_by_outcome' => $this->countBy($this->calls($f), 'outcome', $this->enumLabel(\App\Enums\FrontDesk\CallOutcome::class)),
            'calls_by_department' => $this->countByRelation($this->calls($f)->whereNotNull('department_id'), 'department_id', \App\Models\Department::class, 'name'),
            'calls_by_handler' => $this->countByUser($this->calls($f), 'handled_by'),
            'daily_call_trend' => $this->dailyTrend($this->calls($f), 'started_at'),
        ];
    }

    /* ── Callback report ─────────────────────────────────────────── */

    public function callbackMetrics(array $filters = []): array
    {
        $f = $this->normaliseFilters($filters);
        $flagged = (clone $this->calls($f))->where('follow_up_required', true);

        $completed = (clone $flagged)->where('follow_up_status', FrontDeskCallLog::FOLLOW_UP_COMPLETED)->count();
        $total = (clone $flagged)->count();

        return [
            'callbacks_by_status' => $this->countBy((clone $flagged)->whereNotNull('follow_up_status'), 'follow_up_status', $this->enumLabel(\App\Enums\FrontDesk\CallFollowUpStatus::class)),
            'pending_callbacks' => FrontDeskCallLog::query()->pendingCallback()->count(),
            'overdue_callbacks' => FrontDeskCallLog::query()->overdueCallback()->count(),
            'callbacks_due_today' => FrontDeskCallLog::query()->dueTodayCallback()->count(),
            'callbacks_by_assignee' => $this->countByUser((clone $flagged)->whereNotNull('assigned_follow_up_user_id'), 'assigned_follow_up_user_id'),
            'callback_completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0.0,
        ];
    }

    /* ── Courier report ──────────────────────────────────────────── */

    public function courierMetrics(array $filters = []): array
    {
        $f = $this->normaliseFilters($filters);

        return [
            'couriers_by_direction' => $this->countBy($this->couriers($f), 'direction', $this->enumLabel(\App\Enums\FrontDesk\CourierDirection::class)),
            'couriers_by_type' => $this->countBy($this->couriers($f), 'courier_type', $this->enumLabel(\App\Enums\FrontDesk\CourierType::class)),
            'couriers_by_status' => $this->countBy($this->couriers($f), 'status', $this->enumLabel(\App\Enums\FrontDesk\CourierStatus::class)),
            'couriers_by_handover_status' => $this->countBy((clone $this->couriers($f))->whereNotNull('handover_status'), 'handover_status', $this->enumLabel(\App\Enums\FrontDesk\CourierHandoverStatus::class)),
            'couriers_by_department' => $this->countByRelation($this->couriers($f)->whereNotNull('recipient_department_id'), 'recipient_department_id', \App\Models\Department::class, 'name'),
            'daily_courier_trend' => $this->dailyTrend($this->couriers($f), 'received_or_sent_at'),
            'pending_dispatch' => (clone $this->couriers($f))->pendingDispatch()->count(),
            'in_transit' => (clone $this->couriers($f))->inTransit()->count(),
            'delivered_today' => FrontDeskCourierLog::query()->where('status', CourierStatus::DELIVERED->value)->whereDate('delivered_at', today())->count(),
            'returned_items' => (clone $this->couriers($f))->where('status', CourierStatus::RETURNED->value)->count(),
        ];
    }

    /* ── Courier workflow report ─────────────────────────────────── */

    public function courierWorkflowMetrics(array $filters = []): array
    {
        $f = $this->normaliseFilters($filters);
        $handoffs = FrontDeskCourierHandoff::query()
            ->whereBetween('action_at', [$f['date_from'] . ' 00:00:00', $f['date_to'] . ' 23:59:59']);

        return [
            'handoffs_by_action' => $this->countBy((clone $handoffs), 'action', $this->enumLabel(\App\Enums\FrontDesk\CourierHandoffAction::class)),
            'handoffs_by_department' => $this->countByRelation((clone $handoffs)->whereNotNull('to_department_id'), 'to_department_id', \App\Models\Department::class, 'name'),
            'handoffs_by_user' => $this->countByUser((clone $handoffs)->whereNotNull('created_by'), 'created_by'),
            'recent_handoffs' => (clone $handoffs)->with(['courierLog', 'createdBy', 'toDepartment'])->latest('action_at')->limit(15)->get(),
        ];
    }

    /* ── Facility report (handover / lost & found / incident) ────── */

    public function facilityMetrics(array $filters = []): array
    {
        $f = $this->normaliseFilters($filters);
        $handovers = FrontDeskShiftHandover::query()->whereBetween('shift_date', [$f['date_from'], $f['date_to']]);
        $items = FrontDeskLostFoundItem::query()->whereBetween('found_or_reported_at', [$f['date_from'] . ' 00:00:00', $f['date_to'] . ' 23:59:59']);
        $incidents = FrontDeskIncidentLog::query()->whereBetween('reported_at', [$f['date_from'] . ' 00:00:00', $f['date_to'] . ' 23:59:59']);

        return [
            'handovers_by_status' => $this->countBy((clone $handovers), 'status', $this->enumLabel(\App\Enums\FrontDesk\ShiftHandoverStatus::class)),
            'lost_found_by_status' => $this->countBy((clone $items), 'item_status', $this->enumLabel(\App\Enums\FrontDesk\LostFoundStatus::class)),
            'lost_found_by_category' => $this->countBy((clone $items), 'item_category', $this->enumLabel(\App\Enums\FrontDesk\LostFoundCategory::class)),
            'incidents_by_type' => $this->countBy((clone $incidents), 'incident_type', $this->enumLabel(\App\Enums\FrontDesk\FrontDeskIncidentType::class)),
            'incidents_by_severity' => $this->countBy((clone $incidents), 'severity', $this->enumLabel(\App\Enums\FrontDesk\FrontDeskIncidentSeverity::class)),
            'incidents_by_status' => $this->countBy((clone $incidents), 'status', $this->enumLabel(\App\Enums\FrontDesk\FrontDeskIncidentStatus::class)),
            'daily_incident_trend' => $this->dailyTrend((clone $incidents), 'reported_at'),
            'open_incidents' => FrontDeskIncidentLog::query()->open()->count(),
            'critical_open_incidents' => FrontDeskIncidentLog::query()->critical()->count(),
            'unclaimed_lost_found' => FrontDeskLostFoundItem::query()->unclaimed()->count(),
        ];
    }

    public function dailyTrends(array $filters = []): array
    {
        $f = $this->normaliseFilters($filters);

        return [
            'visitors' => $this->dailyTrend($this->visitors($f), 'time_in'),
            'calls' => $this->dailyTrend($this->calls($f), 'started_at'),
            'couriers' => $this->dailyTrend($this->couriers($f), 'received_or_sent_at'),
        ];
    }

    /**
     * Everything the report page needs in one payload.
     */
    public function dashboardPayload(array $filters = []): array
    {
        return [
            'filters' => $this->normaliseFilters($filters),
            'summary' => $this->summary($filters),
            'visitors' => $this->visitorMetrics($filters),
            'calls' => $this->callMetrics($filters),
            'callbacks' => $this->callbackMetrics($filters),
            'couriers' => $this->courierMetrics($filters),
            'workflow' => $this->courierWorkflowMetrics($filters),
            'facility' => $this->facilityMetrics($filters),
            'trends' => $this->dailyTrends($filters),
        ];
    }

    /* ── CSV export datasets (privacy-safe columns only) ─────────── */

    public const EXPORT_TYPES = [
        'summary', 'visitors', 'visitor_currently_inside', 'visitor_overdue',
        'calls', 'callbacks', 'couriers', 'courier_workflow',
        'handovers', 'lost_found', 'incidents',
    ];

    /**
     * Build a {columns, rows} dataset for a CSV export type. Only safe,
     * non-clinical columns are ever emitted; phone numbers are masked.
     *
     * @return array{columns: array<int, string>, rows: iterable}
     */
    public function exportDataset(string $type, array $filters): array
    {
        $f = $this->normaliseFilters($filters);

        return match ($type) {
            'summary' => $this->summaryDataset($filters),
            'visitors' => $this->visitorDataset($this->visitors($f)),
            'visitor_currently_inside' => $this->visitorDataset(FrontDeskVisitorLog::query()->currentlyInside()),
            'visitor_overdue' => $this->visitorDataset(FrontDeskVisitorLog::query()->overdue()),
            'calls' => $this->callDataset($this->calls($f)),
            'callbacks' => $this->callbackDataset((clone $this->calls($f))->where('follow_up_required', true)),
            'couriers' => $this->courierDataset($this->couriers($f)),
            'courier_workflow' => $this->courierWorkflowDataset($f),
            'handovers' => $this->handoverDataset($f),
            'lost_found' => $this->lostFoundDataset($f),
            'incidents' => $this->incidentDataset($f),
            default => ['columns' => [], 'rows' => []],
        };
    }

    private function handoverDataset(array $f): array
    {
        $rows = FrontDeskShiftHandover::query()
            ->whereBetween('shift_date', [$f['date_from'], $f['date_to']])
            ->with(['outgoingUser', 'incomingUser', 'department'])
            ->latest('shift_date')->limit(5000)->get()
            ->map(fn (FrontDeskShiftHandover $h) => [
                $h->shift_date?->toDateString(),
                $h->shift_name,
                $h->status?->value,
                $h->outgoingUser?->full_name,
                $h->incomingUser?->full_name,
                $h->department?->name,
                $h->visitors_inside_count,
                $h->pending_callbacks_count,
                $h->pending_couriers_count,
                $h->open_incidents_count,
                $h->lost_found_unclaimed_count,
                $h->accepted_at?->format('Y-m-d H:i'),
            ]);

        return [
            'columns' => ['date', 'shift_name', 'status', 'outgoing_user', 'incoming_user', 'department', 'visitors_inside', 'pending_callbacks', 'pending_couriers', 'open_incidents', 'unclaimed_lost_found', 'accepted_at'],
            'rows' => $rows,
        ];
    }

    private function lostFoundDataset(array $f): array
    {
        $rows = FrontDeskLostFoundItem::query()
            ->whereBetween('found_or_reported_at', [$f['date_from'] . ' 00:00:00', $f['date_to'] . ' 23:59:59'])
            ->latest('found_or_reported_at')->limit(5000)->get()
            ->map(fn (FrontDeskLostFoundItem $i) => [
                $i->found_or_reported_at?->toDateString(),
                $i->reference_number,
                $i->item_status?->value,
                $i->item_category?->value,
                $i->item_description,
                $i->found_location,
                $i->stored_location,
                $this->maskPhone($i->reported_by_phone),
                $this->maskPhone($i->claimed_by_phone),
                $i->claimed_at?->format('Y-m-d H:i'),
                $i->released_at?->format('Y-m-d H:i'),
            ]);

        return [
            'columns' => ['date', 'reference_number', 'item_status', 'item_category', 'item_description', 'found_location', 'stored_location', 'reported_phone_masked', 'claimed_phone_masked', 'claimed_at', 'released_at'],
            'rows' => $rows,
        ];
    }

    private function incidentDataset(array $f): array
    {
        // Summary / status fields only — the full narrative description is
        // intentionally NOT exported.
        $rows = FrontDeskIncidentLog::query()
            ->whereBetween('reported_at', [$f['date_from'] . ' 00:00:00', $f['date_to'] . ' 23:59:59'])
            ->with(['department', 'assignedToUser', 'escalatedToUser'])
            ->latest('reported_at')->limit(5000)->get()
            ->map(fn (FrontDeskIncidentLog $i) => [
                $i->reported_at?->toDateString(),
                $i->incident_number,
                $i->incident_type?->value,
                $i->severity?->value,
                $i->status?->value,
                $i->location,
                $i->department?->name,
                $this->maskPhone($i->reported_by_phone),
                $i->assignedToUser?->full_name,
                $i->escalatedToUser?->full_name,
                $i->resolved_at?->format('Y-m-d H:i'),
            ]);

        return [
            'columns' => ['date', 'incident_number', 'incident_type', 'severity', 'status', 'location', 'department', 'reported_phone_masked', 'assigned_to', 'escalated_to', 'resolved_at'],
            'rows' => $rows,
        ];
    }

    private function summaryDataset(array $filters): array
    {
        $rows = [];
        foreach ($this->summary($filters) as $key => $value) {
            $rows[] = [$key, $value];
        }

        return ['columns' => ['Metric', 'Value'], 'rows' => $rows];
    }

    private function visitorDataset(Builder $query): array
    {
        $rows = $query->with(['patient', 'ward', 'bed', 'department', 'checkedInBy', 'checkedOutBy'])
            ->latest('time_in')->limit(5000)->get()
            ->map(fn (FrontDeskVisitorLog $v) => [
                $v->time_in?->toDateString(),
                $v->time_in?->format('H:i'),
                $v->time_out?->format('Y-m-d H:i'),
                $v->status?->value,
                $v->visitor_context?->value,
                $v->visitor_name,
                $this->maskPhone($v->visitor_phone),
                $v->badge_number,
                $v->patient?->patient_number,
                $v->patient?->full_name,
                $v->ward?->name,
                $v->bed?->bed_number,
                $v->department?->name,
                $v->relationship_to_patient,
                $v->durationMinutes(),
                $v->isOverdue() ? 'yes' : 'no',
                $v->checkedInBy?->full_name,
                $v->checkedOutBy?->full_name,
            ]);

        return [
            'columns' => ['date', 'time_in', 'time_out', 'status', 'visitor_context', 'visitor_name', 'visitor_phone_masked', 'badge_number', 'patient_number', 'patient_name_safe', 'ward', 'bed', 'department', 'relationship_to_patient', 'duration_minutes', 'overdue', 'checked_in_by', 'checked_out_by'],
            'rows' => $rows,
        ];
    }

    private function callDataset(Builder $query): array
    {
        $rows = $query->with(['department', 'handledBy', 'assignedFollowUpUser'])
            ->latest('started_at')->limit(5000)->get()
            ->map(fn (FrontDeskCallLog $c) => [
                $c->started_at?->toDateString(),
                $c->started_at?->format('Y-m-d H:i'),
                $c->ended_at?->format('Y-m-d H:i'),
                $c->direction?->value,
                $c->category?->value,
                $c->outcome?->value,
                $c->department?->name,
                $c->caller_name ?: $c->recipient_name,
                $this->maskPhone($c->phone_number),
                $c->handledBy?->full_name,
                $c->follow_up_required ? 'yes' : 'no',
                $c->follow_up_status,
                $c->assignedFollowUpUser?->full_name,
                $c->follow_up_due_at?->format('Y-m-d H:i'),
            ]);

        return [
            'columns' => ['date', 'started_at', 'ended_at', 'direction', 'category', 'outcome', 'department', 'caller_or_recipient', 'phone_masked', 'handled_by', 'follow_up_required', 'follow_up_status', 'assigned_follow_up_user', 'follow_up_due_at'],
            'rows' => $rows,
        ];
    }

    private function callbackDataset(Builder $query): array
    {
        $rows = $query->with(['department', 'assignedFollowUpUser'])
            ->latest('started_at')->limit(5000)->get()
            ->map(fn (FrontDeskCallLog $c) => [
                $c->started_at?->toDateString(),
                'CALL-' . $c->id,
                $c->direction?->value,
                $c->category?->value,
                $c->department?->name,
                $c->follow_up_status,
                $c->assignedFollowUpUser?->full_name,
                $c->follow_up_due_at?->format('Y-m-d H:i'),
                $c->follow_up_completed_at?->format('Y-m-d H:i'),
                $c->follow_up_cancelled_at?->format('Y-m-d H:i'),
                $c->isOverdueCallback() ? 'yes' : 'no',
            ]);

        return [
            'columns' => ['date', 'call_reference', 'direction', 'category', 'department', 'follow_up_status', 'assigned_user', 'follow_up_due_at', 'completed_at', 'cancelled_at', 'overdue'],
            'rows' => $rows,
        ];
    }

    private function courierDataset(Builder $query): array
    {
        $rows = $query->with(['recipientDepartment'])
            ->latest('received_or_sent_at')->limit(5000)->get()
            ->map(fn (FrontDeskCourierLog $c) => [
                $c->received_or_sent_at?->toDateString(),
                $c->received_or_sent_at?->format('Y-m-d H:i'),
                $c->direction?->value,
                $c->courier_type?->value,
                $c->status?->value,
                $c->handover_status?->value,
                $c->sender_name,
                $c->recipient_name,
                $c->recipientDepartment?->name,
                $c->courier_company,
                $c->tracking_number,
                $c->reference_number,
                $c->proof_reference,
                $c->delivered_at?->format('Y-m-d H:i'),
            ]);

        return [
            'columns' => ['date', 'received_or_sent_at', 'direction', 'courier_type', 'status', 'handover_status', 'sender', 'recipient', 'recipient_department', 'courier_company', 'tracking_number', 'reference_number', 'proof_reference', 'delivered_at'],
            'rows' => $rows,
        ];
    }

    private function courierWorkflowDataset(array $f): array
    {
        $rows = FrontDeskCourierHandoff::query()
            ->whereBetween('action_at', [$f['date_from'] . ' 00:00:00', $f['date_to'] . ' 23:59:59'])
            ->with(['courierLog', 'fromDepartment', 'toDepartment', 'fromUser', 'toUser', 'createdBy'])
            ->latest('action_at')->limit(5000)->get()
            ->map(fn (FrontDeskCourierHandoff $h) => [
                $h->action_at?->toDateString(),
                'COURIER-' . $h->courier_log_id,
                $h->courierLog?->tracking_number,
                $h->action?->value,
                $h->action_at?->format('Y-m-d H:i'),
                $h->fromDepartment?->name,
                $h->toDepartment?->name,
                $h->fromUser?->full_name,
                $h->toUser?->full_name,
                $h->createdBy?->full_name,
            ]);

        return [
            'columns' => ['date', 'courier_reference', 'tracking_number', 'action', 'action_at', 'from_department', 'to_department', 'from_user', 'to_user', 'created_by'],
            'rows' => $rows,
        ];
    }

    /** Mask a phone number: keep first 3 + last 2 digits, star the middle. */
    private function maskPhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }
        if (strlen($phone) <= 5) {
            return str_repeat('*', max(0, strlen($phone) - 2)) . substr($phone, -2);
        }

        return substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 5) . substr($phone, -2);
    }

    /* ── Shared aggregation helpers ──────────────────────────────── */

    /**
     * Count grouped by a column, returned as a uniform list of {label, count}.
     * An optional labeler resolves a raw value (e.g. an enum value) to a
     * translated label for display.
     *
     * @return array<int, array{label: string, count: int}>
     */
    private function countBy(Builder $query, string $column, ?callable $labeler = null): array
    {
        return $query->selectRaw("{$column} as k, COUNT(*) as c")
            ->groupBy($column)
            ->orderByDesc('c')
            ->get()
            ->map(fn ($row) => [
                'label' => $labeler ? (string) $labeler((string) $row->k) : (string) $row->k,
                'count' => (int) $row->c,
            ])
            ->all();
    }

    private function enumLabel(string $enumClass): callable
    {
        return fn (string $value) => $enumClass::tryFrom($value)?->translatedLabel() ?? $value;
    }

    /** @return array<int, array{label: string, count: int}> */
    private function countByRelation(Builder $query, string $column, string $modelClass, string $labelColumn): array
    {
        $counts = $query->selectRaw("{$column} as k, COUNT(*) as c")->groupBy($column)->orderByDesc('c')->pluck('c', 'k');
        if ($counts->isEmpty()) {
            return [];
        }
        $labels = $modelClass::whereIn('id', $counts->keys())->pluck($labelColumn, 'id');

        return $counts->map(fn ($c, $id) => ['label' => $labels[$id] ?? '—', 'count' => (int) $c])->values()->all();
    }

    /** @return array<int, array{label: string, count: int}> */
    private function countByUser(Builder $query, string $column): array
    {
        $counts = $query->selectRaw("{$column} as k, COUNT(*) as c")->groupBy($column)->orderByDesc('c')->pluck('c', 'k');
        if ($counts->isEmpty()) {
            return [];
        }
        $users = \App\Models\User::whereIn('id', $counts->keys())->get(['id', 'first_name', 'last_name'])->keyBy('id');

        return $counts->map(fn ($c, $id) => ['label' => $users[$id]?->full_name ?? '—', 'count' => (int) $c])->values()->all();
    }

    /** @return array<int, array{date: string, count: int}> */
    private function dailyTrend(Builder $query, string $column): array
    {
        return $query->selectRaw("DATE({$column}) as d, COUNT(*) as c")
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->map(fn ($row) => ['date' => (string) $row->d, 'count' => (int) $row->c])
            ->all();
    }

    /** @return array<int, array{patient_number: string, patient_name: string, count: int}> */
    private function topPatientsByVisitors(array $f): array
    {
        $counts = (clone $this->visitors($f))->whereNotNull('patient_id')
            ->selectRaw('patient_id, COUNT(*) as c')->groupBy('patient_id')->orderByDesc('c')->limit(10)->pluck('c', 'patient_id');
        if ($counts->isEmpty()) {
            return [];
        }
        $patients = \App\Models\Patient::whereIn('id', $counts->keys())->get(['id', 'patient_number', 'first_name', 'last_name'])->keyBy('id');

        return $counts->map(fn ($c, $id) => [
            'patient_number' => $patients[$id]?->patient_number ?? '—',
            'patient_name' => $patients[$id]?->full_name ?? '—',
            'count' => (int) $c,
        ])->values()->all();
    }
}
