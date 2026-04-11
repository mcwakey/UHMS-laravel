<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
})->name('index');

Route::get('/index', function () {
    return view('index');
})->name('index');

// Doctor dashboard pages

Route::get('/doctor-dashboard', function () {
    return view('doctor-dashboard');
})->name('doctor-dashboard');

Route::get('/doctors-appointments', function () {
    return view('doctors-appointments');
})->name('doctors-appointments');

Route::get('/doctors-patient-details', function () {
    return view('doctors-patient-details');
})->name('doctors-patient-details');

Route::get('/doctors-appointment-details', function () {
    return view('doctors-appointment-details');
})->name('doctors-appointment-details');

Route::get('/online-consultations', function () {
    return view('online-consultations');
})->name('online-consultations');

Route::get('/doctors-schedules', function () {
    return view('doctors-schedules');
})->name('doctors-schedules');

Route::get('/doctors-prescriptions', function () {
    return view('doctors-prescriptions');
})->name('doctors-prescriptions');

Route::get('/doctors-prescription-details', function () {
    return view('doctors-prescription-details');
})->name('doctors-prescription-details');

Route::get('/doctors-leaves', function () {
    return view('doctors-leaves');
})->name('doctors-leaves');

Route::get('/doctors-reviews', function () {
    return view('doctors-reviews');
})->name('doctors-reviews');

Route::get('/doctors-profile-settings', function () {
    return view('doctors-profile-settings');
})->name('doctors-profile-settings');

Route::get('/doctors-password-settings', function () {
    return view('doctors-password-settings');
})->name('doctors-password-settings');

Route::get('/doctors-notification-settings', function () {
    return view('doctors-notification-settings');
})->name('doctors-notification-settings');

Route::get('/doctors-notifications', function () {
    return view('doctors-notifications');
})->name('doctors-notifications');

// Patient Dashboard pages

Route::get('/patient-dashboard', function () {
    return view('patient-dashboard');
})->name('patient-dashboard');

Route::get('/patient-appointments', function () {
    return view('patient-appointments');
})->name('patient-appointments');

Route::get('/patient-appointment-details', function () {
    return view('patient-appointment-details');
})->name('patient-appointment-details');

Route::get('/patient-doctors', function () {
    return view('patient-doctors');
})->name('patient-doctors');

Route::get('/patients-doctor-details', function () {
    return view('patients-doctor-details');
})->name('patients-doctor-details');

Route::get('/patient-prescriptions', function () {
    return view('patient-prescriptions');
})->name('patient-prescriptions');

Route::get('/patient-prescription-details', function () {
    return view('patient-prescription-details');
})->name('patient-prescription-details');

Route::get('/patient-invoices', function () {
    return view('patient-invoices');
})->name('patient-invoices');

Route::get('/patient-invoice-details', function () {
    return view('patient-invoice-details');
})->name('patient-invoice-details');

Route::get('/patient-profile-settings', function () {
    return view('patient-profile-settings');
})->name('patient-profile-settings');

Route::get('/patient-password-settings', function () {
    return view('patient-password-settings');
})->name('patient-password-settings');

Route::get('/patient-notifications-settings', function () {
    return view('patient-notifications-settings');
})->name('patient-notifications-settings');

Route::get('/patient-notifications', function () {
    return view('patient-notifications');
})->name('patient-notifications');

// Clinic modules
Route::get('/doctors', function () {
    return view('doctors');
})->name('doctors');

Route::get('/doctors-list', function () {
    return view('doctors-list');
})->name('doctors-list');

Route::get('/doctor-details', function () {
    return view('doctor-details');
})->name('doctor-details');

Route::get('/add-doctor', function () {
    return view('add-doctor');
})->name('add-doctor');

Route::get('/edit-doctor', function () {
    return view('edit-doctor');
})->name('edit-doctor');

Route::get('/doctor-schedule', function () {
    return view('doctor-schedule');
})->name('doctor-schedule');

Route::get('/patients', function () {
    return view('patients');
})->name('patients');

Route::get('/patients-grid', function () {
    return view('patients-grid');
})->name('patients-grid');

Route::get('/patient-details', function () {
    return view('patient-details');
})->name('patient-details');

Route::get('/create-patient', function () {
    return view('create-patient');
})->name('create-patient');

Route::get('/edit-patient', function () {
    return view('edit-patient');
})->name('edit-patient');

Route::get('/appointments', function () {
    return view('appointments');
})->name('appointments');

Route::get('/appointment-consultations', function () {
    return view('appointment-consultations');
})->name('appointment-consultations');

Route::get('/new-appointment', function () {
    return view('new-appointment');
})->name('new-appointment');

Route::get('/appointment-calendar', function () {
    return view('appointment-calendar');
})->name('appointment-calendar');

Route::get('/locations', function () {
    return view('locations');
})->name('locations');

Route::get('/services', function () {
    return view('services');
})->name('services');

Route::get('/specializations', function () {
    return view('specializations');
})->name('specializations');

Route::get('/assets', function () {
    return view('assets');
})->name('assets');

Route::get('/activities', function () {
    return view('activities');
})->name('activities');

Route::get('/messages', function () {
    return view('messages');
})->name('messages');

// HRM Modules

Route::get('/staffs', function () {
    return view('staffs');
})->name('staffs');

Route::get('/hrm-departments', function () {
    return view('hrm-departments');
})->name('hrm-departments');

Route::get('/designation', function () {
    return view('designation');
})->name('designation');

Route::get('/attendance', function () {
    return view('attendance');
})->name('attendance');

Route::get('/leaves', function () {
    return view('leaves');
})->name('leaves');

Route::get('/leave-type', function () {
    return view('leave-type');
})->name('leave-type');

Route::get('/holidays', function () {
    return view('holidays');
})->name('holidays');

Route::get('/payroll', function () {
    return view('payroll');
})->name('payroll');

Route::get('/payroll-2', function () {
    return view('payroll-2');
})->name('payroll-2');

// Finance & Accounts Modules

Route::get('/expenses', function () {
    return view('expenses');
})->name('expenses');

Route::get('/expense-category', function () {
    return view('expense-category');
})->name('expense-category');

Route::get('/income', function () {
    return view('income');
})->name('income');

Route::get('/invoices', function () {
    return view('invoices');
})->name('invoices');

Route::get('/add-invoices', function () {
    return view('add-invoices');
})->name('add-invoices');

Route::get('/edit-invoices', function () {
    return view('edit-invoices');
})->name('edit-invoices');

Route::get('/invoices-details', function () {
    return view('invoices-details');
})->name('invoices-details');

Route::get('/payments', function () {
    return view('payments');
})->name('payments');

Route::get('/transactions', function () {
    return view('transactions');
})->name('transactions');

// Administration Modules

Route::get('/roles-and-permissions', function () {
    return view('roles-and-permissions');
})->name('roles-and-permissions');

Route::get('/permissions', function () {
    return view('permissions');
})->name('permissions');

Route::get('/delete-account-request', function () {
    return view('delete-account-request');
})->name('delete-account-request');

// Report modules

Route::get('/income-report', function () {
    return view('income-report');
})->name('income-report');

Route::get('/expense-report', function () {
    return view('expense-report');
})->name('expense-report');

Route::get('/profit-and-loss', function () {
    return view('profit-and-loss');
})->name('profit-and-loss');

Route::get('/appointment-report', function () {
    return view('appointment-report');
})->name('appointment-report');

Route::get('/patient-report', function () {
    return view('patient-report');
})->name('patient-report');

// Content Modules

Route::get('/pages', function () {
    return view('pages');
})->name('pages');

Route::get('/add-page', function () {
    return view('add-page');
})->name('add-page');

Route::get('/edit-page', function () {
    return view('edit-page');
})->name('edit-page');

Route::get('/testimonials', function () {
    return view('testimonials');
})->name('testimonials');

Route::get('/faq', function () {
    return view('faq');
})->name('faq');

Route::get('/blogs', function () {
    return view('blogs');
})->name('blogs');

Route::get('/add-blog', function () {
    return view('add-blog');
})->name('add-blog');

Route::get('/edit-blog', function () {
    return view('edit-blog');
})->name('edit-blog');

Route::get('/blog-details', function () {
    return view('blog-details');
})->name('blog-details');

Route::get('/blog-comments', function () {
    return view('blog-comments');
})->name('blog-comments');

Route::get('/blog-categories', function () {
    return view('blog-categories');
})->name('blog-categories');

Route::get('/cities', function () {
    return view('cities');
})->name('cities');

Route::get('/states', function () {
    return view('states');
})->name('states');

Route::get('/countries', function () {
    return view('countries');
})->name('countries');

// Support Modules

Route::get('/contact-messages', function () {
    return view('contact-messages');
})->name('contact-messages');

Route::get('/tickets', function () {
    return view('tickets');
})->name('tickets');

Route::get('/ticket-details', function () {
    return view('ticket-details');
})->name('ticket-details');

Route::get('/announcements', function () {
    return view('announcements');
})->name('announcements');

Route::get('/newsletters', function () {
    return view('newsletters');
})->name('newsletters');

// Pages Modules

Route::get('/starter', function () {
    return view('starter');
})->name('starter');

Route::get('/profile', function () {
    return view('profile');
})->name('profile');

Route::get('/gallery', function () {
    return view('gallery');
})->name('gallery');

Route::get('/timeline', function () {
    return view('timeline');
})->name('timeline');

Route::get('/pricing', function () {
    return view('pricing');
})->name('pricing');

Route::get('/coming-soon', function () {
    return view('coming-soon');
})->name('coming-soon');

Route::get('/under-maintenance', function () {
    return view('under-maintenance');
})->name('under-maintenance');

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy-policy');

Route::get('/terms-and-conditions', function () {
    return view('terms-and-conditions');
})->name('terms-and-conditions');

Route::get('/notifications', function () {
    return view('notifications');
})->name('notifications');

Route::get('/widgets', function () {
    return view('widgets');
})->name('widgets');

// Applications Modules

Route::get('/chat', function () {
    return view('chat');
})->name('chat');

Route::get('/voice-call', function () {
    return view('voice-call');
})->name('voice-call');

Route::get('/video-call', function () {
    return view('video-call');
})->name('video-call');

Route::get('/outgoing-call', function () {
    return view('outgoing-call');
})->name('outgoing-call');

Route::get('/incoming-call', function () {
    return view('incoming-call');
})->name('incoming-call');

Route::get('/call-history', function () {
    return view('call-history');
})->name('call-history');

Route::get('/calendar', function () {
    return view('calendar');
})->name('calendar');

Route::get('/contacts', function () {
    return view('contacts');
})->name('contacts');

Route::get('/email', function () {
    return view('email');
})->name('email');

Route::get('/email-reply', function () {
    return view('email-reply');
})->name('email-reply');

Route::get('/invoice', function () {
    return view('invoice');
})->name('invoice');

Route::get('/invoice-details', function () {
    return view('invoice-details');
})->name('invoice-details');

Route::get('/todo', function () {
    return view('todo');
})->name('todo');

Route::get('/todo-list', function () {
    return view('todo-list');
})->name('todo-list');

Route::get('/notes', function () {
    return view('notes');
})->name('notes');

Route::get('/kanban-view', function () {
    return view('kanban-view');
})->name('kanban-view');

Route::get('/file-manager', function () {
    return view('file-manager');
})->name('file-manager');

Route::get('/social-feed', function () {
    return view('social-feed');
})->name('social-feed');

Route::get('/search-list', function () {
    return view('search-list');
})->name('search-list');

// Settings Modules

Route::get('/profile-settings', function () {
    return view('profile-settings');
})->name('profile-settings');

Route::get('/security-settings', function () {
    return view('security-settings');
})->name('security-settings');

Route::get('/notifications-settings', function () {
    return view('notifications-settings');
})->name('notifications-settings');

Route::get('/integrations-settings', function () {
    return view('integrations-settings');
})->name('integrations-settings');

Route::get('/organization-settings', function () {
    return view('organization-settings');
})->name('organization-settings');

Route::get('/localization-settings', function () {
    return view('localization-settings');
})->name('localization-settings');

Route::get('/prefixes-settings', function () {
    return view('prefixes-settings');
})->name('prefixes-settings');

Route::get('/seo-setup-settings', function () {
    return view('seo-setup-settings');
})->name('seo-setup-settings');

Route::get('/language-settings', function () {
    return view('language-settings');
})->name('language-settings');

Route::get('/language-settings2', function () {
    return view('language-settings2');
})->name('language-settings2');

Route::get('/language-settings3', function () {
    return view('language-settings3');
})->name('language-settings3');

Route::get('/maintenance-mode-settings', function () {
    return view('maintenance-mode-settings');
})->name('maintenance-mode-settings');

Route::get('/login-and-register-settings', function () {
    return view('login-and-register-settings');
})->name('login-and-register-settings');

Route::get('/preferences-settings', function () {
    return view('preferences-settings');
})->name('preferences-settings');

Route::get('/appointment-settings', function () {
    return view('appointment-settings');
})->name('appointment-settings');

Route::get('/working-hours-settings', function () {
    return view('working-hours-settings');
})->name('working-hours-settings');

Route::get('/cancellation-reason-settings', function () {
    return view('cancellation-reason-settings');
})->name('cancellation-reason-settings');

Route::get('/invoice-settings', function () {
    return view('invoice-settings');
})->name('invoice-settings');

Route::get('/invoice-templates-settings', function () {
    return view('invoice-templates-settings');
})->name('invoice-templates-settings');

Route::get('/signatures-settings', function () {
    return view('signatures-settings');
})->name('signatures-settings');

Route::get('/custom-fields-settings', function () {
    return view('custom-fields-settings');
})->name('custom-fields-settings');

Route::get('/email-settings', function () {
    return view('email-settings');
})->name('email-settings');

Route::get('/email-templates-settings', function () {
    return view('email-templates-settings');
})->name('email-templates-settings');

Route::get('/sms-gateways-settings', function () {
    return view('sms-gateways-settings');
})->name('sms-gateways-settings');

Route::get('/sms-templates-settings', function () {
    return view('sms-templates-settings');
})->name('sms-templates-settings');

Route::get('/gdpr-cookies-settings', function () {
    return view('gdpr-cookies-settings');
})->name('gdpr-cookies-settings');

Route::get('/payment-methods-settings', function () {
    return view('payment-methods-settings');
})->name('payment-methods-settings');

Route::get('/bank-accounts-settings', function () {
    return view('bank-accounts-settings');
})->name('bank-accounts-settings');

Route::get('/tax-rates-settings', function () {
    return view('tax-rates-settings');
})->name('tax-rates-settings');

Route::get('/currencies-settings', function () {
    return view('currencies-settings');
})->name('currencies-settings');

Route::get('/sitemap-settings', function () {
    return view('sitemap-settings');
})->name('sitemap-settings');

Route::get('/clear-cache-settings', function () {
    return view('clear-cache-settings');
})->name('clear-cache-settings');

Route::get('/storage-settings', function () {
    return view('storage-settings');
})->name('storage-settings');

Route::get('/cronjob-settings', function () {
    return view('cronjob-settings');
})->name('cronjob-settings');

Route::get('/ban-ip-address-settings', function () {
    return view('ban-ip-address-settings');
})->name('ban-ip-address-settings');

Route::get('/system-backup-settings', function () {
    return view('system-backup-settings');
})->name('system-backup-settings');

Route::get('/database-backup-settings', function () {
    return view('database-backup-settings');
})->name('database-backup-settings');

Route::get('/system-update', function () {
    return view('system-update');
})->name('system-update');

// Layout Pages

Route::get('/layout-dark', function () {
    return view('layout-dark');
})->name('layout-dark');

Route::get('/layout-full-width', function () {
    return view('layout-full-width');
})->name('layout-full-width');

Route::get('/layout-hidden', function () {
    return view('layout-hidden');
})->name('layout-hidden');

Route::get('/layout-hover-view', function () {
    return view('layout-hover-view');
})->name('layout-hover-view');

Route::get('/layout-mini', function () {
    return view('layout-mini');
})->name('layout-mini');

Route::get('/layout-rtl', function () {
    return view('layout-rtl');
})->name('layout-rtl');

// Authentication Pages

Route::get('/login-cover', function () {
    return view('login-cover');
})->name('login-cover');

Route::get('/login-illustration', function () {
    return view('login-illustration');
})->name('login-illustration');

Route::get('/login-basic', function () {
    return view('login-basic');
})->name('login-basic');

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/register-cover', function () {
    return view('register-cover');
})->name('register-cover');

Route::get('/register-illustration', function () {
    return view('register-illustration');
})->name('register-illustration');

Route::get('/register-basic', function () {
    return view('register-basic');
})->name('register-basic');

Route::get('/forgot-password-cover', function () {
    return view('forgot-password-cover');
})->name('forgot-password-cover');

Route::get('/forgot-password-illustration', function () {
    return view('forgot-password-illustration');
})->name('forgot-password-illustration');

Route::get('/forgot-password-basic', function () {
    return view('forgot-password-basic');
})->name('forgot-password-basic');

Route::get('/reset-password-cover', function () {
    return view('reset-password-cover');
})->name('reset-password-cover');

Route::get('/reset-password-illustration', function () {
    return view('reset-password-illustration');
})->name('reset-password-illustration');

Route::get('/reset-password-basic', function () {
    return view('reset-password-basic');
})->name('reset-password-basic');

Route::get('/email-verification-cover', function () {
    return view('email-verification-cover');
})->name('email-verification-cover');

Route::get('/email-verification-illustration', function () {
    return view('email-verification-illustration');
})->name('email-verification-illustration');

Route::get('/email-verification-basic', function () {
    return view('email-verification-basic');
})->name('email-verification-basic');

Route::get('/success-cover', function () {
    return view('success-cover');
})->name('success-cover');

Route::get('/success-illustration', function () {
    return view('success-illustration');
})->name('success-illustration');

Route::get('/success-basic', function () {
    return view('success-basic');
})->name('success-basic');

Route::get('/two-step-verification-cover', function () {
    return view('two-step-verification-cover');
})->name('two-step-verification-cover');

Route::get('/two-step-verification-illustration', function () {
    return view('two-step-verification-illustration');
})->name('two-step-verification-illustration');

Route::get('/two-step-verification-basic', function () {
    return view('two-step-verification-basic');
})->name('two-step-verification-basic');

Route::get('/lock-screen', function () {
    return view('lock-screen');
})->name('lock-screen');

Route::get('/error-404', function () {
    return view('error-404');
})->name('error-404');

Route::get('/error-500', function () {
    return view('error-500');
})->name('error-500');

// UI Charts

Route::get('/chart-apex', function () {
    return view('chart-apex');
})->name('chart-apex');

Route::get('/chart-js', function () {
    return view('chart-js');
})->name('chart-js');

Route::get('/chart-morris', function () {
    return view('chart-morris');
})->name('chart-morris');

Route::get('/chart-flot', function () {
    return view('chart-flot');
})->name('chart-flot');

Route::get('/chart-peity', function () {
    return view('chart-peity');
})->name('chart-peity');

Route::get('/chart-c3', function () {
    return view('chart-c3');
})->name('chart-c3');

// UI - Forms

Route::get('/form-basic-inputs', function () {
    return view('form-basic-inputs');
})->name('form-basic-inputs');

Route::get('/form-checkbox-radios', function () {
    return view('form-checkbox-radios');
})->name('form-checkbox-radios');

Route::get('/form-editors', function () {
    return view('form-editors');
})->name('form-editors');

Route::get('/form-elements', function () {
    return view('form-elements');
})->name('form-elements');

Route::get('/form-fileupload', function () {
    return view('form-fileupload');
})->name('form-fileupload');

Route::get('/form-floating-labels', function () {
    return view('form-floating-labels');
})->name('form-floating-labels');

Route::get('/form-grid-gutters', function () {
    return view('form-grid-gutters');
})->name('form-grid-gutters');

Route::get('/form-horizontal', function () {
    return view('form-horizontal');
})->name('form-horizontal');

Route::get('/form-input-groups', function () {
    return view('form-input-groups');
})->name('form-input-groups');

Route::get('/form-mask', function () {
    return view('form-mask');
})->name('form-mask');

Route::get('/form-pickers', function () {
    return view('form-pickers');
})->name('form-pickers');

Route::get('/form-range-slider', function () {
    return view('form-range-slider');
})->name('form-range-slider');

Route::get('/form-select2', function () {
    return view('form-select2');
})->name('form-select2');

Route::get('/form-validation', function () {
    return view('form-validation');
})->name('form-validation');

Route::get('/form-vertical', function () {
    return view('form-vertical');
})->name('form-vertical');

Route::get('/form-wizard', function () {
    return view('form-wizard');
})->name('form-wizard');

// UI - Icons

Route::get('/icon-bootstrap', function () {
    return view('icon-bootstrap');
})->name('icon-bootstrap');

Route::get('/icon-fontawesome', function () {
    return view('icon-fontawesome');
})->name('icon-fontawesome');

Route::get('/icon-feather', function () {
    return view('icon-feather');
})->name('icon-feather');

Route::get('/icon-ionic', function () {
    return view('icon-ionic');
})->name('icon-ionic');

Route::get('/icon-material', function () {
    return view('icon-material');
})->name('icon-material');

Route::get('/icon-pe7', function () {
    return view('icon-pe7');
})->name('icon-pe7');

Route::get('/icon-simpleline', function () {
    return view('icon-simpleline');
})->name('icon-simpleline');

Route::get('/icon-themify', function () {
    return view('icon-themify');
})->name('icon-themify');

Route::get('/icon-weather', function () {
    return view('icon-weather');
})->name('icon-weather');

Route::get('/icon-typicons', function () {
    return view('icon-typicons');
})->name('icon-typicons');

Route::get('/icon-flag', function () {
    return view('icon-flag');
})->name('icon-flag');

Route::get('/icon-remix', function () {
    return view('icon-remix');
})->name('icon-remix');

Route::get('/icon-tabler', function () {
    return view('icon-tabler');
})->name('icon-tabler');

// UI - Tables

Route::get('/tables-basic', function () {
    return view('tables-basic');
})->name('tables-basic');

Route::get('/data-tables', function () {
    return view('data-tables');
})->name('data-tables');

// UI - UI Components

Route::get('/ui-accordion', function () {
    return view('ui-accordion');
})->name('ui-accordion');

Route::get('/ui-alerts', function () {
    return view('ui-alerts');
})->name('ui-alerts');

Route::get('/ui-avatar', function () {
    return view('ui-avatar');
})->name('ui-avatar');

Route::get('/ui-badges', function () {
    return view('ui-badges');
})->name('ui-badges');

Route::get('/ui-breadcrumb', function () {
    return view('ui-breadcrumb');
})->name('ui-breadcrumb');

Route::get('/ui-buttons-group', function () {
    return view('ui-buttons-group');
})->name('ui-buttons-group');

Route::get('/ui-buttons', function () {
    return view('ui-buttons');
})->name('ui-buttons');

Route::get('/ui-cards', function () {
    return view('ui-cards');
})->name('ui-cards');

Route::get('/ui-carousel', function () {
    return view('ui-carousel');
})->name('ui-carousel');

Route::get('/ui-clipboard', function () {
    return view('ui-clipboard');
})->name('ui-clipboard');

Route::get('/ui-collapse', function () {
    return view('ui-collapse');
})->name('ui-collapse');

Route::get('/ui-dropdowns', function () {
    return view('ui-dropdowns');
})->name('ui-dropdowns');

Route::get('/ui-grid', function () {
    return view('ui-grid');
})->name('ui-grid');

Route::get('/ui-images', function () {
    return view('ui-images');
})->name('ui-images');

Route::get('/ui-lightbox', function () {
    return view('ui-lightbox');
})->name('ui-lightbox');

Route::get('/ui-links', function () {
    return view('ui-links');
})->name('ui-links');

Route::get('/ui-list-group', function () {
    return view('ui-list-group');
})->name('ui-list-group');

Route::get('/ui-modals', function () {
    return view('ui-modals');
})->name('ui-modals');

Route::get('/ui-nav-tabs', function () {
    return view('ui-nav-tabs');
})->name('ui-nav-tabs');

Route::get('/ui-notifications', function () {
    return view('ui-notifications');
})->name('ui-notifications');

Route::get('/ui-offcanvas', function () {
    return view('ui-offcanvas');
})->name('ui-offcanvas');

Route::get('/ui-pagination', function () {
    return view('ui-pagination');
})->name('ui-pagination');

Route::get('/ui-placeholders', function () {
    return view('ui-placeholders');
})->name('ui-placeholders');

Route::get('/ui-popovers', function () {
    return view('ui-popovers');
})->name('ui-popovers');

Route::get('/ui-progress', function () {
    return view('ui-progress');
})->name('ui-progress');

Route::get('/ui-rangeslider', function () {
    return view('ui-rangeslider');
})->name('ui-rangeslider');

Route::get('/ui-ratio', function () {
    return view('ui-ratio');
})->name('ui-ratio');

Route::get('/ui-scrollbar', function () {
    return view('ui-scrollbar');
})->name('ui-scrollbar');

Route::get('/ui-scrollspy', function () {
    return view('ui-scrollspy');
})->name('ui-scrollspy');

Route::get('/ui-sortable', function () {
    return view('ui-sortable');
})->name('ui-sortable');

Route::get('/ui-spinner', function () {
    return view('ui-spinner');
})->name('ui-spinner');

Route::get('/ui-sweetalerts', function () {
    return view('ui-sweetalerts');
})->name('ui-sweetalerts');

Route::get('/ui-toasts', function () {
    return view('ui-toasts');
})->name('ui-toasts');

Route::get('/ui-tooltips', function () {
    return view('ui-tooltips');
})->name('ui-tooltips');

Route::get('/ui-typography', function () {
    return view('ui-typography');
})->name('ui-typography');

Route::get('/ui-utilities', function () {
    return view('ui-utilities');
})->name('ui-utilities');

Route::get('/extended-dragula', function () {
    return view('extended-dragula');
})->name('extended-dragula');