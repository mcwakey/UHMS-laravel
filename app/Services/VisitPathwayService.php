<?php

namespace App\Services;

use App\Models\Visit;
use App\Models\VisitPathwayEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class VisitPathwayService
{
    public function record(Visit $visit, string $eventType, array $data = []): VisitPathwayEvent
    {
        $source = $data['source'] ?? null;

        return VisitPathwayEvent::create([
            'visit_id' => $visit->id,
            'patient_id' => $data['patient_id'] ?? $visit->patient_id,
            'event_type' => $eventType,
            'department_id' => $data['department_id'] ?? null,
            'source_type' => $data['source_type'] ?? ($source instanceof Model ? $source::class : null),
            'source_id' => $data['source_id'] ?? ($source instanceof Model ? $source->getKey() : null),
            'status' => $data['status'] ?? null,
            'title' => $data['title'] ?? ucwords(strtolower(str_replace('_', ' ', $eventType))),
            'description' => $data['description'] ?? null,
            'started_at' => $data['started_at'] ?? now(),
            'completed_at' => $data['completed_at'] ?? null,
            'created_by' => $data['created_by'] ?? Auth::id(),
        ]);
    }

    public function buildTimeline(Visit $visit)
    {
        return $visit->pathwayEvents()
            ->with(['department', 'createdBy'])
            ->orderBy('started_at')
            ->orderBy('id')
            ->get();
    }
}
