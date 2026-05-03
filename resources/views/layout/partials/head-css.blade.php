    <!-- Favicon -->
    <link rel="shortcut icon" href="{{URL::asset('build/img/favicon.png')}}">

    <!-- Apple Icon -->
    <link rel="apple-touch-icon" href="{{URL::asset('build/img/apple-icon.png')}}">

@if (!Route::is(['layout-dark', 'layout-mini', 'layout-hidden', 'layout-hover-view', 'layout-full-width', 'layout-rtl', 'login-basic', 'login-illustration', 'login-cover', 'login', 'register-basic', 'register-illustration', 'register-cover', 'forgot-password-basic', 'forgot-password-illustration', 'forgot-password-cover', 'reset-password-basic', 'reset-password-illustration', 'reset-password-cover', 'email-verification-basic', 'email-verification-illustration', 'email-verification-cover', 'success-basic', 'success-illustration', 'success-cover', 'two-step-verification-basic', 'two-step-verification-illustration', 'two-step-verification-cover', 'lock-screen', 'error-404', 'error-500', 'coming-soon', 'under-maintenance']))    
    <!-- Theme Config Js -->
    <script src="{{URL::asset('build/js/theme-script.js')}}"></script>
@endif    

@if (!Route::is(['layout-rtl']))    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/css/bootstrap.min.css')}}">
@endif     

@if (Route::is(['layout-rtl']))    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/css/bootstrap.rtl.min.css')}}"> 
@endif       

    <!-- Fontawesome CSS -->
	<link rel="stylesheet" href="{{URL::asset('build/plugins/fontawesome/css/fontawesome.min.css')}}">
	<link rel="stylesheet" href="{{URL::asset('build/plugins/fontawesome/css/all.min.css')}}">    

    <!-- Tabler Icon CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/tabler-icons/tabler-icons.min.css')}}">

@if (Route::is(['icon-bootstrap']))    
    <!-- Bootstrap Icon CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/icons/bootstrap/bootstrap-icons.min.css')}}">
@endif

@if (Route::is(['icon-feather', 'tables-basic']))
    <!-- Feather CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/icons/feather/feather.css')}}">
@endif

@if (Route::is(['icon-flag']))
    <!-- Flag CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/icons/flags/flags.css')}}">
@endif

@if (Route::is(['icon-ionic']))
    <!-- Ionic CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/icons/ionic/ionicons.css')}}">
@endif

@if (Route::is(['icon-material']))
    <!-- Material CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/material/materialdesignicons.css')}}">
@endif

@if (Route::is(['icon-pe7']))
    <!-- Pe7 CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/icons/pe7/pe-icon-7.css')}}">
@endif

@if (Route::is(['icon-remix']))
    <!-- Remix Icon CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/icons/remix/remixicon.css')}}">
@endif

@if (Route::is(['icon-simpleline']))
    <!-- Simpleline CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/simpleline/simple-line-icons.css')}}">
@endif

@if (Route::is(['icon-themify']))
    <!-- Themify CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/icons/themify/themify.css')}}">
@endif

@if (Route::is(['icon-typicons']))
    <!-- Typicon CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/icons/typicons/typicons.css')}}">
@endif

@if (Route::is(['icon-weather']))
    <!-- Weather CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/icons/weather/weathericons.css')}}">
@endif

    <!-- Simplebar CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/simplebar/simplebar.min.css')}}">    

    <!-- Daterangepikcer CSS -->
	<link rel="stylesheet" href="{{URL::asset('build/plugins/daterangepicker/daterangepicker.css')}}">

@if (Route::is(['add-doctor', 'add-invoices', 'appointment-calendar', 'appointment-consultations', 'appointment-settings', 'appointments', 'assets', 'calendar', 'create-patient', 'delete-account-request', 'designation', 'doctor-dashboard', 'doctor-details', 'doctor-schedule', 'doctors-appointment-details', 'doctor-appointments', 'doctors-leaves', 'doctors-list', 'doctors-patient-details', 'doctors-prescriptions', 'doctors-reviews', 'doctors-schedules', 'doctors', 'edit-doctor', 'edit-invoices', 'edit-patient', 'expense-category', 'expenses', 'holidays', 'hrm-departments', 'income', 'index', 'invoice', 'invoice-details', 'invoices', 'layout-full-width', 'layout-hidden', 'layout-hover-view', 'layout-mini', 'layout-dark', 'layout-rtl', 'leaves', 'new-appointment', 'notes', 'patient-appointment-details', 'patient-appointments', 'patient-dashboard', 'patient-details', 'patient-doctors', 'patient-invoices', 'patients-doctor-details', 'patient-prescriptions', 'patients', 'payments', 'payroll', 'specializations', 'staffs', 'todo-list', 'todo', 'transactions', 'working-hours-settings']))        
    <!-- Datetimepicker CSS -->
	<link rel="stylesheet" href="{{URL::asset('build/css/bootstrap-datetimepicker.min.css')}}">
@endif    

@if (Route::is(['add-blog', 'add-page', 'appointment-report', 'attendance', 'ban-ip-address-settings', 'clear-cache-settings', 'cronjob-settings', 'currencies-settings', 'database-backup-settings', 'delete-account-request', 'designation', 'doctor-schedule', 'doctors-list', 'doctors-notifications', 'doctor-schedules', 'edit-blog', 'edit-page', 'email-reply', 'email-templates-settings', 'email', 'expense-report', 'hrm-departments', 'income-report', 'language-settings', 'language-settings2', 'language-settings3', 'leaves', 'notifications', 'patient-notifications', 'patient-report', 'payroll-2', 'profit-and-loss', 'roles-and-permissions', 'security-settings', 'seo-setup-settings', 'services', 'sitemap-settigs', 'sms-templates-settings', 'specializations', 'storage-settings', 'system-backup-settings', 'system-update', 'tax-ratings-settings']))
    <!-- Bootstrap Tagsinput CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/bootstrap-tagsinput/bootstrap-tagsinput.css')}}">
@endif 

@if (Route::is(['contact-messages', 'create-patient', 'edit-patient', 'new-appointment', 'patient-details', 'patients-doctor-details', 'security-settings', 'ticket-details', 'tickets']))
	<!-- intltelinput CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/intltelinput/css/intlTelInput.css')}}">
    <link rel="stylesheet" href="{{URL::asset('build/plugins/intltelinput/css/demo.css')}}">
@endif

@if (Route::is(['assets', 'expenses', 'income', 'invoices', 'patient-details', 'patient-doctor-details', 'payments', 'roles-and-permissions', 'services', 'staffs', 'transactions', 'ui-rangeslider']))
    <!-- Rangeslider CSS -->
	<link rel="stylesheet" href="{{URL::asset('build/plugins/ion-rangeslider/css/ion.rangeSlider.css')}}">
	<link rel="stylesheet" href="{{URL::asset('build/plugins/ion-rangeslider/css/ion.rangeSlider.min.css')}}">
@endif    

@if (Route::is(['add-invoices', 'announcements', 'appointment-calendar', 'appointment-consultations', 'appointment-report', 'appointments', 'assets', 'attendance', 'contact-messages', 'contacts', 'data-tables', 'delete-account-request', 'designation', 'doctor-schedule', 'doctors-appointment-details', 'doctors-appointments', 'doctors-leaves', 'doctors-list', 'doctors-notifications', 'doctors-patient-details', 'doctors-prescription-details', 'doctors-prescriptions', 'doctors-reviews', 'doctors-schedules', 'edit-invoices', 'expense-category', 'expense-report', 'expenses', 'hrm-departments', 'income-report', 'income', 'invoice', 'invoice-details', 'invoices', 'leaves', 'newsletters', 'notifications', 'online-consultations', 'pages', 'patient-appointment-details', 'patient-appointments', 'patient-details', 'patient-doctors', 'patient-invoice-details', 'patient-invoices', 'patient-notifications', 'patient-prescription-details', 'patient-prescriptions', 'patient-report', 'patients-doctor-details', 'patients', 'payments', 'payroll-2', 'payroll', 'profit-and-loss', 'roles-and-permissions', 'services', 'specializations', 'staffs', 'tickets', 'transactions']))
    <!-- Datatable CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/css/dataTables.bootstrap5.min.css')}}">
@endif 

@if (Route::is(['add-blog', 'add-page', 'edit-blog', 'edit-page', 'email-templates-settings', 'gdpr-cookies-settings', 'file-manager', 'invoice-settings', 'invoice-templates-settings', 'notes', 'sms-templates-settings', 'notes', 'sms-templates-settings', 'ticket-details', 'todo-list', 'todo']))
    <!-- Quill CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/quill/quill.snow.css')}}">
@endif

@if (Route::is(['form-editors']))
    <!-- Quill css -->
    <link href="{{URL::asset('build/plugins/quill/quill.core.css')}}" rel="stylesheet" type="text/css">
    <link href="{{URL::asset('build/plugins/quill/quill.snow.css')}}" rel="stylesheet" type="text/css">
    <link href="{{URL::asset('build/plugins/quill/quill.bubble.css')}}" rel="stylesheet" type="text/css">
@endif   

@if (Route::is(['email-reply', 'search-list', 'social-feed']))
    <!-- Fancybox -->
	<link rel="stylesheet" href="{{URL::asset('build/plugins/fancybox/jquery.fancybox.min.css')}}">
@endif

@if (Route::is(['kanban-view']))
    <!-- Owl Carousel -->
    <link rel="stylesheet" href="{{URL::asset('build/css/owl.carousel.min.css')}}">
@endif

@if (Route::is(['chart-c3']))
    <!-- ChartC3 CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/c3-chart/c3.min.css')}}">
@endif

@if (Route::is(['chart-morris']))
    <!-- Morris CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/morris/morris.css')}}">
@endif

@if (Route::is(['form-pickers', 'kanban-view', 'notes', 'todo-list', 'todo']))
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/flatpickr/flatpickr.min.css')}}"> 
@endif

@if (Route::is(['form-range-slider']))
    <!-- nouisliderribute css -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/nouislider/nouislider.min.css')}}">
@endif

@if (Route::is(['form-select2', 'email-reply', 'email', 'notes']))
    <!-- Choices CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/choices.js/public/assets/styles/choices.min.css')}}">
@endif

@if (Route::is(['form-wizard']))
    <!-- Wizard CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/twitter-bootstrap-wizard/form-wizard.css')}}">
@endif

@if (Route::is(['gallery', 'ui-lightbox', 'widgets']))
    <!-- Glightbox CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/lightbox/glightbox.min.css')}}">
@endif

@if (Route::is(['ui-sweetalerts']))
    <!-- Sweetalert2 CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/sweetalert2/sweetalert2.min.css')}}">
@endif

@if (Route::is(['ui-toasts']))
    <!-- Toatr CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/toastr/toatr.css')}}">
@endif

    <!-- Select2 CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/select2/css/select2.min.css')}}">

    <!-- Main CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/css/style.css')}}" id="app-style">

    <!-- UHMS Design Rules -->
    <link rel="stylesheet" href="{{URL::asset('build/css/uhms-design-system.css')}}">