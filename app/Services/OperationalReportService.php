<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Admission;
use App\Models\BloodRequest;
use App\Models\BloodUnit;
use App\Models\Claim;
use App\Models\ClinicalTask;
use App\Models\Complaint;
use App\Models\Diagnosis;
use App\Models\DispensingRecord;
use App\Models\EmergencyCase;
use App\Models\HistoryOfPresentingComplaint;
use App\Models\Invoice;
use App\Models\LabRequest;
use App\Models\MedicationAdministration;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\ProcedureRequest;
use App\Models\ServiceRendering;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OperationalReportService
{
    public function catalogue(): array
    {
        return [
            'consultations' => ['title' => __('reports.consultations.title'), 'description' => __('reports.consultations.description')],
            'diagnoses'     => ['title' => __('reports.diagnoses.title'),     'description' => __('reports.diagnoses.description')],
            'complaints'    => ['title' => __('reports.complaints.title'),    'description' => __('reports.complaints.description')],
            'pharmacy'      => ['title' => __('reports.pharmacy.title'),      'description' => __('reports.pharmacy.description')],
            'investigations'=> ['title' => __('reports.investigations.title'),'description' => __('reports.investigations.description')],
            'procedures'    => ['title' => __('reports.procedures.title'),    'description' => __('reports.procedures.description')],
            'theatre'       => ['title' => __('reports.theatre.title'),       'description' => __('reports.theatre.description')],
            'emergency'     => ['title' => __('reports.emergency.title'),     'description' => __('reports.emergency.description')],
            'admission'     => ['title' => __('reports.admissions.title'),    'description' => __('reports.admissions.description')],
            'mar'           => ['title' => __('reports.mar.title'),           'description' => __('reports.mar.description')],
            'billing'       => ['title' => __('reports.billing.title'),       'description' => __('reports.billing.description')],
            'claims'        => ['title' => __('reports.claims.title'),        'description' => __('reports.claims.description')],
            'stock'         => ['title' => __('reports.stock.title'),         'description' => __('reports.stock.description')],
            'blood-bank'    => ['title' => __('reports.blood_bank.title'),    'description' => __('reports.blood_bank.description')],
        ];
    }

    public function dashboard(array $filters = []): array
    {
        $from = $filters['date_from'] ?? now()->subDays(30)->toDateString();
        $to = $filters['date_to'] ?? now()->toDateString();
        $range = ['date_from' => $from, 'date_to' => $to];

        return [
            'filters' => $range,
            'cards' => [
                ['label' => __('reports.consultations.title'),  'value' => $this->countInRange(VisitConsultationRoute::query(), 'created_at', $range),   'route' => 'admin.reports.consultations', 'color' => 'primary'],
                ['label' => __('reports.diagnoses.title'),      'value' => $this->countInRange(Diagnosis::query(), 'created_at', $range),                 'route' => 'admin.reports.diagnoses',     'color' => 'success'],
                ['label' => __('reports.investigations.title'), 'value' => $this->countInRange(LabRequest::query(), 'created_at', $range),                'route' => 'admin.reports.investigations','color' => 'info'],
                ['label' => __('reports.procedures.title'),     'value' => $this->countInRange(ProcedureRequest::query(), 'requested_at', $range),        'route' => 'admin.reports.procedures',    'color' => 'warning'],
                ['label' => __('reports.emergency.title'),      'value' => $this->countInRange(EmergencyCase::query(), 'arrival_time', $range),           'route' => 'admin.reports.emergency',     'color' => 'danger'],
                ['label' => __('reports.admissions.title'),     'value' => $this->countInRange(Admission::query(), 'admission_date', $range),             'route' => 'admin.reports.admission',     'color' => 'secondary'],
                ['label' => __('reports.mar.title'),            'value' => $this->countInRange(MedicationAdministration::query(), 'administered_at', $range), 'route' => 'admin.reports.mar',       'color' => 'primary'],
                ['label' => __('reports.billing.title'),        'value' => $this->countInRange(Invoice::query(), 'created_at', $range),                  'route' => 'admin.reports.billing',       'color' => 'success'],
                ['label' => __('reports.claims.title'),         'value' => $this->countInRange(Claim::query(), 'claim_date', $range),                    'route' => 'admin.reports.claims',        'color' => 'info'],
                ['label' => __('reports.blood_bank.title'),     'value' => class_exists(BloodRequest::class) ? $this->countInRange(BloodRequest::query(), 'requested_at', $range) : 0, 'route' => 'admin.reports.blood-bank', 'color' => 'danger'],
            ],
            'financial' => [
                'billed' => $this->sumInRange(Invoice::query(), 'created_at', $range, 'total_amount'),
                'collected' => $this->sumInRange(Payment::query(), 'paid_at', $range, 'amount'),
                'outstanding' => (float) Invoice::whereIn('status', [InvoiceStatus::PENDING->value, InvoiceStatus::PARTIALLY_PAID->value])->sum('balance'),
                'claims_total' => $this->sumInRange(Claim::query(), 'claim_date', $range, 'total_claim_amount'),
            ],
            'renderingRisk' => [
                'billed_not_rendered' => ServiceRendering::whereIn('status', ServiceRendering::ACTIVE_STATUSES)->count(),
                'rendered_unpaid' => ServiceRendering::where('status', ServiceRendering::STATUS_RENDERED)
                    ->whereHas('invoiceItem', fn ($q) => $q->where('payment_status', '!=', 'paid'))
                    ->count(),
            ],
            'catalogue' => $this->catalogue(),
        ];
    }

    public function build(string $key, array $filters = [], bool $export = false, ?User $user = null): array
    {
        if (! isset($this->catalogue()[$key])) {
            throw new InvalidArgumentException("Unknown operational report [{$key}].");
        }

        return match ($key) {
            'consultations' => $this->consultations($filters, $export),
            'diagnoses' => $this->diagnoses($filters, $export, $user),
            'complaints' => $this->complaints($filters, $export, $user),
            'pharmacy' => $this->pharmacy($filters, $export),
            'investigations' => $this->investigations($filters, $export),
            'procedures', 'theatre' => $this->procedures($filters, $export, $key),
            'emergency' => $this->emergency($filters, $export),
            'admission' => $this->admission($filters, $export),
            'mar' => $this->mar($filters, $export),
            'billing' => $this->billing($filters, $export, $user),
            'claims' => $this->claims($filters, $export, $user),
            'stock' => $this->stock($filters, $export),
            'blood-bank' => $this->bloodBank($filters, $export),
        };
    }

    protected function consultations(array $filters, bool $export): array
    {
        $query = VisitConsultationRoute::with(['visit', 'patient', 'department', 'doctor', 'service', 'services'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', strtoupper($v)))
            ->when($filters['department_id'] ?? null, fn ($q, $v) => $q->where('department_id', $v))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('doctor_id', $v));

        $this->dateRange($query, 'created_at', $filters);

        return $this->table('consultations', $filters, $query, [
            __('reports.col_visit'), __('reports.col_patient'), __('reports.col_department'), __('reports.col_services'), __('reports.col_doctor'), __('reports.col_status'), __('reports.col_started'), __('reports.col_completed'),
        ], fn ($route) => [
            $route->visit?->visit_number,
            $route->patient?->full_name,
            $route->department?->name,
            $route->services->pluck('name')->implode(', ') ?: $route->service?->name,
            $route->doctor?->name,
            $route->status,
            optional($route->started_at)->format('d M Y H:i'),
            optional($route->completed_at)->format('d M Y H:i'),
        ], $export, 'created_at');
    }

    protected function diagnoses(array $filters, bool $export, ?User $user): array
    {
        $filters['_can_view_sensitive_clinical'] = $this->canViewSensitiveClinical($user);

        $query = Diagnosis::with(['visit', 'patient', 'department', 'creator', 'doctor'])
            ->when($filters['department_id'] ?? null, fn ($q, $v) => $q->where('department_id', $v))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('created_by', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('type', $v));

        $this->dateRange($query, 'created_at', $filters);

        return $this->table('diagnoses', $filters, $query, [
            __('reports.col_date'), __('reports.col_visit'), __('reports.col_patient'), __('reports.col_department'), __('reports.col_diagnosis'), __('reports.col_type'), __('reports.col_primary'), __('reports.col_entered_by'),
        ], fn ($diagnosis) => [
            optional($diagnosis->created_at)->format('d M Y H:i'),
            $diagnosis->visit?->visit_number,
            $diagnosis->patient?->full_name,
            $diagnosis->department?->name,
            trim(($diagnosis->icd_code ? $diagnosis->icd_code.' — ' : '').$diagnosis->description),
            $diagnosis->type,
            $diagnosis->is_primary ? 'Yes' : 'No',
            $diagnosis->creator?->name ?? $diagnosis->doctor?->name,
        ], $export, 'created_at');
    }

    protected function complaints(array $filters, bool $export, ?User $user): array
    {
        $filters['_can_view_sensitive_clinical'] = $this->canViewSensitiveClinical($user);

        $query = Complaint::with(['visit', 'patient', 'department', 'creator', 'doctor'])
            ->when($filters['department_id'] ?? null, fn ($q, $v) => $q->where('department_id', $v))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('created_by', $v));

        $this->dateRange($query, 'created_at', $filters);

        return $this->table('complaints', $filters, $query, [
            __('reports.col_date'), __('reports.col_visit'), __('reports.col_patient'), __('reports.col_department'), __('reports.col_complaint'), __('reports.col_duration'), __('reports.col_severity'), __('reports.col_entered_by'),
        ], fn ($complaint) => [
            optional($complaint->created_at)->format('d M Y H:i'),
            $complaint->visit?->visit_number,
            $complaint->patient?->full_name,
            $complaint->department?->name,
            $complaint->description,
            trim(($complaint->duration ?? '').' '.($complaint->duration_unit ?? '')),
            $complaint->severity,
            $complaint->creator?->name ?? $complaint->doctor?->name,
        ], $export, 'created_at', [
            'hopc_entries' => HistoryOfPresentingComplaint::query()->tap(fn ($q) => $this->dateRange($q, 'created_at', $filters))->count(),
        ]);
    }

    protected function pharmacy(array $filters, bool $export): array
    {
        $query = DispensingRecord::with(['patient', 'visit', 'dispensedBy', 'prescriptionItem.drug', 'prescription'])
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('dispensed_by', $v));

        $this->dateRange($query, 'dispensed_at', $filters);

        return $this->table('pharmacy', $filters, $query, [
            __('reports.col_dispensed_at'), __('reports.col_visit'), __('reports.col_patient'), __('reports.col_prescription'), __('reports.col_medication'), __('reports.col_quantity'), __('reports.col_dispensed_by'),
        ], fn ($record) => [
            optional($record->dispensed_at)->format('d M Y H:i'),
            $record->visit?->visit_number,
            $record->patient?->full_name,
            $record->prescription?->prescription_number,
            $record->prescriptionItem?->drug?->name ?? $record->prescriptionItem?->drug_name,
            $record->quantity_dispensed,
            $record->dispensedBy?->name,
        ], $export, 'dispensed_at', [
            'prescriptions' => Prescription::query()->tap(fn ($q) => $this->dateRange($q, 'created_at', $filters))->count(),
        ]);
    }

    protected function investigations(array $filters, bool $export): array
    {
        $query = LabRequest::with(['visit', 'patient', 'targetDepartment', 'requestedBy', 'items'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['department_id'] ?? null, fn ($q, $v) => $q->where('target_department_id', $v))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('requested_by', $v));

        $this->dateRange($query, 'created_at', $filters);

        return $this->table('investigations', $filters, $query, [
            __('reports.col_request'), __('reports.col_visit'), __('reports.col_patient'), __('reports.col_department'), __('reports.col_urgency'), __('reports.col_items'), __('reports.col_status'), __('reports.col_requested_by'),
        ], fn ($request) => [
            $request->request_number,
            $request->visit?->visit_number,
            $request->patient?->full_name,
            $request->targetDepartment?->name,
            $request->urgency,
            $request->items->count(),
            $request->status,
            $request->requestedBy?->name,
        ], $export, 'created_at');
    }

    protected function procedures(array $filters, bool $export, string $key): array
    {
        $query = ProcedureRequest::with(['visit', 'patient', 'department', 'requestingDoctor', 'service'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['department_id'] ?? null, fn ($q, $v) => $q->where('department_id', $v))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('requested_by', $v));

        $this->dateRange($query, 'requested_at', $filters);

        return $this->table($key, $filters, $query, [
            __('reports.col_request'), __('reports.col_visit'), __('reports.col_patient'), __('reports.col_department'), __('reports.col_service'), __('reports.col_priority'), __('reports.col_status'), __('reports.col_requested_by'),
        ], fn ($request) => [
            $request->request_number,
            $request->visit?->visit_number,
            $request->patient?->full_name,
            $request->department?->name,
            $request->service?->name,
            $request->priority,
            (string) ($request->status?->value ?? $request->status),
            $request->requestingDoctor?->name,
        ], $export, 'requested_at');
    }

    protected function emergency(array $filters, bool $export): array
    {
        $query = EmergencyCase::with(['visit', 'patient', 'bay', 'assignedDoctor', 'assignedNurse'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('emergency_status', strtoupper($v)));

        $this->dateRange($query, 'arrival_time', $filters);

        return $this->table('emergency', $filters, $query, [
            __('reports.col_emergency_no'), __('reports.col_patient'), __('reports.col_arrival'), __('reports.col_triage'), __('reports.col_status'), __('reports.col_disposition'), __('reports.col_bay'), __('reports.col_team'),
        ], fn ($case) => [
            $case->emergency_number,
            $case->patient?->full_name,
            optional($case->arrival_time)->format('d M Y H:i'),
            $case->current_triage_category,
            $case->emergency_status,
            $case->disposition ?: 'Open',
            $case->bay?->name,
            trim(($case->assignedDoctor?->name ? 'Dr: '.$case->assignedDoctor->name : '').' '.($case->assignedNurse?->name ? 'Nurse: '.$case->assignedNurse->name : '')),
        ], $export, 'arrival_time');
    }

    protected function admission(array $filters, bool $export): array
    {
        $query = Admission::with(['visit', 'patient', 'bed.ward', 'admittedBy', 'dischargedBy'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v));

        $this->dateRange($query, 'admission_date', $filters);

        return $this->table('admission', $filters, $query, [
            __('reports.col_admission_no'), __('reports.col_patient'), __('reports.col_ward_bed'), __('reports.col_admission_date'), __('reports.col_status'), __('reports.col_los'), __('reports.col_admitted_by'), __('reports.col_discharged'),
        ], fn ($admission) => [
            $admission->admission_number,
            $admission->patient?->full_name,
            trim(($admission->bed?->ward?->name ?? '').' / '.($admission->bed?->bed_number ?? '')),
            optional($admission->admission_date)->format('d M Y H:i'),
            (string) ($admission->status?->value ?? $admission->status),
            $admission->length_of_stay,
            $admission->admittedBy?->name,
            optional($admission->actual_discharge_date)->format('d M Y H:i'),
        ], $export, 'admission_date');
    }

    protected function mar(array $filters, bool $export): array
    {
        $query = MedicationAdministration::with(['patient', 'admission.bed.ward', 'emergencyCase', 'medicationOrder.prescriber', 'administeredBy'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', strtoupper($v)))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('administered_by', $v));

        $this->dateRange($query, 'administered_at', $filters);

        return $this->table('mar', $filters, $query, [
            __('reports.col_dispensed_at'), __('reports.col_patient'), __('reports.col_location'), __('reports.col_medication'), __('reports.col_status'), __('reports.col_dose'), __('reports.col_nurse'), __('reports.col_reason'),
        ], fn ($record) => [
            optional($record->administered_at)->format('d M Y H:i'),
            $record->patient?->full_name,
            $record->admission?->bed?->ward?->name ?? $record->emergencyCase?->emergency_number ?? 'Visit',
            $record->medicationOrder?->display_name,
            $record->status,
            trim(($record->dose_given ?? '').' '.($record->dose_unit ?? '').' '.$record->route),
            $record->administeredBy?->name,
            $record->reason_not_given ?: $record->reaction ?: $record->notes,
        ], $export, 'administered_at', [
            'overdue_medication_tasks' => ClinicalTask::where('task_type', ClinicalTask::TYPE_MEDICATION_ADMINISTRATION)
                ->where('status', ClinicalTask::STATUS_OVERDUE)
                ->count(),
        ]);
    }

    protected function billing(array $filters, bool $export, ?User $user): array
    {
        $filters['_can_view_financial_values'] = $this->canViewFinancialValues($user);

        $query = Invoice::with(['patient', 'visit', 'createdBy'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v));

        $this->dateRange($query, 'created_at', $filters);

        return $this->table('billing', $filters, $query, [
            __('reports.col_invoice'), __('reports.col_visit'), __('reports.col_patient'), __('reports.col_billing_type'), __('reports.col_status'), __('reports.col_total'), __('reports.col_paid'), __('reports.col_balance'),
        ], fn ($invoice) => [
            $invoice->invoice_number,
            $invoice->visit?->visit_number,
            $invoice->patient?->full_name,
            (string) ($invoice->billing_type?->value ?? $invoice->billing_type),
            (string) ($invoice->status?->value ?? $invoice->status),
            number_format((float) $invoice->total_amount, 2),
            number_format((float) $invoice->amount_paid, 2),
            number_format((float) $invoice->balance, 2),
        ], $export, 'created_at', [
            'payments' => Payment::query()->tap(fn ($q) => $this->dateRange($q, 'paid_at', $filters))->count(),
            'billed_not_rendered' => ServiceRendering::whereIn('status', ServiceRendering::ACTIVE_STATUSES)->count(),
        ]);
    }

    protected function claims(array $filters, bool $export, ?User $user): array
    {
        $filters['_can_view_financial_values'] = $this->canViewFinancialValues($user);

        $query = Claim::with(['patient', 'visit', 'invoice', 'insuranceProvider', 'insuranceType'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v));

        $this->dateRange($query, 'claim_date', $filters);

        return $this->table('claims', $filters, $query, [
            __('reports.col_claim'), __('reports.col_patient'), __('reports.col_visit'), __('reports.col_claim_type'), __('reports.provider'), __('reports.col_status'), __('reports.col_claim_amount'), __('reports.col_paid'),
        ], fn ($claim) => [
            $claim->claim_number,
            $claim->patient?->full_name,
            $claim->visit?->visit_number,
            $claim->claim_type_code ?? $claim->insuranceType?->code,
            $claim->insuranceProvider?->name,
            (string) ($claim->status?->value ?? $claim->status),
            number_format((float) ($claim->total_claim_amount ?? $claim->total_amount), 2),
            number_format((float) $claim->paid_amount, 2),
        ], $export, 'claim_date');
    }

    protected function stock(array $filters, bool $export): array
    {
        $query = StockMovement::with(['product', 'drug', 'location', 'performedBy'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('direction', $v));

        $this->dateRange($query, 'movement_date', $filters);

        return $this->table('stock', $filters, $query, [
            __('reports.col_date'), __('reports.col_item'), __('reports.col_location'), __('reports.col_type'), __('reports.col_direction'), __('reports.col_quantity'), __('reports.col_batch'), __('reports.col_expiry'), __('reports.col_performed_by'),
        ], fn ($movement) => [
            optional($movement->movement_date)->format('d M Y H:i'),
            $movement->product?->name ?? $movement->drug?->name,
            $movement->location?->name,
            (string) ($movement->movement_type?->value ?? $movement->movement_type),
            (string) ($movement->direction?->value ?? $movement->direction),
            $movement->quantity,
            $movement->batch_no,
            optional($movement->expiry_date)->format('d M Y'),
            $movement->performedBy?->name,
        ], $export, 'movement_date');
    }

    protected function bloodBank(array $filters, bool $export): array
    {
        $query = BloodUnit::with(['donor', 'storageLocation', 'reservedForRequest.patient'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', strtoupper($v)))
            ->when($filters['blood_group'] ?? null, fn ($q, $v) => $q->where('blood_group', $v));

        $this->dateRange($query, 'created_at', $filters);

        return $this->table('blood-bank', $filters, $query, [
            __('reports.col_item'), __('reports.col_blood_group'), __('reports.col_type'), __('reports.investigations.title'), __('reports.col_status'), __('reports.col_expiry'), __('reports.col_location'), __('reports.col_patient'),
        ], fn ($unit) => [
            $unit->unit_number,
            $unit->blood_group,
            $unit->component_type,
            $unit->screening_status,
            $unit->status,
            optional($unit->expiry_date)->format('d M Y'),
            $unit->storageLocation?->name,
            $unit->reservedForRequest?->patient?->full_name,
        ], $export, 'created_at', [
            'pending_requests' => BloodRequest::where('status', BloodRequest::STATUS_PENDING)->count(),
        ]);
    }

    protected function table(string $key, array $filters, Builder $query, array $columns, callable $map, bool $export, string $orderColumn, array $extraSummary = []): array
    {
        $totalQuery = clone $query;

        // Build the status breakdown using the report's real status column (some
        // tables use `type` / `emergency_status` / `direction`, and several cast
        // status to a backed enum). We aggregate on the base query so enum-cast
        // keys stay scalar and can be used as array offsets.
        $statusColumn = $this->statusColumnFor($key);
        $statusCounts = [];
        if ($statusColumn) {
            $statusRows = (clone $query)->toBase()
                ->select($statusColumn, DB::raw('COUNT(*) as total'))
                ->groupBy($statusColumn)
                ->limit(12)
                ->get();

            foreach ($statusRows as $row) {
                $value = $row->{$statusColumn};
                $label = $value instanceof \BackedEnum ? $value->value : (string) ($value ?? '—');
                $statusCounts[$label] = $row->total;
            }
        }

        $rows = $export
            ? $query->orderByDesc($orderColumn)->limit(5000)->get()->map($map)->values()
            : $query->orderByDesc($orderColumn)->paginate(30)->withQueryString()->through($map);

        [$columns, $rows] = $this->redactProtectedColumns($key, $filters, $columns, $rows);

        return [
            'key' => $key,
            'meta' => $this->catalogue()[$key],
            'filters' => collect($filters)->except([
                '_can_view_sensitive_clinical',
                '_can_view_financial_values',
            ])->all(),
            'columns' => $columns,
            'rows' => $rows,
            'summary' => array_merge([
                'total' => (clone $totalQuery)->count(),
                'status_counts' => $statusCounts,
            ], $extraSummary),
        ];
    }

    protected function dateRange(Builder $query, string $column, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate($column, '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate($column, '<=', $filters['date_to']);
        }
    }

    protected function countInRange(Builder $query, string $column, array $filters): int
    {
        $this->dateRange($query, $column, $filters);

        return $query->count();
    }

    protected function sumInRange(Builder $query, string $column, array $filters, string $sumColumn): float
    {
        $this->dateRange($query, $column, $filters);

        return (float) $query->sum($sumColumn);
    }

    /**
     * The real "status" column to group the summary breakdown by, per report.
     * Returns null when the underlying table has no groupable status column.
     */
    protected function statusColumnFor(string $key): ?string
    {
        return match ($key) {
            'diagnoses' => 'type',
            'emergency' => 'emergency_status',
            'stock' => 'direction',
            'complaints', 'pharmacy' => null,
            default => 'status',
        };
    }

    protected function redactProtectedColumns(string $key, array $filters, array $columns, mixed $rows): array
    {
        $remove = match (true) {
            in_array($key, ['diagnoses', 'complaints'], true)
                && empty($filters['_can_view_sensitive_clinical']) => [4],
            $key === 'billing' && empty($filters['_can_view_financial_values']) => [5, 6, 7],
            $key === 'claims' && empty($filters['_can_view_financial_values']) => [6, 7],
            default => [],
        };

        if (empty($remove)) {
            return [$columns, $rows];
        }

        $redact = fn (array $row) => array_values(array_filter(
            $row,
            fn ($value, int $index) => ! in_array($index, $remove, true),
            ARRAY_FILTER_USE_BOTH
        ));

        $columns = array_values(array_filter(
            $columns,
            fn ($value, int $index) => ! in_array($index, $remove, true),
            ARRAY_FILTER_USE_BOTH
        ));

        if ($rows instanceof \Illuminate\Contracts\Pagination\Paginator) {
            $rows->setCollection($rows->getCollection()->map($redact));
        } elseif ($rows instanceof \Illuminate\Support\Collection) {
            $rows = $rows->map($redact)->values();
        }

        return [$columns, $rows];
    }

    protected function canViewFinancialValues(?User $user): bool
    {
        return $user?->can('reports.financial_values.view') ?? false;
    }

    protected function canViewSensitiveClinical(?User $user): bool
    {
        return $user?->can('reports.clinical_sensitive.view') ?? false;
    }
}
