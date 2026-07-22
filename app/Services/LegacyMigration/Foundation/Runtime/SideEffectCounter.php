<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

final class SideEffectCounter
{
    /** @var array<string, int> */
    private array $attempts = [];

    public function record(ProhibitedSubsystem $subsystem): void
    {
        $this->attempts[$subsystem->value] = ($this->attempts[$subsystem->value] ?? 0) + 1;
    }

    /** @return array<string, int> */
    public function snapshot(): array
    {
        $snapshot = $this->attempts;
        ksort($snapshot, SORT_STRING);

        return $snapshot;
    }

    /** @param array<string, int> $before @return array<string, int> */
    public function delta(array $before): array
    {
        $delta = [];
        foreach ($this->attempts as $name => $count) {
            $difference = $count - ($before[$name] ?? 0);
            if ($difference > 0) {
                $delta[$name] = $difference;
            }
        }
        ksort($delta, SORT_STRING);

        return $delta;
    }
}
