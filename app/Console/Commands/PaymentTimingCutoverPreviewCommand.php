<?php

namespace App\Console\Commands;

use App\Data\Billing\PaymentGateContext;
use App\Enums\PaymentGateStage;
use App\Models\InvoiceItem;
use App\Models\Visit;
use App\Services\Billing\ApprovedArrangementOperationalEligibilityService;
use App\Services\Billing\BillingPolicyService;
use App\Services\Billing\OperationalVisitPaymentTimingResolver;
use App\Services\Billing\PaymentGateOperationConfigurationService;
use App\Services\Billing\PaymentTimingCutoverConfigurationService;
use App\Services\Billing\TypedPaymentGateDecisionService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Read-only preview comparing the legacy and would-be typed decision for one
 * visit/operation/invoice-item (Payment Timing Policy Phase 8). It mutates no
 * state, creates no invoice/payment/override, writes no activity log and emits
 * no patient-sensitive data. Missing context is reported, not invented.
 */
class PaymentTimingCutoverPreviewCommand extends Command
{
    protected $signature = 'billing:payment-timing-cutover-preview
        {--visit= : Visit id}
        {--operation= : Operation code (e.g. laboratory.result.enter)}
        {--invoice-item= : Invoice item id}
        {--json : Emit machine-readable JSON}';

    protected $description = 'Preview legacy vs typed payment-gate decision for a visit/operation/item (read-only).';

    public function handle(
        BillingPolicyService $billingPolicy,
        OperationalVisitPaymentTimingResolver $operationalResolver,
        TypedPaymentGateDecisionService $typedDecision,
        PaymentGateOperationConfigurationService $operationConfig,
        PaymentTimingCutoverConfigurationService $cutover,
        ApprovedArrangementOperationalEligibilityService $eligibility,
    ): int {
        try {
            $operation = (string) $this->option('operation');
            $visit = ($id = $this->option('visit')) ? Visit::find($id) : null;
            $item = ($iid = $this->option('invoice-item')) ? InvoiceItem::find($iid) : null;

            if ($operation === '' || $visit === null || $item === null) {
                return $this->emit(['error' => 'missing_context', 'need' => ['visit', 'operation', 'invoice-item']]);
            }

            $context = PaymentGateContext::forOperation(PaymentGateStage::RENDER, $operation);
            $legacy = $billingPolicy->legacyInvoiceItemPolicy($item);
            $operational = $operationalResolver->resolve($visit, $context);
            $operationPolicy = $operationConfig->policyFor($operation);
            $typed = $typedDecision->evaluate($legacy, $visit, $item, $operational->policy, $operationPolicy, $context);
            $arrangement = $eligibility->evaluate($visit, $operation);

            $usesTyped = $cutover->operationUsesTypedPolicy($operation);
            $authority = $usesTyped
                ? ($operational->usedApprovedArrangement ? 'approved_arrangement' : 'typed_baseline')
                : 'legacy';

            return $this->emit([
                'visit_id' => $visit->id,
                'operation' => $operation,
                'invoice_item_id' => $item->id,
                'legacy_decision' => ['allowed' => $legacy->allowed, 'reason' => $legacy->reason],
                'typed_baseline_policy' => $operational->baseline->policy->value,
                'operational_policy' => $operational->policy->value,
                'operational_source' => $operational->source->value,
                'current_approved_arrangement' => $operational->approvedArrangementId,
                'arrangement_eligibility' => $arrangement->reasonCode,
                'would_be_typed_decision' => ['allowed' => $typed->allowed, 'reason' => $typed->reason],
                'comparison' => $legacy->allowed === $typed->allowed ? 'match' : 'differ',
                'effective_runtime_authority' => $authority,
                'effective_mode' => $cutover->effectiveMode()->value,
            ]);
        } catch (Throwable $e) {
            $this->error('Preview failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /** @param array<string, mixed> $data */
    private function emit(array $data): int
    {
        if ($this->option('json')) {
            $this->line((string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }
        foreach ($data as $k => $v) {
            $this->line(str_pad($k, 32).': '.(is_array($v) ? json_encode($v) : (is_bool($v) ? ($v ? 'true' : 'false') : (string) ($v ?? '—'))));
        }

        return self::SUCCESS;
    }
}
