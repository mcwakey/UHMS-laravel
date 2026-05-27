<?php

namespace App\Services;

use App\Models\MedicationAdministration;
use App\Models\MedicationAdministrationSchedule;
use App\Models\MedicationOrder;

class MedicationProgressService
{
    public function summarize(MedicationOrder $order): array
    {
        $order->loadMissing(['schedules.administration', 'frequency']);

        $schedules = $order->schedules;
        $given = $schedules->where('status', MedicationAdministrationSchedule::STATUS_GIVEN)->count();
        $missed = $schedules->where('status', MedicationAdministrationSchedule::STATUS_MISSED)->count();
        $held = $schedules->where('status', MedicationAdministrationSchedule::STATUS_HELD)->count();
        $refused = $schedules->where('status', MedicationAdministrationSchedule::STATUS_REFUSED)->count();
        $cancelled = $schedules->where('status', MedicationAdministrationSchedule::STATUS_CANCELLED)->count();
        $closed = $schedules->whereIn('status', $order->getClosedStatuses())->count();
        $total = max((int) $order->total_doses, $schedules->count());
        $remaining = max(0, $total - $closed);
        $nextDue = $schedules
            ->whereNotIn('status', $order->getClosedStatuses())
            ->sortBy('scheduled_at')
            ->first()?->scheduled_at;

        return [
            'total_doses' => $total,
            'given_doses' => $given,
            'remaining_doses' => $remaining,
            'missed_doses' => $missed,
            'held_doses' => $held,
            'refused_doses' => $refused,
            'cancelled_doses' => $cancelled,
            'closed_doses' => $closed,
            'next_due_at' => $nextDue,
            'progress_percentage' => $total > 0 ? round(($given / $total) * 100, 1) : 0,
            'available_patient_doses' => $this->availablePatientDispensedDoses($order),
            'is_prn' => (bool) $order->frequency?->is_prn,
            'is_stat' => (bool) $order->frequency?->is_stat,
        ];
    }

    public function refreshOrderStatus(MedicationOrder $order): MedicationOrder
    {
        $summary = $this->summarize($order->fresh(['schedules', 'frequency']));

        if (in_array($order->status, [
            MedicationOrder::STATUS_HELD,
            MedicationOrder::STATUS_STOPPED,
            MedicationOrder::STATUS_CANCELLED,
        ], true)) {
            return $order;
        }

        if ($summary['total_doses'] > 0 && $summary['given_doses'] >= $summary['total_doses']) {
            $order->update(['status' => MedicationOrder::STATUS_COMPLETED]);
        } elseif ($order->quantity_dispensed > 0 || $summary['closed_doses'] > 0) {
            $order->update(['status' => MedicationOrder::STATUS_ACTIVE_ADMINISTRATION]);
        }

        return $order->fresh();
    }

    public function availablePatientDispensedDoses(MedicationOrder $order): float
    {
        $used = MedicationAdministration::query()
            ->where('medication_order_id', $order->id)
            ->where('source_stock_type', MedicationAdministration::SOURCE_PATIENT_STOCK)
            ->whereIn('status', [
                MedicationAdministration::STATUS_GIVEN,
                MedicationAdministration::STATUS_PARTIALLY_GIVEN,
            ])
            ->count();

        return max(0, (float) $order->quantity_dispensed - $used);
    }
}
