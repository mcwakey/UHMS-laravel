<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

use Spatie\Activitylog\ActivityLogStatus;

final class ActivityLogIsolationHook implements SubsystemIsolationHook
{
    public function __construct(private readonly ActivityLogStatus $status, private readonly ApplicationIsolationState $state) {}

    public function proofReference(): string
    {
        return 'spatie-activity-log-status';
    }

    public function capture(): bool
    {
        return $this->status->disabled();
    }

    public function isolate(): void
    {
        $this->status->disable();
        $this->state->activate(ProhibitedSubsystem::OperationalActivityLog);
    }

    public function isolated(): bool
    {
        return $this->status->disabled() && $this->state->isolated(ProhibitedSubsystem::OperationalActivityLog);
    }

    public function restore(mixed $state): void
    {
        $state === true ? $this->status->disable() : $this->status->enable();
        $this->state->deactivate(ProhibitedSubsystem::OperationalActivityLog);
    }
}
