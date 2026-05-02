<!-- Sidenav Menu Start -->
<div class="sidebar" id="sidebar">

    <!-- Start Logo -->
    <div class="sidebar-logo">
        <div>
            <a href="{{ route('dashboard') }}" class="logo logo-normal">
                <img src="{{ URL::asset('build/img/logo.svg') }}" alt="UHMS">
            </a>
            <a href="{{ route('dashboard') }}" class="logo-small">
                <img src="{{ URL::asset('build/img/logo-small.svg') }}" alt="UHMS">
            </a>
            <a href="{{ route('dashboard') }}" class="dark-logo">
                <img src="{{ URL::asset('build/img/logo-white.svg') }}" alt="UHMS">
            </a>
        </div>
        <button class="sidenav-toggle-btn btn border-0 p-0 active" id="toggle_btn">
            <i class="ti ti-arrow-left"></i>
        </button>
        <button class="sidebar-close">
            <i class="ti ti-x align-middle"></i>
        </button>
    </div>
    <!-- End Logo -->

    <!-- Sidenav Menu -->
    <div class="sidebar-inner" data-simplebar>
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>

                {{-- DASHBOARD --}}
                <li class="menu-title"><span>Main Menu</span></li>
                <li><ul>
                    @if(Auth::user()->hasAnyRole(['Super Admin', 'Admin', 'Receptionist', 'Nurse', 'Lab Technician', 'Pharmacist', 'Accountant']))
                    <li class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <a href="{{ route('admin.dashboard') }}"><i class="ti ti-layout-dashboard"></i><span>Dashboard</span></a>
                    </li>
                    @endif
                    @if(Auth::user()->hasRole('Doctor'))
                    <li class="{{ request()->routeIs('doctor.dashboard') ? 'active' : '' }}">
                        <a href="{{ route('doctor.dashboard') }}"><i class="ti ti-layout-dashboard"></i><span>Dashboard</span></a>
                    </li>
                    @endif
                </ul></li>

                {{-- PATIENT SERVICES --}}
                @if(Auth::user()->canAny(['patients.view', 'visits.view', 'queue.view', 'appointments.view']))
                <li class="menu-title"><span>Patient Services</span></li>
                <li><ul>
                    @can('patients.view')
                    <li class="{{ request()->routeIs('admin.patients.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.patients.index') }}"><i class="ti ti-user-heart"></i><span>Patients</span></a>
                    </li>
                    @endcan

                    @can('appointments.view')
                    <li class="submenu">
                        <a href="javascript:void(0);" class="{{ request()->routeIs('admin.appointments.*') ? 'active subdrop' : '' }}">
                            <i class="ti ti-calendar-event"></i><span>Appointments</span><span class="menu-arrow"></span>
                        </a>
                        <ul>
                            <li><a href="{{ route('admin.appointments.index') }}" class="{{ request()->routeIs('admin.appointments.index') ? 'active' : '' }}">All Appointments</a></li>
                            <li><a href="{{ route('admin.appointments.calendar') }}" class="{{ request()->routeIs('admin.appointments.calendar') ? 'active' : '' }}">Calendar View</a></li>
                            @can('appointments.create')
                            <li><a href="{{ route('admin.appointments.create') }}" class="{{ request()->routeIs('admin.appointments.create') ? 'active' : '' }}">Schedule New</a></li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    @can('visits.view')
                    <li class="{{ request()->routeIs('admin.visits.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.visits.index') }}"><i class="ti ti-calendar-check"></i><span>Visits / OPD</span></a>
                    </li>
                    @endcan

                    @can('queue.view')
                    <li class="submenu">
                        <a href="javascript:void(0);" class="{{ request()->routeIs('admin.queue.*') ? 'active subdrop' : '' }}">
                            <i class="ti ti-list-numbers"></i><span>Queue</span><span class="menu-arrow"></span>
                        </a>
                        <ul>
                            <li><a href="{{ route('admin.queue.manage') }}" class="{{ request()->routeIs('admin.queue.manage') ? 'active' : '' }}">Manage Queue</a></li>
                            <li><a href="{{ route('admin.queue.board') }}" class="{{ request()->routeIs('admin.queue.board') ? 'active' : '' }}">Queue Board</a></li>
                        </ul>
                    </li>
                    @endcan

                    @can('vitals.view')
                    <li class="{{ request()->routeIs('admin.triage.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.triage.index') }}"><i class="ti ti-ambulance"></i><span>Triage</span></a>
                    </li>
                    @endcan
                </ul></li>
                @endif

                {{-- CLINICAL --}}
                @if(Auth::user()->canAny(['consultations.view', 'vitals.view', 'procedures.view', 'icd.manage']))
                <li class="menu-title"><span>Clinical</span></li>
                <li><ul>
                    @can('consultations.view')
                    <li class="{{ request()->routeIs('admin.consultations.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.consultations.index') }}"><i class="ti ti-stethoscope"></i><span>Consultations</span></a>
                    </li>
                    @endcan

                    @can('vitals.view')
                    <li class="{{ request()->routeIs('admin.vitals.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.vitals.create') }}"><i class="ti ti-heartbeat"></i><span>Vitals</span></a>
                    </li>
                    @endcan

                    @can('consultations.view')
                    <li class="{{ request()->routeIs('admin.patterns.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.patterns.index') }}"><i class="ti ti-template"></i><span>Medical Patterns</span></a>
                    </li>
                    @endcan

                    @can('procedures.view')
                    <li class="submenu">
                        <a href="javascript:void(0);" class="{{ request()->routeIs('admin.procedures.*') ? 'active subdrop' : '' }}">
                            <i class="ti ti-surgery"></i><span>Procedures</span><span class="menu-arrow"></span>
                        </a>
                        <ul>
                            <li><a href="{{ route('admin.procedures.index') }}" class="{{ request()->routeIs('admin.procedures.index') ? 'active' : '' }}">Procedure Catalog</a></li>
                            <li><a href="{{ route('admin.procedures.schedule') }}" class="{{ request()->routeIs('admin.procedures.schedule') ? 'active' : '' }}">Scheduled Procedures</a></li>
                        </ul>
                    </li>
                    @endcan

                    @can('icd.manage')
                    <li class="{{ request()->routeIs('admin.icd-codes.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.icd-codes.index') }}"><i class="ti ti-medical-cross"></i><span>ICD-10 Codes</span></a>
                    </li>
                    @endcan
                </ul></li>
                @endif

                {{-- WARD / INPATIENT --}}
                @can('ward.view')
                <li class="menu-title"><span>Ward / Inpatient</span></li>
                <li><ul>
                    <li class="{{ request()->routeIs('admin.admissions.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.admissions.index') }}"><i class="ti ti-bed"></i><span>Admissions</span></a>
                    </li>
                    <li class="{{ request()->routeIs('admin.wards.bed-map') ? 'active' : '' }}">
                        <a href="{{ route('admin.wards.bed-map') }}"><i class="ti ti-map"></i><span>Bed Map</span></a>
                    </li>
                    @can('ward.manage')
                    <li class="{{ request()->routeIs('admin.wards.index') ? 'active' : '' }}">
                        <a href="{{ route('admin.wards.index') }}"><i class="ti ti-building-hospital"></i><span>Wards</span></a>
                    </li>
                    @endcan
                    @can('beds.manage')
                    <li class="{{ request()->routeIs('admin.wards.beds') ? 'active' : '' }}">
                        <a href="{{ route('admin.wards.beds') }}"><i class="ti ti-bed-flat"></i><span>Bed Management</span></a>
                    </li>
                    @endcan
                </ul></li>
                @endcan

                {{-- PHARMACY --}}
                @module('pharmacy')
                @if(Auth::user()->canAny(['prescriptions.view', 'pharmacy.dispensing.view', 'pharmacy.drugs.manage', 'pharmacy.stock.manage']))
                <li class="menu-title"><span>Pharmacy</span></li>
                <li><ul>
                    @can('prescriptions.view')
                    <li class="{{ request()->routeIs('admin.prescriptions.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.prescriptions.index') }}"><i class="ti ti-prescription"></i><span>Prescriptions</span></a>
                    </li>
                    @endcan
                    @can('pharmacy.dispensing.view')
                    <li class="{{ request()->routeIs('admin.pharmacy.dispensing.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.pharmacy.dispensing.index') }}"><i class="ti ti-pill"></i><span>Dispensing</span></a>
                    </li>
                    @endcan
                    @can('pharmacy.drugs.manage')
                    <li class="{{ request()->routeIs('admin.pharmacy.drugs.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.pharmacy.drugs.index') }}"><i class="ti ti-medicine-syrup"></i><span>Drug Catalog</span></a>
                    </li>
                    @endcan
                    @can('pharmacy.stock.manage')
                    <li class="{{ request()->routeIs('admin.pharmacy.stock.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.pharmacy.stock.index') }}"><i class="ti ti-packages"></i><span>Drug Stock</span></a>
                    </li>
                    @endcan
                </ul></li>
                @endif
                @endmodule

                {{-- INVESTIGATIONS --}}
                @module('investigations')
                @if(Auth::user()->canAny(['lab.requests.view', 'lab.results.view', 'lab.tests.manage', 'analyzer.manage']))
                <li class="menu-title"><span>Investigations</span></li>
                <li><ul>
                    @can('lab.requests.view')
                    <li class="{{ request()->routeIs('admin.lab.requests.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.lab.requests.index') }}"><i class="ti ti-test-pipe"></i><span>Investigation Requests</span></a>
                    </li>
                    @endcan
                    @can('lab.results.view')
                    <li class="{{ request()->routeIs('admin.lab.results.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.lab.results.index') }}"><i class="ti ti-report-medical"></i><span>Investigation Results</span></a>
                    </li>
                    @endcan
                    @can('lab.tests.manage')
                    <li class="{{ request()->routeIs('admin.lab.tests.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.lab.tests.index') }}"><i class="ti ti-flask"></i><span>Test Catalog</span></a>
                    </li>
                    <li class="{{ request()->routeIs('admin.investigations.items.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.investigations.items.index') }}"><i class="ti ti-microscope"></i><span>Investigation Items</span></a>
                    </li>
                    <li class="{{ request()->routeIs('admin.investigations.stock.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.investigations.stock.index') }}"><i class="ti ti-packages"></i><span>Investigation Stock</span></a>
                    </li>
                    @endcan
                    @can('analyzer.manage')
                    <li class="{{ request()->routeIs('admin.analyzers.index', 'admin.analyzers.show') ? 'active' : '' }}">
                        <a href="{{ route('admin.analyzers.index') }}"><i class="ti ti-device-analytics"></i><span>Analyzers</span></a>
                    </li>
                    <li class="{{ request()->routeIs('admin.analyzers.diagnostics') ? 'active' : '' }}">
                        <a href="{{ route('admin.analyzers.diagnostics') }}"><i class="ti ti-activity"></i><span>Analyzer Messages</span></a>
                    </li>
                    @endcan
                </ul></li>
                @endif
                @endmodule

                {{-- BILLING --}}
                @if(Auth::user()->canAny(['invoices.view', 'payments.view', 'services.manage']))
                <li class="menu-title"><span>Billing</span></li>
                <li><ul>
                    @can('invoices.view')
                    <li class="{{ request()->routeIs('admin.billing.invoices.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.billing.invoices.index') }}"><i class="ti ti-file-invoice"></i><span>Invoices</span></a>
                    </li>
                    @endcan
                    @can('payments.view')
                    <li class="{{ request()->routeIs('admin.billing.payments.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.billing.payments.index') }}"><i class="ti ti-cash"></i><span>Payments</span></a>
                    </li>
                    @endcan
                    @can('services.manage')
                    <li class="{{ request()->routeIs('admin.services.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.services.index') }}"><i class="ti ti-list-details"></i><span>Service Catalog</span></a>
                    </li>
                    <li class="{{ request()->routeIs('admin.specialties.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.specialties.index') }}"><i class="ti ti-stethoscope"></i><span>Specialties</span></a>
                    </li>
                    @endcan
                </ul></li>
                @endif

                {{-- CLAIMS & INSURANCE --}}
                @can('claims.view')
                <li class="menu-title"><span>Claims & Insurance</span></li>
                <li><ul>
                    <li class="{{ request()->routeIs('admin.claims.index', 'admin.claims.show', 'admin.claims.review') ? 'active' : '' }}">
                        <a href="{{ route('admin.claims.index') }}"><i class="ti ti-file-check"></i><span>Claims</span></a>
                    </li>
                    @can('claims.create')
                    <li class="{{ request()->routeIs('admin.claims.create') ? 'active' : '' }}">
                        <a href="{{ route('admin.claims.create') }}"><i class="ti ti-file-plus"></i><span>New Claim</span></a>
                    </li>
                    @endcan
                    <li class="{{ request()->routeIs('admin.insurance-providers.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.insurance-providers.index') }}"><i class="ti ti-shield-check"></i><span>Insurance Providers</span></a>
                    </li>
                </ul></li>
                @endcan

                {{-- STORE & PROCUREMENT --}}
                @if(Auth::user()->canAny(['store.purchase.view', 'store.transfer.view']))
                <li class="menu-title"><span>Store & Procurement</span></li>
                <li><ul>
                    @can('store.purchase.view')
                    <li class="{{ request()->routeIs('admin.store.suppliers.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.store.suppliers.index') }}"><i class="ti ti-truck"></i><span>Suppliers</span></a>
                    </li>
                    <li class="{{ request()->routeIs('admin.store.purchase-orders.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.store.purchase-orders.index') }}"><i class="ti ti-file-text"></i><span>Purchase Orders</span></a>
                    </li>
                    @endcan
                    @can('store.transfer.view')
                    <li class="{{ request()->routeIs('admin.store.transfers.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.store.transfers.index') }}"><i class="ti ti-transfer"></i><span>Stock Transfers</span></a>
                    </li>
                    @endcan
                </ul></li>
                @endif

                {{-- ACCOUNTS & FINANCE --}}
                @if(Auth::user()->canAny(['accounts.entries.view', 'accounts.manage', 'accounts.cashier']))
                <li class="menu-title"><span>Accounts & Finance</span></li>
                <li><ul>
                    @can('accounts.manage')
                    <li class="{{ request()->routeIs('admin.accounts.categories.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.accounts.categories.index') }}"><i class="ti ti-category"></i><span>Account Categories</span></a>
                    </li>
                    @endcan
                    @can('accounts.entries.view')
                    <li class="{{ request()->routeIs('admin.accounts.expenses.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.accounts.expenses.index') }}"><i class="ti ti-trending-down"></i><span>Expenses</span></a>
                    </li>
                    <li class="{{ request()->routeIs('admin.accounts.income.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.accounts.income.index') }}"><i class="ti ti-trending-up"></i><span>Income</span></a>
                    </li>
                    <li class="{{ request()->routeIs('admin.accounts.daily-collection') ? 'active' : '' }}">
                        <a href="{{ route('admin.accounts.daily-collection') }}"><i class="ti ti-report-money"></i><span>Daily Collection</span></a>
                    </li>
                    <li class="{{ request()->routeIs('admin.accounts.reconciliation') ? 'active' : '' }}">
                        <a href="{{ route('admin.accounts.reconciliation') }}"><i class="ti ti-chart-bar"></i><span>Reconciliation</span></a>
                    </li>
                    @endcan
                    @can('accounts.cashier')
                    <li class="{{ request()->routeIs('admin.accounts.handover.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.accounts.handover.index') }}"><i class="ti ti-cash-register"></i><span>Cashier Handover</span></a>
                    </li>
                    @endcan
                </ul></li>
                @endif

                {{-- HR & PAYROLL --}}
                @if(Auth::user()->canAny(['hr.employees.view', 'hr.leave.view', 'hr.payroll.view', 'hr.attendance.view']))
                <li class="menu-title"><span>HR & Payroll</span></li>
                <li><ul>
                    @can('hr.employees.view')
                    <li class="{{ request()->routeIs('admin.hr.employees.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.hr.employees.index') }}"><i class="ti ti-id-badge-2"></i><span>Employees</span></a>
                    </li>
                    @endcan
                    @can('hr.attendance.view')
                    <li class="{{ request()->routeIs('admin.hr.attendance.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.hr.attendance.index') }}"><i class="ti ti-clock-record"></i><span>Attendance</span></a>
                    </li>
                    @endcan
                    @can('hr.leave.view')
                    <li class="{{ request()->routeIs('admin.hr.leave.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.hr.leave.index') }}"><i class="ti ti-calendar-off"></i><span>Leave Requests</span></a>
                    </li>
                    @endcan
                    @can('hr.payroll.view')
                    <li class="{{ request()->routeIs('admin.hr.payroll.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.hr.payroll.index') }}"><i class="ti ti-report-money"></i><span>Payroll</span></a>
                    </li>
                    @endcan
                </ul></li>
                @endif

                {{-- REPORTS --}}
                @module('reports')
                @can('reports.view')
                <li class="menu-title"><span>Reports</span></li>
                <li><ul>
                    <li class="submenu">
                        <a href="javascript:void(0);" class="{{ request()->routeIs('admin.reports.income', 'admin.reports.daily-collection', 'admin.reports.nhis', 'admin.reports.claims', 'admin.reports.statement-search', 'admin.reports.patient-statement') ? 'active subdrop' : '' }}">
                            <i class="ti ti-report-money"></i><span>Financial Reports</span><span class="menu-arrow"></span>
                        </a>
                        <ul>
                            <li><a href="{{ route('admin.reports.income') }}" class="{{ request()->routeIs('admin.reports.income') ? 'active' : '' }}">Income Report</a></li>
                            <li><a href="{{ route('admin.reports.daily-collection') }}" class="{{ request()->routeIs('admin.reports.daily-collection') ? 'active' : '' }}">Daily Collection</a></li>
                            <li><a href="{{ route('admin.reports.nhis') }}" class="{{ request()->routeIs('admin.reports.nhis') ? 'active' : '' }}">NHIS Report</a></li>
                            <li><a href="{{ route('admin.reports.claims') }}" class="{{ request()->routeIs('admin.reports.claims') ? 'active' : '' }}">Claims Report</a></li>
                            <li><a href="{{ route('admin.reports.statement-search') }}" class="{{ request()->routeIs('admin.reports.statement-search', 'admin.reports.patient-statement') ? 'active' : '' }}">Patient Statement</a></li>
                        </ul>
                    </li>
                    <li class="submenu">
                        <a href="javascript:void(0);" class="{{ request()->routeIs('admin.reports.patients', 'admin.reports.visits', 'admin.reports.consultation-stats', 'admin.reports.admissions', 'admin.reports.discharges', 'admin.reports.investigation-revenue') ? 'active subdrop' : '' }}">
                            <i class="ti ti-stethoscope"></i><span>Clinical Reports</span><span class="menu-arrow"></span>
                        </a>
                        <ul>
                            <li><a href="{{ route('admin.reports.patients') }}" class="{{ request()->routeIs('admin.reports.patients') ? 'active' : '' }}">Patient Report</a></li>
                            <li><a href="{{ route('admin.reports.visits') }}" class="{{ request()->routeIs('admin.reports.visits') ? 'active' : '' }}">Visit Report</a></li>
                            <li><a href="{{ route('admin.reports.consultation-stats') }}" class="{{ request()->routeIs('admin.reports.consultation-stats') ? 'active' : '' }}">Consultation Stats</a></li>
                            <li><a href="{{ route('admin.reports.admissions') }}" class="{{ request()->routeIs('admin.reports.admissions') ? 'active' : '' }}">Admissions Report</a></li>
                            <li><a href="{{ route('admin.reports.discharges') }}" class="{{ request()->routeIs('admin.reports.discharges') ? 'active' : '' }}">Discharges Report</a></li>
                            <li><a href="{{ route('admin.reports.investigation-revenue') }}" class="{{ request()->routeIs('admin.reports.investigation-revenue') ? 'active' : '' }}">Investigation Revenue</a></li>
                        </ul>
                    </li>
                    <li class="submenu">
                        <a href="javascript:void(0);" class="{{ request()->routeIs('admin.reports.pharmacy-sales', 'admin.reports.pharmacy-sales-summary', 'admin.reports.stock-valuation', 'admin.reports.expired-stock', 'admin.reports.leave', 'admin.reports.payroll') ? 'active subdrop' : '' }}">
                            <i class="ti ti-pill"></i><span>Pharmacy & HR</span><span class="menu-arrow"></span>
                        </a>
                        <ul>
                            <li><a href="{{ route('admin.reports.pharmacy-sales') }}" class="{{ request()->routeIs('admin.reports.pharmacy-sales') ? 'active' : '' }}">Pharmacy Sales</a></li>
                            <li><a href="{{ route('admin.reports.pharmacy-sales-summary') }}" class="{{ request()->routeIs('admin.reports.pharmacy-sales-summary') ? 'active' : '' }}">Sales Summary</a></li>
                            <li><a href="{{ route('admin.reports.stock-valuation') }}" class="{{ request()->routeIs('admin.reports.stock-valuation') ? 'active' : '' }}">Stock Valuation</a></li>
                            <li><a href="{{ route('admin.reports.expired-stock') }}" class="{{ request()->routeIs('admin.reports.expired-stock') ? 'active' : '' }}">Expired Stock</a></li>
                            <li><a href="{{ route('admin.reports.leave') }}" class="{{ request()->routeIs('admin.reports.leave') ? 'active' : '' }}">Leave Report</a></li>
                            <li><a href="{{ route('admin.reports.payroll') }}" class="{{ request()->routeIs('admin.reports.payroll') ? 'active' : '' }}">Payroll Report</a></li>
                        </ul>
                    </li>
                </ul></li>
                @endcan
                @endmodule

                {{-- ADMINISTRATION --}}
                @if(Auth::user()->canAny(['users.view', 'departments.view', 'settings.view']))
                <li class="menu-title"><span>Administration</span></li>
                <li><ul>
                    @can('users.view')
                    <li class="submenu">
                        <a href="javascript:void(0);" class="{{ request()->routeIs('admin.users.*') ? 'active subdrop' : '' }}">
                            <i class="ti ti-users-group"></i><span>Users</span><span class="menu-arrow"></span>
                        </a>
                        <ul>
                            <li><a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.index') ? 'active' : '' }}">All Users</a></li>
                            <li><a href="{{ route('admin.users.create') }}" class="{{ request()->routeIs('admin.users.create') ? 'active' : '' }}">Add User</a></li>
                        </ul>
                    </li>
                    <li class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.roles.index') }}"><i class="ti ti-shield-lock"></i><span>Roles & Permissions</span></a>
                    </li>
                    @endcan
                    @can('departments.view')
                    <li class="{{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.departments.index') }}"><i class="ti ti-building-bank"></i><span>Departments</span></a>
                    </li>
                    <li class="{{ request()->routeIs('admin.designations.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.designations.index') }}"><i class="ti ti-user-cog"></i><span>Designations</span></a>
                    </li>
                    @endcan
                </ul></li>
                @endif

                {{-- NOTIFICATIONS --}}
                @can('notifications.view')
                @php
                    /** @var \App\Models\User $sidebarUser */
                    $sidebarUser = \Illuminate\Support\Facades\Auth::user();
                    $unreadCount = $sidebarUser->unreadNotifications()->count();
                @endphp
                <li class="{{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.notifications.index') }}">
                        <i class="ti ti-bell"></i><span>Notifications</span>
                        @if($unreadCount > 0)
                            <span class="badge bg-danger rounded-pill ms-auto">{{ $unreadCount }}</span>
                        @endif
                    </a>
                </li>
                @endcan

                {{-- SETTINGS --}}
                @canany(['settings.manage', 'modules.manage'])
                <li class="menu-title"><span>Settings</span></li>
                <li class="submenu">
                    <a href="javascript:void(0);" class="{{ request()->routeIs('admin.settings.*') ? 'active subdrop' : '' }}">
                        <i class="ti ti-settings"></i><span>Settings</span><span class="menu-arrow"></span>
                    </a>
                    <ul>
                        @can('settings.manage')
                        <li class="{{ request()->routeIs('admin.settings.organization') ? 'active' : '' }}">
                            <a href="{{ route('admin.settings.organization') }}"><i class="ti ti-building"></i><span>Organization</span></a>
                        </li>
                        <li class="{{ request()->routeIs('admin.settings.invoice') ? 'active' : '' }}">
                            <a href="{{ route('admin.settings.invoice') }}"><i class="ti ti-file-invoice"></i><span>Invoice Settings</span></a>
                        </li>
                        <li class="{{ request()->routeIs('admin.settings.payment-methods') ? 'active' : '' }}">
                            <a href="{{ route('admin.settings.payment-methods') }}"><i class="ti ti-credit-card"></i><span>Payment Methods</span></a>
                        </li>
                        <li class="{{ request()->routeIs('admin.settings.activity-log') ? 'active' : '' }}">
                            <a href="{{ route('admin.settings.activity-log') }}"><i class="ti ti-history"></i><span>Activity Log</span></a>
                        </li>
                        @endcan
                        @can('modules.manage')
                        <li class="{{ request()->routeIs('admin.modules.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.modules.index') }}"><i class="ti ti-puzzle"></i><span>Modules</span></a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endcanany

            </ul>
        </div>
    </div>
    <!-- /Sidenav Menu -->

</div>
<!-- Sidenav Menu End -->
