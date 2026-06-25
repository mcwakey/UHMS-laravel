<?php

namespace App\Services\Dashboard;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Builds the common dashboard data contract for a given department-type key.
 *
 * Contract:
 *   [
 *     'title'         => string,
 *     'key'           => string,
 *     'department'    => ?Department,
 *     'kpis'          => array<int,array>,   // {title,value,icon,variant,route,format}
 *     'alerts'        => array<int,array>,   // {title,variant,icon,route}
 *     'queues'        => array<int,array>,   // {title,icon,view_all,rows,empty}
 *     'quick_actions' => array<int,array>,   // {label,icon,route,variant}
 *     'reports'       => array<int,array>,   // {label,icon,route}
 *   ]
 *
 * Every metric is wrapped so a bad query degrades to 0 rather than breaking the
 * page, and every link is gated on route existence + permission.
 */
class DepartmentDashboardService
{
    private User $user;

    public function build(string $key, User $user): array
    {
        $this->user = $user;

        $data = match ($key) {
            DepartmentDashboardResolver::MANAGEMENT => $this->management(),
            DepartmentDashboardResolver::CONSULTATION => $this->consultation(),
            DepartmentDashboardResolver::PHARMACY => $this->pharmacy(),
            DepartmentDashboardResolver::INVESTIGATION => $this->investigation(),
            DepartmentDashboardResolver::THEATRE => $this->theatre(),
            DepartmentDashboardResolver::BILLING => $this->billing(),
            DepartmentDashboardResolver::STOCK => $this->stock(),
            DepartmentDashboardResolver::ACCOUNTING => $this->accounting(),
            DepartmentDashboardResolver::EMERGENCY => $this->emergency(),
            DepartmentDashboardResolver::ADMISSION => $this->admission(),
            DepartmentDashboardResolver::BLOOD_BANK => $this->bloodBank(),
            DepartmentDashboardResolver::CLAIMS => $this->claims(),
            DepartmentDashboardResolver::HR => $this->hr(),
            DepartmentDashboardResolver::RECEPTION => $this->reception(),
            default => $this->generic(),
        };

        return array_merge([
            'key' => $key,
            'department' => $user->department,
            'kpis' => [],
            'alerts' => [],
            'queues' => [],
            'quick_actions' => [],
            'reports' => [],
        ], $data);
    }

    /* ===================================================================== */
    /* Department builders                                                   */
    /* ===================================================================== */

    private function consultation(): array
    {
        $V = \App\Models\Visit::class;
        $s = fn (string $status) => $this->count(fn () => $V::query()->whereDate('created_at', today())->where('status', $status)->count());

        return [
            'title' => __('dashboards.titles.consultation'),
            'kpis' => array_values(array_filter([
                $this->kpi(__('dashboards.consultation.waiting'), $s('waiting'), 'ti-users', 'warning'),
                $this->kpi(__('dashboards.consultation.in_consultation'), $s('consulting'), 'ti-stethoscope', 'info'),
                $this->kpi(__('dashboards.consultation.completed_today'), $s('completed'), 'ti-check', 'success'),
                $this->kpi(__('dashboards.consultation.visits_today'), $this->count(fn () => $V::query()->whereDate('created_at', today())->count()), 'ti-calendar', 'primary'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert(__('dashboards.consultation.alert_waiting'), $s('waiting'), 'warning', 'ti-clock'),
            ])),
            'queues' => [
                $this->visitQueue(__('dashboards.consultation.queue_title'), ['waiting', 'consulting']),
            ],
            'quick_actions' => $this->actions([
                [__('dashboards.consultation.action_new_visit'), 'ti-plus', 'admin.visits.create', 'primary', null],
                [__('dashboards.consultation.action_queue'), 'ti-list', 'admin.consultations.index', 'secondary', null],
                [__('dashboards.consultation.action_patients'), 'ti-users', 'admin.patients.index', 'secondary', 'patient.view'],
            ]),
            'reports' => $this->reportLinks([
                [__('dashboards.consultation.report_patients'), 'admin.reports.patients', 'ti-report'],
            ]),
        ];
    }

    private function pharmacy(): array
    {
        $P = \App\Models\Prescription::class;
        $s = fn (string $status) => $this->count(fn () => $P::query()->where('status', $status)->count());
        $lowStock = $this->pharmacyStockCount('<=', 'reorder');
        $outStock = $this->pharmacyStockCount('<=', 'zero');

        return [
            'title' => __('dashboards.titles.pharmacy'),
            'kpis' => array_values(array_filter([
                $this->kpi(__('dashboards.pharmacy.pending_to_bill'), $s('pending') + $s('partially_billed'), 'ti-clipboard-list', 'warning', 'admin.prescriptions.index'),
                $this->kpi(__('dashboards.pharmacy.billed_to_dispense'), $s('billed') + $s('partially_dispensed'), 'ti-receipt', 'info', 'admin.prescriptions.index'),
                $this->kpi(__('dashboards.pharmacy.dispensed_today'), $this->count(fn () => $P::query()->where('status', 'dispensed')->whereDate('updated_at', today())->count()), 'ti-check', 'success'),
                $this->kpi(__('dashboards.pharmacy.low_stock'), $lowStock, 'ti-alert-triangle', 'warning', 'admin.store.stock.balances'),
                $this->kpi(__('dashboards.pharmacy.out_of_stock'), $outStock, 'ti-x', 'danger', 'admin.store.stock.balances'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert(__('dashboards.pharmacy.alert_out_of_stock'), $outStock, 'danger', 'ti-x', 'admin.store.stock.balances'),
                $this->alert(__('dashboards.pharmacy.alert_low_stock'), $lowStock, 'warning', 'ti-alert-triangle', 'admin.store.stock.balances'),
            ])),
            'queues' => [
                $this->prescriptionQueue(),
            ],
            'quick_actions' => $this->actions([
                [__('dashboards.pharmacy.action_prescriptions'), 'ti-prescription', 'admin.prescriptions.index', 'primary', 'prescriptions.view'],
                [__('dashboards.pharmacy.action_counter_sale'), 'ti-cash-register', 'admin.billing.counter-sale.create', 'secondary', 'invoices.create'],
                [__('dashboards.pharmacy.action_stock_balances'), 'ti-list-numbers', 'admin.store.stock.balances', 'secondary', 'store.purchase.view'],
                [__('dashboards.pharmacy.action_new_requisition'), 'ti-clipboard-plus', 'admin.store.stock-requisitions.create', 'secondary', 'store.requisition.create'],
            ]),
            'reports' => $this->reportLinks([
                [__('dashboards.pharmacy.report_stock_valuation'), 'admin.reports.stock-valuation', 'ti-report-money'],
                [__('dashboards.pharmacy.report_expired_stock'), 'admin.reports.expired-stock', 'ti-clock-x'],
            ]),
        ];
    }

    private function investigation(): array
    {
        $L = \App\Models\LabRequest::class;
        $deptId = $this->user->department_id;
        $scope = fn ($q) => $deptId ? $q->where('target_department_id', $deptId) : $q;
        $s = fn (string $status) => $this->count(fn () => $scope($L::query())->where('status', $status)->count());

        return [
            'title' => __('dashboards.titles.investigation'),
            'kpis' => array_values(array_filter([
                $this->kpi(__('dashboards.investigation.pending_requests'), $s('pending'), 'ti-clipboard-list', 'warning', 'admin.lab.requests.index'),
                $this->kpi(__('dashboards.investigation.in_progress'), $s('processing'), 'ti-flask', 'info', 'admin.lab.requests.index'),
                $this->kpi(__('dashboards.investigation.completed_today'), $this->count(fn () => $scope($L::query())->where('status', 'completed')->whereDate('updated_at', today())->count()), 'ti-check', 'success'),
                $this->kpi(__('dashboards.investigation.urgent'), $this->count(fn () => $scope($L::query())->whereIn('status', ['pending', 'processing'])->where('urgency', 'urgent')->count()), 'ti-urgent', 'danger'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert(__('dashboards.investigation.alert_urgent'), $this->count(fn () => $scope($L::query())->whereIn('status', ['pending', 'processing'])->where('urgency', 'urgent')->count()), 'danger', 'ti-urgent', 'admin.lab.requests.index'),
            ])),
            'queues' => [
                $this->labQueue($scope),
            ],
            'quick_actions' => $this->actions([
                [__('dashboards.investigation.action_requests'), 'ti-microscope', 'admin.lab.requests.index', 'primary', 'lab.requests.view'],
                [__('dashboards.investigation.action_results'), 'ti-file-text', 'admin.lab.results.index', 'secondary', 'lab.results.view'],
                [__('dashboards.investigation.action_counter_sale'), 'ti-cash-register', 'admin.billing.counter-sale.create', 'secondary', 'invoices.create'],
            ]),
            'reports' => $this->reportLinks([
                [__('dashboards.investigation.report_revenue'), 'admin.reports.investigation-revenue', 'ti-report-money'],
            ]),
        ];
    }

    private function theatre(): array
    {
        $R = \App\Models\ProcedureRequest::class;
        $s = fn ($status) => $this->count(fn () => $R::query()->where('status', is_array($status) ? null : $status)->when(is_array($status), fn ($q) => $q->whereIn('status', $status))->count());

        return [
            'title' => __('dashboards.titles.theatre'),
            'kpis' => array_values(array_filter([
                $this->kpi(__('dashboards.theatre.pending_requests'), $s('requested'), 'ti-clipboard-list', 'secondary', 'admin.theatre.index'),
                $this->kpi(__('dashboards.theatre.scheduled'), $s('scheduled'), 'ti-calendar', 'primary', 'admin.theatre.index'),
                $this->kpi(__('dashboards.theatre.in_theatre'), $s(['pre_op', 'anaesthesia', 'in_surgery', 'surgery_done', 'post_op']), 'ti-activity', 'danger', 'admin.theatre.index'),
                $this->kpi(__('dashboards.theatre.completed_today'), $this->count(fn () => $R::query()->where('status', 'completed')->whereDate('updated_at', today())->count()), 'ti-check', 'success'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert(__('dashboards.theatre.alert_awaiting'), $s('requested'), 'warning', 'ti-clipboard-list', 'admin.theatre.index'),
            ])),
            'queues' => [
                $this->procedureQueue(),
            ],
            'quick_actions' => $this->actions([
                [__('dashboards.theatre.action_board'), 'ti-layout-board', 'admin.theatre.index', 'primary', 'procedures.view'],
                [__('dashboards.theatre.action_schedule'), 'ti-calendar', 'admin.theatre.calendar', 'secondary', 'procedure.schedule'],
                [__('dashboards.theatre.action_counter_sale'), 'ti-cash-register', 'admin.billing.counter-sale.create', 'secondary', 'invoices.create'],
            ]),
            'reports' => [],
        ];
    }

    private function billing(): array
    {
        $I = \App\Models\Invoice::class;
        $Pay = \App\Models\Payment::class;
        $unpaid = $this->count(fn () => $I::query()->whereIn('status', ['pending', 'partially_paid'])->count());
        $paymentsToday = $this->sum(fn () => $Pay::query()->whereDate('created_at', today())->sum('amount'));
        $invoicesToday = $this->count(fn () => $I::query()->whereDate('created_at', today())->count());

        return [
            'title' => __('dashboards.titles.billing'),
            'kpis' => array_values(array_filter([
                $this->kpi(__('dashboards.billing.unpaid_invoices'), $unpaid, 'ti-file-invoice', 'warning', 'admin.billing.invoices.index'),
                $this->kpi(__('dashboards.billing.invoices_today'), $invoicesToday, 'ti-files', 'info', 'admin.billing.invoices.index'),
                $this->permits('payments.view') ? $this->kpi(__('dashboards.billing.payments_today'), $paymentsToday, 'ti-cash', 'success', null, [], 'currency') : null,
                $this->kpi(__('dashboards.billing.partial_payments'), $this->count(fn () => $I::query()->where('status', 'partially_paid')->count()), 'ti-progress', 'secondary', 'admin.billing.invoices.index'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert(__('dashboards.billing.alert_awaiting_payment'), $unpaid, 'warning', 'ti-file-invoice', 'admin.billing.invoices.index'),
            ])),
            'queues' => [
                $this->invoiceQueue(),
            ],
            'quick_actions' => $this->actions([
                [__('dashboards.billing.action_invoices'), 'ti-file-invoice', 'admin.billing.invoices.index', 'primary', 'invoices.view'],
                [__('dashboards.billing.action_receive_payment'), 'ti-cash', 'admin.billing.payments.index', 'secondary', 'payments.create'],
                [__('dashboards.billing.action_counter_sale'), 'ti-cash-register', 'admin.billing.counter-sale.create', 'secondary', 'invoices.create'],
            ]),
            'reports' => $this->reportLinks([
                [__('dashboards.billing.report_ar_aging'), 'admin.billing.reports.aging', 'ti-report-money'],
            ]),
        ];
    }

    private function stock(): array
    {
        $low = $this->stockCount('reorder');
        $out = $this->stockCount('zero');
        $req = $this->count(fn () => \App\Models\StockRequisition::query()->where('status', 'submitted')->count());
        $po = $this->count(fn () => \App\Models\PurchaseOrder::query()->whereIn('status', ['submitted', 'approved', 'partially_received'])->count());

        return [
            'title' => __('dashboards.titles.stock'),
            'kpis' => array_values(array_filter([
                $this->kpi(__('dashboards.stock.low_stock'), $low, 'ti-alert-triangle', 'warning', 'admin.store.stock.balances'),
                $this->kpi(__('dashboards.stock.out_of_stock'), $out, 'ti-x', 'danger', 'admin.store.stock.balances'),
                $this->kpi(__('dashboards.stock.requisitions_to_approve'), $req, 'ti-clipboard-check', 'info', 'admin.store.stock-requisitions.index'),
                $this->kpi(__('dashboards.stock.open_purchase_orders'), $po, 'ti-truck-delivery', 'primary', 'admin.store.purchase-orders.index'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert(__('dashboards.stock.alert_out_of_stock'), $out, 'danger', 'ti-x', 'admin.store.stock.balances'),
                $this->alert(__('dashboards.stock.alert_low_stock'), $low, 'warning', 'ti-alert-triangle', 'admin.store.stock.balances'),
                $this->alert(__('dashboards.stock.alert_requisitions'), $req, 'info', 'ti-clipboard-check', 'admin.store.stock-requisitions.index'),
            ])),
            'queues' => [
                $this->requisitionQueue(),
            ],
            'quick_actions' => $this->actions([
                [__('dashboards.stock.action_stock_balances'), 'ti-list-numbers', 'admin.store.stock.balances', 'primary', 'store.purchase.view'],
                [__('dashboards.stock.action_new_requisition'), 'ti-clipboard-plus', 'admin.store.stock-requisitions.create', 'secondary', 'store.requisition.create'],
                [__('dashboards.stock.action_new_transfer'), 'ti-transfer', 'admin.store.stock.transfers.create', 'secondary', 'store.purchase.create'],
                [__('dashboards.stock.action_new_po'), 'ti-shopping-cart', 'admin.store.purchase-orders.create', 'secondary', 'store.purchase.create'],
            ]),
            'reports' => $this->reportLinks([
                [__('dashboards.stock.report_stock_operations'), 'admin.reports.stock', 'ti-report'],
                [__('dashboards.stock.report_stock_valuation'), 'admin.reports.stock-valuation', 'ti-report-money'],
            ]),
        ];
    }

    private function accounting(): array
    {
        $J = \App\Models\JournalEntry::class;
        $failed = $this->count(fn () => \App\Models\Invoice::query()->where('accounting_status', 'failed')->count());

        return [
            'title' => __('dashboards.titles.accounting'),
            'kpis' => array_values(array_filter([
                $this->kpi(__('dashboards.accounting.journal_entries'), $this->count(fn () => $J::query()->count()), 'ti-book', 'primary', 'admin.accounting.journals.index'),
                $this->kpi(__('dashboards.accounting.failed_postings'), $failed, 'ti-alert-octagon', 'danger'),
                $this->kpi(__('dashboards.accounting.invoices_today'), $this->count(fn () => \App\Models\Invoice::query()->whereDate('created_at', today())->count()), 'ti-files', 'info', 'admin.billing.invoices.index'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert(__('dashboards.accounting.alert_failed_postings'), $failed, 'danger', 'ti-alert-octagon'),
            ])),
            'queues' => [],
            'quick_actions' => $this->actions([
                [__('dashboards.accounting.action_general_ledger'), 'ti-book', 'admin.accounting.general-ledger.index', 'primary', 'accounts.view'],
                [__('dashboards.accounting.action_trial_balance'), 'ti-scale', 'admin.accounting.trial-balance.index', 'secondary', 'accounts.view'],
                [__('dashboards.accounting.action_journals'), 'ti-notebook', 'admin.accounting.journals.index', 'secondary', 'accounts.view'],
            ]),
            'reports' => $this->reportLinks([
                [__('dashboards.accounting.report_ar_aging'), 'admin.billing.reports.aging', 'ti-report-money'],
            ]),
        ];
    }

    private function management(): array
    {
        return [
            'title' => __('dashboards.titles.management'),
            'kpis' => array_values(array_filter([
                $this->kpi(__('dashboards.management.visits_today'), $this->count(fn () => \App\Models\Visit::query()->whereDate('created_at', today())->count()), 'ti-calendar', 'primary'),
                $this->kpi(__('dashboards.management.admitted_patients'), $this->count(fn () => \App\Models\Admission::query()->whereNull('discharged_at')->count()), 'ti-bed', 'info'),
                $this->permits('payments.view') ? $this->kpi(__('dashboards.management.revenue_today'), $this->sum(fn () => \App\Models\Payment::query()->whereDate('created_at', today())->sum('amount')), 'ti-cash', 'success', null, [], 'currency') : null,
                $this->kpi(__('dashboards.management.unpaid_invoices'), $this->count(fn () => \App\Models\Invoice::query()->whereIn('status', ['pending', 'partially_paid'])->count()), 'ti-file-invoice', 'warning', 'admin.billing.invoices.index'),
                $this->kpi(__('dashboards.management.low_stock'), $this->stockCount('reorder'), 'ti-alert-triangle', 'warning', 'admin.store.stock.balances'),
                $this->kpi(__('dashboards.management.pending_claims'), $this->count(fn () => \App\Models\Claim::query()->whereIn('status', ['draft', 'ready', 'submitted'])->count()), 'ti-clipboard-text', 'secondary'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert(__('dashboards.management.alerts_failed_postings'), $this->count(fn () => \App\Models\Invoice::query()->where('accounting_status', 'failed')->count()), 'danger', 'ti-alert-octagon'),
                $this->alert(__('dashboards.management.alerts_out_of_stock'), $this->stockCount('zero'), 'danger', 'ti-x', 'admin.store.stock.balances'),
            ])),
            'queues' => [],
            'quick_actions' => $this->actions([
                [__('dashboards.management.action_invoices'), 'ti-file-invoice', 'admin.billing.invoices.index', 'primary', 'invoices.view'],
                [__('dashboards.management.action_stock_balances'), 'ti-list-numbers', 'admin.store.stock.balances', 'secondary', 'store.purchase.view'],
                [__('dashboards.management.action_users'), 'ti-users-group', 'admin.users.index', 'secondary', 'users.view'],
                [__('dashboards.management.action_activity_logs'), 'ti-history', 'admin.activity-logs.index', 'secondary', null],
            ]),
            'reports' => $this->reportLinks([
                [__('dashboards.management.report_hub'), 'admin.reports.index', 'ti-report'],
            ]),
        ];
    }

    private function generic(): array
    {
        return [
            'title' => __('dashboards.titles.generic'),
            'kpis' => array_values(array_filter([
                $this->kpi(__('dashboards.generic.visits_today'), $this->count(fn () => \App\Models\Visit::query()->whereDate('created_at', today())->count()), 'ti-calendar', 'primary'),
            ])),
            'quick_actions' => $this->actions([
                [__('dashboards.generic.action_patients'), 'ti-users', 'admin.patients.index', 'primary', 'patient.view'],
                [__('dashboards.generic.action_visits'), 'ti-clipboard', 'admin.visits.index', 'secondary', 'visits.view'],
            ]),
        ];
    }

    private function emergency(): array
    {
        $E = \App\Models\EmergencyCase::class;
        $open = fn () => $E::query()->where(fn ($q) => $q->whereNull('disposition')->orWhere('disposition', ''));
        $triage = fn (array $cats) => $this->count(fn () => $open()->whereIn('final_triage_category', $cats)->count());

        return [
            'title' => __('dashboards.titles.emergency'),
            'kpis' => array_values(array_filter([
                $this->kpi(__('dashboards.emergency.active_cases'), $this->count(fn () => $open()->count()), 'ti-ambulance', 'danger', 'admin.emergency.board'),
                $this->kpi(__('dashboards.emergency.critical_red'), $triage(['RED', 'red']), 'ti-urgent', 'danger'),
                $this->kpi(__('dashboards.emergency.urgent_orange'), $triage(['ORANGE', 'orange']), 'ti-alert-triangle', 'warning'),
                $this->kpi(__('dashboards.emergency.cases_today'), $this->count(fn () => $E::query()->whereDate('created_at', today())->count()), 'ti-calendar', 'info'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert(__('dashboards.emergency.alert_critical'), $triage(['RED', 'red']), 'danger', 'ti-urgent', 'admin.emergency.board'),
            ])),
            'queues' => [$this->emergencyQueue($open)],
            'quick_actions' => $this->actions([
                [__('dashboards.emergency.action_board'), 'ti-layout-board', 'admin.emergency.board', 'primary', 'emergency.view'],
                [__('dashboards.emergency.action_new_case'), 'ti-plus', 'admin.emergency.cases.create', 'secondary', 'emergency.case.create'],
                [__('dashboards.emergency.action_bays'), 'ti-bed', 'admin.emergency.bays.index', 'secondary', null],
            ]),
            'reports' => $this->reportLinks([[__('dashboards.emergency.report_emergency'), 'admin.emergency.reports.index', 'ti-report']]),
        ];
    }

    private function admission(): array
    {
        $A = \App\Models\Admission::class;
        $B = \App\Models\Bed::class;
        $admitted = $this->count(fn () => $A::query()->where('status', 'admitted')->count());

        return [
            'title' => __('dashboards.titles.admission'),
            'kpis' => array_values(array_filter([
                $this->kpi(__('dashboards.admission.admitted_patients'), $admitted, 'ti-bed', 'primary', 'admin.admissions.index'),
                $this->kpi(__('dashboards.admission.beds_occupied'), $this->count(fn () => $B::query()->where('status', 'occupied')->count()), 'ti-bed-filled', 'info'),
                $this->kpi(__('dashboards.admission.beds_available'), $this->count(fn () => $B::query()->where('status', 'available')->count()), 'ti-bed', 'success'),
                $this->kpi(__('dashboards.admission.admission_requests'), $this->count(fn () => $A::query()->where('status', 'pending')->count()), 'ti-clipboard-list', 'warning', 'admin.admissions.requests'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert(__('dashboards.admission.alert_no_beds'), $this->count(fn () => $B::query()->where('status', 'available')->count()) === 0 ? 1 : 0, 'danger', 'ti-bed-off'),
            ])),
            'queues' => [$this->admissionQueue()],
            'quick_actions' => $this->actions([
                [__('dashboards.admission.action_admissions'), 'ti-bed', 'admin.admissions.index', 'primary', 'admissions.view'],
                [__('dashboards.admission.action_admit'), 'ti-plus', 'admin.admissions.create', 'secondary', 'admissions.create'],
                [__('dashboards.admission.action_medication_board'), 'ti-pill', 'admin.admissions.medication-board', 'secondary', null],
                [__('dashboards.admission.action_discharge'), 'ti-logout', 'admin.admissions.requests', 'secondary', null],
            ]),
            'reports' => $this->reportLinks([[__('dashboards.admission.report_admissions'), 'admin.reports.admissions', 'ti-report']]),
        ];
    }

    private function bloodBank(): array
    {
        $U = \App\Models\BloodUnit::class;
        $R = \App\Models\BloodRequest::class;
        $expiring = $this->count(fn () => $U::query()->where('status', 'AVAILABLE')->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', today()->addDays(7))->count());
        $pendingReq = $this->count(fn () => $R::query()->whereIn('status', ['PENDING', 'pending'])->count());

        return [
            'title' => __('dashboards.titles.blood_bank'),
            'kpis' => array_values(array_filter([
                $this->kpi(__('dashboards.blood_bank.available_units'), $this->count(fn () => $U::query()->where('status', 'AVAILABLE')->count()), 'ti-droplet', 'danger', 'admin.blood-bank.units.index'),
                $this->kpi(__('dashboards.blood_bank.expiring_soon'), $expiring, 'ti-clock-x', 'warning', 'admin.blood-bank.units.index'),
                $this->kpi(__('dashboards.blood_bank.pending_requests'), $pendingReq, 'ti-clipboard-list', 'info', 'admin.blood-bank.requests.index'),
                $this->kpi(__('dashboards.blood_bank.pending_screening'), $this->count(fn () => $U::query()->whereIn('screening_status', ['PENDING', 'pending'])->count()), 'ti-test-pipe', 'secondary'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert(__('dashboards.blood_bank.alert_expiring'), $expiring, 'warning', 'ti-clock-x', 'admin.blood-bank.units.index'),
                $this->alert(__('dashboards.blood_bank.alert_pending_requests'), $pendingReq, 'info', 'ti-clipboard-list', 'admin.blood-bank.requests.index'),
            ])),
            'queues' => [$this->bloodRequestQueue()],
            'quick_actions' => $this->actions([
                [__('dashboards.blood_bank.action_dashboard'), 'ti-layout-dashboard', 'admin.blood-bank.dashboard', 'primary', 'blood_bank.view'],
                [__('dashboards.blood_bank.action_requests'), 'ti-clipboard-list', 'admin.blood-bank.requests.index', 'secondary', null],
                [__('dashboards.blood_bank.action_units'), 'ti-droplet', 'admin.blood-bank.units.index', 'secondary', null],
                [__('dashboards.blood_bank.action_donations'), 'ti-heart-handshake', 'admin.blood-bank.donations.index', 'secondary', null],
            ]),
            'reports' => $this->reportLinks([[__('dashboards.blood_bank.report_blood_bank'), 'admin.reports.blood-bank', 'ti-report']]),
        ];
    }

    private function claims(): array
    {
        $C = \App\Models\Claim::class;
        $s = fn ($statuses) => $this->count(fn () => $C::query()->whereIn('status', (array) $statuses)->count());
        $rejected = $s(['rejected']);

        return [
            'title' => __('dashboards.titles.claims'),
            'kpis' => array_values(array_filter([
                $this->kpi(__('dashboards.claims.to_prepare'), $s(['draft']), 'ti-clipboard-list', 'secondary', 'admin.claims.index'),
                $this->kpi(__('dashboards.claims.ready_submitted'), $s(['ready', 'submitted', 'resubmitted']), 'ti-send', 'info', 'admin.claims.index'),
                $this->kpi(__('dashboards.claims.under_review'), $s(['acknowledged', 'under_review']), 'ti-eye-search', 'primary', 'admin.claims.index'),
                $this->kpi(__('dashboards.claims.approved_unpaid'), $s(['approved', 'partially_approved']), 'ti-checks', 'success', 'admin.claims.index'),
                $this->kpi(__('dashboards.claims.rejected'), $rejected, 'ti-x', 'danger', 'admin.claims.index'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert(__('dashboards.claims.alert_rejected'), $rejected, 'danger', 'ti-x', 'admin.claims.index'),
            ])),
            'queues' => [$this->claimsQueue()],
            'quick_actions' => $this->actions([
                [__('dashboards.claims.action_claims'), 'ti-clipboard-text', 'admin.claims.index', 'primary', 'claims.view'],
                [__('dashboards.claims.action_new_claim'), 'ti-plus', 'admin.claims.create', 'secondary', 'claims.create'],
            ]),
            'reports' => $this->reportLinks([[__('dashboards.claims.report_claims'), 'admin.reports.claims', 'ti-report-money']]),
        ];
    }

    private function hr(): array
    {
        $L = \App\Models\LeaveRequest::class;
        $pendingLeave = $this->count(fn () => $L::query()->where('status', 'pending')->count());

        return [
            'title' => __('dashboards.titles.hr'),
            'kpis' => array_values(array_filter([
                $this->kpi(__('dashboards.hr.active_staff'), $this->count(fn () => \App\Models\User::query()->count()), 'ti-users-group', 'primary', 'admin.hr.employees.index'),
                $this->kpi(__('dashboards.hr.pending_leave'), $pendingLeave, 'ti-calendar-off', 'warning', 'admin.hr.leave.index'),
                $this->kpi(__('dashboards.hr.payroll_draft'), $this->count(fn () => \App\Models\PayrollRecord::query()->where('status', 'draft')->count()), 'ti-file-dollar', 'secondary'),
                $this->kpi(__('dashboards.hr.payroll_approved'), $this->count(fn () => \App\Models\PayrollRecord::query()->where('status', 'approved')->count()), 'ti-check', 'success'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert(__('dashboards.hr.alert_pending_leave'), $pendingLeave, 'warning', 'ti-calendar-off', 'admin.hr.leave.index'),
            ])),
            'queues' => [$this->leaveQueue()],
            'quick_actions' => $this->actions([
                [__('dashboards.hr.action_employees'), 'ti-users-group', 'admin.hr.employees.index', 'primary', null],
                [__('dashboards.hr.action_leave'), 'ti-calendar', 'admin.hr.leave.index', 'secondary', null],
                [__('dashboards.hr.action_attendance'), 'ti-user-check', 'admin.hr.attendance.index', 'secondary', null],
                [__('dashboards.hr.action_payroll'), 'ti-coin', 'admin.hr.payroll.index', 'secondary', null],
            ]),
            'reports' => [],
        ];
    }

    private function reception(): array
    {
        $V = \App\Models\Visit::class;
        $A = \App\Models\Appointment::class;

        return [
            'title' => __('dashboards.titles.reception'),
            'kpis' => array_values(array_filter([
                $this->kpi(__('dashboards.reception.visits_today'), $this->count(fn () => $V::query()->whereDate('created_at', today())->count()), 'ti-clipboard', 'primary', 'admin.visits.index'),
                $this->kpi(__('dashboards.reception.appointments_today'), $this->count(fn () => $A::query()->whereDate('appointment_date', today())->count()), 'ti-calendar', 'info', 'admin.appointments.index'),
                $this->kpi(__('dashboards.reception.checked_in'), $this->count(fn () => $A::query()->whereDate('appointment_date', today())->where('status', 'checked_in')->count()), 'ti-user-check', 'success'),
                $this->kpi(__('dashboards.reception.waiting'), $this->count(fn () => $V::query()->whereDate('created_at', today())->whereIn('status', ['created', 'registered', 'walked_in', 'checked_in', 'queued'])->count()), 'ti-clock', 'warning'),
            ])),
            'alerts' => [],
            'queues' => [$this->appointmentQueue()],
            'quick_actions' => $this->actions([
                [__('dashboards.reception.action_new_visit'), 'ti-plus', 'admin.visits.create', 'primary', 'visits.create'],
                [__('dashboards.reception.action_new_appointment'), 'ti-calendar-plus', 'admin.appointments.create', 'secondary', null],
                [__('dashboards.reception.action_appointments'), 'ti-calendar', 'admin.appointments.index', 'secondary', null],
                [__('dashboards.reception.action_register_patient'), 'ti-user-plus', 'admin.patients.create', 'secondary', 'patient.create'],
            ]),
            'reports' => [],
        ];
    }

    /* ===================================================================== */
    /* Queue builders (defensive, limited)                                   */
    /* ===================================================================== */

    private function visitQueue(string $title, array $statuses): array
    {
        $rows = $this->rows(function () use ($statuses) {
            return \App\Models\Visit::query()
                ->with(['patient:id,first_name,last_name', 'department:id,name'])
                ->whereIn('status', $statuses)
                ->latest('id')->limit(10)->get()
                ->map(fn ($v) => [
                    'label' => trim(($v->patient?->first_name ?? '').' '.($v->patient?->last_name ?? '')) ?: 'Walk-in',
                    'meta' => $v->visit_number.' · '.($v->department?->name ?? ''),
                    'badge' => str_replace('_', ' ', (string) $v->status),
                    'badge_variant' => 'info',
                    'url' => $this->routeUrl('admin.consultations.show', $v->id),
                ])->all();
        });

        return $this->queue($title, 'ti-users', $rows, 'admin.consultations.index', __('dashboards.no_items_in_queue'));
    }

    private function prescriptionQueue(): array
    {
        $rows = $this->rows(function () {
            return \App\Models\Prescription::query()
                ->with('patient:id,first_name,last_name')
                ->whereIn('status', ['pending', 'partially_billed', 'billed', 'partially_dispensed'])
                ->latest('id')->limit(10)->get()
                ->map(fn ($p) => [
                    'label' => trim(($p->patient?->first_name ?? '').' '.($p->patient?->last_name ?? '')) ?: '—',
                    'meta' => $p->prescription_number ?? ('#'.$p->id),
                    'badge' => str_replace('_', ' ', (string) ($p->status?->value ?? $p->status)),
                    'badge_variant' => 'warning',
                    'url' => $this->routeUrl('admin.prescriptions.show', $p->id),
                ])->all();
        });

        return $this->queue(__('dashboards.pharmacy.queue_title'), 'ti-prescription', $rows, 'admin.prescriptions.index', __('dashboards.pharmacy.queue_empty'));
    }

    private function labQueue(Closure $scope): array
    {
        $rows = $this->rows(function () use ($scope) {
            return $scope(\App\Models\LabRequest::query())
                ->whereIn('status', ['pending', 'processing'])
                ->latest('id')->limit(10)->get()
                ->map(fn ($r) => [
                    'label' => $r->request_number,
                    'meta' => $r->patient?->full_name ?? $r->external_party_name ?? 'Walk-in',
                    'badge' => ucfirst((string) $r->status),
                    'badge_variant' => $r->urgency === 'urgent' ? 'danger' : 'info',
                    'url' => $this->routeUrl('admin.lab.requests.show', $r->id),
                ])->all();
        });

        return $this->queue(__('dashboards.investigation.queue_title'), 'ti-microscope', $rows, 'admin.lab.requests.index', __('dashboards.investigation.queue_empty'));
    }

    private function procedureQueue(): array
    {
        $rows = $this->rows(function () {
            return \App\Models\ProcedureRequest::query()
                ->with(['patient:id,first_name,last_name', 'service:id,name'])
                ->whereNotIn('status', ['completed', 'cancelled', 'rejected'])
                ->latest('id')->limit(10)->get()
                ->map(fn ($p) => [
                    'label' => $p->request_number ?? ('#'.$p->id),
                    'meta' => trim(($p->patient?->first_name ?? '').' '.($p->patient?->last_name ?? '')).' · '.($p->service?->name ?? ''),
                    'badge' => str_replace('_', ' ', (string) ($p->status?->value ?? $p->status)),
                    'badge_variant' => 'primary',
                    'url' => $this->routeUrl('admin.theatre.show', $p->id),
                ])->all();
        });

        return $this->queue(__('dashboards.theatre.queue_title'), 'ti-stethoscope', $rows, 'admin.theatre.index', __('dashboards.theatre.queue_empty'));
    }

    private function invoiceQueue(): array
    {
        $rows = $this->rows(function () {
            return \App\Models\Invoice::query()
                ->with('patient:id,first_name,last_name')
                ->whereIn('status', ['pending', 'partially_paid'])
                ->latest('id')->limit(10)->get()
                ->map(fn ($i) => [
                    'label' => $i->invoice_number,
                    'meta' => $i->patient?->full_name ?? $i->external_party_name ?? '—',
                    'badge' => 'GHS '.number_format((float) ($i->balance ?? 0), 2),
                    'badge_variant' => 'warning',
                    'url' => $this->routeUrl('admin.billing.invoices.show', $i->id),
                ])->all();
        });

        return $this->queue(__('dashboards.billing.queue_title'), 'ti-file-invoice', $rows, 'admin.billing.invoices.index', __('dashboards.billing.queue_empty'));
    }

    private function requisitionQueue(): array
    {
        $rows = $this->rows(function () {
            return \App\Models\StockRequisition::query()
                ->with('department:id,name')
                ->where('status', 'submitted')
                ->latest('id')->limit(10)->get()
                ->map(fn ($r) => [
                    'label' => $r->requisition_number,
                    'meta' => $r->department?->name ?? '',
                    'badge' => 'Submitted',
                    'badge_variant' => 'info',
                    'url' => $this->routeUrl('admin.store.stock-requisitions.show', $r->id),
                ])->all();
        });

        return $this->queue(__('dashboards.stock.queue_title'), 'ti-clipboard-check', $rows, 'admin.store.stock-requisitions.index', __('dashboards.stock.queue_empty'));
    }

    private function emergencyQueue(Closure $open): array
    {
        $rows = $this->rows(function () use ($open) {
            return $open()->with('patient:id,first_name,last_name')->latest('id')->limit(10)->get()
                ->map(fn ($c) => [
                    'label' => trim(($c->patient?->first_name ?? '').' '.($c->patient?->last_name ?? '')) ?: ($c->case_number ?? ('#'.$c->id)),
                    'meta' => $c->case_number ?? '',
                    'badge' => ucfirst(strtolower((string) ($c->final_triage_category ?? '—'))),
                    'badge_variant' => in_array(strtolower((string) $c->final_triage_category), ['red', 'orange'], true) ? 'danger' : 'info',
                    'url' => $this->routeUrl('admin.emergency.cases.show', $c->id),
                ])->all();
        });

        return $this->queue(__('dashboards.emergency.queue_title'), 'ti-ambulance', $rows, 'admin.emergency.board', __('dashboards.emergency.queue_empty'));
    }

    private function admissionQueue(): array
    {
        $rows = $this->rows(function () {
            return \App\Models\Admission::query()
                ->with('patient:id,first_name,last_name')
                ->where('status', 'admitted')
                ->latest('id')->limit(10)->get()
                ->map(fn ($a) => [
                    'label' => trim(($a->patient?->first_name ?? '').' '.($a->patient?->last_name ?? '')) ?: ('#'.$a->id),
                    'meta' => $a->admission_date ? 'Admitted '.\Illuminate\Support\Carbon::parse($a->admission_date)->format('d M Y') : '',
                    'badge' => 'Admitted',
                    'badge_variant' => 'info',
                    'url' => $this->routeUrl('admin.admissions.show', $a->id),
                ])->all();
        });

        return $this->queue(__('dashboards.admission.queue_title'), 'ti-bed', $rows, 'admin.admissions.index', __('dashboards.admission.queue_empty'));
    }

    private function bloodRequestQueue(): array
    {
        $rows = $this->rows(function () {
            return \App\Models\BloodRequest::query()
                ->whereIn('status', ['PENDING', 'pending', 'APPROVED', 'approved'])
                ->latest('id')->limit(10)->get()
                ->map(fn ($r) => [
                    'label' => $r->request_number ?? ('#'.$r->id),
                    'meta' => $r->blood_group ?? '',
                    'badge' => ucfirst(strtolower((string) $r->status)),
                    'badge_variant' => 'danger',
                    'url' => $this->routeUrl('admin.blood-bank.requests.index'),
                ])->all();
        });

        return $this->queue(__('dashboards.blood_bank.queue_title'), 'ti-droplet', $rows, 'admin.blood-bank.requests.index', __('dashboards.blood_bank.queue_empty'));
    }

    private function claimsQueue(): array
    {
        $rows = $this->rows(function () {
            return \App\Models\Claim::query()
                ->whereIn('status', ['draft', 'ready', 'rejected', 'resubmitted'])
                ->latest('id')->limit(10)->get()
                ->map(fn ($c) => [
                    'label' => $c->claim_number ?? ('#'.$c->id),
                    'meta' => '',
                    'badge' => str_replace('_', ' ', (string) ($c->status?->value ?? $c->status)),
                    'badge_variant' => (string) ($c->status?->value ?? $c->status) === 'rejected' ? 'danger' : 'secondary',
                    'url' => $this->routeUrl('admin.claims.show', $c->id),
                ])->all();
        });

        return $this->queue(__('dashboards.claims.queue_title'), 'ti-clipboard-text', $rows, 'admin.claims.index', __('dashboards.claims.queue_empty'));
    }

    private function leaveQueue(): array
    {
        $rows = $this->rows(function () {
            return \App\Models\LeaveRequest::query()
                ->with('user:id,first_name,last_name')
                ->where('status', 'pending')
                ->latest('id')->limit(10)->get()
                ->map(fn ($l) => [
                    'label' => trim(($l->user?->first_name ?? '').' '.($l->user?->last_name ?? '')) ?: ('#'.$l->id),
                    'meta' => $l->leave_type ?? '',
                    'badge' => 'Pending',
                    'badge_variant' => 'warning',
                    'url' => $this->routeUrl('admin.hr.leave.index'),
                ])->all();
        });

        return $this->queue(__('dashboards.hr.queue_title'), 'ti-calendar-off', $rows, 'admin.hr.leave.index', __('dashboards.hr.queue_empty'));
    }

    private function appointmentQueue(): array
    {
        $rows = $this->rows(function () {
            return \App\Models\Appointment::query()
                ->with('patient:id,first_name,last_name')
                ->whereDate('appointment_date', today())
                ->whereIn('status', ['scheduled', 'confirmed', 'checked_in'])
                ->orderBy('start_time')->limit(10)->get()
                ->map(fn ($a) => [
                    'label' => trim(($a->patient?->first_name ?? '').' '.($a->patient?->last_name ?? '')) ?: ('#'.$a->id),
                    'meta' => $a->start_time ? \Illuminate\Support\Str::of((string) $a->start_time)->substr(0, 5) : '',
                    'badge' => ucfirst(str_replace('_', ' ', (string) ($a->status?->value ?? $a->status))),
                    'badge_variant' => 'info',
                    'url' => $this->routeUrl('admin.appointments.show', $a->id),
                ])->all();
        });

        return $this->queue(__('dashboards.reception.queue_title'), 'ti-calendar', $rows, 'admin.appointments.index', __('dashboards.reception.queue_empty'));
    }

    /* ===================================================================== */
    /* Helpers                                                               */
    /* ===================================================================== */

    private function count(Closure $q): int
    {
        try {
            return (int) $q();
        } catch (Throwable) {
            return 0;
        }
    }

    private function sum(Closure $q): float
    {
        try {
            return (float) $q();
        } catch (Throwable) {
            return 0.0;
        }
    }

    private function rows(Closure $q): array
    {
        try {
            return $q();
        } catch (Throwable) {
            return [];
        }
    }

    private function permits(string $permission): bool
    {
        try {
            return $this->user->can($permission);
        } catch (Throwable) {
            return false;
        }
    }

    private function kpi(string $title, $value, string $icon, string $variant, ?string $routeName = null, array $params = [], ?string $format = null): array
    {
        return [
            'title' => $title,
            'value' => $value,
            'icon' => $icon,
            'variant' => $variant,
            'route' => $this->routeUrl($routeName, ...$params),
            'format' => $format ?? (is_numeric($value) ? 'number' : null),
        ];
    }

    private function alert(string $title, int $count, string $variant, string $icon, ?string $routeName = null): ?array
    {
        if ($count <= 0) {
            return null;
        }

        return [
            'title' => $title,
            'count' => $count,
            'variant' => $variant,
            'icon' => $icon,
            'route' => $this->routeUrl($routeName),
        ];
    }

    /**
     * @param  array<int,array{0:string,1:string,2:string,3:string,4:?string}>  $defs
     */
    private function actions(array $defs): array
    {
        $out = [];
        foreach ($defs as [$label, $icon, $routeName, $variant, $permission]) {
            $url = $this->routeUrl($routeName);
            if (! $url) {
                continue;
            }
            if ($permission && ! $this->permits($permission)) {
                continue;
            }
            $out[] = ['label' => $label, 'icon' => $icon, 'route' => $url, 'variant' => $variant];
        }

        return $out;
    }

    /**
     * @param  array<int,array{0:string,1:string,2:string}>  $defs
     */
    private function reportLinks(array $defs): array
    {
        $out = [];
        foreach ($defs as [$label, $routeName, $icon]) {
            $url = $this->routeUrl($routeName);
            if ($url) {
                $out[] = ['label' => $label, 'icon' => $icon, 'route' => $url];
            }
        }

        return $out;
    }

    private function queue(string $title, string $icon, array $rows, ?string $viewAllRoute, string $empty): array
    {
        return [
            'title' => $title,
            'icon' => $icon,
            'rows' => $rows,
            'view_all' => $this->routeUrl($viewAllRoute),
            'empty' => $empty,
        ];
    }

    private function routeUrl(?string $name, ...$params): ?string
    {
        if (! $name || ! Route::has($name)) {
            return null;
        }
        try {
            return route($name, $params);
        } catch (Throwable) {
            return null;
        }
    }

    /** Count pharmacy-location products at/under reorder or zero. */
    private function pharmacyStockCount(string $op, string $mode): int
    {
        return $this->count(function () use ($mode) {
            $q = \App\Models\StockBalance::query()
                ->whereHas('location', fn ($l) => $l->where('type', 'pharmacy'));
            if ($mode === 'zero') {
                $q->where('quantity_on_hand', '<=', 0);
            } else {
                $q->whereColumn('quantity_on_hand', '<=', 'products.reorder_level')
                  ->join('products', 'products.id', '=', 'stock_balances.product_id')
                  ->where('products.reorder_level', '>', 0);
            }

            return $q->count();
        });
    }

    /** Count all stock products at/under reorder or zero (store-wide). */
    private function stockCount(string $mode): int
    {
        return $this->count(function () use ($mode) {
            if ($mode === 'zero') {
                return \App\Models\StockBalance::query()->where('quantity_on_hand', '<=', 0)->count();
            }

            return \App\Models\StockBalance::query()
                ->join('products', 'products.id', '=', 'stock_balances.product_id')
                ->whereColumn('stock_balances.quantity_on_hand', '<=', 'products.reorder_level')
                ->where('products.reorder_level', '>', 0)
                ->count();
        });
    }
}
