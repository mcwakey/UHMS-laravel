<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\ActivityLogStatus;

final class ApplicationIsolationControlFactory
{
    public function __construct(private readonly ApplicationIsolationState $state, private readonly ActivityLogStatus $activity) {}

    /** @return list<ApplicationBoundSubsystemControl> */
    public function make(): array
    {
        $facades = [
            ProhibitedSubsystem::Notifications->value => Notification::class,
            ProhibitedSubsystem::Mail->value => Mail::class,
            ProhibitedSubsystem::QueueDispatch->value => Queue::class,
            ProhibitedSubsystem::BusJobs->value => Bus::class,
            ProhibitedSubsystem::FileAvatarWrites->value => Storage::class,
            ProhibitedSubsystem::ExternalIntegrations->value => Http::class,
        ];
        $controls = [];
        foreach (ProhibitedSubsystem::cases() as $subsystem) {
            $hook = match ($subsystem) {
                ProhibitedSubsystem::LaravelEvents => new EloquentEventIsolationHook($this->state),
                ProhibitedSubsystem::OperationalActivityLog => new ActivityLogIsolationHook($this->activity, $this->state),
                default => isset($facades[$subsystem->value])
                    ? new FacadeSinkIsolationHook($facades[$subsystem->value], $this->state, $subsystem, 'laravel-facade-sink:'.$subsystem->value)
                    : new CircuitBreakerIsolationHook($this->state, $subsystem, 'container-resolution-circuit-breaker:'.$subsystem->value),
            };
            $controls[] = new ApplicationBoundSubsystemControl($subsystem, $hook);
        }

        return $controls;
    }
}
