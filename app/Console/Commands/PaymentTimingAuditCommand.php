<?php

namespace App\Console\Commands;

use App\Enums\PaymentTimingComparisonOutcome;
use App\Enums\VisitType;
use App\Models\Visit;
use App\Services\Billing\PaymentTimingLegacyCompatibilityService;
use App\Services\Billing\PaymentTimingPolicyComparisonService;
use App\Services\Billing\VisitPaymentTimingResolver;
use Illuminate\Console\Command;

class PaymentTimingAuditCommand extends Command
{
    protected $signature = 'billing:payment-timing-audit
        {--visit= : Inspect one visit ID}
        {--visit-type= : Limit to outpatient, inpatient, or emergency}
        {--active-only : Inspect active visits only}
        {--limit=100 : Maximum visits to inspect (1-1000)}
        {--mismatches-only : Show only mismatch examples}
        {--json : Emit machine-readable JSON}';

    protected $description = 'Compare configured payment timing with the legacy billing policy without changing data.';

    public function handle(
        VisitPaymentTimingResolver $resolver,
        PaymentTimingLegacyCompatibilityService $compatibility,
        PaymentTimingPolicyComparisonService $comparisonService,
    ): int {
        $limit = max(1, min(1000, (int) $this->option('limit')));
        $visitType = $this->option('visit-type');
        if ($visitType !== null && ! VisitType::tryFrom((string) $visitType)) {
            $this->error('Invalid visit type. Use outpatient, inpatient, or emergency.');

            return self::FAILURE;
        }

        $visits = Visit::query()
            ->with('billingOverrides')
            ->when($this->option('visit'), fn ($query, $id) => $query->whereKey($id))
            ->when($visitType, fn ($query, $type) => $query->where('visit_type', $type))
            ->when($this->option('active-only'), fn ($query) => $query->active())
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $totals = array_fill_keys(array_map(fn ($case) => $case->value, PaymentTimingComparisonOutcome::cases()), 0);
        $examples = [];

        foreach ($visits as $visit) {
            $typed = $resolver->resolve($visit);
            $legacy = $compatibility->describeLegacyVisitContext($visit);
            $comparison = $comparisonService->compare(
                $visit,
                $legacy,
                $typed,
                ['gate_operation' => 'visit_context_audit'],
                false,
            );
            $totals[$comparison->outcome->value]++;

            if ($this->option('mismatches-only') && ! $comparison->isMismatch()) {
                continue;
            }

            $examples[] = [
                'visit_id' => $visit->id,
                'visit_type' => $visit->visit_type->value,
                'legacy_policy' => $legacy->expectedPolicy?->value,
                'legacy_reason' => $legacy->reasonCode,
                'typed_policy' => $typed->policy->value,
                'typed_source' => $typed->source->value,
                'typed_reason' => $typed->reasonCode,
                'outcome' => $comparison->outcome->value,
            ];
        }

        $result = [
            'visits_inspected' => $visits->count(),
            'totals' => $totals,
            'examples' => $examples,
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info("Visits inspected: {$result['visits_inspected']}");
        $this->table(['Outcome', 'Count'], collect($totals)->map(fn ($count, $outcome) => [$outcome, $count])->values()->all());
        if ($examples !== []) {
            $this->table(array_keys($examples[0]), array_map('array_values', $examples));
        }

        return self::SUCCESS;
    }
}
