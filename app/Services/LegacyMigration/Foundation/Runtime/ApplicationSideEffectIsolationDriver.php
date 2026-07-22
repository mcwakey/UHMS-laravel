<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

use Throwable;

/**
 * Concrete coordinator for application-level isolation hooks. Construction
 * fails unless every directive subsystem has a distinct observable control.
 */
final class ApplicationSideEffectIsolationDriver implements SideEffectIsolationDriver
{
    /** @var array<string, SubsystemIsolationControl> */
    private array $controls = [];

    /** @param iterable<SubsystemIsolationControl> $controls */
    public function __construct(iterable $controls)
    {
        foreach ($controls as $control) {
            if (! $control instanceof SubsystemIsolationControl) {
                throw new RuntimeIsolationException('FOUNDATION_ISOLATION_CONTROL_INVALID', 'An application isolation control is invalid.');
            }
            $name = $control->subsystem()->value;
            if (isset($this->controls[$name])) {
                throw new RuntimeIsolationException('FOUNDATION_ISOLATION_CONTROL_DUPLICATE', 'An application isolation control is duplicated.');
            }
            $this->controls[$name] = $control;
        }

        foreach (ProhibitedSubsystem::cases() as $required) {
            if (! isset($this->controls[$required->value])) {
                throw new RuntimeIsolationException('FOUNDATION_ISOLATION_CONTROL_INCOMPLETE', 'The application isolation control set is incomplete.');
            }
        }
        ksort($this->controls, SORT_STRING);
    }

    public function capture(array $subsystems): SideEffectIsolationSnapshot
    {
        $states = [];
        foreach ($subsystems as $subsystem) {
            $states[$subsystem->value] = $this->control($subsystem)->isIsolated();
        }
        ksort($states, SORT_STRING);

        return new SideEffectIsolationSnapshot($states);
    }

    public function isolate(array $subsystems): void
    {
        foreach ($subsystems as $subsystem) {
            $this->control($subsystem)->isolate();
        }
    }

    public function isIsolated(ProhibitedSubsystem $subsystem): bool
    {
        return $this->control($subsystem)->isIsolated();
    }

    public function restore(SideEffectIsolationSnapshot $snapshot): void
    {
        $firstFailure = null;
        foreach ($snapshot->isolated as $name => $previouslyIsolated) {
            try {
                $subsystem = ProhibitedSubsystem::tryFrom($name);
                if ($subsystem === null) {
                    throw new RuntimeIsolationException('FOUNDATION_ISOLATION_SNAPSHOT_INVALID', 'An isolation snapshot is invalid.');
                }
                $this->control($subsystem)->restore($previouslyIsolated);
            } catch (Throwable $failure) {
                $firstFailure ??= $failure;
            }
        }
        if ($firstFailure !== null) {
            throw new RuntimeIsolationException('FOUNDATION_ISOLATION_RESTORE_PARTIAL_FAILURE', 'One or more subsystem restoration controls failed.', $firstFailure);
        }
    }

    public function matches(SideEffectIsolationSnapshot $snapshot): bool
    {
        foreach ($snapshot->isolated as $name => $expected) {
            $subsystem = ProhibitedSubsystem::tryFrom($name);
            if ($subsystem === null || $this->control($subsystem)->isIsolated() !== $expected) {
                return false;
            }
        }

        return true;
    }

    private function control(ProhibitedSubsystem $subsystem): SubsystemIsolationControl
    {
        return $this->controls[$subsystem->value]
            ?? throw new RuntimeIsolationException('FOUNDATION_ISOLATION_CONTROL_MISSING', 'An application isolation control is missing.');
    }
}
