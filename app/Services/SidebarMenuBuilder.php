<?php

namespace App\Services;

use App\Models\User;
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
                        'permission' => 'appointments.view',
                        'active_patterns' => ['admin.appointments.*'],
                        'children' => [
                            [
                                'label' => 'All Appointments',
                                'route' => 'admin.appointments.index',
                                'active_patterns' => ['admin.appointments.index'],
                                'permission' => 'appointments.view',
                            ],
                            [
                                'label' => 'Calendar View',
                                'route' => 'admin.appointments.calendar',
                                'active_patterns' => ['admin.appointments.calendar'],
                                'permission' => 'appointments.view',
                            ],
                            [
                                'label' => 'Schedule New',
                                'route' => 'admin.appointments.create',
                                'active_patterns' => ['admin.appointments.create'],
                                'permission' => 'appointments.create',
                            ],
                        ],
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
                    [
                        'label' => 'Triage',
                        'icon' => 'ti ti-ambulance',
                        'route' => 'admin.triage.index',
                        'active_patterns' => ['admin.triage.*'],
                        'permission' => 'vitals.view',
                        'module' => 'triage',
                    ],
                ],
            ],
            [
                'title' => 'Clinical',
                'items' => [
                    [
                        'label' => 'Consultations',
                        'icon' => 'ti ti-stethoscope',
                        'route' => 'admin.consultations.index',
                        'active_patterns' => ['admin.consultations.*'],
                        'permission' => 'consultations.view',
                        'module' => 'consultation',
                    ],
                    [
                        'label' => 'Vitals',
                        'icon' => 'ti ti-heartbeat',
                        'route' => 'admin.vitals.create',
                        'active_patterns' => ['admin.vitals.*'],
                        'permission' => 'vitals.view',
                        'module' => 'triage',
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
                        'label' => 'Procedures',
                        'icon' => 'ti ti-surgery',
                        'permission' => 'procedures.view',
                        'active_patterns' => ['admin.procedures.*'],
                        'children' => [
                            [
                                'label' => 'Procedure Catalog',
                                'route' => 'admin.procedures.index',
                                'active_patterns' => ['admin.procedures.index'],
                                'permission' => 'procedures.view',
                            ],
                            [
                                'label' => 'Scheduled Procedures',
                                'route' => 'admin.procedures.schedule',
                                'active_patterns' => ['admin.procedures.schedule'],
                                'permission' => 'procedures.view',
                            ],
                        ],
                    ],
                    [
                        'label' => 'ICD-10 Codes',
                        'icon' => 'ti ti-medical-cross',
                        'route' => 'admin.icd-codes.index',
                        'active_patterns' => ['admin.icd-codes.*'],
                        'permission' => 'icd.manage',
                    ],
                ],
            ],
            [
                'title' => 'Ward / Inpatient',
                'items' => [
                    [
                        'label' => 'Admissions',
                        'icon' => 'ti ti-bed',
                        'route' => 'admin.admissions.index',
                        'active_patterns' => ['admin.admissions.*'],
                        'permission' => 'ward.view',
                    ],
                    [
                        'label' => 'Bed Map',
                        'icon' => 'ti ti-map',
                        'route' => 'admin.wards.bed-map',
                        'active_patterns' => ['admin.wards.bed-map'],
                        'permission' => 'ward.view',
                    ],
                    [
                        'label' => 'Wards',
                        'icon' => 'ti ti-building-hospital',
                        'route' => 'admin.wards.index',
                        'active_patterns' => ['admin.wards.index'],
                        'permission' => 'ward.manage',
                    ],
                    [
                        'label' => 'Bed Management',
                        'icon' => 'ti ti-bed-flat',
                        'route' => 'admin.wards.beds',
                        'active_patterns' => ['admin.wards.beds'],
                        'permission' => 'beds.manage',
                    ],
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
                    [
                        'label' => 'Drug Stock',
                        'icon' => 'ti ti-packages',
                        'route' => 'admin.pharmacy.stock.index',
                        'active_patterns' => ['admin.pharmacy.stock.*'],
                        'permission' => 'pharmacy.stock.manage',
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
                        'label' => 'Test Catalog',
                        'icon' => 'ti ti-flask',
                        'route' => 'admin.lab.tests.index',
                        'active_patterns' => ['admin.lab.tests.*'],
                        'permission' => 'lab.tests.manage',
                        'module' => 'investigations',
                    ],
                    [
                        'label' => 'Investigation Items',
                        'icon' => 'ti ti-microscope',
                        'route' => 'admin.investigations.items.index',
                        'active_patterns' => ['admin.investigations.items.*'],
                        'permission' => 'lab.tests.manage',
                        'module' => 'investigations',
                    ],
                    [
                        'label' => 'Investigation Stock',
                        'icon' => 'ti ti-packages',
                        'route' => 'admin.investigations.stock.index',
                        'active_patterns' => ['admin.investigations.stock.*'],
                        'permission' => 'lab.tests.manage',
                        'module' => 'investigations',
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
            [
                'title' => 'Billing',
                'items' => [
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
                        'active_patterns' => ['admin.billing.payments.*'],
                        'permission' => 'payments.view',
                        'module' => 'billing',
                    ],
                    [
                        'label' => 'Service Catalog',
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
                    [
                        'label' => 'New Claim',
                        'icon' => 'ti ti-file-plus',
                        'route' => 'admin.claims.create',
                        'active_patterns' => ['admin.claims.create'],
                        'permission' => 'claims.create',
                        'module' => 'claims',
                    ],
                    [
                        'label' => 'Insurance Providers',
                        'icon' => 'ti ti-shield-check',
                        'route' => 'admin.insurance-providers.index',
                        'active_patterns' => ['admin.insurance-providers.*', 'admin.insurance-tiers.*'],
                        'permission' => 'claims.view',
                        'module' => 'insurance',
                    ],
                ],
            ],
            [
                'title' => 'Store & Procurement',
                'items' => [
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
                        'label' => 'Stock Transfers',
                        'icon' => 'ti ti-transfer',
                        'route' => 'admin.store.transfers.index',
                        'active_patterns' => ['admin.store.transfers.*'],
                        'permission' => 'store.transfer.view',
                        'module' => 'inventory',
                    ],
                ],
            ],
            [
                'title' => 'Accounts & Finance',
                'items' => [
                    [
                        'label' => 'Account Categories',
                        'icon' => 'ti ti-category',
                        'route' => 'admin.accounts.categories.index',
                        'active_patterns' => ['admin.accounts.categories.*'],
                        'permission' => 'accounts.manage',
                    ],
                    [
                        'label' => 'Expenses',
                        'icon' => 'ti ti-trending-down',
                        'route' => 'admin.accounts.expenses.index',
                        'active_patterns' => ['admin.accounts.expenses.*'],
                        'permission' => 'accounts.entries.view',
                    ],
                    [
                        'label' => 'Income',
                        'icon' => 'ti ti-trending-up',
                        'route' => 'admin.accounts.income.index',
                        'active_patterns' => ['admin.accounts.income.*'],
                        'permission' => 'accounts.entries.view',
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
                        'label' => 'Cashier Handover',
                        'icon' => 'ti ti-cash-register',
                        'route' => 'admin.accounts.handover.index',
                        'active_patterns' => ['admin.accounts.handover.*'],
                        'permission' => 'accounts.cashier',
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
                            'admin.reports.nhis',
                            'admin.reports.claims',
                            'admin.reports.statement-search',
                            'admin.reports.patient-statement',
                        ],
                        'children' => [
                            ['label' => 'Income Report', 'route' => 'admin.reports.income', 'active_patterns' => ['admin.reports.income'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Daily Collection', 'route' => 'admin.reports.daily-collection', 'active_patterns' => ['admin.reports.daily-collection'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'NHIS Report', 'route' => 'admin.reports.nhis', 'active_patterns' => ['admin.reports.nhis'], 'permission' => 'reports.view', 'module' => 'reports'],
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
                'title' => null,
                'items' => [
                    [
                        'label' => 'Notifications',
                        'icon' => 'ti ti-bell',
                        'route' => 'admin.notifications.index',
                        'active_patterns' => ['admin.notifications.*'],
                        'permission' => 'notifications.view',
                        'module' => 'notifications',
                        'badge' => $unreadNotifications > 0 ? $unreadNotifications : null,
                        'badge_class' => 'badge bg-danger rounded-pill ms-auto',
                    ],
                ],
            ],
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

        return array_values(array_filter(array_map(
            fn (array $section) => $this->filterSection($section, $user, $currentRouteName),
            $sections,
        )));
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