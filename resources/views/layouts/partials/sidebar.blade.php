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

                {{-- ========================================== --}}
                {{-- DASHBOARD --}}
                {{-- ========================================== --}}
                <li class="menu-title"><span>Main Menu</span></li>
                <li>
                    <ul>
                        @if(Auth::user()->hasAnyRole(['Super Admin', 'Admin', 'Receptionist', 'Nurse', 'Lab Technician', 'Pharmacist', 'Accountant']))
                        <li class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <a href="{{ route('admin.dashboard') }}">
                                <i class="ti ti-layout-dashboard"></i><span>Dashboard</span>
                            </a>
                        </li>
                        @endif

                        @if(Auth::user()->hasRole('Doctor'))
                        <li class="{{ request()->routeIs('doctor.dashboard') ? 'active' : '' }}">
                            <a href="{{ route('doctor.dashboard') }}">
                                <i class="ti ti-layout-dashboard"></i><span>Dashboard</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>

                {{-- ========================================== --}}
                {{-- CLINIC / OPD (Future phases) --}}
                {{-- ========================================== --}}
                @can('patients.view')
                <li class="menu-title"><span>Clinic</span></li>
                <li>
                    <ul>
                        @can('patients.view')
                        <li class="{{ request()->routeIs('admin.patients.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.patients.index') }}">
                                <i class="ti ti-user-heart"></i><span>Patients</span>
                            </a>
                        </li>
                        @endcan

                        @can('visits.view')
                        <li class="{{ request()->routeIs('admin.visits.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.visits.index') }}">
                                <i class="ti ti-calendar-check"></i><span>Visits / OPD</span>
                            </a>
                        </li>
                        @endcan

                        @can('queue.view')
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('admin.queue.*') ? 'active subdrop' : '' }}">
                                <i class="ti ti-list-numbers"></i><span>Queue</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('admin.queue.manage') }}" class="{{ request()->routeIs('admin.queue.manage') ? 'active' : '' }}">Manage Queue</a></li>
                                <li><a href="{{ route('admin.queue.board') }}" class="{{ request()->routeIs('admin.queue.board') ? 'active' : '' }}">Queue Board</a></li>
                            </ul>
                        </li>
                        @endcan

                        @can('consultations.view')
                        <li class="{{ request()->routeIs('admin.consultations.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.consultations.index') }}">
                                <i class="ti ti-stethoscope"></i><span>Consultations</span>
                            </a>
                        </li>
                        @endcan

                        @can('vitals.view')
                        <li class="{{ request()->routeIs('admin.vitals.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.vitals.create') }}">
                                <i class="ti ti-heartbeat"></i><span>Vitals / Triage</span>
                            </a>
                        </li>
                        @endcan

                        @can('consultations.view')
                        <li class="{{ request()->routeIs('admin.patterns.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.patterns.index') }}">
                                <i class="ti ti-template"></i><span>Medical Patterns</span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endcan

                {{-- ========================================== --}}
                {{-- PHARMACY & LAB (Future phases) --}}
                {{-- ========================================== --}}
                @if(Auth::user()->canAny(['prescriptions.view', 'pharmacy.dispensing.view', 'pharmacy.drugs.manage', 'pharmacy.stock.manage', 'lab.requests.view', 'lab.results.view', 'lab.tests.manage']))
                <li class="menu-title"><span>Pharmacy & Lab</span></li>
                <li>
                    <ul>
                        @can('prescriptions.view')
                        <li class="{{ request()->routeIs('admin.prescriptions.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.prescriptions.index') }}">
                                <i class="ti ti-prescription"></i><span>Prescriptions</span>
                            </a>
                        </li>
                        @endcan

                        @can('pharmacy.dispensing.view')
                        <li class="{{ request()->routeIs('admin.pharmacy.dispensing.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.pharmacy.dispensing.index') }}">
                                <i class="ti ti-pill"></i><span>Dispensing</span>
                            </a>
                        </li>
                        @endcan

                        @can('pharmacy.drugs.manage')
                        <li class="{{ request()->routeIs('admin.pharmacy.drugs.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.pharmacy.drugs.index') }}">
                                <i class="ti ti-medicine-syrup"></i><span>Drug Catalog</span>
                            </a>
                        </li>
                        @endcan

                        @can('pharmacy.stock.manage')
                        <li class="{{ request()->routeIs('admin.pharmacy.stock.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.pharmacy.stock.index') }}">
                                <i class="ti ti-packages"></i><span>Drug Stock</span>
                            </a>
                        </li>
                        @endcan

                        @if(Auth::user()->canAny(['lab.requests.view', 'lab.results.view', 'lab.tests.manage']))
                        <li class="{{ request()->routeIs('admin.lab.requests.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.lab.requests.index') }}">
                                <i class="ti ti-test-pipe"></i><span>Lab Requests</span>
                            </a>
                        </li>
                        @endif

                        @can('lab.results.view')
                        <li class="{{ request()->routeIs('admin.lab.results.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.lab.results.index') }}">
                                <i class="ti ti-report-medical"></i><span>Lab Results</span>
                            </a>
                        </li>
                        @endcan

                        @can('lab.tests.manage')
                        <li class="{{ request()->routeIs('admin.lab.tests.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.lab.tests.index') }}">
                                <i class="ti ti-flask"></i><span>Lab Test Catalog</span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endif

                {{-- ========================================== --}}
                {{-- BILLING --}}
                {{-- ========================================== --}}
                @if(Auth::user()->canAny(['invoices.view', 'payments.view', 'services.manage']))
                <li class="menu-title"><span>Finance</span></li>
                <li>
                    <ul>
                        @can('invoices.view')
                        <li class="{{ request()->routeIs('admin.billing.invoices.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.billing.invoices.index') }}">
                                <i class="ti ti-file-invoice"></i><span>Invoices</span>
                            </a>
                        </li>
                        @endcan

                        @can('payments.view')
                        <li class="{{ request()->routeIs('admin.billing.payments.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.billing.payments.index') }}">
                                <i class="ti ti-cash"></i><span>Payments</span>
                            </a>
                        </li>
                        @endcan

                        @can('services.manage')
                        <li class="{{ request()->routeIs('admin.services.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.services.index') }}">
                                <i class="ti ti-list-details"></i><span>Service Catalog</span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endif

                {{-- ========================================== --}}
                {{-- ADMINISTRATION (Phase 1 — Active) --}}
                {{-- ========================================== --}}
                @if(Auth::user()->canAny(['users.view', 'departments.view', 'settings.view']))
                <li class="menu-title"><span>Administration</span></li>
                <li>
                    <ul>
                        @can('users.view')
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('admin.users.*') ? 'active subdrop' : '' }}">
                                <i class="ti ti-users-group"></i><span>Users</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.index') ? 'active' : '' }}">All Users</a></li>
                                <li><a href="{{ route('admin.users.create') }}" class="{{ request()->routeIs('admin.users.create') ? 'active' : '' }}">Add User</a></li>
                            </ul>
                        </li>
                        @endcan

                        @can('users.view')
                        <li class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.roles.index') }}">
                                <i class="ti ti-shield-lock"></i><span>Roles & Permissions</span>
                            </a>
                        </li>
                        @endcan

                        @can('departments.view')
                        <li class="{{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.departments.index') }}">
                                <i class="ti ti-building-bank"></i><span>Departments</span>
                            </a>
                        </li>
                        @endcan

                        @can('departments.view')
                        <li class="{{ request()->routeIs('admin.designations.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.designations.index') }}">
                                <i class="ti ti-user-cog"></i><span>Designations</span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endif

                {{-- ========================================== --}}
                {{-- REPORTS (Future phases) --}}
                {{-- ========================================== --}}
                @can('reports.view')
                <li class="menu-title"><span>Reports</span></li>
                <li>
                    <ul>
                        <li class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                            <a href="javascript:void(0);">
                                <i class="ti ti-report"></i><span>Reports</span>
                            </a>
                        </li>
                    </ul>
                </li>
                @endcan

            </ul>
        </div>
    </div>
    <!-- /Sidenav Menu -->

</div>
<!-- Sidenav Menu End -->
