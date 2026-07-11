<?php

namespace App\Services\FrontDesk;

use App\Enums\FrontDesk\FrontDeskIncidentStatus;
use App\Enums\LogModule;
use App\Models\FrontDeskIncidentLog;
use App\Models\User;
use App\Services\ActivityLogService;

/**
 * Front Desk / Security Desk incident workflow (Phase 18E). Operational and
 * non-clinical; long narratives stay in-column (never in audit properties).
 */
class IncidentLogService
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function create(array $data, User $actor): FrontDeskIncidentLog
    {
        $data['created_by'] = $actor->id;
        $data['status'] = $data['status'] ?? FrontDeskIncidentStatus::OPEN->value;
        $data['reported_at'] = $data['reported_at'] ?? now();
        if (empty($data['incident_number'])) {
            $data['incident_number'] = FrontDeskIncidentLog::generateIncidentNumber();
        }

        $incident = FrontDeskIncidentLog::create($data);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_INCIDENT_CREATED', $this->context($incident), $incident);

        return $incident;
    }

    public function update(FrontDeskIncidentLog $incident, array $data, User $actor): FrontDeskIncidentLog
    {
        $data['updated_by'] = $actor->id;
        unset($data['incident_number'], $data['created_by']);

        $incident->fill($data)->save();

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_INCIDENT_UPDATED', $this->context($incident), $incident);

        return $incident->refresh();
    }

    public function assign(FrontDeskIncidentLog $incident, User $assignee, User $actor): FrontDeskIncidentLog
    {
        $incident->update([
            'assigned_to_user_id' => $assignee->id,
            'status' => $incident->status === FrontDeskIncidentStatus::OPEN
                ? FrontDeskIncidentStatus::IN_PROGRESS->value
                : $incident->status->value,
            'updated_by' => $actor->id,
        ]);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_INCIDENT_ASSIGNED', $this->context($incident), $incident);

        return $incident;
    }

    public function escalate(FrontDeskIncidentLog $incident, User $target, User $actor, ?string $note = null): FrontDeskIncidentLog
    {
        $metadata = $incident->metadata ?? [];
        if ($note !== null && trim($note) !== '') {
            $metadata['escalation_note'] = trim($note);
        }

        $incident->update([
            'escalated_to_user_id' => $target->id,
            'escalated_at' => now(),
            'status' => FrontDeskIncidentStatus::ESCALATED->value,
            'updated_by' => $actor->id,
            'metadata' => $metadata ?: null,
        ]);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_INCIDENT_ESCALATED', $this->context($incident), $incident);

        return $incident;
    }

    public function resolve(FrontDeskIncidentLog $incident, User $actor, ?string $resolutionNote = null): FrontDeskIncidentLog
    {
        $incident->update([
            'status' => FrontDeskIncidentStatus::RESOLVED->value,
            'resolved_by' => $actor->id,
            'resolved_at' => now(),
            'resolution_note' => $resolutionNote ?: $incident->resolution_note,
            'updated_by' => $actor->id,
        ]);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_INCIDENT_RESOLVED', $this->context($incident), $incident);

        return $incident;
    }

    public function cancel(FrontDeskIncidentLog $incident, User $actor, ?string $reason = null): FrontDeskIncidentLog
    {
        $metadata = $incident->metadata ?? [];
        if ($reason !== null && trim($reason) !== '') {
            $metadata['cancellation_reason'] = trim($reason);
        }

        $incident->update([
            'status' => FrontDeskIncidentStatus::CANCELLED->value,
            'updated_by' => $actor->id,
            'metadata' => $metadata ?: null,
        ]);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_INCIDENT_CANCELLED', $this->context($incident), $incident);

        return $incident;
    }

    /**
     * @return array<string, mixed>
     */
    private function context(FrontDeskIncidentLog $incident): array
    {
        return [
            'department_id' => $incident->department_id,
            'metadata' => array_filter([
                'front_desk_incident_log_id' => $incident->id,
                'incident_number' => $incident->incident_number,
                'incident_type' => $incident->incident_type?->value,
                'severity' => $incident->severity?->value,
                'status' => $incident->status?->value,
            ], fn ($v) => $v !== null),
        ];
    }
}
