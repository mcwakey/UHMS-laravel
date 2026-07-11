<?php

namespace App\Services\Claims;

use App\Models\Claim;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Collection;

class NhiaClaimWorkflow extends GenericClaimWorkflow
{
    public function validateClaim(Claim $claim): ClaimValidationResult
    {
        $base = parent::validateClaim($claim);
        $claim->loadMissing([
            'items.invoiceItem',
            'items.department',
            'insuranceProvider.insuranceType',
            'patient',
            'visit.medicalRecords.diagnoses',
            'assignedDoctor',
        ]);

        $errors = $base->errors;
        $warnings = $base->warnings;
        $provider = $claim->insuranceProvider;
        $insuranceType = $provider?->insuranceType;

        if (($claim->claim_workflow_code ?: $provider?->claimWorkflowCode()) !== 'NHIA') {
            $errors[] = 'Claim workflow is not NHIA.';
        }

        if (($insuranceType?->code ?? $claim->claim_type_code) !== 'NHIA') {
            $errors[] = 'Insurance type must be NHIA.';
        }

        if (! $claim->membership_number) {
            $errors[] = 'Membership number is required for NHIA claims.';
        }

        foreach ($claim->items as $item) {
            if (! $item->invoiceItem) {
                $errors[] = "Claim item {$item->description} must be linked to an invoice item snapshot.";
            } elseif (in_array($item->invoiceItem->payment_status, ['cancelled', 'voided'], true)) {
                $errors[] = "Invoice item {$item->description} has been cancelled or voided.";
            }

            if (! ($item->department_id ?: $item->invoiceItem?->department_id)) {
                $warnings[] = "Department is missing for {$item->description}.";
            }
            if (! ($item->description ?: $item->service_name)) {
                $errors[] = 'Each claim item must have a service/product description.';
            }
        }

        if ($insuranceType?->requires_diagnosis && ! $this->primaryDiagnosis($claim)) {
            $warnings[] = 'Final diagnosis is missing.';
        }

        if ($insuranceType?->requires_doctor && ! $claim->assignedDoctor) {
            $warnings[] = 'Attending doctor is missing.';
        }

        if (! $claim->visit?->visit_date) {
            $errors[] = 'Visit date is required.';
        }

        return ClaimValidationResult::make($errors, array_values(array_unique($warnings)));
    }

    public function markReady(Claim $claim, User $user): Claim
    {
        $claim = $this->syncNhiaSnapshot($claim);

        return parent::markReady($claim, $user);
    }

    public function submit(Claim $claim, User $user, array $data = []): Claim
    {
        $claim = $this->syncNhiaSnapshot($claim);

        return parent::submit($claim, $user, array_merge(['submission_mode' => 'EXPORT'], $data));
    }

    private function syncNhiaSnapshot(Claim $claim): Claim
    {
        $claim->loadMissing('insuranceProvider.insuranceType');

        $claim->forceFill([
            'claim_type_code' => 'NHIA',
            'claim_workflow_code' => 'NHIA',
            'insurance_type_id' => $claim->insuranceProvider?->insurance_type_id ?: $claim->insurance_type_id,
        ])->save();

        return $claim->fresh();
    }

    protected function claimableInvoiceItems(Invoice $invoice): Collection
    {
        return $invoice->items
            ->reject(fn ($item) => in_array($item->payment_status, ['cancelled', 'voided'], true))
            ->values();
    }

    protected function insuranceCoveredAmount($item): float
    {
        $covered = parent::insuranceCoveredAmount($item);
        if ($covered > 0) {
            return $covered;
        }

        $quantity = max(1, (int) ($item->quantity ?? 1));
        $lineTotal = (float) ($item->selected_price ?: $item->insurance_price ?: $item->total_price ?: $item->unit_price);

        return round($lineTotal * $quantity, 2);
    }
}
