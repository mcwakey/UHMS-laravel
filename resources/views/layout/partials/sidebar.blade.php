@if (!Route::is(['doctor-dashboard', 'doctors-appointments', 'doctors-appointment-details', 'doctors-patient-details', 'online-consultations', 'doctors-schedules', 'doctors-prescriptions', 'doctors-prescription-details', 'doctors-leaves', 'doctors-reviews', 'doctors-profile-settings', 'doctors-password-settings', 'doctors-notification-settings', 'doctors-notifications', 'patient-dashboard', 'patient-appointments', 'patient-appointment-details', 'patients-doctor-details', 'patient-doctors', 'patient-prescriptions', 'patient-prescription-details', 'patient-invoices', 'patient-invoice-details', 'patient-profile-settings', 'patient-password-settings', 'patient-notifications-settings', 'patient-notifications']))
<!-- Search Modal -->
<div class="modal fade" id="searchModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-transparent">
            <div class="card shadow-none mb-0">
                <div class="px-3 py-2 d-flex flex-row align-items-center" id="search-top">
                    <i class="ti ti-search fs-22"></i>
                    <input type="search" class="form-control border-0" placeholder="Search">
                    <button type="button" class="btn p-0" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x fs-22"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Sidenav Menu Start -->
<div class="sidebar" id="sidebar">
    
    <!-- Start Logo -->
    <div class="sidebar-logo">
        <div>
            <!-- Logo Normal -->
            <a href="{{url('index')}}" class="logo logo-normal">
                <img src="{{URL::asset('build/img/logo.svg')}}" alt="Logo">
            </a>

            <!-- Logo Small -->
            <a href="{{url('index')}}" class="logo-small">
                <img src="{{URL::asset('build/img/logo-small.svg')}}" alt="Logo">
            </a>

            <!-- Logo Dark -->
            <a href="{{url('index')}}" class="dark-logo">
                <img src="{{URL::asset('build/img/logo-white.svg')}}" alt="Logo">
            </a>
        </div>
        <button class="sidenav-toggle-btn btn border-0 p-0 active" id="toggle_btn"> 
            <i class="ti ti-arrow-left"></i>
        </button>

        <!-- Sidebar Menu Close -->
        <button class="sidebar-close">
            <i class="ti ti-x align-middle"></i>
        </button>                
    </div>
    <!-- End Logo -->

    <!-- Sidenav Menu -->
    <div class="sidebar-inner" data-simplebar>                
        <div id="sidebar-menu" class="sidebar-menu">            
            @if (!Route::is(['doctor-dashboard', 'doctors-appointments', 'doctors-appointment-details', 'doctors-patient-details', 'online-consultations', 'doctors-schedules', 'doctors-prescriptions', 'doctors-prescription-details', 'doctors-leaves', 'doctors-reviews', 'doctors-profile-settings', 'doctors-password-settings', 'doctors-notification-settings', 'doctors-notifications', 'patient-dashboard', 'patient-appointments', 'patient-appointment-details', 'patients-doctor-details', 'patient-doctors', 'patient-prescriptions', 'patient-prescription-details', 'patient-invoices', 'patient-invoice-details', 'patient-profile-settings', 'patient-password-settings', 'patient-notifications-settings', 'patient-notifications']))
            <div class="sidebar-top shadow-sm p-2 rounded-1 mb-3 dropend">
                <a href="javascript:void(0);" class="drop-arrow-none" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-offset="0,22" aria-haspopup="false" aria-expanded="false">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <span class="avatar rounded-circle flex-shrink-0 p-2"><img src="{{URL::asset('./build/img/icons/trustcare.svg')}}" alt="img"></span>
                            <div class="ms-2">
                                <h6 class="fs-14 fw-semibold mb-0">Trustcare Clinic</h6>
                                <p class="fs-13 mb-0">Lasvegas</p>
                            </div>
                        </div>
                        <i class="ti ti-arrows-transfer-up"></i>                            
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-lg">
                    <div class="p-2"> 
                        <label class="dropdown-item d-flex align-items-center justify-content-between p-1">
                            <span class="d-flex align-items-center">
                                <span class="me-2"><img src="{{URL::asset('build/img/icons/clinic-01.svg')}}" alt=""></span>
                                <span class="fw-semibold text-dark">CureWell Medical Hub<small class="d-block text-muted fw-normal fs-13">Ohio</small></span>
                            </span>
                            <input class="form-check-input m-0 me-2" type="checkbox">
                        </label> 
                        <label class="dropdown-item d-flex align-items-center justify-content-between p-1">
                            <span class="d-flex align-items-center">
                                <span class="me-2"><img src="{{URL::asset('build/img/icons/clinic-02.svg')}}" alt=""></span>
                                <span class="fw-semibold text-dark">Trustcare Clinic<small class="d-block text-muted fw-normal fs-13">Lasvegas</small></span>
                            </span>
                            <input class="form-check-input m-0 me-2" type="checkbox">
                        </label> 
                        <label class="dropdown-item d-flex align-items-center justify-content-between p-1">
                            <span class="d-flex align-items-center">
                                <span class="me-2"><img src="{{URL::asset('build/img/icons/clinic-03.svg')}}" alt=""></span>
                                <span class="fw-semibold text-dark">NovaCare Medical<small class="d-block text-muted fw-normal fs-13">Washington</small></span>
                            </span>
                            <input class="form-check-input m-0 me-2" type="checkbox">
                        </label> 
                        <label class="dropdown-item d-flex align-items-center justify-content-between p-1">
                            <span class="d-flex align-items-center">
                                <span class="me-2"><img src="{{URL::asset('build/img/icons/clinic-04.svg')}}" alt=""></span>
                                <span class="fw-semibold text-dark">Greeny Medical Clinic<small class="d-block text-muted fw-normal fs-13">Illinios</small></span>
                            </span>
                            <input class="form-check-input m-0 me-2" type="checkbox">
                        </label> 
                    </div>
                </div>
            </div>

            <ul>
                <li class="menu-title"><span>Main Menu</span></li>
                <li>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('index', '/') ? 'active subdrop' : '' }}">
                                <i class="ti ti-layout-dashboard"></i><span>Dashboard</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('index')}}" class="{{ Request::is('index', '/') ? 'active' : '' }}">Admin Dashboard</a></li>
                                <li><a href="{{url('doctor-dashboard')}}">Doctor Dashboard</a></li>
                                <li><a href="{{url('patient-dashboard')}}">Patient Dashboard</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('chat', 'voice-call', 'video-call', 'outgoing-call', 'incoming-call', 'call-history', 'calendar', 'contacts', 'email', 'email-reply', 'invoice', 'add-invoices', 'edit-invoices', 'invoice-details', 'todo', 'todo-list', 'notes', 'kanban-view', 'file-manager', 'social-feed', 'search-list') ? 'active subdrop' : '' }}">
                                <i class="ti ti-apps"></i><span>Applications</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('chat')}}" class="{{ Request::is('chat') ? 'active' : '' }}">Chat</a></li>
                                <li class="submenu submenu-two">
                                    <a href="#" class="{{ Request::is('voice-call', 'video-call', 'outgoing-call', 'incoming-call', 'call-history') ? 'active subdrop' : '' }}">Calls<span class="menu-arrow inside-submenu"></span></a>
                                    <ul>
                                        <li><a href="{{url('voice-call')}}" class="{{ Request::is('voice-call') ? 'active' : '' }}">Voice Call</a></li>
                                        <li><a href="{{url('video-call')}}" class="{{ Request::is('video-call') ? 'active' : '' }}">Video Call</a></li>
                                        <li><a href="{{url('outgoing-call')}}" class="{{ Request::is('outgoing-call') ? 'active' : '' }}">Outgoing Call</a></li>
                                        <li><a href="{{url('incoming-call')}}" class="{{ Request::is('incoming-call') ? 'active' : '' }}">Incoming Call</a></li>
                                        <li><a href="{{url('call-history')}}" class="{{ Request::is('call-history') ? 'active' : '' }}">Call History</a></li>
                                    </ul>
                                </li>
                                <li><a href="{{url('calendar')}}" class="{{ Request::is('calendar') ? 'active' : '' }}">Calendar</a></li>
                                <li><a href="{{url('contacts')}}" class="{{ Request::is('contacts') ? 'active' : '' }}">Contacts</a></li>		
                                <li><a href="{{url('email')}}" class="{{ Request::is('email', 'email-reply') ? 'active' : '' }}">Email</a></li>
                                <li class="submenu submenu-two">
                                    <a href="#" class="{{ Request::is('invoice', 'add-invoices', 'edit-invoices', 'invoice-details') ? 'active subdrop' : '' }}">Invoices<span class="menu-arrow inside-submenu"></span></a>
                                    <ul>
                                        <li><a href="{{url('invoice')}}" class="{{ Request::is('invoice', 'add-invoices', 'edit-invoices') ? 'active' : '' }}">Invoices</a></li>
                                        <li><a href="{{url('invoice-details')}}" class="{{ Request::is('invoice-details') ? 'active' : '' }}">Invoice Details</a></li>
                                    </ul>
                                </li>
                                <li><a href="{{url('todo')}}" class="{{ Request::is('todo', 'todo-list') ? 'active' : '' }}">To Do</a></li>
                                <li><a href="{{url('notes')}}" class="{{ Request::is('notes') ? 'active' : '' }}">Notes</a></li>
                                <li><a href="{{url('kanban-view')}}" class="{{ Request::is('kanban-view') ? 'active' : '' }}">Kanban Board</a></li>
                                <li><a href="{{url('file-manager')}}" class="{{ Request::is('file-manager') ? 'active' : '' }}">File Manager</a></li>
                                <li><a href="{{url('social-feed')}}" class="{{ Request::is('social-feed') ? 'active' : '' }}">Social Feed</a></li>
                                <li><a href="{{url('search-list')}}" class="{{ Request::is('search-list') ? 'active' : '' }}">Search Result</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('layout-mini', 'layout-hover-view', 'layout-hidden', 'layout-full-width', 'layout-rtl', 'layout-dark') ? 'active subdrop' : '' }}">
                                <i class="ti ti-layout-sidebar"></i><span>Layouts</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('index')}}" class="{{ Request::is('index') ? 'active' : '' }}">Default</a></li>
                                <li><a href="{{url('layout-mini')}}" class="{{ Request::is('layout-mini') ? 'active' : '' }}">Mini</a></li>
                                <li><a href="{{url('layout-hover-view')}}" class="{{ Request::is('layout-hover-view') ? 'active' : '' }}">Hover View</a></li>
                                <li><a href="{{url('layout-hidden')}}" class="{{ Request::is('layout-hidden') ? 'active' : '' }}">Hidden</a></li>
                                <li><a href="{{url('layout-full-width')}}" class="{{ Request::is('layout-full-width') ? 'active' : '' }}">Full Width</a></li>
                                <li><a href="{{url('layout-rtl')}}" class="{{ Request::is('layout-rtl') ? 'active' : '' }}">RTL</a></li>
                                <li><a href="{{url('layout-dark')}}" class="{{ Request::is('layout-dark') ? 'active' : '' }}">Dark</a></li>                                
                            </ul>
                        </li>
                    </ul>
                </li>
                <li class="menu-title"><span>Clinic</span></li>
                <li>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('doctors', 'doctors-list', 'edit-doctor', 'doctor-details', 'add-doctor', 'doctor-schedule') ? 'active subdrop' : '' }}">
                                <i class="ti ti-user-plus"></i><span>Doctors</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('doctors')}}" class="{{ Request::is('doctors', 'doctors-list', 'edit-doctor') ? 'active' : '' }}">Doctors</a></li>
                                <li><a href="{{url('doctor-details')}}" class="{{ Request::is('doctor-details') ? 'active' : '' }}">Doctor Details</a></li>
                                <li><a href="{{url('add-doctor')}}" class="{{ Request::is('add-doctor') ? 'active' : '' }}">Add Doctor</a></li>
                                <li><a href="{{url('doctor-schedule')}}" class="{{ Request::is('doctor-schedule') ? 'active' : '' }}">Doctor Schedule</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('patients', 'patients-grid', 'edit-patient', 'patient-details', 'create-patient') ? 'active subdrop' : '' }}">
                                <i class="ti ti-user-heart"></i><span>Patients</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('patients')}}" class="{{ Request::is('patients', 'edit-patient', 'patients-grid') ? 'active' : '' }}">Patients</a></li>
                                <li><a href="{{url('patient-details')}}" class="{{ Request::is('patient-details') ? 'active' : '' }}">Patient Details</a></li>
                                <li><a href="{{ route('admin.patients.create') }}" class="{{ request()->routeIs('admin.patients.create') ? 'active' : '' }}">Create Patient</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('appointments', 'appointment-consultations', 'new-appointment', 'appointment-calendar') ? 'active subdrop' : '' }}">
                                <i class="ti ti-calendar-check"></i><span>Appointments</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('appointments')}}" class="{{ Request::is('appointments', 'appointment-consultations') ? 'active' : '' }}">Appointments</a></li>
                                <li><a href="{{url('new-appointment')}}" class="{{ Request::is('new-appointment') ? 'active' : '' }}">New Appointment</a></li>
                                <li><a href="{{url('appointment-calendar')}}" class="{{ Request::is('appointment-calendar') ? 'active' : '' }}">Calendar</a></li>
                            </ul>
                        </li>
                        <li class="{{ Request::is('locations') ? 'active' : '' }}">
                            <a href="{{url('locations')}}">
                                <i class="ti ti-map-pin"></i><span>Locations</span>
                            </a>
                        </li>  
                        <li class="{{ Request::is('services') ? 'active' : '' }}">
                            <a href="{{url('services')}}">
                                <i class="ti ti-user-cog"></i><span>Services</span>
                            </a>
                        </li>  
                        <li class="{{ Request::is('specializations') ? 'active' : '' }}">
                            <a href="{{url('specializations')}}">
                                <i class="ti ti-user-shield"></i><span>Specializations</span>
                            </a>
                        </li> 
                        <li class="{{ Request::is('assets') ? 'active' : '' }}">
                            <a href="{{url('assets')}}">
                                <i class="ti ti-asset"></i><span>Assets</span>
                            </a>
                        </li>  
                        <li class="{{ Request::is('activities') ? 'active' : '' }}">
                            <a href="{{url('activities')}}">
                                <i class="ti ti-activity"></i><span>Activities</span>
                            </a>
                        </li> 
                        <li class="{{ Request::is('messages') ? 'active' : '' }}">
                            <a href="{{url('messages')}}">
                                <i class="ti ti-messages"></i><span>Messages</span>
                            </a>
                        </li>                           
                    </ul>
                </li>
                <li class="menu-title"><span>HRM</span></li>
                <li>
                    <ul>
                        <li class="{{ Request::is('staffs') ? 'active' : '' }}">
                            <a href="{{url('staffs')}}">
                                <i class="ti ti-users-group"></i><span>Staffs</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('hrm-departments') ? 'active' : '' }}">
                            <a href="{{url('hrm-departments')}}">
                                <i class="ti ti-building-bank"></i><span>Departments</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('designation') ? 'active' : '' }}">
                            <a href="{{url('designation')}}">
                                <i class="ti ti-user-cog"></i><span>Designation</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('attendance') ? 'active' : '' }}">
                            <a href="{{url('attendance')}}">
                                <i class="ti ti-user-check"></i><span>Attendance</span>
                            </a>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('leaves', 'leave-type') ? 'active subdrop' : '' }}">
                                <i class="ti ti-users-minus"></i><span>Leaves</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('leaves')}}" class="{{ Request::is('leaves') ? 'active' : '' }}">Leaves</a></li>
                                <li><a href="{{url('leave-type')}}" class="{{ Request::is('leave-type') ? 'active' : '' }}">Leave Type</a></li>
                            </ul>
                        </li>
                        <li class="{{ Request::is('holidays') ? 'active' : '' }}">
                            <a href="{{url('holidays')}}">
                                <i class="ti ti-home-exclamation"></i><span>Holidays</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('payroll') ? 'active' : '' }}">
                            <a href="{{url('payroll')}}">
                                <i class="ti ti-coin"></i><span>Payroll</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="menu-title"><span>Finance & Accounts</span></li>
                <li>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('expenses', 'expense-category') ? 'active subdrop' : '' }}">
                                <i class="ti ti-credit-card"></i><span>Expenses</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('expenses')}}" class="{{ Request::is('expenses') ? 'active' : '' }}">Expenses</a></li>
                                <li><a href="{{url('expense-category')}}" class="{{ Request::is('expense-category') ? 'active' : '' }}">Expense Category</a></li>
                            </ul>
                        </li>
                        <li class="{{ Request::is('income') ? 'active' : '' }}">
                            <a href="{{url('income')}}">
                                <i class="ti ti-coins"></i><span>Income</span>
                            </a>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('invoices', 'invoices-details') ? 'active subdrop' : '' }}">
                                <i class="ti ti-file-invoice"></i><span>Invoices</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('invoices')}}" class="{{ Request::is('invoices') ? 'active' : '' }}">Invoices</a></li>
                                <li><a href="{{url('invoices-details')}}" class="{{ Request::is('invoices-details') ? 'active' : '' }}">Invoice Details</a></li>
                            </ul>
                        </li>
                        <li class="{{ Request::is('payments') ? 'active' : '' }}">
                            <a href="{{url('payments')}}">
                                <i class="ti ti-cards"></i><span>Payments</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('transactions') ? 'active' : '' }}">
                            <a href="{{url('transactions')}}">
                                <i class="ti ti-transition-right"></i><span>Transactions</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="menu-title"><span>Administration</span></li>
                <li>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('roles-and-permissions', 'permissions', 'delete-account-request') ? 'active subdrop' : '' }}">
                                <i class="ti ti-user"></i><span>Users</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('roles-and-permissions')}}" class="{{ Request::is('roles-and-permissions', 'permissions') ? 'active' : '' }}">Roles & Permissions</a></li>
                                <li><a href="{{url('delete-account-request')}}" class="{{ Request::is('delete-account-request') ? 'active' : '' }}">Delete Account Request</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('income-report', 'expense-report', 'profit-and-loss', 'appointment-report', 'patient-report') ? 'active subdrop' : '' }}">
                                <i class="ti ti-report"></i><span>Reports</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('income-report')}}" class="{{ Request::is('income-report') ? 'active' : '' }}">Income Report</a></li>
                                <li><a href="{{url('expense-report')}}" class="{{ Request::is('expense-report') ? 'active' : '' }}">Expense Report</a></li>
                                <li><a href="{{url('profit-and-loss')}}" class="{{ Request::is('profit-and-loss') ? 'active' : '' }}">Profit & Loss</a></li>
                                <li><a href="{{url('appointment-report')}}" class="{{ Request::is('appointment-report') ? 'active' : '' }}">Appointment Report</a></li>
                                <li><a href="{{url('patient-report')}}" class="{{ Request::is('patient-report') ? 'active' : '' }}">Patient Report</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <li class="menu-title"><span>Content</span></li>
                <li>
                    <ul>
                        <li class="{{ Request::is('pages', 'add-page', 'edit-page') ? 'active' : '' }}">
                            <a href="{{url('pages')}}">
                                <i class="ti ti-brand-pagekit"></i><span>Pages</span>
                            </a>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('add-blog', 'blogs', 'edit-blog', 'blog-details', 'blog-categories', 'blog-comments') ? 'active subdrop' : '' }}">
                                <i class="ti ti-brand-blogger"></i><span>Blogs</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('add-blog')}}" class="{{ Request::is('add-blog') ? 'active' : '' }}">Add Blog</a></li>
                                <li><a href="{{url('blogs')}}" class="{{ Request::is('blogs', 'edit-blog', 'blog-details') ? 'active' : '' }}">Blogs</a></li>
                                <li><a href="{{url('blog-categories')}}" class="{{ Request::is('blog-categories') ? 'active' : '' }}">Blog Categories</a></li>
                                <li><a href="{{url('blog-comments')}}" class="{{ Request::is('blog-comments') ? 'active' : '' }}">Blog Comments</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('countries', 'states', 'cities') ? 'active subdrop' : '' }}">
                                <i class="ti ti-map-pins"></i><span>Location</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('countries')}}" class="{{ Request::is('countries') ? 'active' : '' }}">Countries</a></li>
                                <li><a href="{{url('states')}}" class="{{ Request::is('states') ? 'active' : '' }}">States</a></li>
                                <li><a href="{{url('cities')}}" class="{{ Request::is('cities') ? 'active' : '' }}">Cities</a></li>
                            </ul>
                        </li>
                        <li class="{{ Request::is('testimonials') ? 'active' : '' }}">
                            <a href="{{url('testimonials')}}">
                                <i class="ti ti-brand-wechat"></i><span>Testimonials</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('faq') ? 'active' : '' }}">
                            <a href="{{url('faq')}}">
                                <i class="ti ti-question-mark"></i><span>FAQ</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="menu-title"><span>Support</span></li>
                <li>
                    <ul>
                        <li class="{{ Request::is('contact-messages') ? 'active' : '' }}">
                            <a href="{{url('contact-messages')}}">
                                <i class="ti ti-message-dots"></i><span>Contact Messages</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('tickets', 'ticket-details') ? 'active' : '' }}">
                            <a href="{{url('tickets')}}">
                                <i class="ti ti-ticket"></i><span>Tickets</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('announcements') ? 'active' : '' }}">
                            <a href="{{url('announcements')}}">
                                <i class="ti ti-speakerphone"></i><span>Announcements</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('newsletters') ? 'active' : '' }}">
                            <a href="{{url('newsletters')}}">
                                <i class="ti ti-mail-bolt"></i><span>Newsletters</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="menu-title"><span>Pages</span></li>
                <li>
                    <ul>
                        <li class="{{ Request::is('starter') ? 'active' : '' }}">
                            <a href="{{url('starter')}}">
                                <i class="ti ti-player-play"></i><span>Starter</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('profile') ? 'active' : '' }}">
                            <a href="{{url('profile')}}">
                                <i class="ti ti-user-circle"></i><span>Profile</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('gallery') ? 'active' : '' }}">
                            <a href="{{url('gallery')}}">
                                <i class="ti ti-photo"></i><span>Gallery</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('timeline') ? 'active' : '' }}">
                            <a href="{{url('timeline')}}">
                                <i class="ti ti-timeline-event-text"></i><span>Timeline</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('pricing') ? 'active' : '' }}">
                            <a href="{{url('pricing')}}">
                                <i class="ti ti-tag"></i><span>Pricing</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{url('coming-soon')}}">
                                <i class="ti ti-sparkles"></i><span>Coming Soon</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{url('under-maintenance')}}">
                                <i class="ti ti-settings-down"></i><span>Under Maintenance</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('privacy-policy') ? 'active' : '' }}">
                            <a href="{{url('privacy-policy')}}">
                                <i class="ti ti-shield-check"></i><span>Privacy Policy</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('terms-and-conditions') ? 'active' : '' }}">
                            <a href="{{url('terms-and-conditions')}}">
                                <i class="ti ti-file-time"></i><span>Terms & Conditions</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="menu-title"><span>Authentication</span></li>
                <li>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);">
                                <i class="ti ti-login"></i><span>Login</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('login-cover')}}">Cover</a></li>
                                <li><a href="{{url('login-illustration')}}">Illustration</a></li>
                                <li><a href="{{url('login-basic')}}">Basic</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);">
                                <i class="ti ti-file-pencil"></i><span>Register</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('register-cover')}}">Cover</a></li>
                                <li><a href="{{url('register-illustration')}}">Illustration</a></li>
                                <li><a href="{{url('register-basic')}}">Basic</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);">
                                <i class="ti ti-lock-exclamation"></i><span>Forgot Password</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('forgot-password-cover')}}">Cover</a></li>
                                <li><a href="{{url('forgot-password-illustration')}}">Illustration</a></li>
                                <li><a href="{{url('forgot-password-basic')}}">Basic</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);">
                                <i class="ti ti-restore"></i><span>Reset Password</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('reset-password-cover')}}">Cover</a></li>
                                <li><a href="{{url('reset-password-illustration')}}">Illustration</a></li>
                                <li><a href="{{url('reset-password-basic')}}">Basic</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);">
                                <i class="ti ti-mail-check"></i><span>Email Verification</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('email-verification-cover')}}">Cover</a></li>
                                <li><a href="{{url('email-verification-illustration')}}">Illustration</a></li>
                                <li><a href="{{url('email-verification-basic')}}">Basic</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);">
                                <i class="ti ti-discount-check"></i><span>2 Step Verification</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('two-step-verification-cover')}}">Cover</a></li>
                                <li><a href="{{url('two-step-verification-illustration')}}">Illustration</a></li>
                                <li><a href="{{url('two-step-verification-basic')}}">Basic</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="{{url('lock-screen')}}">
                                <i class="ti ti-lock"></i><span>Lock Screen</span>
                            </a>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);">
                                <i class="ti ti-exclamation-mark-off"></i><span>Error Pages</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('error-404')}}">404 Error</a></li>
                                <li><a href="{{url('error-500')}}">500 Error</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <li class="menu-title"><span>Settings</span></li>
                <li>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('profile-settings', 'security-settings', 'notifications-settings', 'integrations-settings') ? 'active subdrop' : '' }}">
                                <i class="ti ti-user-cog"></i><span>Account Settings</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('profile-settings')}}" class="{{ Request::is('profile-settings') ? 'active' : '' }}">Profile</a></li>
                                <li><a href="{{url('security-settings')}}" class="{{ Request::is('security-settings') ? 'active' : '' }}">Security</a></li>
                                <li><a href="{{url('notifications-settings')}}" class="{{ Request::is('notifications-settings') ? 'active' : '' }}">Notifications</a></li>
                                <li><a href="{{url('integrations-settings')}}" class="{{ Request::is('integrations-settings') ? 'active' : '' }}">Integrations</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('organization-settings', 'localization-settings', 'prefixes-settings', 'seo-setup-settings', 'language-settings', 'language-settings2','language-settings3', 'maintenance-mode-settings', 'login-and-register-settings', 'preferences-settings') ? 'active subdrop' : '' }}">
                                <i class="ti ti-world-cog"></i><span>Website Settings</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('organization-settings')}}" class="{{ Request::is('organization-settings') ? 'active' : '' }}">Organization</a></li>
                                <li><a href="{{url('localization-settings')}}" class="{{ Request::is('localization-settings') ? 'active' : '' }}">Localization</a></li>
                                <li><a href="{{url('prefixes-settings')}}" class="{{ Request::is('prefixes-settings') ? 'active' : '' }}">Prefixes</a></li>
                                <li><a href="{{url('seo-setup-settings')}}" class="{{ Request::is('seo-setup-settings') ? 'active' : '' }}">SEO Setup</a></li>
                                <li><a href="{{url('language-settings')}}" class="{{ Request::is('language-settings','language-settings2','language-settings3') ? 'active' : '' }}">Language</a></li>
                                <li><a href="{{url('maintenance-mode-settings')}}" class="{{ Request::is('maintenance-mode-settings') ? 'active' : '' }}">Maintenance Mode</a></li>
                                <li><a href="{{url('login-and-register-settings')}}" class="{{ Request::is('login-and-register-settings') ? 'active' : '' }}">Login & Register</a></li>
                                <li><a href="{{url('preferences-settings')}}" class="{{ Request::is('preferences-settings') ? 'active' : '' }}">Preferences</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('appointment-settings', 'working-hours-settings', 'cancellation-reason-settings') ? 'active subdrop' : '' }}">
                                <i class="ti ti-building-hospital"></i><span>Clinic Settings</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('appointment-settings')}}" class="{{ Request::is('appointment-settings') ? 'active' : '' }}">Appointment</a></li>
                                <li><a href="{{url('working-hours-settings')}}" class="{{ Request::is('working-hours-settings') ? 'active' : '' }}">Working Hours</a></li>
                                <li><a href="{{url('cancellation-reason-settings')}}" class="{{ Request::is('cancellation-reason-settings') ? 'active' : '' }}">Cancellation Reason</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('invoice-settings', 'invoice-templates-settings', 'signatures-settings', 'custom-fields-settings') ? 'active subdrop' : '' }}">
                                <i class="ti ti-device-mobile-cog"></i><span>App Settings</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('invoice-settings')}}" class="{{ Request::is('invoice-settings') ? 'active' : '' }}">Invoice Settings</a></li>
                                <li><a href="{{url('invoice-templates-settings')}}" class="{{ Request::is('invoice-templates-settings') ? 'active' : '' }}">Invoice Templates</a></li>
                                <li><a href="{{url('signatures-settings')}}" class="{{ Request::is('signatures-settings') ? 'active' : '' }}">Signatures</a></li>
                                <li><a href="{{url('custom-fields-settings')}}" class="{{ Request::is('custom-fields-settings') ? 'active' : '' }}">Custom Fields</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('email-settings', 'email-templates-settings', 'sms-gateways-settings', 'sms-templates-settings', 'gdpr-cookies-settings') ? 'active subdrop' : '' }}">
                                <i class="ti ti-device-desktop-cog"></i><span>System Settings</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('email-settings')}}" class="{{ Request::is('email-settings') ? 'active' : '' }}">Email Settings</a></li>
                                <li><a href="{{url('email-templates-settings')}}" class="{{ Request::is('email-templates-settings') ? 'active' : '' }}">Email Templates</a></li>
                                <li><a href="{{url('sms-gateways-settings')}}" class="{{ Request::is('sms-gateways-settings') ? 'active' : '' }}">SMS Gateways</a></li>
                                <li><a href="{{url('sms-templates-settings')}}" class="{{ Request::is('sms-templates-settings') ? 'active' : '' }}">SMS Templates</a></li>
                                <li><a href="{{url('gdpr-cookies-settings')}}" class="{{ Request::is('gdpr-cookies-settings') ? 'active' : '' }}">GDPR Cookies</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('payment-methods-settings', 'bank-accounts-settings', 'tax-rates-settings', 'currencies-settings') ? 'active subdrop' : '' }}">
                                <i class="ti ti-settings-dollar"></i><span>Finance & Accounts</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('payment-methods-settings')}}" class="{{ Request::is('payment-methods-settings') ? 'active' : '' }}">Payment Methods</a></li>
                                <li><a href="{{url('bank-accounts-settings')}}" class="{{ Request::is('bank-accounts-settings') ? 'active' : '' }}">Bank Accounts</a></li>
                                <li><a href="{{url('tax-rates-settings')}}" class="{{ Request::is('tax-rates-settings') ? 'active' : '' }}">Tax Rates</a></li>
                                <li><a href="{{url('currencies-settings')}}" class="{{ Request::is('currencies-settings') ? 'active' : '' }}">Currencies</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('sitemap-settings', 'clear-cache-settings', 'storage-settings', 'cronjob-settings', 'ban-ip-address-settings', 'system-backup-settings', 'database-backup-settings', 'system-update') ? 'active subdrop' : '' }}">
                                <i class="ti ti-settings-2"></i><span>Other Settings</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('sitemap-settings')}}" class="{{ Request::is('sitemap-settings') ? 'active' : '' }}">Sitemap</a></li>
                                <li><a href="{{url('clear-cache-settings')}}" class="{{ Request::is('clear-cache-settings') ? 'active' : '' }}">Clear Cache</a></li>
                                <li><a href="{{url('storage-settings')}}" class="{{ Request::is('storage-settings') ? 'active' : '' }}">Storage</a></li>
                                <li><a href="{{url('cronjob-settings')}}" class="{{ Request::is('cronjob-settings') ? 'active' : '' }}">Cronjob</a></li>
                                <li><a href="{{url('ban-ip-address-settings')}}" class="{{ Request::is('ban-ip-address-settings') ? 'active' : '' }}">Ban IP Address</a></li>
                                <li><a href="{{url('system-backup-settings')}}" class="{{ Request::is('system-backup-settings') ? 'active' : '' }}">System Backup</a></li>
                                <li><a href="{{url('database-backup-settings')}}" class="{{ Request::is('database-backup-settings') ? 'active' : '' }}">Database Backup</a></li>
                                <li><a href="{{url('system-update')}}" class="{{ Request::is('system-update') ? 'active' : '' }}">System Update</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <li class="menu-title"><span>UI Interface</span></li>
                <li>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('ui-accordion', 'ui-alerts', 'ui-avatar', 'ui-badges', 'ui-breadcrumb', 'ui-buttons', 'ui-buttons-group', 'ui-cards', 'ui-carousel', 'ui-collapse', 'ui-dropdowns', 'ui-ratio', 'ui-grid', 'ui-images', 'ui-links', 'ui-list-group', 'ui-modals', 'ui-offcanvas', 'ui-pagination', 'ui-placeholders', 'ui-popovers', 'ui-progress', 'ui-scrollspy', 'ui-spinner', 'ui-nav-tabs', 'ui-toasts', 'ui-tooltips', 'ui-typography', 'ui-utilities') ? 'active subdrop' : '' }}">
                                <i class="ti ti-chart-pie"></i><span>Base UI</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('ui-accordion')}}" class="{{ Request::is('ui-accordion') ? 'active' : '' }}">Accordion</a></li>
                                <li><a href="{{url('ui-alerts')}}" class="{{ Request::is('ui-alerts') ? 'active' : '' }}">Alerts</a></li>
                                <li><a href="{{url('ui-avatar')}}" class="{{ Request::is('ui-avatar') ? 'active' : '' }}">Avatar</a></li>
                                <li><a href="{{url('ui-badges')}}" class="{{ Request::is('ui-badges') ? 'active' : '' }}">Badges</a></li>
                                <li><a href="{{url('ui-breadcrumb')}}" class="{{ Request::is('ui-breadcrumb') ? 'active' : '' }}">Breadcrumb</a></li>
                                <li><a href="{{url('ui-buttons')}}" class="{{ Request::is('ui-buttons') ? 'active' : '' }}">Buttons</a></li>
                                <li><a href="{{url('ui-buttons-group')}}" class="{{ Request::is('ui-buttons-group') ? 'active' : '' }}">Button Group</a></li>
                                <li><a href="{{url('ui-cards')}}" class="{{ Request::is('ui-cards') ? 'active' : '' }}">Card</a></li>
                                <li><a href="{{url('ui-carousel')}}" class="{{ Request::is('ui-carousel') ? 'active' : '' }}">Carousel</a></li>
                                <li><a href="{{url('ui-collapse')}}" class="{{ Request::is('ui-collapse') ? 'active' : '' }}">Collapse</a></li>
                                <li><a href="{{url('ui-dropdowns')}}" class="{{ Request::is('ui-dropdowns') ? 'active' : '' }}">Dropdowns</a></li>
                                <li><a href="{{url('ui-ratio')}}" class="{{ Request::is('ui-ratio') ? 'active' : '' }}">Ratio</a></li>
                                <li><a href="{{url('ui-grid')}}" class="{{ Request::is('ui-grid') ? 'active' : '' }}">Grid</a></li>
                                <li><a href="{{url('ui-images')}}" class="{{ Request::is('ui-images') ? 'active' : '' }}">Images</a></li>
                                <li><a href="{{url('ui-links')}}" class="{{ Request::is('ui-links') ? 'active' : '' }}">Links</a></li>
                                <li><a href="{{url('ui-list-group')}}" class="{{ Request::is('ui-list-group') ? 'active' : '' }}">List Group</a></li>
                                <li><a href="{{url('ui-modals')}}" class="{{ Request::is('ui-modals') ? 'active' : '' }}">Modals</a></li>
                                <li><a href="{{url('ui-offcanvas')}}" class="{{ Request::is('ui-offcanvas') ? 'active' : '' }}">Offcanvas</a></li>
                                <li><a href="{{url('ui-pagination')}}" class="{{ Request::is('ui-pagination') ? 'active' : '' }}">Pagination</a></li>
                                <li><a href="{{url('ui-placeholders')}}" class="{{ Request::is('ui-placeholders') ? 'active' : '' }}">Placeholders</a></li>
                                <li><a href="{{url('ui-popovers')}}" class="{{ Request::is('ui-popovers') ? 'active' : '' }}">Popovers</a></li>
                                <li><a href="{{url('ui-progress')}}" class="{{ Request::is('ui-progress') ? 'active' : '' }}">Progress</a></li>
                                <li><a href="{{url('ui-scrollspy')}}" class="{{ Request::is('ui-scrollspy') ? 'active' : '' }}">Scrollspy</a></li>
                                <li><a href="{{url('ui-spinner')}}" class="{{ Request::is('ui-spinner') ? 'active' : '' }}">Spinner</a></li>
                                <li><a href="{{url('ui-nav-tabs')}}" class="{{ Request::is('ui-nav-tabs') ? 'active' : '' }}">Tabs</a></li>
                                <li><a href="{{url('ui-toasts')}}" class="{{ Request::is('ui-toasts') ? 'active' : '' }}">Toasts</a></li>
                                <li><a href="{{url('ui-tooltips')}}" class="{{ Request::is('ui-tooltips') ? 'active' : '' }}">Tooltips</a></li>
                                <li><a href="{{url('ui-typography')}}" class="{{ Request::is('ui-typography') ? 'active' : '' }}">Typography</a></li>
                                <li><a href="{{url('ui-utilities')}}" class="{{ Request::is('ui-utilities') ? 'active' : '' }}">Utilities</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('extended-dragula', 'ui-clipboard', 'ui-rangeslider', 'ui-sweetalerts', 'ui-lightbox', 'ui-scrollbar') ? 'active subdrop' : '' }}">
                                <i class="ti ti-radar"></i><span>Advanced UI</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('extended-dragula')}}" class="{{ Request::is('extended-dragula') ? 'active' : '' }}">Dragula</a></li>
                                <li><a href="{{url('ui-clipboard')}}" class="{{ Request::is('ui-clipboard') ? 'active' : '' }}">Clipboard</a></li>
                                <li><a href="{{url('ui-rangeslider')}}" class="{{ Request::is('ui-rangeslider') ? 'active' : '' }}">Range Slider</a></li>
                                <li><a href="{{url('ui-sweetalerts')}}" class="{{ Request::is('ui-sweetalerts') ? 'active' : '' }}">Sweet Alerts</a></li>
                                <li><a href="{{url('ui-lightbox')}}" class="{{ Request::is('ui-lightbox') ? 'active' : '' }}">Lightbox</a></li>
                                <li><a href="{{url('ui-scrollbar')}}" class="{{ Request::is('ui-scrollbar') ? 'active' : '' }}">Scrollbar</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('form-basic-inputs', 'form-checkbox-radios', 'form-input-groups', 'form-grid-gutters', 'form-mask', 'form-fileupload', 'form-horizontal', 'form-vertical', 'form-floating-labels', 'form-validation', 'form-select2', 'form-wizard', 'form-pickers') ? 'active subdrop' : '' }}">
                                <i class="ti ti-forms"></i><span>Forms</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li class="submenu submenu-two">
                                    <a href="javascript:void(0);" class="{{ Request::is('form-basic-inputs', 'form-checkbox-radios', 'form-input-groups', 'form-grid-gutters', 'form-mask', 'form-fileupload') ? 'active subdrop' : '' }}">Form Elements<span class="menu-arrow inside-submenu"></span></a>
                                    <ul>
                                        <li><a href="{{url('form-basic-inputs')}}" class="{{ Request::is('form-basic-inputs') ? 'active' : '' }}">Basic Inputs</a></li>
                                        <li><a href="{{url('form-checkbox-radios')}}" class="{{ Request::is('form-checkbox-radios') ? 'active' : '' }}">Checkbox & Radios</a></li>
                                        <li><a href="{{url('form-input-groups')}}" class="{{ Request::is('form-input-groups') ? 'active' : '' }}">Input Groups</a></li>
                                        <li><a href="{{url('form-grid-gutters')}}" class="{{ Request::is('form-grid-gutters') ? 'active' : '' }}">Grid & Gutters</a></li>
                                        <li><a href="{{url('form-mask')}}" class="{{ Request::is('form-mask') ? 'active' : '' }}">Input Masks</a></li>
                                        <li><a href="{{url('form-fileupload')}}" class="{{ Request::is('form-fileupload') ? 'active' : '' }}">File Uploads</a></li>
                                    </ul>
                                </li>
                                <li class="submenu submenu-two">
                                    <a href="javascript:void(0);" class="{{ Request::is('form-horizontal', 'form-vertical', 'form-floating-labels') ? 'active subdrop' : '' }}">Layouts<span class="menu-arrow inside-submenu"></span></a>
                                    <ul>
                                        <li><a href="{{url('form-horizontal')}}" class="{{ Request::is('form-horizontal') ? 'active' : '' }}">Horizontal Form</a></li>
                                        <li><a href="{{url('form-vertical')}}" class="{{ Request::is('form-vertical') ? 'active' : '' }}">Vertical Form</a></li>
                                        <li><a href="{{url('form-floating-labels')}}" class="{{ Request::is('form-floating-labels') ? 'active' : '' }}">Floating Labels</a></li>
                                    </ul>
                                </li>
                                <li><a href="{{url('form-validation')}}" class="{{ Request::is('form-validation') ? 'active' : '' }}">Form Validation</a></li>
                                <li><a href="{{url('form-select2')}}" class="{{ Request::is('form-select2') ? 'active' : '' }}">Select2</a></li>
                                <li><a href="{{url('form-wizard')}}" class="{{ Request::is('form-wizard') ? 'active' : '' }}">Form Wizard</a></li>
                                <li><a href="{{url('form-pickers')}}" class="{{ Request::is('form-pickers') ? 'active' : '' }}">Form Picker</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('tables-basic', 'data-tables') ? 'active subdrop' : '' }}">
                                <i class="ti ti-table-row"></i><span>Tables</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('tables-basic')}}" class="{{ Request::is('tables-basic') ? 'active' : '' }}">Basic Tables </a></li>
                                <li><a href="{{url('data-tables')}}" class="{{ Request::is('data-tables') ? 'active' : '' }}">Data Table </a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('chart-apex', 'chart-c3', 'chart-js', 'chart-morris', 'chart-flot', 'chart-peity') ? 'active subdrop' : '' }}">
                                <i class="ti ti-chart-donut"></i>
                                <span>Charts</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('chart-apex')}}" class="{{ Request::is('chart-apex') ? 'active' : '' }}">Apex Charts</a></li>
                                <li><a href="{{url('chart-c3')}}" class="{{ Request::is('chart-c3') ? 'active' : '' }}">Chart C3</a></li>
                                <li><a href="{{url('chart-js')}}" class="{{ Request::is('chart-js') ? 'active' : '' }}">Chart Js</a></li>
                                <li><a href="{{url('chart-morris')}}" class="{{ Request::is('chart-morris') ? 'active' : '' }}">Morris Charts</a></li>
                                <li><a href="{{url('chart-flot')}}" class="{{ Request::is('chart-flot') ? 'active' : '' }}">Flot Charts</a></li>
                                <li><a href="{{url('chart-peity')}}" class="{{ Request::is('chart-peity') ? 'active' : '' }}">Peity Charts</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('icon-fontawesome', 'icon-tabler', 'icon-bootstrap', 'icon-remix', 'icon-feather', 'icon-ionic', 'icon-material', 'icon-pe7', 'icon-simpleline', 'icon-themify', 'icon-weather', 'icon-typicons', 'icon-flag') ? 'active subdrop' : '' }}">
                                <i class="ti ti-icons"></i>
                                <span>Icons</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('icon-fontawesome')}}" class="{{ Request::is('icon-fontawesome') ? 'active' : '' }}">Fontawesome Icons</a></li>
                                <li><a href="{{url('icon-tabler')}}" class="{{ Request::is('icon-tabler') ? 'active' : '' }}">Tabler Icons</a></li>
                                <li><a href="{{url('icon-bootstrap')}}" class="{{ Request::is('icon-bootstrap') ? 'active' : '' }}">Bootstrap Icons</a></li>
                                <li><a href="{{url('icon-remix')}}" class="{{ Request::is('icon-remix') ? 'active' : '' }}">Remix Icons</a></li>
                                <li><a href="{{url('icon-feather')}}" class="{{ Request::is('icon-feather') ? 'active' : '' }}">Feather Icons</a></li>
                                <li><a href="{{url('icon-ionic')}}" class="{{ Request::is('icon-ionic') ? 'active' : '' }}">Ionic Icons</a></li>
                                <li><a href="{{url('icon-material')}}" class="{{ Request::is('icon-material') ? 'active' : '' }}">Material Icons</a></li>
                                <li><a href="{{url('icon-pe7')}}" class="{{ Request::is('icon-pe7') ? 'active' : '' }}">Pe7 Icons</a></li>
                                <li><a href="{{url('icon-simpleline')}}" class="{{ Request::is('icon-simpleline') ? 'active' : '' }}">Simpleline Icons</a></li>
                                <li><a href="{{url('icon-themify')}}" class="{{ Request::is('icon-themify') ? 'active' : '' }}">Themify Icons</a></li>
                                <li><a href="{{url('icon-weather')}}" class="{{ Request::is('icon-weather') ? 'active' : '' }}">Weather Icons</a></li>
                                <li><a href="{{url('icon-typicons')}}" class="{{ Request::is('icon-typicons') ? 'active' : '' }}">Typicons Icons</a></li>
                                <li><a href="{{url('icon-flag')}}" class="{{ Request::is('icon-flag') ? 'active' : '' }}">Flag Icons</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <li class="menu-title"><span>Help</span></li>
                <li>
                    <ul>
                        <li>
                            <a href="https://preclinic.dreamstechnologies.com/documentation/laravel.html" target="_blank"><i class="ti ti-file-dots"></i><span>Documentation</span></a>
                        </li>
                        <li>
                            <a href="https://preclinic.dreamstechnologies.com/documentation/changelog.html" target="_blank"><i class="ti ti-status-change"></i><span>Changelog</span></a>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);">
                                <i class="ti ti-versions"></i><span>Multi Level</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="javascript:void(0);">Multilevel 1</a></li>
                                <li class="submenu submenu-two">
                                    <a href="javascript:void(0);">Multilevel 2<span
                                            class="menu-arrow inside-submenu"></span></a>
                                    <ul>
                                        <li><a href="javascript:void(0);">Multilevel 2.1</a></li>
                                        <li class="submenu submenu-two submenu-three">
                                            <a href="javascript:void(0);">Multilevel 2.2<span
                                                    class="menu-arrow inside-submenu inside-submenu-two"></span></a>
                                            <ul>
                                                <li><a href="javascript:void(0);">Multilevel 2.2.1</a></li>
                                                <li><a href="javascript:void(0);">Multilevel 2.2.2</a></li>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                                <li><a href="javascript:void(0);">Multilevel 3</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>                        
            </ul>  
            @endif   
            
            @if (Route::is(['doctor-dashboard', 'doctors-appointments', 'doctors-appointment-details', 'doctors-patient-details', 'online-consultations', 'doctors-schedules', 'doctors-prescriptions', 'doctors-prescription-details', 'doctors-leaves', 'doctors-reviews', 'doctors-profile-settings', 'doctors-password-settings', 'doctors-notification-settings', 'doctors-notifications']))
            <ul>
                <li class="menu-title"><span>Main Menu</span></li>
                <li>
                    <ul>
                        <li class="{{ Request::is('doctor-dashboard', 'doctors-notifications') ? 'active' : '' }}">
                            <a href="{{url('doctor-dashboard')}}">
                                <i class="ti ti-layout-dashboard"></i><span>Dashboard</span>
                            </a>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('doctors-appointments', 'doctors-appointment-details', 'doctors-patient-details', 'online-consultations') ? 'active subdrop' : '' }}">
                                <i class="ti ti-calendar-check"></i><span>Appointments</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('doctors-appointments')}}" class="{{ Request::is('doctors-appointments', 'doctors-appointment-details', 'doctors-patient-details') ? 'active' : '' }}">Appointments</a></li>
                                <li><a href="{{url('online-consultations')}}" class="{{ Request::is('online-consultations') ? 'active' : '' }}">Online Consultations</a></li>
                            </ul>
                        </li>
                        <li class="{{ Request::is('doctors-schedules') ? 'active' : '' }}">
                            <a href="{{url('doctors-schedules')}}">
                                <i class="ti ti-clock-check"></i><span>My Schedule</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('doctors-prescriptions', 'doctors-prescription-details') ? 'active' : '' }}">
                            <a href="{{url('doctors-prescriptions')}}">
                                <i class="ti ti-prescription"></i><span>Prescriptions</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('doctors-leaves') ? 'active' : '' }}">
                            <a href="{{url('doctors-leaves')}}">
                                <i class="ti ti-calendar-x"></i><span>Leave</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('doctors-reviews') ? 'active' : '' }}">
                            <a href="{{url('doctors-reviews')}}">
                                <i class="ti ti-star"></i><span>Reviews</span>
                            </a>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('doctors-profile-settings', 'doctors-password-settings', 'doctors-notification-settings') ? 'active subdrop' : '' }}">
                                <i class="ti ti-settings"></i><span>Settings</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('doctors-profile-settings')}}" class="{{ Request::is('doctors-profile-settings') ? 'active' : '' }}">Profile Settings</a></li>
                                <li><a href="{{url('doctors-password-settings')}}" class="{{ Request::is('doctors-password-settings') ? 'active' : '' }}">Change Password</a></li>
                                <li><a href="{{url('doctors-notification-settings')}}" class="{{ Request::is('doctors-notification-settings') ? 'active' : '' }}">Notifications</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>                                
            </ul>  
            @endif

            @if (Route::is(['patient-dashboard', 'patient-appointments', 'patient-appointment-details', 'patients-doctor-details', 'patient-doctors', 'patient-prescriptions', 'patient-prescription-details', 'patient-invoices', 'patient-invoice-details', 'patient-profile-settings', 'patient-password-settings', 'patient-notifications-settings', 'patient-notifications']))
            <ul>
                <li class="menu-title"><span>Main Menu</span></li>
                <li>
                    <ul>
                        <li class="{{ Request::is('patient-dashboard', 'patient-notifications') ? 'active' : '' }}">
                            <a href="{{url('patient-dashboard')}}">
                                <i class="ti ti-layout-dashboard"></i><span>Dashboard</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('patient-appointments', 'patient-appointment-details') ? 'active' : '' }}">
                            <a href="{{url('patient-appointments')}}">
                                <i class="ti ti-calendar-check"></i><span>Appointments</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('patient-doctors', 'patients-doctor-details') ? 'active' : '' }}">
                            <a href="{{url('patient-doctors')}}">
                                <i class="ti ti-stethoscope"></i><span>Doctors</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('patient-prescriptions', 'patient-prescription-details') ? 'active' : '' }}">
                            <a href="{{url('patient-prescriptions')}}">
                                <i class="ti ti-prescription"></i><span>Prescriptions</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('patient-invoices', 'patient-invoice-details') ? 'active' : '' }}">
                            <a href="{{url('patient-invoices')}}">
                                <i class="ti ti-star"></i><span>Invoice</span>
                            </a>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Request::is('patient-profile-settings', 'patient-password-settings', 'patient-notifications-settings') ? 'active subdrop' : '' }}">
                                <i class="ti ti-settings"></i><span>Settings</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{url('patient-profile-settings')}}" class="{{ Request::is('patient-profile-settings') ? 'active' : '' }}">Profile Settings</a></li>
                                <li><a href="{{url('patient-password-settings')}}" class="{{ Request::is('patient-password-settings') ? 'active' : '' }}">Change Password</a></li>
                                <li><a href="{{url('patient-notifications-settings')}}" class="{{ Request::is('patient-notifications-settings') ? 'active' : '' }}">Notifications</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>                                
            </ul>  
            @endif
        </div>
        <div class="sidebar-footer border-top mt-3">
            <div class="trial-item mt-0 p-3 text-center">
                <div class="trial-item-icon rounded-4 mb-3 p-2 text-center shadow-sm d-inline-flex">
                    <img src="{{URL::asset('./build/img/icons/sidebar-icon.svg')}}" alt="img">
                </div>
                <div>
                    <h6 class="fs-14 fw-semibold mb-1">Upgrade To Pro</h6>
                    <p class="fs-13 mb-0">Check 1 min video and begin use Preclinic like a pro</p>
                </div>
                <a href="javascript:void(0);" class="close-icon shadow-sm"><i class="ti ti-x"></i></a>
            </div>
        </div>
    </div>

</div>
<!-- Sidenav Menu End -->