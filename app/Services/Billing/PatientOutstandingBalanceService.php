<?php

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceReceivable;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\InvoiceReceivableService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Reads a patient's outstanding balance across ALL their visits from the AR
 * ledger (InvoiceReceivable). It NEVER mutates invoices and NEVER merges old
 * invoice lines into a new visit — each visit keeps its own invoice/balance.
 *
 *   visit-level balance   = what is owed for ONE specific visit
 *   patient-level balance = SUM of unpaid balances across ALL visits
 *
 * Only PATIENT-responsibility receivables are counted here — insurance /
 * sponsor / corporate balances are owed by third parties, not the patient, and
 * are settled through their own workflows.
 */
class PatientOutstandingBalanceService
{
    public function __construct(protected InvoiceReceivableService $receivableService) {}

    /**
     * Previous outstanding = patient-owed balance from every visit EXCEPT the
     * one supplied (and direct/no-visit invoices). Pass the current visit to
     * exclude it; pass null to get the patient's whole patient-owed balance.
     */
    public function getPreviousOutstandingBalance(Patient $patient, ?Visit $currentVisit = null): float
    {
        return $this->sumBalance(
            $this->getOutstandingReceivables($patient)
                ->when($currentVisit, fn (Collection $rows) => $rows->where('visit_id', '!=', $currentVisit->id))
        );
    }

    /** Patient-owed balance for a single visit. */
    public function getCurrentVisitBalance(Visit $visit): float
    {
        $patient = $visit->patient ?: Patient::find($visit->patient_id);
        if (! $patient) {
            return 0.0;
        }

        return $this->sumBalance(
            $this->getOutstandingReceivables($patient)->where('visit_id', $visit->id)
        );
    }

    /** Total patient account balance = SUM of every unpaid patient-owed invoice balance. */
    public function getTotalOutstandingBalance(Patient $patient): float
    {
        return $this->sumBalance($this->getOutstandingReceivables($patient));
    }

    /**
     * Open patient-responsibility receivables for the patient, newest debt last
     * (oldest aging_start_date first). Ensures receivables exist for any unpaid
     * invoice first so nothing is missed.
     *
     * @return Collection<int,InvoiceReceivable>
     */
    public function getOutstandingReceivables(Patient $patient, array $filters = []): Collection
    {
        $this->ensureReceivables($patient);

        $query = InvoiceReceivable::query()
            ->where('patient_id', $patient->id)
            ->where('payer_type', InvoiceReceivable::PAYER_PATIENT)
            ->open()
            ->with(['invoice:id,invoice_number,visit_id,total_amount,balance,status,created_at,due_date', 'visit:id,visit_number,visit_date,visit_type']);

        if (! empty($filters['visit_id'])) {
            $query->where('visit_id', $filters['visit_id']);
        }
        if (array_key_exists('exclude_visit_id', $filters) && $filters['exclude_visit_id']) {
            $query->where('visit_id', '!=', $filters['exclude_visit_id']);
        }

        return $query
            ->orderByRaw('aging_start_date IS NULL, aging_start_date ASC')
            ->orderBy('id')
            ->get();
    }

    /**
     * Unpaid invoices (patient-owed) for the patient, oldest first.
     *
     * @return Collection<int,Invoice>
     */
    public function getOutstandingInvoices(Patient $patient, array $filters = []): Collection
    {
        return $this->getOutstandingReceivables($patient, $filters)
            ->map(fn (InvoiceReceivable $r) => $r->invoice)
            ->filter()
            ->unique('id')
            ->sortBy(fn (Invoice $i) => optional($i->created_at)->timestamp ?? 0)
            ->values();
    }

    public function getOldestOutstandingInvoice(Patient $patient): ?Invoice
    {
        return $this->getOldestOutstandingReceivable($patient)?->invoice;
    }

    public function getOldestOutstandingReceivable(Patient $patient): ?InvoiceReceivable
    {
        return $this->getOutstandingReceivables($patient)->first();
    }

    public function hasPreviousOutstandingBalance(Patient $patient, ?Visit $currentVisit = null): bool
    {
        return $this->getPreviousOutstandingBalance($patient, $currentVisit) > 0.009;
    }

    /**
     * The complete balance summary used by warnings, cashier UI and statements.
     *
     * @return array{
     *     previous_outstanding: float,
     *     current_visit_outstanding: float,
     *     total_outstanding: float,
     *     oldest_unpaid_invoice: ?Invoice,
     *     oldest_age_days: ?int,
     *     ar_bucket: ?string,
     *     previous_invoice_count: int,
     *     has_previous_outstanding: bool,
     * }
     */
    public function buildPatientBalanceSummary(Patient $patient, ?Visit $currentVisit = null): array
    {
        $receivables = $this->getOutstandingReceivables($patient);

        $currentVisitReceivables = $currentVisit
            ? $receivables->where('visit_id', $currentVisit->id)
            : collect();
        $previousReceivables = $currentVisit
            ? $receivables->where('visit_id', '!=', $currentVisit->id)
            : $receivables;

        $oldest = $receivables->first();
        $ageDays = $this->ageInDays($oldest);

        return [
            'previous_outstanding' => $this->sumBalance($previousReceivables),
            'current_visit_outstanding' => $currentVisit ? $this->sumBalance($currentVisitReceivables) : 0.0,
            'total_outstanding' => $this->sumBalance($receivables),
            'oldest_unpaid_invoice' => $oldest?->invoice,
            'oldest_age_days' => $ageDays,
            'ar_bucket' => $ageDays === null ? null : $this->bucketFor($ageDays),
            'previous_invoice_count' => $previousReceivables->count(),
            'has_previous_outstanding' => $this->sumBalance($previousReceivables) > 0.009,
        ];
    }

    /**
     * Age (in days) of the oldest unpaid receivable — the head of the aging
     * bucket. Uses due_date when present, else aging_start_date.
     */
    public function ageInDays(?InvoiceReceivable $receivable): ?int
    {
        if (! $receivable) {
            return null;
        }
        $reference = $receivable->due_date ?: $receivable->aging_start_date;
        if (! $reference) {
            return null;
        }

        return max(0, Carbon::parse($reference)->startOfDay()->diffInDays(Carbon::today(), false));
    }

    /**
     * Batch helper for list views: total open patient-owed balance per patient,
     * in ONE query (no per-row work, no receivable sync). Missing/zero patients
     * are simply absent from the map.
     *
     * @param  array<int>  $patientIds
     * @return array<int,float>  patient_id => total outstanding
     */
    public function totalOutstandingMap(array $patientIds): array
    {
        $patientIds = array_values(array_unique(array_filter($patientIds)));
        if (empty($patientIds)) {
            return [];
        }

        return InvoiceReceivable::query()
            ->where('payer_type', InvoiceReceivable::PAYER_PATIENT)
            ->whereIn('patient_id', $patientIds)
            ->open()
            ->selectRaw('patient_id, ROUND(SUM(balance), 2) as total')
            ->groupBy('patient_id')
            ->pluck('total', 'patient_id')
            ->map(fn ($v) => round((float) $v, 2))
            ->filter(fn ($v) => $v > 0.009)
            ->all();
    }

    /** Human-readable aging bucket, aligned with ARAgingService thresholds. */
    public function bucketFor(int $days): string
    {
        return match (true) {
            $days <= 0 => 'current',
            $days <= 30 => '0-30',
            $days <= 60 => '31-60',
            $days <= 90 => '61-90',
            $days <= 120 => '91-120',
            default => '120+',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    private function sumBalance(Collection $receivables): float
    {
        return round((float) $receivables->sum(fn (InvoiceReceivable $r) => (float) $r->balance), 2);
    }

    /**
     * Make sure open patient invoices have their receivables materialised so
     * nothing owed is invisible to the summary. Centralised through
     * InvoiceReceivableService (no parallel balance logic).
     */
    private function ensureReceivables(Patient $patient): void
    {
        Invoice::query()
            ->where('patient_id', $patient->id)
            ->whereIn('status', [InvoiceStatus::PENDING->value, InvoiceStatus::PARTIALLY_PAID->value])
            ->where('balance', '>', 0)
            ->whereDoesntHave('receivables')
            ->with(['items', 'payments', 'creditNotes', 'claim', 'visit.visitInsurance'])
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->each(fn (Invoice $invoice) => $this->receivableService->syncFromInvoice($invoice));
    }
}
