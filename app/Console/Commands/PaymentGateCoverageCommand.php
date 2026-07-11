<?php

namespace App\Console\Commands;

use App\Services\Billing\PaymentGateOperationRegistry;
use Illuminate\Console\Command;

class PaymentGateCoverageCommand extends Command
{
    protected $signature = 'billing:payment-gate-coverage {--json : Emit machine-readable JSON}';

    protected $description = 'Report registered payment-gate stages and production wiring without changing data.';

    public function handle(PaymentGateOperationRegistry $registry): int
    {
        $operations = collect($registry->operations())->map(function (array $entry, string $operation): array {
            return [
                'operation' => $operation,
                'stage' => $entry['stage']->value,
                'facade_method' => $entry['facade_method'],
                'production_wired' => $entry['production_wired'],
                'hard_enforcement' => $entry['hard_enforcement'],
                'production_caller' => $entry['production_caller'],
                'legacy_behaviour' => $entry['legacy_behaviour'],
            ];
        })->values();

        $result = [
            'registered' => $operations->count(),
            'production_wired' => $operations->where('production_wired', true)->count(),
            'missing_production_wiring' => $operations->where('production_wired', false)->count(),
            'operations' => $operations->all(),
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info("Registered operations: {$result['registered']}");
        $this->info("Production wired: {$result['production_wired']}");
        if ($result['missing_production_wiring'] > 0) {
            $this->warn("Missing production wiring: {$result['missing_production_wiring']} (informational; no enforcement was added)");
        }
        $this->table(
            ['Operation', 'Stage', 'Facade', 'Wired', 'Hard gate', 'Caller', 'Legacy / missing-item behavior'],
            $operations->map(fn (array $entry) => [
                $entry['operation'],
                $entry['stage'],
                $entry['facade_method'],
                $entry['production_wired'] ? 'yes' : 'no',
                $entry['hard_enforcement'] ? 'yes' : 'no',
                $entry['production_caller'] ?? '—',
                $entry['legacy_behaviour'],
            ])->all(),
        );

        return self::SUCCESS;
    }
}
