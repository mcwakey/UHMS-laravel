<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class SidebarMenuBuilder
{
    public function __construct(
        protected ModuleService $moduleService,
    ) {}

    public function build(?User $user, string $currentRouteName = '', int $unreadNotifications = 0): array
    {
        if (! $user) {
            return [];
        }

        // Non-admin clinical staff get a focused consultation sidebar
        if ($user->isConsultationUser() && ! $user->isAdminUser()) {
            return $this->finaliseSections(
                $this->consultationSections($unreadNotifications),
                $user,
                $currentRouteName
            );
        }

        $sections = [
            [
                'title' => 'Main Menu',
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'icon' => 'ti ti-layout-dashboard',
                        'route' => 'dashboard',
                        'active_patterns' => ['dashboard', 'admin.dashboard', 'doctor.dashboard', 'staff.dashboard'],
                    ],
                ],
            ],
            [
                'title' => 'Patient Services',
                'items' => [
                    [
                        'label' => 'Patients',
                        'icon' => 'ti ti-user-heart',
                        'route' => 'admin.patients.index',
                        'active_patterns' => ['admin.patients.*'],
                        'permission' => 'patients.view',
                        'module' => 'patients',
                    ],
                    [
                        'label' => 'Appointments',
                        'icon' => 'ti ti-calendar-event',
                        'route' => 'admin.appointments.index',
                        'permission' => 'appointments.view',
                        'active_patterns' => ['admin.appointments.*'],
                        'module' => 'appointments',
                        // 'children' => [
                        //     [
                        //         'label' => 'All Appointments',
                        //         'route' => 'admin.appointments.index',
                        //         'active_patterns' => ['admin.appointments.index'],
                        //         'permission' => 'appointments.view',
                        //     ],
                        //     [
                        //         'label' => 'Calendar View',
                        //         'route' => 'admin.appointments.calendar',
                        //         'active_patterns' => ['admin.appointments.calendar'],
                        //         'permission' => 'appointments.view',
                        //     ],
                        //     [
                        //         'label' => 'Schedule New',
                        //         'route' => 'admin.appointments.create',
                        //         'active_patterns' => ['admin.appointments.create'],
                        //         'permission' => 'appointments.create',
                        //     ],
                        // ],
                    ],
                    [
                        'label' => 'Visits / OPD',
                        'icon' => 'ti ti-calendar-check',
                        'route' => 'admin.visits.index',
                        'active_patterns' => ['admin.visits.*'],
                        'permission' => 'visits.view',
                        'module' => 'visits',
                    ],
                    [
                        'label' => 'Queue',
                        'icon' => 'ti ti-list-numbers',
                        'permission' => 'queue.view',
                        'active_patterns' => ['admin.queue.*'],
                        'children' => [
                            [
                                'label' => 'Manage Queue',
                                'route' => 'admin.queue.manage',
                                'active_patterns' => ['admin.queue.manage'],
                                'permission' => 'queue.view',
                            ],
                            [
                                'label' => 'Queue Board',
                                'route' => 'admin.queue.board',
                                'active_patterns' => ['admin.queue.board'],
                                'permission' => 'queue.view',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Clinical',
                'items' => [
                    // [
                    //     'label' => 'Vitals / Triage',
                    //     'icon' => 'ti ti-heartbeat',
                    //     'route' => 'admin.vitals.create',
                    //     'active_patterns' => ['admin.vitals.*'],
                    //     'permission' => 'vitals.view',
                    //     'module' => 'triage',
                    // ],
                    [
                        'label' => 'Vitals / Triage',
                        'icon' => 'ti ti-ambulance',
                        'route' => 'admin.triage.index',
                        'active_patterns' => ['admin.triage.*'],
                        'permission' => 'vitals.view',
                        'module' => 'triage',
                    ],
                    [
                        'label' => 'Consultations',
                        'icon' => 'ti ti-stethoscope',
                        'route' => 'admin.consultations.index',
                        'active_patterns' => ['admin.consultations.*'],
                        'permission' => 'consultations.view',
                        'module' => 'consultation',
                    ],
                    // [
                    //     'label' => 'Procedures',
                    //     'icon' => 'ti ti-surgery',
                    //     'permission' => 'procedures.view',
                    //     'active_patterns' => ['admin.procedures.*', 'admin.procedure-catalogue.*'],
                    //     'children' => [
                    //         [
                    //             'label' => 'Procedure Catalog',
                    //             'route' => 'admin.procedure-catalogue.index',
                    //             'active_patterns' => ['admin.procedure-catalogue.*'],
                    //             'permission' => 'procedure_catalogue.view',
                    //         ],
                    //         [
                    //             'label' => 'Scheduled Procedures',
                    //             'route' => 'admin.procedures.schedule',
                    //             'active_patterns' => ['admin.procedures.schedule'],
                    //             'permission' => 'procedures.view',
                    //         ],
                    //     ],
                    // ],
                    // [
                    //     'label' => 'Theatre / Procedures',
                    //     'icon' => 'ti ti-stethoscope',
                    //     'permission' => 'procedure.view',
                    //     'active_patterns' => ['admin.theatre.*'],
                    //     'children' => [
                    //         [
                    //             'label' => 'Procedure Catalogue',
                    //             'route' => 'admin.procedure-catalogue.index',
                    //             'active_patterns' => ['admin.procedure-catalogue.*'],
                    //             'permission' => 'procedure_catalogue.view',
                    //         ],
                    //         [
                    //             'label' => 'Procedure Consumables',
                    //             'route' => 'admin.theatre.consumables.index',
                    //             'active_patterns' => ['admin.theatre.consumables.*'],
                    //             'permission' => 'procedure.view',
                    //         ],
                    //         // [
                    //         //     'label' => 'Scheduled Procedures',
                    //         //     'route' => 'admin.procedures.schedule',
                    //         //     'active_patterns' => ['admin.procedures.schedule'],
                    //         //     'permission' => 'procedures.view',
                    //         // ],
                    //         [
                    //             'label' => 'Scheduled Procedures',
                    //             'route' => 'admin.theatre.index',
                    //             'route_params' => ['tab' => 'pending'],
                    //             'active_patterns' => ['admin.theatre.index'],
                    //             'permission' => 'procedure.view',
                    //         ],
                    //         // [
                    //         //     'label' => 'Scheduled',
                    //         //     'route' => 'admin.theatre.index',
                    //         //     'route_params' => ['tab' => 'scheduled'],
                    //         //     'active_patterns' => ['admin.theatre.index'],
                    //         //     'permission' => 'procedure.view',
                    //         // ],
                    //         // [
                    //         //     'label' => 'In Theatre',
                    //         //     'route' => 'admin.theatre.index',
                    //         //     'route_params' => ['tab' => 'in_theatre'],
                    //         //     'active_patterns' => ['admin.theatre.index'],
                    //         //     'permission' => 'procedure.view',
                    //         // ],
                    //         // [
                    //         //     'label' => 'Recovery',
                    //         //     'route' => 'admin.theatre.index',
                    //         //     'route_params' => ['tab' => 'recovery'],
                    //         //     'active_patterns' => ['admin.theatre.index'],
                    //         //     'permission' => 'procedure.view',
                    //         // ],
                    //         // [
                    //         //     'label' => 'Completed',
                    //         //     'route' => 'admin.theatre.index',
                    //         //     'route_params' => ['tab' => 'completed'],
                    //         //     'active_patterns' => ['admin.theatre.index'],
                    //         //     'permission' => 'procedure.view',
                    //         // ],
                    //     ],
                    // ],
                ],
            ],
            [
                'title' => 'Ward / Inpatient',
                'items' => [
                    [
                        'label' => 'Admissions Requests',
                        'icon' => 'ti ti-bed',
                        'route' => 'admin.admissions.index',
                        'active_patterns' => ['admin.admissions.*'],
                        'permission' => 'ward.view',
                    ],
                    [
                        'label' => 'Admissions Board',
                        'icon' => 'ti ti-bed',
                        'route' => 'admin.admissions.index',
                        'active_patterns' => ['admin.admissions.*'],
                        'permission' => 'ward.view',
                    ],
                    // [
                    //     'label' => 'Bed Map',
                    //     'icon' => 'ti ti-map',
                    //     'route' => 'admin.wards.bed-map',
                    //     'active_patterns' => ['admin.wards.bed-map'],
                    //     'permission' => 'ward.view',
                    // ],
                    [
                        'label' => 'Wards / Bed Management',
                        'icon' => 'ti ti-building-hospital',
                        'route' => 'admin.wards.index',
                        'active_patterns' => ['admin.wards.index'],
                        'permission' => 'ward.manage',
                    ],
                    // [
                    //     'label' => 'Bed Management',
                    //     'icon' => 'ti ti-bed-flat',
                    //     'route' => 'admin.wards.beds',
                    //     'active_patterns' => ['admin.wards.beds'],
                    //     'permission' => 'beds.manage',
                    // ],
                ],
            ],
            [
                'title' => 'Pharmacy',
                'items' => [
                    [
                        'label' => 'Prescriptions',
                        'icon' => 'ti ti-prescription',
                        'route' => 'admin.prescriptions.index',
                        'active_patterns' => ['admin.prescriptions.*'],
                        'permission' => 'prescriptions.view',
                        'module' => 'pharmacy',
                    ],
                    [
                        'label' => 'Dispensing',
                        'icon' => 'ti ti-pill',
                        'route' => 'admin.pharmacy.dispensing.index',
                        'active_patterns' => ['admin.pharmacy.dispensing.*'],
                        'permission' => 'pharmacy.dispensing.view',
                        'module' => 'pharmacy',
                    ],
                    [
                        'label' => 'Drug Catalog',
                        'icon' => 'ti ti-medicine-syrup',
                        'route' => 'admin.pharmacy.drugs.index',
                        'active_patterns' => ['admin.pharmacy.drugs.*'],
                        'permission' => 'pharmacy.drugs.manage',
                        'module' => 'pharmacy',
                    ],
                ],
            ],
            [
                'title' => 'Investigations',
                'items' => [
                    [
                        'label' => 'Investigation Requests',
                        'icon' => 'ti ti-test-pipe',
                        'route' => 'admin.lab.requests.index',
                        'active_patterns' => ['admin.lab.requests.*'],
                        'permission' => 'lab.requests.view',
                        'module' => 'investigations',
                    ],
                    [
                        'label' => 'Investigation Results',
                        'icon' => 'ti ti-report-medical',
                        'route' => 'admin.lab.results.index',
                        'active_patterns' => ['admin.lab.results.*'],
                        'permission' => 'lab.results.view',
                        'module' => 'investigations',
                    ],
                    [
                        'label' => 'Investigation Catalogue',
                        'icon' => 'ti ti-flask',
                        'route' => 'admin.investigation-catalogue.index',
                        'active_patterns' => ['admin.investigation-catalogue.*'],
                        'permission' => 'lab.tests.manage',
                        'module' => 'investigations',
                    ],
                    [
                        'label' => 'Investigation Consumables',
                        'icon' => 'ti ti-microscope',
                        'route' => 'admin.investigations.items.index',
                        'active_patterns' => ['admin.investigations.items.*'],
                        'permission' => 'lab.tests.manage',
                        'module' => 'investigations',
                    ],
                    // [
                    //     'label' => 'Investigation Stock',
                    //     'icon' => 'ti ti-packages',
                    //     'route' => 'admin.investigations.stock.index',
                    //     'active_patterns' => ['admin.investigations.stock.*'],
                    //     'permission' => 'lab.tests.manage',
                    //     'module' => 'investigations',
                    // ],
                ],
            ],
            [
                'title' => 'Theatre / Procedures',
                'items' => [
                    [
                        'label' => 'Procedure Requests',
                        'icon' => 'ti ti-test-pipe',
                        'route' => 'admin.theatre.index',
                        'active_patterns' => ['admin.theatre.index'],
                        'permission' => 'procedure.view',
                        'module' => 'procedure',
                    ],
                    // [
                    //     'label' => 'Investigation Results',
                    //     'icon' => 'ti ti-report-medical',
                    //     'route' => 'admin.lab.results.index',
                    //     'active_patterns' => ['admin.lab.results.*'],
                    //     'permission' => 'lab.results.view',
                    //     'module' => 'investigations',
                    // ],
                    [
                        'label' => 'Procedure Catalogue',
                        'icon' => 'ti ti-flask',
                        'route' => 'admin.procedure-catalogue.index',
                        'active_patterns' => ['admin.procedure-catalogue.*'],
                        'permission' => 'procedure_catalogue.view',
                        'module' => 'procedure_catalogue',
                    ],
                    [
                        'label' => 'Procedure Consumables',
                        'icon' => 'ti ti-microscope',
                        'route' => 'admin.theatre.consumables.index',
                        'active_patterns' => ['admin.theatre.consumables.*'],
                        'permission' => 'procedure.view',
                        'module' => 'procedure',
                    ],
                    // [
                    //     'label' => 'Investigation Stock',
                    //     'icon' => 'ti ti-packages',
                    //     'route' => 'admin.investigations.stock.index',
                    //     'active_patterns' => ['admin.investigations.stock.*'],
                    //     'permission' => 'lab.tests.manage',
                    //     'module' => 'investigations',
                    // ],
                    // [
                    //     'label' => 'Analyzers',
                    //     'icon' => 'ti ti-device-analytics',
                    //     'route' => 'admin.analyzers.index',
                    //     'active_patterns' => ['admin.analyzers.index', 'admin.analyzers.show'],
                    //     'permission' => 'analyzer.manage',
                    //     'module' => 'analyzer',
                    // ],
                    // [
                    //     'label' => 'Analyzer Messages',
                    //     'icon' => 'ti ti-activity',
                    //     'route' => 'admin.analyzers.diagnostics',
                    //     'active_patterns' => ['admin.analyzers.diagnostics'],
                    //     'permission' => 'analyzer.manage',
                    //     'module' => 'analyzer',
                    // ],
                ],
            ],
            [
                'title' => 'Claims & Insurance',
                'items' => [
                    [
                        'label' => 'Claims',
                        'icon' => 'ti ti-file-check',
                        'route' => 'admin.claims.index',
                        'active_patterns' => ['admin.claims.index', 'admin.claims.show', 'admin.claims.review'],
                        'permission' => 'claims.view',
                        'module' => 'claims',
                    ],
                    // [
                    //     'label' => 'New Claim',
                    //     'icon' => 'ti ti-file-plus',
                    //     'route' => 'admin.claims.create',
                    //     'active_patterns' => ['admin.claims.create'],
                    //     'permission' => 'claims.create',
                    //     'module' => 'claims',
                    // ],
                ],
            ],
            [
                'title' => 'Store & Procurement',
                'items' => [
                    [
                        'label' => 'Products',
                        'icon' => 'ti ti-box',
                        'route' => 'admin.products.index',
                        'active_patterns' => ['admin.products.*'],
                        'permission' => 'product.view',
                        'module' => 'inventory',
                    ],
                    [
                        'label' => 'Suppliers',
                        'icon' => 'ti ti-truck',
                        'route' => 'admin.store.suppliers.index',
                        'active_patterns' => ['admin.store.suppliers.*'],
                        'permission' => 'store.purchase.view',
                        'module' => 'inventory',
                    ],
                    [
                        'label' => 'Purchase Orders',
                        'icon' => 'ti ti-file-text',
                        'route' => 'admin.store.purchase-orders.index',
                        'active_patterns' => ['admin.store.purchase-orders.*'],
                        'permission' => 'store.purchase.view',
                        'module' => 'inventory',
                    ],
                    [
                        'label' => 'Purchase Returns',
                        'icon' => 'ti ti-file-minus',
                        'route' => 'admin.store.purchase-returns.index',
                        'active_patterns' => ['admin.store.purchase-returns.*'],
                        'permission' => 'store.return.view',
                        'module' => 'inventory',
                    ],
                    [
                        'label' => 'Stock Requisitions',
                        'icon' => 'ti ti-clipboard-list',
                        'route' => 'admin.store.stock-requisitions.index',
                        'active_patterns' => ['admin.store.stock-requisitions.*'],
                        'permission' => 'store.requisition.view',
                        'module' => 'inventory',
                    ],
                    [
                        'label' => 'Stock Ledger',
                        'icon' => 'ti ti-history',
                        'route' => 'admin.product-stock.ledger',
                        'active_patterns' => ['admin.product-stock.ledger'],
                        'permission' => 'stock.view',
                        'module' => 'inventory',
                    ],
                    [
                        'label' => 'Stock Balances',
                        'icon' => 'ti ti-list-numbers',
                        'route' => 'admin.product-stock.balances',
                        'active_patterns' => ['admin.product-stock.balances'],
                        'permission' => 'stock.view',
                        'module' => 'inventory',
                    ],
                    [
                        'label' => 'Stock Locations',
                        'icon' => 'ti ti-building-warehouse',
                        'route' => 'admin.stock-locations.index',
                        'active_patterns' => ['admin.stock-locations.*'],
                        'permission' => 'stock.location.manage',
                        'module' => 'inventory',
                    ],
                    // [
                    //     'label' => 'Receive Stock',
                    //     'icon' => 'ti ti-arrow-down',
                    //     'route' => 'admin.product-stock.receive.form',
                    //     'active_patterns' => ['admin.product-stock.receive*'],
                    //     'permission' => 'stock.adjust',
                    //     'module' => 'inventory',
                    // ],
                    // [
                    //     'label' => 'Stock Transfers',
                    //     'icon' => 'ti ti-transfer',
                    //     'route' => 'admin.product-stock.transfer.form',
                    //     'active_patterns' => ['admin.store.transfers.*', 'admin.product-stock.transfer*'],
                    //     'permission' => 'stock.transfer',
                    //     'module' => 'inventory',
                    // ],
                    // [
                    //     'label' => 'Stock Adjustments',
                    //     'icon' => 'ti ti-adjustments',
                    //     'route' => 'admin.product-stock.adjust.form',
                    //     'active_patterns' => ['admin.product-stock.adjust*', 'admin.store.stock.adjustments.*'],
                    //     'permission' => 'stock.adjust',
                    //     'module' => 'inventory',
                    // ],
                    // [
                    //     'label' => 'Stock Returns',
                    //     'icon' => 'ti ti-rotate',
                    //     'route' => 'admin.product-stock.return.form',
                    //     'active_patterns' => ['admin.product-stock.return*', 'admin.store.stock.returns.*'],
                    //     'permission' => 'stock.return',
                    //     'module' => 'inventory',
                    // ],
                ],
            ],
            [
                'title' => 'Accounts & Finance',
                'items' => [
                    // [
                    //     'label' => 'Cashier Handover',
                    //     'icon' => 'ti ti-cash',
                    //     'route' => 'admin.accounts.handover.index',
                    //     'active_patterns' => ['admin.accounts.handover.*'],
                    //     'permission' => 'accounts.cashier',
                    // ],
                    [
                        'label' => 'Receive Payments',
                        'icon' => 'ti ti-cash',
                        'route' => 'admin.billing.payments.receive',
                        'active_patterns' => ['admin.billing.payments.receive'],
                        'permission' => 'payments.create',
                        'module' => 'billing',
                    ],
                    [
                        'label' => 'Income',
                        'icon' => 'ti ti-trending-up',
                        'route' => 'admin.accounts.income.index',
                        'active_patterns' => ['admin.accounts.income.*'],
                        'permission' => 'accounts.entries.view',
                    ],
                    [
                        'label' => 'Expenses',
                        'icon' => 'ti ti-trending-down',
                        'route' => 'admin.accounts.expenses.index',
                        'active_patterns' => ['admin.accounts.expenses.*'],
                        'permission' => 'accounts.entries.view',
                    ],
                    [
                        'label' => 'Invoices',
                        'icon' => 'ti ti-file-invoice',
                        'route' => 'admin.billing.invoices.index',
                        'active_patterns' => ['admin.billing.invoices.*'],
                        'permission' => 'invoices.view',
                        'module' => 'billing',
                    ],
                    [
                        'label' => 'Payments',
                        'icon' => 'ti ti-cash',
                        'route' => 'admin.billing.payments.index',
                        'active_patterns' => ['admin.billing.payments.index', 'admin.billing.payments.receipt'],
                        'permission' => 'payments.view',
                        'module' => 'billing',
                    ],
                    [
                        'label' => 'Daily Collection',
                        'icon' => 'ti ti-report-money',
                        'route' => 'admin.accounts.daily-collection',
                        'active_patterns' => ['admin.accounts.daily-collection'],
                        'permission' => 'accounts.entries.view',
                    ],
                    [
                        'label' => 'Reconciliation',
                        'icon' => 'ti ti-chart-bar',
                        'route' => 'admin.accounts.reconciliation',
                        'active_patterns' => ['admin.accounts.reconciliation'],
                        'permission' => 'accounts.entries.view',
                    ],
                    [
                        'label' => 'Account Categories',
                        'icon' => 'ti ti-category',
                        'route' => 'admin.accounts.categories.index',
                        'active_patterns' => ['admin.accounts.categories.*'],
                        'permission' => 'accounts.manage',
                    ],
                ],
            ],
            [
                'title' => 'HR & Payroll',
                'items' => [
                    [
                        'label' => 'Employees',
                        'icon' => 'ti ti-id-badge-2',
                        'route' => 'admin.hr.employees.index',
                        'active_patterns' => ['admin.hr.employees.*'],
                        'permission' => 'hr.employees.view',
                        'module' => 'hr',
                    ],
                    [
                        'label' => 'Attendance',
                        'icon' => 'ti ti-clock-record',
                        'route' => 'admin.hr.attendance.index',
                        'active_patterns' => ['admin.hr.attendance.*'],
                        'permission' => 'hr.attendance.view',
                        'module' => 'hr',
                    ],
                    [
                        'label' => 'Leave Requests',
                        'icon' => 'ti ti-calendar-off',
                        'route' => 'admin.hr.leave.index',
                        'active_patterns' => ['admin.hr.leave.*'],
                        'permission' => 'hr.leave.view',
                        'module' => 'hr',
                    ],
                    [
                        'label' => 'Payroll',
                        'icon' => 'ti ti-report-money',
                        'route' => 'admin.hr.payroll.index',
                        'active_patterns' => ['admin.hr.payroll.*'],
                        'permission' => 'hr.payroll.view',
                        'module' => 'payroll',
                    ],
                ],
            ],
            [
                'title' => 'Reports',
                'items' => [
                    [
                        'label' => 'Financial Reports',
                        'icon' => 'ti ti-report-money',
                        'permission' => 'reports.view',
                        'module' => 'reports',
                        'active_patterns' => [
                            'admin.reports.income',
                            'admin.reports.daily-collection',
                            'admin.reports.insurance-claims',
                            'admin.reports.claims',
                            'admin.reports.statement-search',
                            'admin.reports.patient-statement',
                        ],
                        'children' => [
                            ['label' => 'Income Report', 'route' => 'admin.reports.income', 'active_patterns' => ['admin.reports.income'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Daily Collection', 'route' => 'admin.reports.daily-collection', 'active_patterns' => ['admin.reports.daily-collection'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Insurance Claims Report', 'route' => 'admin.reports.insurance-claims', 'active_patterns' => ['admin.reports.insurance-claims'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Claims Report', 'route' => 'admin.reports.claims', 'active_patterns' => ['admin.reports.claims'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Patient Statement', 'route' => 'admin.reports.statement-search', 'active_patterns' => ['admin.reports.statement-search', 'admin.reports.patient-statement'], 'permission' => 'reports.view', 'module' => 'reports'],
                        ],
                    ],
                    [
                        'label' => 'Clinical Reports',
                        'icon' => 'ti ti-stethoscope',
                        'permission' => 'reports.view',
                        'module' => 'reports',
                        'active_patterns' => [
                            'admin.reports.patients',
                            'admin.reports.visits',
                            'admin.reports.consultation-stats',
                            'admin.reports.admissions',
                            'admin.reports.discharges',
                            'admin.reports.investigation-revenue',
                        ],
                        'children' => [
                            ['label' => 'Patient Report', 'route' => 'admin.reports.patients', 'active_patterns' => ['admin.reports.patients'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Visit Report', 'route' => 'admin.reports.visits', 'active_patterns' => ['admin.reports.visits'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Consultation Stats', 'route' => 'admin.reports.consultation-stats', 'active_patterns' => ['admin.reports.consultation-stats'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Admissions Report', 'route' => 'admin.reports.admissions', 'active_patterns' => ['admin.reports.admissions'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Discharges Report', 'route' => 'admin.reports.discharges', 'active_patterns' => ['admin.reports.discharges'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Investigation Revenue', 'route' => 'admin.reports.investigation-revenue', 'active_patterns' => ['admin.reports.investigation-revenue'], 'permission' => 'reports.view', 'module' => 'reports'],
                        ],
                    ],
                    [
                        'label' => 'Pharmacy & HR',
                        'icon' => 'ti ti-pill',
                        'permission' => 'reports.view',
                        'module' => 'reports',
                        'active_patterns' => [
                            'admin.reports.pharmacy-sales',
                            'admin.reports.pharmacy-sales-summary',
                            'admin.reports.stock-valuation',
                            'admin.reports.expired-stock',
                            'admin.reports.leave',
                            'admin.reports.payroll',
                        ],
                        'children' => [
                            ['label' => 'Pharmacy Sales', 'route' => 'admin.reports.pharmacy-sales', 'active_patterns' => ['admin.reports.pharmacy-sales'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Sales Summary', 'route' => 'admin.reports.pharmacy-sales-summary', 'active_patterns' => ['admin.reports.pharmacy-sales-summary'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Stock Valuation', 'route' => 'admin.reports.stock-valuation', 'active_patterns' => ['admin.reports.stock-valuation'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Expired Stock', 'route' => 'admin.reports.expired-stock', 'active_patterns' => ['admin.reports.expired-stock'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Leave Report', 'route' => 'admin.reports.leave', 'active_patterns' => ['admin.reports.leave'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Payroll Report', 'route' => 'admin.reports.payroll', 'active_patterns' => ['admin.reports.payroll'], 'permission' => 'reports.view', 'module' => 'reports'],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Administration',
                'items' => [
                    [
                        'label' => 'Users',
                        'icon' => 'ti ti-users-group',
                        'permission' => 'users.view',
                        'module' => 'users',
                        'active_patterns' => ['admin.users.*'],
                        'children' => [
                            ['label' => 'All Users', 'route' => 'admin.users.index', 'active_patterns' => ['admin.users.index'], 'permission' => 'users.view', 'module' => 'users'],
                            ['label' => 'Add User', 'route' => 'admin.users.create', 'active_patterns' => ['admin.users.create'], 'permission' => 'users.view', 'module' => 'users'],
                        ],
                    ],
                    [
                        'label' => 'Roles & Permissions',
                        'icon' => 'ti ti-shield-lock',
                        'route' => 'admin.roles.index',
                        'active_patterns' => ['admin.roles.*'],
                        'permission' => 'users.view',
                        'module' => 'users',
                    ],
                    [
                        'label' => 'Departments',
                        'icon' => 'ti ti-building-bank',
                        'route' => 'admin.departments.index',
                        'active_patterns' => ['admin.departments.*'],
                        'permission' => 'departments.view',
                        'module' => 'departments',
                    ],
                    [
                        'label' => 'Designations',
                        'icon' => 'ti ti-user-cog',
                        'route' => 'admin.designations.index',
                        'active_patterns' => ['admin.designations.*'],
                        'permission' => 'departments.view',
                        'module' => 'departments',
                    ],
                ],
            ],
            [
                'title' => 'Configurations',
                'items' => [
                    [
                        'label' => 'Services',
                        'icon' => 'ti ti-list-details',
                        'route' => 'admin.services.index',
                        'active_patterns' => ['admin.services.*'],
                        'permission' => 'services.manage',
                        'module' => 'services',
                    ],
                    [
                        'label' => 'Specialties',
                        'icon' => 'ti ti-stethoscope',
                        'route' => 'admin.specialties.index',
                        'active_patterns' => ['admin.specialties.*'],
                        'permission' => 'services.manage',
                        'module' => 'services',
                    ],
                    [
                        'label' => 'Insurance Providers',
                        'icon' => 'ti ti-shield-check',
                        'route' => 'admin.insurance-providers.index',
                        'active_patterns' => ['admin.insurance-providers.*', 'admin.insurance-tiers.*'],
                        'permission' => 'claims.view',
                        'module' => 'insurance',
                    ],
                    [
                        'label' => 'Medical Patterns',
                        'icon' => 'ti ti-template',
                        'route' => 'admin.patterns.index',
                        'active_patterns' => ['admin.patterns.*'],
                        'permission' => 'consultations.view',
                        'module' => 'medical-patterns',
                    ],
                    [
                        'label' => 'ICD-10 Codes',
                        'icon' => 'ti ti-medical-cross',
                        'route' => 'admin.icd-codes.index',
                        'active_patterns' => ['admin.icd-codes.*'],
                        'permission' => 'icd.manage',
                    ],
                    [
                        'label' => 'Analyzers',
                        'icon' => 'ti ti-device-analytics',
                        'route' => 'admin.analyzers.index',
                        'active_patterns' => ['admin.analyzers.index', 'admin.analyzers.show'],
                        'permission' => 'analyzer.manage',
                        'module' => 'analyzer',
                    ],
                    [
                        'label' => 'Analyzer Messages',
                        'icon' => 'ti ti-activity',
                        'route' => 'admin.analyzers.diagnostics',
                        'active_patterns' => ['admin.analyzers.diagnostics'],
                        'permission' => 'analyzer.manage',
                        'module' => 'analyzer',
                    ],
                ],
            ],
            // [
            //     'title' => null,
            //     'items' => [
            //         [
            //             'label' => 'Notifications',
            //             'icon' => 'ti ti-bell',
            //             'route' => 'admin.notifications.index',
            //             'active_patterns' => ['admin.notifications.*'],
            //             'permission' => 'notifications.view',
            //             'module' => 'notifications',
            //             'badge' => $unreadNotifications > 0 ? $unreadNotifications : null,
            //             'badge_class' => 'badge bg-danger rounded-pill ms-auto',
            //         ],
            //     ],
            // ],
            [
                'title' => 'Settings',
                'items' => [
                    [
                        'label' => 'Settings',
                        'icon' => 'ti ti-settings',
                        'active_patterns' => ['admin.settings.*', 'admin.modules.*'],
                        'children' => [
                            [
                                'label' => 'Organization',
                                'icon' => 'ti ti-building',
                                'route' => 'admin.settings.organization',
                                'active_patterns' => ['admin.settings.organization'],
                                'permission' => 'settings.manage',
                                'module' => 'settings',
                            ],
                            [
                                'label' => 'Invoice Settings',
                                'icon' => 'ti ti-file-invoice',
                                'route' => 'admin.settings.invoice',
                                'active_patterns' => ['admin.settings.invoice'],
                                'permission' => 'settings.manage',
                                'module' => 'settings',
                            ],
                            [
                                'label' => 'Payment Methods',
                                'icon' => 'ti ti-credit-card',
                                'route' => 'admin.settings.payment-methods',
                                'active_patterns' => ['admin.settings.payment-methods'],
                                'permission' => 'settings.manage',
                                'module' => 'settings',
                            ],
                            [
                                'label' => 'Activity Log',
                                'icon' => 'ti ti-history',
                                'route' => 'admin.settings.activity-log',
                                'active_patterns' => ['admin.settings.activity-log'],
                                'permission' => 'settings.manage',
                                'module' => 'settings',
                            ],
                            [
                                'label' => 'Modules',
                                'icon' => 'ti ti-puzzle',
                                'route' => 'admin.modules.index',
                                'active_patterns' => ['admin.modules.*'],
                                'permission' => 'modules.manage',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $this->finaliseSections($sections, $user, $currentRouteName);
    }

    protected function finaliseSections(array $sections, User $user, string $currentRouteName): array
    {
        return array_values(array_filter(array_map(
            fn (array $section) => $this->filterSection($section, $user, $currentRouteName),
            $sections,
        )));
    }

    // ------------------------------------------------------------------
    // Consultation / Doctor focused sidebar
    // ------------------------------------------------------------------
    protected function consultationSections(int $unreadNotifications): array
    {
        return [
            [
                'title' => 'Main Menu',
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'icon' => 'ti ti-layout-dashboard',
                        'route' => 'admin.dashboard',
                        'active_patterns' => ['admin.dashboard', 'doctor.dashboard', 'dashboard'],
                    ],
                ],
            ],
            [
                'title' => 'Consultation Queue',
                'items' => [
                    [
                        'label' => 'Consultation Queue',
                        'icon' => 'ti ti-list-numbers',
                        'route' => 'admin.queue.manage',
                        'active_patterns' => ['admin.queue.*'],
                        'permission' => 'consultation.queue',
                    ],
                    [
                        'label' => 'My Appointments',
                        'icon' => 'ti ti-calendar-event',
                        'route' => 'admin.appointments.index',
                        'active_patterns' => ['admin.appointments.*'],
                        'permission' => 'appointments.view',
                    ],
                ],
            ],
            [
                'title' => 'Clinical Work',
                'items' => [
                    [
                        'label' => 'Patients',
                        'icon' => 'ti ti-user-heart',
                        'route' => 'admin.patients.index',
                        'active_patterns' => ['admin.patients.*'],
                        'permission' => 'consultation.view_patient',
                    ],
                    [
                        'label' => 'Consultations',
                        'icon' => 'ti ti-stethoscope',
                        'route' => 'admin.consultations.index',
                        'active_patterns' => ['admin.consultations.*'],
                        'permission' => 'consultation.create',
                    ],
                    [
                        'label' => 'Vitals',
                        'icon' => 'ti ti-heartbeat',
                        'route' => 'admin.vitals.create',
                        'active_patterns' => ['admin.vitals.*'],
                        'permission' => 'vitals.view',
                    ],
                    [
                        'label' => 'Visits / OPD',
                        'icon' => 'ti ti-calendar-check',
                        'route' => 'admin.visits.index',
                        'active_patterns' => ['admin.visits.*'],
                        'permission' => 'visits.view',
                    ],
                    [
                        'label' => 'Admissions',
                        'icon' => 'ti ti-bed',
                        'route' => 'admin.admissions.index',
                        'active_patterns' => ['admin.admissions.*'],
                        'permission' => 'ward.view',
                    ],
                ],
            ],
            [
                'title' => 'Requests & Results',
                'items' => [
                    [
                        'label' => 'Prescriptions',
                        'icon' => 'ti ti-prescription',
                        'route' => 'admin.prescriptions.index',
                        'active_patterns' => ['admin.prescriptions.*'],
                        'permission' => 'consultation.prescribe',
                    ],
                    [
                        'label' => 'Lab Requests',
                        'icon' => 'ti ti-test-pipe',
                        'route' => 'admin.lab.requests.index',
                        'active_patterns' => ['admin.lab.requests.*'],
                        'permission' => 'consultation.request_lab',
                    ],
                    [
                        'label' => 'Lab Results',
                        'icon' => 'ti ti-report-medical',
                        'route' => 'admin.lab.results.index',
                        'active_patterns' => ['admin.lab.results.*'],
                        'permission' => 'consultation.view_results',
                    ],
                    [
                        'label' => 'Procedures',
                        'icon' => 'ti ti-surgery',
                        'route' => 'admin.theatre.index',
                        'active_patterns' => ['admin.theatre.*'],
                        'permission' => 'consultation.request_procedure',
                    ],
                ],
            ],
            [
                'title' => 'Clinical Tools',
                'items' => [
                    [
                        'label' => 'ICD-10 Codes',
                        'icon' => 'ti ti-medical-cross',
                        'route' => 'admin.icd-codes.index',
                        'active_patterns' => ['admin.icd-codes.*'],
                        'permission' => 'icd.view',
                    ],
                    [
                        'label' => 'Procedure Catalogue',
                        'icon' => 'ti ti-list-details',
                        'route' => 'admin.procedure-catalogue.index',
                        'active_patterns' => ['admin.procedure-catalogue.*'],
                        'permission' => 'procedure.catalogue.view',
                    ],
                    [
                        'label' => 'Investigation Catalogue',
                        'icon' => 'ti ti-flask',
                        'route' => 'admin.investigation-catalogue.index',
                        'active_patterns' => ['admin.investigation-catalogue.*'],
                        'permission' => 'investigation.catalogue.view',
                    ],
                    [
                        'label' => 'Medical Patterns',
                        'icon' => 'ti ti-template',
                        'route' => 'admin.patterns.index',
                        'active_patterns' => ['admin.patterns.*'],
                        'permission' => 'consultations.view',
                    ],
                ],
            ],
            [
                'title' => 'Reports',
                'items' => [
                    [
                        'label' => 'Clinical Reports',
                        'icon' => 'ti ti-report',
                        'route' => 'admin.reports.visits',
                        'active_patterns' => ['admin.reports.*'],
                        'permission' => 'reports.view',
                    ],
                ],
            ],
            // [
            //     'title' => null,
            //     'items' => [
            //         [
            //             'label' => 'Notifications',
            //             'icon' => 'ti ti-bell',
            //             'route' => 'admin.notifications.index',
            //             'active_patterns' => ['admin.notifications.*'],
            //             'permission' => 'notifications.view',
            //             'badge' => $unreadNotifications > 0 ? $unreadNotifications : null,
            //             'badge_class' => 'badge bg-danger rounded-pill ms-auto',
            //         ],
            //     ],
            // ],
            [
                'title' => 'Profile',
                'items' => [
                    [
                        'label' => 'My Profile',
                        'icon' => 'ti ti-user-circle',
                        'route' => 'admin.profile',
                        'active_patterns' => ['admin.profile'],
                    ],
                ],
            ],
        ];
    }

    protected function filterSection(array $section, User $user, string $currentRouteName): ?array
    {
        $items = array_values(array_filter(array_map(
            fn (array $item) => $this->filterItem($item, $user, $currentRouteName),
            $section['items'] ?? [],
        )));

        if (empty($items)) {
            return null;
        }

        $section['items'] = $items;

        return $section;
    }

    protected function filterItem(array $item, User $user, string $currentRouteName): ?array
    {
        if (! $this->isVisible($item, $user)) {
            return null;
        }

        $children = array_values(array_filter(array_map(
            fn (array $child) => $this->filterItem($child, $user, $currentRouteName),
            $item['children'] ?? [],
        )));

        if (array_key_exists('children', $item)) {
            if (empty($children)) {
                return null;
            }

            $item['children'] = $children;
        }

        $item['active'] = $this->isActive($item, $currentRouteName)
            || collect($item['children'] ?? [])->contains(fn (array $child) => $child['active'] ?? false);

        return $item;
    }

    protected function isVisible(array $item, User $user): bool
    {
        // Drop items whose named route doesn't exist in this installation
        if (! empty($item['route']) && ! Route::has($item['route'])) {
            return false;
        }

        if (! empty($item['module']) && ! $this->moduleService->enabled($item['module'])) {
            return false;
        }

        if (! empty($item['permission']) && ! $user->can($item['permission'])) {
            return false;
        }

        if (! empty($item['permissions_any']) && ! $user->canAny($item['permissions_any'])) {
            return false;
        }

        if (! empty($item['role']) && ! $user->hasRole($item['role'])) {
            return false;
        }

        if (! empty($item['roles_any']) && ! $user->hasAnyRole($item['roles_any'])) {
            return false;
        }

        return true;
    }

    protected function isActive(array $item, string $currentRouteName): bool
    {
        $patterns = $item['active_patterns'] ?? [];

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $currentRouteName)) {
                return true;
            }
        }

        return false;
    }
}