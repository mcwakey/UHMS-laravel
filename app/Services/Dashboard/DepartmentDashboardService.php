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
            'title' => 'Consultation / OPD Dashboard',
            'kpis' => array_values(array_filter([
                $this->kpi('Waiting Consultation', $s('waiting_consultation'), 'ti-users', 'warning'),
                $this->kpi('In Consultation', $s('consulting'), 'ti-stethoscope', 'info'),
                $this->kpi('Completed Today', $s('completed'), 'ti-check', 'success'),
                $this->kpi('Visits Today', $this->count(fn () => $V::query()->whereDate('created_at', today())->count()), 'ti-calendar', 'primary'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert('Patients waiting for consultation', $s('waiting_consultation'), 'warning', 'ti-clock'),
            ])),
            'queues' => [
                $this->visitQueue('Waiting Consultation Queue', ['waiting_consultation', 'consulting']),
            ],
            'quick_actions' => $this->actions([
                ['New Visit', 'ti-plus', 'admin.visits.create', 'primary', null],
                ['Consultation Queue', 'ti-list', 'admin.consultations.index', 'secondary', null],
                ['Patients', 'ti-users', 'admin.patients.index', 'secondary', 'patient.view'],
            ]),
            'reports' => $this->reportLinks([
                ['Patient Report', 'admin.reports.patients', 'ti-report'],
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
            'title' => 'Pharmacy Dashboard',
            'kpis' => array_values(array_filter([
                $this->kpi('Pending (to bill)', $s('pending') + $s('partially_billed'), 'ti-clipboard-list', 'warning', 'admin.prescriptions.index'),
                $this->kpi('Billed (to dispense)', $s('billed') + $s('partially_dispensed'), 'ti-receipt', 'info', 'admin.prescriptions.index'),
                $this->kpi('Dispensed Today', $this->count(fn () => $P::query()->where('status', 'dispensed')->whereDate('updated_at', today())->count()), 'ti-check', 'success'),
                $this->kpi('Low Stock Drugs', $lowStock, 'ti-alert-triangle', 'warning', 'admin.store.stock.balances'),
                $this->kpi('Out of Stock', $outStock, 'ti-x', 'danger', 'admin.store.stock.balances'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert('Drugs out of stock', $outStock, 'danger', 'ti-x', 'admin.store.stock.balances'),
                $this->alert('Drugs low on stock', $lowStock, 'warning', 'ti-alert-triangle', 'admin.store.stock.balances'),
            ])),
            'queues' => [
                $this->prescriptionQueue(),
            ],
            'quick_actions' => $this->actions([
                ['Prescriptions', 'ti-prescription', 'admin.prescriptions.index', 'primary', 'prescriptions.view'],
                ['Counter Sale', 'ti-cash-register', 'admin.billing.counter-sale.create', 'secondary', 'invoices.create'],
                ['Stock Balances', 'ti-list-numbers', 'admin.store.stock.balances', 'secondary', 'store.purchase.view'],
                ['New Requisition', 'ti-clipboard-plus', 'admin.store.stock-requisitions.create', 'secondary', 'store.requisition.create'],
            ]),
            'reports' => $this->reportLinks([
                ['Stock Valuation', 'admin.reports.stock-valuation', 'ti-report-money'],
                ['Expired Stock', 'admin.reports.expired-stock', 'ti-clock-x'],
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
            'title' => 'Investigations / Laboratory Dashboard',
            'kpis' => array_values(array_filter([
                $this->kpi('Pending Requests', $s('pending'), 'ti-clipboard-list', 'warning', 'admin.lab.requests.index'),
                $this->kpi('In Progress', $s('processing'), 'ti-flask', 'info', 'admin.lab.requests.index'),
                $this->kpi('Completed Today', $this->count(fn () => $scope($L::query())->where('status', 'completed')->whereDate('updated_at', today())->count()), 'ti-check', 'success'),
                $this->kpi('Urgent', $this->count(fn () => $scope($L::query())->whereIn('status', ['pending', 'processing'])->where('urgency', 'urgent')->count()), 'ti-urgent', 'danger'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert('Urgent investigations waiting', $this->count(fn () => $scope($L::query())->whereIn('status', ['pending', 'processing'])->where('urgency', 'urgent')->count()), 'danger', 'ti-urgent', 'admin.lab.requests.index'),
            ])),
            'queues' => [
                $this->labQueue($scope),
            ],
            'quick_actions' => $this->actions([
                ['Investigation Requests', 'ti-microscope', 'admin.lab.requests.index', 'primary', 'lab.requests.view'],
                ['Results', 'ti-file-text', 'admin.lab.results.index', 'secondary', 'lab.results.view'],
                ['Counter Sale', 'ti-cash-register', 'admin.billing.counter-sale.create', 'secondary', 'invoices.create'],
            ]),
            'reports' => $this->reportLinks([
                ['Investigation Revenue', 'admin.reports.investigation-revenue', 'ti-report-money'],
            ]),
        ];
    }

    private function theatre(): array
    {
        $R = \App\Models\ProcedureRequest::class;
        $s = fn ($status) => $this->count(fn () => $R::query()->where('status', is_array($status) ? null : $status)->when(is_array($status), fn ($q) => $q->whereIn('status', $status))->count());

        return [
            'title' => 'Theatre & Procedures Dashboard',
            'kpis' => array_values(array_filter([
                $this->kpi('Pending Requests', $s('requested'), 'ti-clipboard-list', 'secondary', 'admin.theatre.index'),
                $this->kpi('Scheduled', $s('scheduled'), 'ti-calendar', 'primary', 'admin.theatre.index'),
                $this->kpi('In Theatre', $s(['pre_op', 'anaesthesia', 'in_surgery', 'surgery_done', 'post_op']), 'ti-activity', 'danger', 'admin.theatre.index'),
                $this->kpi('Completed Today', $this->count(fn () => $R::query()->where('status', 'completed')->whereDate('updated_at', today())->count()), 'ti-check', 'success'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert('Procedures awaiting acceptance', $s('requested'), 'warning', 'ti-clipboard-list', 'admin.theatre.index'),
            ])),
            'queues' => [
                $this->procedureQueue(),
            ],
            'quick_actions' => $this->actions([
                ['Theatre Board', 'ti-layout-board', 'admin.theatre.index', 'primary', 'procedures.view'],
                ['Schedule', 'ti-calendar', 'admin.theatre.calendar', 'secondary', 'procedure.schedule'],
                ['Counter Sale', 'ti-cash-register', 'admin.billing.counter-sale.create', 'secondary', 'invoices.create'],
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
            'title' => 'Billing / Cashier Dashboard',
            'kpis' => array_values(array_filter([
                $this->kpi('Unpaid Invoices', $unpaid, 'ti-file-invoice', 'warning', 'admin.billing.invoices.index'),
                $this->kpi('Invoices Today', $invoicesToday, 'ti-files', 'info', 'admin.billing.invoices.index'),
                $this->permits('payments.view') ? $this->kpi('Payments Today', $paymentsToday, 'ti-cash', 'success', null, [], 'currency') : null,
                $this->kpi('Partial Payments', $this->count(fn () => $I::query()->where('status', 'partially_paid')->count()), 'ti-progress', 'secondary', 'admin.billing.invoices.index'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert('Invoices awaiting payment', $unpaid, 'warning', 'ti-file-invoice', 'admin.billing.invoices.index'),
            ])),
            'queues' => [
                $this->invoiceQueue(),
            ],
            'quick_actions' => $this->actions([
                ['Invoices', 'ti-file-invoice', 'admin.billing.invoices.index', 'primary', 'invoices.view'],
                ['Receive Payment', 'ti-cash', 'admin.billing.payments.index', 'secondary', 'payments.create'],
                ['Counter Sale', 'ti-cash-register', 'admin.billing.counter-sale.create', 'secondary', 'invoices.create'],
            ]),
            'reports' => $this->reportLinks([
                ['AR Aging', 'admin.billing.reports.aging', 'ti-report-money'],
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
            'title' => 'Stock / Store & Procurement Dashboard',
            'kpis' => array_values(array_filter([
                $this->kpi('Low Stock', $low, 'ti-alert-triangle', 'warning', 'admin.store.stock.balances'),
                $this->kpi('Out of Stock', $out, 'ti-x', 'danger', 'admin.store.stock.balances'),
                $this->kpi('Requisitions to Approve', $req, 'ti-clipboard-check', 'info', 'admin.store.stock-requisitions.index'),
                $this->kpi('Open Purchase Orders', $po, 'ti-truck-delivery', 'primary', 'admin.store.purchase-orders.index'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert('Products out of stock', $out, 'danger', 'ti-x', 'admin.store.stock.balances'),
                $this->alert('Products low on stock', $low, 'warning', 'ti-alert-triangle', 'admin.store.stock.balances'),
                $this->alert('Requisitions awaiting approval', $req, 'info', 'ti-clipboard-check', 'admin.store.stock-requisitions.index'),
            ])),
            'queues' => [
                $this->requisitionQueue(),
            ],
            'quick_actions' => $this->actions([
                ['Stock Balances', 'ti-list-numbers', 'admin.store.stock.balances', 'primary', 'store.purchase.view'],
                ['New Requisition', 'ti-clipboard-plus', 'admin.store.stock-requisitions.create', 'secondary', 'store.requisition.create'],
                ['New Transfer', 'ti-transfer', 'admin.store.stock.transfers.create', 'secondary', 'store.purchase.create'],
                ['New Purchase Order', 'ti-shopping-cart', 'admin.store.purchase-orders.create', 'secondary', 'store.purchase.create'],
            ]),
            'reports' => $this->reportLinks([
                ['Stock Operations', 'admin.reports.stock', 'ti-report'],
                ['Stock Valuation', 'admin.reports.stock-valuation', 'ti-report-money'],
            ]),
        ];
    }

    private function accounting(): array
    {
        $J = \App\Models\JournalEntry::class;
        $failed = $this->count(fn () => \App\Models\Invoice::query()->where('accounting_status', 'failed')->count());

        return [
            'title' => 'Accounting / Finance Dashboard',
            'kpis' => array_values(array_filter([
                $this->kpi('Journal Entries', $this->count(fn () => $J::query()->count()), 'ti-book', 'primary', 'admin.accounting.journals.index'),
                $this->kpi('Failed Postings', $failed, 'ti-alert-octagon', 'danger'),
                $this->kpi('Invoices Today', $this->count(fn () => \App\Models\Invoice::query()->whereDate('created_at', today())->count()), 'ti-files', 'info', 'admin.billing.invoices.index'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert('Failed accounting postings', $failed, 'danger', 'ti-alert-octagon'),
            ])),
            'queues' => [],
            'quick_actions' => $this->actions([
                ['General Ledger', 'ti-book', 'admin.accounting.general-ledger.index', 'primary', 'accounts.view'],
                ['Trial Balance', 'ti-scale', 'admin.accounting.trial-balance.index', 'secondary', 'accounts.view'],
                ['Journals', 'ti-notebook', 'admin.accounting.journals.index', 'secondary', 'accounts.view'],
            ]),
            'reports' => $this->reportLinks([
                ['AR Aging', 'admin.billing.reports.aging', 'ti-report-money'],
            ]),
        ];
    }

    private function management(): array
    {
        return [
            'title' => 'Management Dashboard',
            'kpis' => array_values(array_filter([
                $this->kpi('Visits Today', $this->count(fn () => \App\Models\Visit::query()->whereDate('created_at', today())->count()), 'ti-calendar', 'primary'),
                $this->kpi('Admitted Patients', $this->count(fn () => \App\Models\Admission::query()->whereNull('discharged_at')->count()), 'ti-bed', 'info'),
                $this->permits('payments.view') ? $this->kpi('Revenue Today', $this->sum(fn () => \App\Models\Payment::query()->whereDate('created_at', today())->sum('amount')), 'ti-cash', 'success', null, [], 'currency') : null,
                $this->kpi('Unpaid Invoices', $this->count(fn () => \App\Models\Invoice::query()->whereIn('status', ['pending', 'partially_paid'])->count()), 'ti-file-invoice', 'warning', 'admin.billing.invoices.index'),
                $this->kpi('Low Stock', $this->stockCount('reorder'), 'ti-alert-triangle', 'warning', 'admin.store.stock.balances'),
                $this->kpi('Pending Claims', $this->count(fn () => \App\Models\Claim::query()->whereIn('status', ['draft', 'ready', 'submitted'])->count()), 'ti-clipboard-text', 'secondary'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert('Failed accounting postings', $this->count(fn () => \App\Models\Invoice::query()->where('accounting_status', 'failed')->count()), 'danger', 'ti-alert-octagon'),
                $this->alert('Products out of stock', $this->stockCount('zero'), 'danger', 'ti-x', 'admin.store.stock.balances'),
            ])),
            'queues' => [],
            'quick_actions' => $this->actions([
                ['Invoices', 'ti-file-invoice', 'admin.billing.invoices.index', 'primary', 'invoices.view'],
                ['Stock Balances', 'ti-list-numbers', 'admin.store.stock.balances', 'secondary', 'store.purchase.view'],
                ['Users & Roles', 'ti-users-group', 'admin.users.index', 'secondary', 'users.view'],
                ['Activity Logs', 'ti-history', 'admin.activity-logs.index', 'secondary', null],
            ]),
            'reports' => $this->reportLinks([
                ['Reports Hub', 'admin.reports.index', 'ti-report'],
            ]),
        ];
    }

    private function generic(): array
    {
        return [
            'title' => 'My Dashboard',
            'kpis' => array_values(array_filter([
                $this->kpi('Visits Today', $this->count(fn () => \App\Models\Visit::query()->whereDate('created_at', today())->count()), 'ti-calendar', 'primary'),
            ])),
            'quick_actions' => $this->actions([
                ['Patients', 'ti-users', 'admin.patients.index', 'primary', 'patient.view'],
                ['Visits', 'ti-clipboard', 'admin.visits.index', 'secondary', 'visits.view'],
            ]),
        ];
    }

    private function emergency(): array
    {
        $E = \App\Models\EmergencyCase::class;
        $open = fn () => $E::query()->where(fn ($q) => $q->whereNull('disposition')->orWhere('disposition', ''));
        $triage = fn (array $cats) => $this->count(fn () => $open()->whereIn('final_triage_category', $cats)->count());

        return [
            'title' => 'Emergency / Casualty Dashboard',
            'kpis' => array_values(array_filter([
                $this->kpi('Active Cases', $this->count(fn () => $open()->count()), 'ti-ambulance', 'danger', 'admin.emergency.board'),
                $this->kpi('Critical (Red)', $triage(['RED', 'red']), 'ti-urgent', 'danger'),
                $this->kpi('Urgent (Orange)', $triage(['ORANGE', 'orange']), 'ti-alert-triangle', 'warning'),
                $this->kpi('Cases Today', $this->count(fn () => $E::query()->whereDate('created_at', today())->count()), 'ti-calendar', 'info'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert('Critical (Red) cases active', $triage(['RED', 'red']), 'danger', 'ti-urgent', 'admin.emergency.board'),
            ])),
            'queues' => [$this->emergencyQueue($open)],
            'quick_actions' => $this->actions([
                ['Emergency Board', 'ti-layout-board', 'admin.emergency.board', 'primary', 'emergency.view'],
                ['New Case', 'ti-plus', 'admin.emergency.cases.create', 'secondary', 'emergency.case.create'],
                ['Bays', 'ti-bed', 'admin.emergency.bays.index', 'secondary', null],
            ]),
            'reports' => $this->reportLinks([['Emergency Reports', 'admin.emergency.reports.index', 'ti-report']]),
        ];
    }

    private function admission(): array
    {
        $A = \App\Models\Admission::class;
        $B = \App\Models\Bed::class;
        $admitted = $this->count(fn () => $A::query()->where('status', 'admitted')->count());

        return [
            'title' => 'Admission / Ward Dashboard',
            'kpis' => array_values(array_filter([
                $this->kpi('Admitted Patients', $admitted, 'ti-bed', 'primary', 'admin.admissions.index'),
                $this->kpi('Beds Occupied', $this->count(fn () => $B::query()->where('status', 'occupied')->count()), 'ti-bed-filled', 'info'),
                $this->kpi('Beds Available', $this->count(fn () => $B::query()->where('status', 'available')->count()), 'ti-bed', 'success'),
                $this->kpi('Admission Requests', $this->count(fn () => $A::query()->where('status', 'pending')->count()), 'ti-clipboard-list', 'warning', 'admin.admissions.requests'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert('No beds available', $this->count(fn () => $B::query()->where('status', 'available')->count()) === 0 ? 1 : 0, 'danger', 'ti-bed-off'),
            ])),
            'queues' => [$this->admissionQueue()],
            'quick_actions' => $this->actions([
                ['Admissions', 'ti-bed', 'admin.admissions.index', 'primary', 'admissions.view'],
                ['Admit Patient', 'ti-plus', 'admin.admissions.create', 'secondary', 'admissions.create'],
                ['Medication Board', 'ti-pill', 'admin.admissions.medication-board', 'secondary', null],
                ['Discharge Requests', 'ti-logout', 'admin.admissions.requests', 'secondary', null],
            ]),
            'reports' => $this->reportLinks([['Admission Report', 'admin.reports.admissions', 'ti-report']]),
        ];
    }

    private function bloodBank(): array
    {
        $U = \App\Models\BloodUnit::class;
        $R = \App\Models\BloodRequest::class;
        $expiring = $this->count(fn () => $U::query()->where('status', 'AVAILABLE')->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', today()->addDays(7))->count());
        $pendingReq = $this->count(fn () => $R::query()->whereIn('status', ['PENDING', 'pending'])->count());

        return [
            'title' => 'Blood Bank Dashboard',
            'kpis' => array_values(array_filter([
                $this->kpi('Available Units', $this->count(fn () => $U::query()->where('status', 'AVAILABLE')->count()), 'ti-droplet', 'danger', 'admin.blood-bank.units.index'),
                $this->kpi('Expiring ≤ 7 days', $expiring, 'ti-clock-x', 'warning', 'admin.blood-bank.units.index'),
                $this->kpi('Pending Requests', $pendingReq, 'ti-clipboard-list', 'info', 'admin.blood-bank.requests.index'),
                $this->kpi('Pending Screening', $this->count(fn () => $U::query()->whereIn('screening_status', ['PENDING', 'pending'])->count()), 'ti-test-pipe', 'secondary'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert('Units expiring within 7 days', $expiring, 'warning', 'ti-clock-x', 'admin.blood-bank.units.index'),
                $this->alert('Blood requests pending', $pendingReq, 'info', 'ti-clipboard-list', 'admin.blood-bank.requests.index'),
            ])),
            'queues' => [$this->bloodRequestQueue()],
            'quick_actions' => $this->actions([
                ['Blood Bank Dashboard', 'ti-layout-dashboard', 'admin.blood-bank.dashboard', 'primary', 'blood_bank.view'],
                ['Requests', 'ti-clipboard-list', 'admin.blood-bank.requests.index', 'secondary', null],
                ['Units', 'ti-droplet', 'admin.blood-bank.units.index', 'secondary', null],
                ['Donations', 'ti-heart-handshake', 'admin.blood-bank.donations.index', 'secondary', null],
            ]),
            'reports' => $this->reportLinks([['Blood Bank Report', 'admin.reports.blood-bank', 'ti-report']]),
        ];
    }

    private function claims(): array
    {
        $C = \App\Models\Claim::class;
        $s = fn ($statuses) => $this->count(fn () => $C::query()->whereIn('status', (array) $statuses)->count());
        $rejected = $s(['rejected']);

        return [
            'title' => 'Insurance / Claims Dashboard',
            'kpis' => array_values(array_filter([
                $this->kpi('To Prepare', $s(['draft']), 'ti-clipboard-list', 'secondary', 'admin.claims.index'),
                $this->kpi('Ready / Submitted', $s(['ready', 'submitted', 'resubmitted']), 'ti-send', 'info', 'admin.claims.index'),
                $this->kpi('Under Review', $s(['acknowledged', 'under_review']), 'ti-eye-search', 'primary', 'admin.claims.index'),
                $this->kpi('Approved (unpaid)', $s(['approved', 'partially_approved']), 'ti-checks', 'success', 'admin.claims.index'),
                $this->kpi('Rejected', $rejected, 'ti-x', 'danger', 'admin.claims.index'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert('Rejected claims need correction', $rejected, 'danger', 'ti-x', 'admin.claims.index'),
            ])),
            'queues' => [$this->claimsQueue()],
            'quick_actions' => $this->actions([
                ['Claims', 'ti-clipboard-text', 'admin.claims.index', 'primary', 'claims.view'],
                ['New Claim', 'ti-plus', 'admin.claims.create', 'secondary', 'claims.create'],
            ]),
            'reports' => $this->reportLinks([['Claims Report', 'admin.reports.claims', 'ti-report-money']]),
        ];
    }

    private function hr(): array
    {
        $L = \App\Models\LeaveRequest::class;
        $pendingLeave = $this->count(fn () => $L::query()->where('status', 'pending')->count());

        return [
            'title' => 'HR / Payroll Dashboard',
            'kpis' => array_values(array_filter([
                $this->kpi('Active Staff', $this->count(fn () => \App\Models\User::query()->count()), 'ti-users-group', 'primary', 'admin.hr.employees.index'),
                $this->kpi('Pending Leave', $pendingLeave, 'ti-calendar-off', 'warning', 'admin.hr.leave.index'),
                $this->kpi('Payroll Draft', $this->count(fn () => \App\Models\PayrollRecord::query()->where('status', 'draft')->count()), 'ti-file-dollar', 'secondary'),
                $this->kpi('Payroll Approved', $this->count(fn () => \App\Models\PayrollRecord::query()->where('status', 'approved')->count()), 'ti-check', 'success'),
            ])),
            'alerts' => array_values(array_filter([
                $this->alert('Leave requests pending approval', $pendingLeave, 'warning', 'ti-calendar-off', 'admin.hr.leave.index'),
            ])),
            'queues' => [$this->leaveQueue()],
            'quick_actions' => $this->actions([
                ['Employees', 'ti-users-group', 'admin.hr.employees.index', 'primary', null],
                ['Leave', 'ti-calendar', 'admin.hr.leave.index', 'secondary', null],
                ['Attendance', 'ti-user-check', 'admin.hr.attendance.index', 'secondary', null],
                ['Payroll', 'ti-coin', 'admin.hr.payroll.index', 'secondary', null],
            ]),
            'reports' => [],
        ];
    }

    private function reception(): array
    {
        $V = \App\Models\Visit::class;
        $A = \App\Models\Appointment::class;

        return [
            'title' => 'Reception / Front Desk Dashboard',
            'kpis' => array_values(array_filter([
                $this->kpi('Visits Today', $this->count(fn () => $V::query()->whereDate('created_at', today())->count()), 'ti-clipboard', 'primary', 'admin.visits.index'),
                $this->kpi('Appointments Today', $this->count(fn () => $A::query()->whereDate('appointment_date', today())->count()), 'ti-calendar', 'info', 'admin.appointments.index'),
                $this->kpi('Checked-in', $this->count(fn () => $A::query()->whereDate('appointment_date', today())->where('status', 'checked_in')->count()), 'ti-user-check', 'success'),
                $this->kpi('Waiting', $this->count(fn () => $V::query()->whereDate('created_at', today())->whereIn('status', ['waiting', 'registered'])->count()), 'ti-clock', 'warning'),
            ])),
            'alerts' => [],
            'queues' => [$this->appointmentQueue()],
            'quick_actions' => $this->actions([
                ['New Visit', 'ti-plus', 'admin.visits.create', 'primary', 'visits.create'],
                ['New Appointment', 'ti-calendar-plus', 'admin.appointments.create', 'secondary', null],
                ['Appointments', 'ti-calendar', 'admin.appointments.index', 'secondary', null],
                ['Register Patient', 'ti-user-plus', 'admin.patients.create', 'secondary', 'patient.create'],
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

        return $this->queue($title, 'ti-users', $rows, 'admin.consultations.index', 'No patients in the queue.');
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

        return $this->queue('Prescriptions to Bill / Dispense', 'ti-prescription', $rows, 'admin.prescriptions.index', 'No pending prescriptions.');
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

        return $this->queue('Investigation Worklist', 'ti-microscope', $rows, 'admin.lab.requests.index', 'No pending investigations.');
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

        return $this->queue('Procedure Board', 'ti-stethoscope', $rows, 'admin.theatre.index', 'No open procedures.');
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

        return $this->queue('Unpaid Invoices', 'ti-file-invoice', $rows, 'admin.billing.invoices.index', 'No unpaid invoices.');
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

        return $this->queue('Requisitions Awaiting Approval', 'ti-clipboard-check', $rows, 'admin.store.stock-requisitions.index', 'No requisitions awaiting approval.');
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

        return $this->queue('Active Emergency Cases', 'ti-ambulance', $rows, 'admin.emergency.board', 'No active emergency cases.');
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

        return $this->queue('Current Admissions', 'ti-bed', $rows, 'admin.admissions.index', 'No active admissions.');
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

        return $this->queue('Pending Blood Requests', 'ti-droplet', $rows, 'admin.blood-bank.requests.index', 'No pending blood requests.');
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

        return $this->queue('Claims Needing Attention', 'ti-clipboard-text', $rows, 'admin.claims.index', 'No claims need attention.');
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

        return $this->queue('Leave Requests Pending', 'ti-calendar-off', $rows, 'admin.hr.leave.index', 'No pending leave requests.');
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

        return $this->queue("Today's Appointments", 'ti-calendar', $rows, 'admin.appointments.index', 'No appointments today.');
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
