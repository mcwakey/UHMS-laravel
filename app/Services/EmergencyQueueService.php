<?php

namespace App\Services;

use App\Enums\EmergencyTriageCategory;
use App\Models\EmergencyCase;
use Illuminate\Database\Eloquent\Collection;

/**
 * EmergencyQueueService — produces the live ER queue, sorted by triage
 * weight (RED > ORANGE > YELLOW > GREEN > BLACK > untriaged) and arrival
 * time (oldest first within the same triage bucket).
 *
 * This queue is intentionally separate from the OPD queue managed by
 * QueueService. Emergency visits never appear in the OPD triage / waiting
 * queues because Emergency visits enter VisitStatus::EMERGENCY directly.
 */
class EmergencyQueueService
{
    /**
     * Get the live queue of open ER cases.
     *
     * @return Collection<int, EmergencyCase>
     */
    public function getOpenQueue(): Collection
    {
        $cases = EmergencyCase::with(['patient', 'treatmentArea', 'assignedDoctor', 'assignedNurse'])
            ->open()
            ->orderBy('arrival_time')
            ->get();

        return $cases->sortBy(function (EmergencyCase $c) {
            $weight = $c->triage_category instanceof EmergencyTriageCategory
                ? $c->triage_category->weight()
                : 99; // untriaged sorts after all triaged
            return sprintf('%02d_%s', $weight, optional($c->arrival_time)->format('YmdHis') ?? '99999999999999');
        })->values();
    }

    /**
     * Cases that are currently open and untriaged.
     *
     * @return Collection<int, EmergencyCase>
     */
    public function getAwaitingTriage(): Collection
    {
        return EmergencyCase::with('patient')
            ->open()
            ->whereNull('triage_category')
            ->orderBy('arrival_time')
            ->get();
    }

    /**
     * Counts by triage category for the ER dashboard.
     *
     * @return array<string, int>
     */
    public function getTriageCounts(): array
    {
        $rows = EmergencyCase::open()
            ->selectRaw('triage_category, COUNT(*) as c')
            ->groupBy('triage_category')
            ->pluck('c', 'triage_category');

        $out = [];
        foreach (EmergencyTriageCategory::cases() as $cat) {
            $out[$cat->value] = (int) ($rows[$cat->value] ?? 0);
        }
        $out['untriaged'] = (int) ($rows[null] ?? $rows->get(null) ?? 0);
        return $out;
    }

    /**
     * Aggregate counts for the ER dashboard cards.
     *
     * @return array{open:int,today:int,admitted_today:int,discharged_today:int,deceased_today:int}
     */
    public function getDashboardStats(): array
    {
        $today = today();

        return [
            'open' => EmergencyCase::open()->count(),
            'today' => EmergencyCase::whereDate('arrival_time', $today)->count(),
            'admitted_today' => EmergencyCase::whereDate('disposition_at', $today)
                ->where('disposition', \App\Enums\EmergencyDisposition::ADMITTED_TO_WARD->value)->count(),
            'discharged_today' => EmergencyCase::whereDate('disposition_at', $today)
                ->where('disposition', \App\Enums\EmergencyDisposition::DISCHARGED_HOME->value)->count(),
            'deceased_today' => EmergencyCase::whereDate('disposition_at', $today)
                ->where('disposition', \App\Enums\EmergencyDisposition::DECEASED->value)->count(),
        ];
    }
}
