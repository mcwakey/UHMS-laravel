<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Unified, permission-aware report catalogue.
 *
 * Each entry in the registry defines a report with its title, description,
 * required permission, module gate, route name, and export/print capabilities.
 * The service resolves which reports are visible to a given user and groups
 * them into sections for the reports hub.
 */
class ReportRegistryService
{
    public function __construct(
        protected ModuleService $moduleService,
    ) {}

    /**
     * Full report registry.
     * Each item: [key, title, description, permission, module, route, exportable, printable]
     */
    public function all(): array
    {
        return [

            /* ---------------------------------------------------------- */
            /* Management                                                   */
            /* ---------------------------------------------------------- */
            [
                'key'         => 'management.dashboard',
                'section'     => 'management',
                'title'       => __('reports.management.title'),
                'description' => __('reports.management.description'),
                'permission'  => 'reports.view',
                'module'      => 'reports',
                'route'       => 'admin.reports.dashboard',
                'exportable'  => false,
                'printable'   => false,
                'icon'        => 'ti-chart-bar',
            ],

            /* ---------------------------------------------------------- */
            /* Clinical                                                     */
            /* ---------------------------------------------------------- */
            [
                'key'         => 'clinical.consultations',
                'section'     => 'clinical',
                'title'       => __('reports.consultations.title'),
                'description' => __('reports.consultations.description'),
                'permission'  => 'reports.consultations',
                'module'      => 'consultation',
                'route'       => 'admin.reports.consultations',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-stethoscope',
            ],
            [
                'key'         => 'clinical.diagnoses',
                'section'     => 'clinical',
                'title'       => __('reports.diagnoses.title'),
                'description' => __('reports.diagnoses.description'),
                'permission'  => 'reports.diagnoses',
                'module'      => 'consultation',
                'route'       => 'admin.reports.diagnoses',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-clipboard-text',
            ],
            [
                'key'         => 'clinical.consultation_stats',
                'section'     => 'clinical',
                'title'       => __('reports.consultations.title').' (Stats)',
                'description' => __('reports.consultations.description'),
                'permission'  => 'reports.view',
                'module'      => 'consultation',
                'route'       => 'admin.reports.consultation-stats',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-chart-dots',
            ],

            /* ---------------------------------------------------------- */
            /* Patients / Visits                                            */
            /* ---------------------------------------------------------- */
            [
                'key'         => 'patients.register',
                'section'     => 'patients',
                'title'       => __('reports.patients.title'),
                'description' => __('reports.patients.description'),
                'permission'  => 'reports.view',
                'module'      => 'patients',
                'route'       => 'admin.reports.patients',
                'exportable'  => true,
                'printable'   => true,
                'icon'        => 'ti-users',
            ],
            [
                'key'         => 'patients.visits',
                'section'     => 'patients',
                'title'       => __('reports.visits.title'),
                'description' => __('reports.visits.description'),
                'permission'  => 'reports.view',
                'module'      => 'visits',
                'route'       => 'admin.reports.visits',
                'exportable'  => true,
                'printable'   => true,
                'icon'        => 'ti-clipboard',
            ],
            [
                'key'         => 'patients.statement',
                'section'     => 'patients',
                'title'       => __('reports.statement.title'),
                'description' => __('reports.statement.description'),
                'permission'  => 'reports.view',
                'module'      => 'patients',
                'route'       => 'admin.reports.statement-search',
                'exportable'  => true,
                'printable'   => true,
                'icon'        => 'ti-file-text',
            ],

            /* ---------------------------------------------------------- */
            /* Emergency                                                    */
            /* ---------------------------------------------------------- */
            [
                'key'         => 'emergency.summary',
                'section'     => 'emergency',
                'title'       => __('reports.emergency.title'),
                'description' => __('reports.emergency.description'),
                'permission'  => 'reports.emergency',
                'module'      => 'emergency',
                'route'       => 'admin.reports.emergency',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-ambulance',
            ],

            /* ---------------------------------------------------------- */
            /* Admissions / Ward                                            */
            /* ---------------------------------------------------------- */
            [
                'key'         => 'admissions.register',
                'section'     => 'admissions',
                'title'       => __('reports.admissions.title'),
                'description' => __('reports.admissions.description'),
                'permission'  => 'reports.admission',
                'module'      => 'ward',
                'route'       => 'admin.reports.admissions',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-bed',
            ],
            [
                'key'         => 'admissions.discharges',
                'section'     => 'admissions',
                'title'       => __('reports.admissions.discharge_title'),
                'description' => __('reports.admissions.discharge_description'),
                'permission'  => 'reports.admission',
                'module'      => 'ward',
                'route'       => 'admin.reports.discharges',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-logout',
            ],
            [
                'key'         => 'admissions.mar',
                'section'     => 'admissions',
                'title'       => __('reports.mar.title'),
                'description' => __('reports.mar.description'),
                'permission'  => 'reports.mar',
                'module'      => 'ward',
                'route'       => 'admin.reports.mar',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-pill',
            ],

            /* ---------------------------------------------------------- */
            /* Pharmacy                                                     */
            /* ---------------------------------------------------------- */
            [
                'key'         => 'pharmacy.sales',
                'section'     => 'pharmacy',
                'title'       => __('reports.pharmacy.sales_title'),
                'description' => __('reports.pharmacy.sales_description'),
                'permission'  => 'reports.pharmacy',
                'module'      => 'pharmacy',
                'route'       => 'admin.reports.pharmacy-sales',
                'exportable'  => true,
                'printable'   => true,
                'icon'        => 'ti-prescription',
            ],
            [
                'key'         => 'pharmacy.summary',
                'section'     => 'pharmacy',
                'title'       => __('reports.pharmacy.summary_title'),
                'description' => __('reports.pharmacy.summary_description'),
                'permission'  => 'reports.pharmacy',
                'module'      => 'pharmacy',
                'route'       => 'admin.reports.pharmacy-sales-summary',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-chart-bar',
            ],
            [
                'key'         => 'pharmacy.dispensing',
                'section'     => 'pharmacy',
                'title'       => __('reports.pharmacy.title'),
                'description' => __('reports.pharmacy.description'),
                'permission'  => 'reports.pharmacy',
                'module'      => 'pharmacy',
                'route'       => 'admin.reports.pharmacy',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-pill',
            ],
            [
                'key'         => 'stock.expired',
                'section'     => 'pharmacy',
                'title'       => __('reports.stock.expired_stock'),
                'description' => __('reports.stock.expired_description'),
                'permission'  => 'reports.stock',
                'module'      => 'pharmacy',
                'route'       => 'admin.reports.expired-stock',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-clock-x',
            ],

            /* ---------------------------------------------------------- */
            /* Investigations / Lab                                         */
            /* ---------------------------------------------------------- */
            [
                'key'         => 'investigations.requests',
                'section'     => 'investigations',
                'title'       => __('reports.investigations.title'),
                'description' => __('reports.investigations.description'),
                'permission'  => 'reports.investigations',
                'module'      => 'investigations',
                'route'       => 'admin.reports.investigations',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-microscope',
            ],
            [
                'key'         => 'investigations.revenue',
                'section'     => 'investigations',
                'title'       => __('reports.investigations.revenue_title'),
                'description' => __('reports.investigations.revenue_description'),
                'permission'  => 'reports.investigations',
                'module'      => 'investigations',
                'route'       => 'admin.reports.investigation-revenue',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-report-money',
            ],

            /* ---------------------------------------------------------- */
            /* Theatre / Procedures                                         */
            /* ---------------------------------------------------------- */
            [
                'key'         => 'theatre.procedures',
                'section'     => 'theatre',
                'title'       => __('reports.procedures.title'),
                'description' => __('reports.procedures.description'),
                'permission'  => 'reports.procedures',
                'module'      => 'services',
                'route'       => 'admin.reports.procedures',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-stethoscope',
            ],
            [
                'key'         => 'theatre.theatre',
                'section'     => 'theatre',
                'title'       => __('reports.theatre.title'),
                'description' => __('reports.theatre.description'),
                'permission'  => 'reports.theatre',
                'module'      => 'services',
                'route'       => 'admin.reports.theatre',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-activity',
            ],

            /* ---------------------------------------------------------- */
            /* Billing                                                      */
            /* ---------------------------------------------------------- */
            [
                'key'         => 'billing.daily_collections',
                'section'     => 'billing',
                'title'       => __('reports.billing.daily_collections'),
                'description' => __('reports.billing.daily_collections_description'),
                'permission'  => 'reports.billing',
                'module'      => 'billing',
                'route'       => 'admin.reports.daily-collection',
                'exportable'  => true,
                'printable'   => true,
                'icon'        => 'ti-cash',
            ],
            [
                'key'         => 'billing.income',
                'section'     => 'billing',
                'title'       => __('reports.billing.income_title'),
                'description' => __('reports.billing.income_description'),
                'permission'  => 'reports.billing',
                'module'      => 'billing',
                'route'       => 'admin.reports.income',
                'exportable'  => true,
                'printable'   => true,
                'icon'        => 'ti-report-money',
            ],
            [
                'key'         => 'billing.invoices',
                'section'     => 'billing',
                'title'       => __('reports.billing.title'),
                'description' => __('reports.billing.description'),
                'permission'  => 'reports.billing',
                'module'      => 'billing',
                'route'       => 'admin.reports.billing',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-file-invoice',
            ],
            [
                'key'         => 'billing.ar_aging',
                'section'     => 'billing',
                'title'       => __('reports.billing.ar_aging_title'),
                'description' => __('reports.billing.ar_aging_description'),
                'permission'  => 'reports.ar_aging.view',
                'module'      => 'billing',
                'route'       => 'admin.billing.reports.aging',
                'exportable'  => true,
                'printable'   => true,
                'icon'        => 'ti-clock-dollar',
            ],

            /* ---------------------------------------------------------- */
            /* Claims / Insurance                                           */
            /* ---------------------------------------------------------- */
            [
                'key'         => 'claims.register',
                'section'     => 'claims',
                'title'       => __('reports.claims.title'),
                'description' => __('reports.claims.description'),
                'permission'  => 'reports.claims',
                'module'      => 'claims',
                'route'       => 'admin.reports.claims',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-clipboard-text',
            ],
            [
                'key'         => 'claims.insurance',
                'section'     => 'claims',
                'title'       => __('reports.patients.title').' (Insurance)',
                'description' => __('reports.claims.description'),
                'permission'  => 'reports.claims',
                'module'      => 'insurance',
                'route'       => 'admin.reports.insurance-claims',
                'exportable'  => true,
                'printable'   => true,
                'icon'        => 'ti-shield-check',
            ],

            /* ---------------------------------------------------------- */
            /* Accounting                                                   */
            /* ---------------------------------------------------------- */
            [
                'key'         => 'accounting.trial_balance',
                'section'     => 'accounting',
                'title'       => __('reports.accounting.trial_balance'),
                'description' => __('reports.accounting.description'),
                'permission'  => 'accounting.reports.trial_balance',
                'module'      => 'accounting',
                'route'       => 'admin.accounting.trial-balance',
                'exportable'  => false,
                'printable'   => true,
                'icon'        => 'ti-scale',
            ],
            [
                'key'         => 'accounting.general_ledger',
                'section'     => 'accounting',
                'title'       => __('reports.accounting.general_ledger'),
                'description' => __('reports.accounting.description'),
                'permission'  => 'accounting.reports.general_ledger',
                'module'      => 'accounting',
                'route'       => 'admin.accounting.general-ledger',
                'exportable'  => false,
                'printable'   => true,
                'icon'        => 'ti-book',
            ],
            [
                'key'         => 'accounting.profit_loss',
                'section'     => 'accounting',
                'title'       => __('reports.accounting.profit_loss'),
                'description' => __('reports.accounting.description'),
                'permission'  => 'accounting.reports.profit_loss',
                'module'      => 'accounting',
                'route'       => 'admin.accounting.reports.profit-loss',
                'exportable'  => false,
                'printable'   => true,
                'icon'        => 'ti-trending-up',
            ],
            [
                'key'         => 'accounting.balance_sheet',
                'section'     => 'accounting',
                'title'       => __('reports.accounting.balance_sheet'),
                'description' => __('reports.accounting.description'),
                'permission'  => 'accounting.reports.balance_sheet',
                'module'      => 'accounting',
                'route'       => 'admin.accounting.reports.balance-sheet',
                'exportable'  => false,
                'printable'   => true,
                'icon'        => 'ti-report',
            ],
            [
                'key'         => 'accounting.cashbook',
                'section'     => 'accounting',
                'title'       => __('reports.accounting.cashbook'),
                'description' => __('reports.accounting.description'),
                'permission'  => 'accounting.reports.cashbook',
                'module'      => 'accounting',
                'route'       => 'admin.accounting.reports.cashbook',
                'exportable'  => false,
                'printable'   => true,
                'icon'        => 'ti-cash',
            ],

            /* ---------------------------------------------------------- */
            /* Receivables                                                  */
            /* ---------------------------------------------------------- */
            [
                'key'         => 'receivables.ar_aging',
                'section'     => 'receivables',
                'title'       => __('reports.receivables.ar_aging'),
                'description' => __('reports.receivables.description'),
                'permission'  => 'reports.ar_aging.view',
                'module'      => 'billing',
                'route'       => 'admin.billing.reports.aging',
                'exportable'  => true,
                'printable'   => true,
                'icon'        => 'ti-clock-dollar',
            ],

            /* ---------------------------------------------------------- */
            /* Payables                                                     */
            /* ---------------------------------------------------------- */
            [
                'key'         => 'payables.ap_aging',
                'section'     => 'payables',
                'title'       => __('reports.payables.ap_aging'),
                'description' => __('reports.payables.description'),
                'permission'  => 'reports.ap_aging.view',
                'module'      => 'billing',
                'route'       => 'admin.accounts-payable.aging',
                'exportable'  => true,
                'printable'   => true,
                'icon'        => 'ti-clock-dollar',
            ],
            [
                'key'         => 'payables.supplier_statement',
                'section'     => 'payables',
                'title'       => __('reports.payables.supplier_stmt'),
                'description' => __('reports.payables.description'),
                'permission'  => 'reports.supplier_statement.view',
                'module'      => 'inventory',
                'route'       => 'admin.billing.statements',
                'exportable'  => false,
                'printable'   => true,
                'icon'        => 'ti-file-text',
            ],

            /* ---------------------------------------------------------- */
            /* Stock                                                        */
            /* ---------------------------------------------------------- */
            [
                'key'         => 'stock.valuation',
                'section'     => 'stock',
                'title'       => __('reports.stock.stock_valuation'),
                'description' => __('reports.stock.stock_valuation_description'),
                'permission'  => 'reports.inventory_valuation.view',
                'module'      => 'inventory',
                'route'       => 'admin.reports.stock-valuation',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-report-money',
            ],
            [
                'key'         => 'stock.movements',
                'section'     => 'stock',
                'title'       => __('reports.stock.title'),
                'description' => __('reports.stock.description'),
                'permission'  => 'reports.stock',
                'module'      => 'inventory',
                'route'       => 'admin.reports.stock',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-package',
            ],

            /* ---------------------------------------------------------- */
            /* HR                                                           */
            /* ---------------------------------------------------------- */
            [
                'key'         => 'hr.leave',
                'section'     => 'hr',
                'title'       => __('reports.hr.leave_title'),
                'description' => __('reports.hr.leave_description'),
                'permission'  => 'reports.view',
                'module'      => 'hr',
                'route'       => 'admin.reports.leave',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-calendar-off',
            ],
            [
                'key'         => 'hr.payroll',
                'section'     => 'hr',
                'title'       => __('reports.hr.payroll_title'),
                'description' => __('reports.hr.payroll_description'),
                'permission'  => 'reports.view',
                'module'      => 'payroll',
                'route'       => 'admin.reports.payroll',
                'exportable'  => true,
                'printable'   => true,
                'icon'        => 'ti-coin',
            ],

            /* ---------------------------------------------------------- */
            /* Audit                                                        */
            /* ---------------------------------------------------------- */
            [
                'key'         => 'audit.activity',
                'section'     => 'audit',
                'title'       => __('reports.audit.title'),
                'description' => __('reports.audit.description'),
                'permission'  => 'logs.view',
                'module'      => 'settings',
                'route'       => 'admin.logs.index',
                'exportable'  => true,
                'printable'   => false,
                'icon'        => 'ti-history',
            ],
        ];
    }

    /**
     * Return only the reports visible to the given user,
     * respecting both permissions and module gates.
     */
    public function forUser(User $user): array
    {
        return array_filter($this->all(), function (array $report) use ($user) {
            // Permission check
            if ($report['permission'] && ! $user->can($report['permission'])) {
                return false;
            }
            if ($report['module'] && ! $this->moduleService->enabled($report['module'])) {
                return false;
            }

            if ($report['route'] && ! Route::has($report['route'])) {
                return false;
            }

            return true;
        });
    }

    /**
     * Group reports by section, returning only sections that have at least
     * one visible report.
     */
    public function groupedForUser(User $user): array
    {
        $sections = [];
        foreach ($this->forUser($user) as $report) {
            $sections[$report['section']][] = $report;
        }

        return $sections;
    }

    /**
     * Section display metadata (title, icon).
     */
    public function sectionMeta(): array
    {
        return [
            'management'     => ['title' => __('reports.sections.management'),     'icon' => 'ti-chart-bar'],
            'clinical'       => ['title' => __('reports.sections.clinical'),       'icon' => 'ti-stethoscope'],
            'patients'       => ['title' => __('reports.sections.patients'),       'icon' => 'ti-users'],
            'emergency'      => ['title' => __('reports.sections.emergency'),      'icon' => 'ti-ambulance'],
            'admissions'     => ['title' => __('reports.sections.admissions'),     'icon' => 'ti-bed'],
            'pharmacy'       => ['title' => __('reports.sections.pharmacy'),       'icon' => 'ti-prescription'],
            'investigations' => ['title' => __('reports.sections.investigations'), 'icon' => 'ti-microscope'],
            'theatre'        => ['title' => __('reports.sections.theatre'),        'icon' => 'ti-activity'],
            'billing'        => ['title' => __('reports.sections.billing'),        'icon' => 'ti-file-invoice'],
            'claims'         => ['title' => __('reports.sections.claims'),         'icon' => 'ti-shield-check'],
            'accounting'     => ['title' => __('reports.sections.accounting'),     'icon' => 'ti-book'],
            'receivables'    => ['title' => __('reports.sections.receivables'),    'icon' => 'ti-clock-dollar'],
            'payables'       => ['title' => __('reports.sections.payables'),       'icon' => 'ti-truck-delivery'],
            'stock'          => ['title' => __('reports.sections.stock'),          'icon' => 'ti-package'],
            'hr'             => ['title' => __('reports.sections.hr'),             'icon' => 'ti-users-group'],
            'audit'          => ['title' => __('reports.sections.audit'),          'icon' => 'ti-history'],
        ];
    }
}
