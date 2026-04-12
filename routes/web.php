<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DesignationController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\VisitController;
use App\Http\Controllers\Admin\QueueController;
use App\Http\Controllers\Admin\VitalController;
use App\Http\Controllers\Doctor\ConsultationController;
use App\Http\Controllers\Doctor\MedicalPatternController;
use App\Http\Controllers\Doctor\PrescriptionController;
use App\Http\Controllers\Lab\LabRequestController;
use App\Http\Controllers\Lab\LabResultController;
use App\Http\Controllers\Admin\LabTestController;
use App\Http\Controllers\Admin\DrugController;
use App\Http\Controllers\Admin\DrugStockController;
use App\Http\Controllers\Admin\ServiceCatalogController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PaymentController;
use App\Http\Controllers\Pharmacy\DispensingController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\WardController;
use App\Http\Controllers\Admin\AdmissionController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Doctor\DashboardController as DoctorDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest / Auth Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);

    Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');

    Route::get('reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Redirect Root
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Generic dashboard redirect (resolves based on role)
    Route::get('dashboard', function () {
        $user = auth()->user();

        if ($user->hasRole('Super Admin') || $user->hasRole('Admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('Doctor')) {
            return redirect()->route('doctor.dashboard');
        }

        // Default fallback for other roles
        return redirect()->route('admin.dashboard');
    })->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Admin Routes
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->group(function () {

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // User Management
        Route::middleware('can:users.view')->group(function () {
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::get('users/create', [UserController::class, 'create'])->name('users.create')->middleware('can:users.create');
            Route::post('users', [UserController::class, 'store'])->name('users.store')->middleware('can:users.create');
            Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit')->middleware('can:users.edit');
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('can:users.edit');
            Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status')->middleware('can:users.edit');
        });

        // Roles & Permissions
        Route::middleware('can:roles.manage')->group(function () {
            Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
            Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
            Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
            Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
            Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');
        });

        // Patients
        Route::middleware('can:patients.view')->group(function () {
            Route::get('patients', [PatientController::class, 'index'])->name('patients.index');
            Route::get('patients/create', [PatientController::class, 'create'])->name('patients.create')->middleware('can:patients.create');
            Route::post('patients', [PatientController::class, 'store'])->name('patients.store')->middleware('can:patients.create');
            Route::get('patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
            Route::get('patients/{patient}/edit', [PatientController::class, 'edit'])->name('patients.edit')->middleware('can:patients.edit');
            Route::put('patients/{patient}', [PatientController::class, 'update'])->name('patients.update')->middleware('can:patients.edit');
            Route::patch('patients/{patient}/toggle-status', [PatientController::class, 'toggleStatus'])->name('patients.toggle-status')->middleware('can:patients.edit');
        });

        // Visits
        Route::middleware('can:visits.view')->group(function () {
            Route::get('visits', [VisitController::class, 'index'])->name('visits.index');
            Route::get('visits/create', [VisitController::class, 'create'])->name('visits.create')->middleware('can:visits.create');
            Route::post('visits', [VisitController::class, 'store'])->name('visits.store')->middleware('can:visits.create');
            Route::get('visits/patient-search', [VisitController::class, 'patientSearch'])->name('visits.patient-search');
            Route::get('visits/{visit}', [VisitController::class, 'show'])->name('visits.show');
            Route::patch('visits/{visit}/transition', [VisitController::class, 'transition'])->name('visits.transition')->middleware('can:visits.transition');
        });

        // Wards & Beds
        Route::middleware('can:ward.view')->group(function () {
            Route::get('wards', [WardController::class, 'index'])->name('wards.index');
            Route::post('wards', [WardController::class, 'store'])->name('wards.store')->middleware('can:ward.manage');
            Route::put('wards/{ward}', [WardController::class, 'update'])->name('wards.update')->middleware('can:ward.manage');
            Route::patch('wards/{ward}/toggle', [WardController::class, 'toggle'])->name('wards.toggle')->middleware('can:ward.manage');
            Route::get('beds', [WardController::class, 'beds'])->name('wards.beds');
            Route::post('beds', [WardController::class, 'storeBed'])->name('wards.beds.store')->middleware('can:beds.manage');
            Route::put('beds/{bed}', [WardController::class, 'updateBed'])->name('wards.beds.update')->middleware('can:beds.manage');
            Route::get('bed-map', [WardController::class, 'bedMap'])->name('wards.bed-map');
        });

        // Admissions
        Route::middleware('can:ward.view')->group(function () {
            Route::get('admissions', [AdmissionController::class, 'index'])->name('admissions.index');
            Route::get('admissions/create', [AdmissionController::class, 'create'])->name('admissions.create')->middleware('can:ward.admit');
            Route::post('admissions', [AdmissionController::class, 'store'])->name('admissions.store')->middleware('can:ward.admit');
            Route::get('admissions/{admission}', [AdmissionController::class, 'show'])->name('admissions.show');
            Route::get('admissions/{admission}/discharge', [AdmissionController::class, 'discharge'])->name('admissions.discharge')->middleware('can:ward.discharge');
            Route::post('admissions/{admission}/discharge', [AdmissionController::class, 'processDischarge'])->name('admissions.process-discharge')->middleware('can:ward.discharge');
            Route::post('admissions/{admission}/rounds', [AdmissionController::class, 'storeRound'])->name('admissions.rounds.store');
        });

        // Appointments
        Route::middleware('can:appointments.view')->group(function () {
            Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
            Route::get('appointments/create', [AppointmentController::class, 'create'])->name('appointments.create')->middleware('can:appointments.create');
            Route::post('appointments', [AppointmentController::class, 'store'])->name('appointments.store')->middleware('can:appointments.create');
            Route::get('appointments/calendar', [AppointmentController::class, 'calendar'])->name('appointments.calendar');
            Route::get('appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
            Route::get('appointments/{appointment}/edit', [AppointmentController::class, 'edit'])->name('appointments.edit')->middleware('can:appointments.edit');
            Route::put('appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update')->middleware('can:appointments.edit');
            Route::post('appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn'])->name('appointments.check-in')->middleware('can:appointments.create');
            Route::patch('appointments/{appointment}/transition', [AppointmentController::class, 'transition'])->name('appointments.transition')->middleware('can:appointments.edit');
            Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel')->middleware('can:appointments.edit');
            Route::post('appointments/{appointment}/no-show', [AppointmentController::class, 'noShow'])->name('appointments.no-show')->middleware('can:appointments.edit');
        });

        // Queue
        Route::middleware('can:queue.view')->group(function () {
            Route::get('queue/manage', [QueueController::class, 'manage'])->name('queue.manage');
            Route::get('queue/board', [QueueController::class, 'board'])->name('queue.board');
            Route::post('queue/call-next', [QueueController::class, 'callNext'])->name('queue.call-next')->middleware('can:queue.manage');
            Route::patch('queue/{queueEntry}/complete', [QueueController::class, 'complete'])->name('queue.complete')->middleware('can:queue.manage');
            Route::patch('queue/{queueEntry}/skip', [QueueController::class, 'skip'])->name('queue.skip')->middleware('can:queue.manage');
            Route::patch('queue/{queueEntry}/requeue', [QueueController::class, 'requeue'])->name('queue.requeue')->middleware('can:queue.manage');
        });

        // Departments
        Route::middleware('can:departments.view')->group(function () {
            Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
            Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store')->middleware('can:departments.manage');
            Route::put('departments/{department}', [DepartmentController::class, 'update'])->name('departments.update')->middleware('can:departments.manage');
            Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy')->middleware('can:departments.manage');
        });

        // Designations
        Route::middleware('can:departments.view')->group(function () {
            Route::get('designations', [DesignationController::class, 'index'])->name('designations.index');
            Route::post('designations', [DesignationController::class, 'store'])->name('designations.store')->middleware('can:departments.manage');
            Route::put('designations/{designation}', [DesignationController::class, 'update'])->name('designations.update')->middleware('can:departments.manage');
            Route::delete('designations/{designation}', [DesignationController::class, 'destroy'])->name('designations.destroy')->middleware('can:departments.manage');
        });

        // Consultations (Doctor EHR)
        Route::middleware('can:consultations.view')->group(function () {
            Route::get('consultations', [ConsultationController::class, 'index'])->name('consultations.index');
            Route::get('consultations/{visit}', [ConsultationController::class, 'show'])->name('consultations.show');
            Route::get('consultations/{visit}/history', [ConsultationController::class, 'history'])->name('consultations.history');
            Route::patch('consultations/{visit}/transition', [ConsultationController::class, 'transitionVisit'])->name('consultations.transition')->middleware('can:visits.transition');

            // Consultation sub-resources (complaints, diagnoses, investigations, treatments, prescriptions)
            Route::middleware('can:consultations.create')->group(function () {
                Route::post('consultations/{visit}/complaints', [ConsultationController::class, 'storeComplaint'])->name('consultations.complaints.store');
                Route::delete('consultations/complaints/{complaint}', [ConsultationController::class, 'destroyComplaint'])->name('consultations.complaints.destroy');

                Route::post('consultations/{visit}/diagnoses', [ConsultationController::class, 'storeDiagnosis'])->name('consultations.diagnoses.store');
                Route::delete('consultations/diagnoses/{diagnosis}', [ConsultationController::class, 'destroyDiagnosis'])->name('consultations.diagnoses.destroy');

                Route::post('consultations/{visit}/investigations', [ConsultationController::class, 'storeInvestigation'])->name('consultations.investigations.store');
                Route::delete('consultations/investigations/{investigation}', [ConsultationController::class, 'destroyInvestigation'])->name('consultations.investigations.destroy');

                Route::post('consultations/{visit}/treatments', [ConsultationController::class, 'storeTreatment'])->name('consultations.treatments.store');
                Route::delete('consultations/treatments/{treatment}', [ConsultationController::class, 'destroyTreatment'])->name('consultations.treatments.destroy');
            });

            Route::post('consultations/{visit}/prescriptions', [ConsultationController::class, 'storePrescription'])->name('consultations.prescriptions.store')->middleware('can:prescriptions.create');
            Route::post('consultations/{visit}/lab-request', [ConsultationController::class, 'storeLabRequest'])->name('consultations.lab-request.store')->middleware('can:lab.requests.create');
        });

        // Vitals (Nurse Triage)
        Route::middleware('can:vitals.view')->group(function () {
            Route::get('vitals', [VitalController::class, 'create'])->name('vitals.create');
            Route::post('vitals', [VitalController::class, 'store'])->name('vitals.store')->middleware('can:vitals.create');
            Route::get('vitals/{visit}', [VitalController::class, 'show'])->name('vitals.show');
        });

        // Prescriptions
        Route::middleware('can:prescriptions.view')->group(function () {
            Route::get('prescriptions', [PrescriptionController::class, 'index'])->name('prescriptions.index');
            Route::get('prescriptions/{prescription}', [PrescriptionController::class, 'show'])->name('prescriptions.show');
            Route::patch('prescriptions/{prescription}/cancel', [PrescriptionController::class, 'cancel'])->name('prescriptions.cancel')->middleware('can:prescriptions.create');
        });

        // Medical Patterns
        Route::middleware('can:consultations.view')->group(function () {
            Route::get('patterns', [MedicalPatternController::class, 'index'])->name('patterns.index');
            Route::get('patterns/create', [MedicalPatternController::class, 'create'])->name('patterns.create')->middleware('can:consultations.create');
            Route::post('patterns', [MedicalPatternController::class, 'store'])->name('patterns.store')->middleware('can:consultations.create');
            Route::get('patterns/suggest', [MedicalPatternController::class, 'suggest'])->name('patterns.suggest');
            Route::get('patterns/{pattern}', [MedicalPatternController::class, 'show'])->name('patterns.show');
            Route::put('patterns/{pattern}', [MedicalPatternController::class, 'update'])->name('patterns.update')->middleware('can:consultations.create');
            Route::patch('patterns/{pattern}/toggle', [MedicalPatternController::class, 'toggleActive'])->name('patterns.toggle')->middleware('can:consultations.create');
            Route::delete('patterns/{pattern}', [MedicalPatternController::class, 'destroy'])->name('patterns.destroy')->middleware('can:consultations.create');
            Route::post('patterns/{pattern}/apply', [MedicalPatternController::class, 'apply'])->name('patterns.apply')->middleware('can:consultations.create');
            Route::post('patterns/from-record/{visit}', [MedicalPatternController::class, 'storeFromRecord'])->name('patterns.from-record')->middleware('can:consultations.create');
        });

        // Laboratory
        Route::prefix('lab')->name('lab.')->group(function () {
            // Lab Requests
            Route::middleware('can:lab.requests.view')->group(function () {
                Route::get('requests', [LabRequestController::class, 'index'])->name('requests.index');
                Route::get('requests/{labRequest}', [LabRequestController::class, 'show'])->name('requests.show');
                Route::patch('requests/{labRequest}/accept', [LabRequestController::class, 'accept'])->name('requests.accept')->middleware('can:lab.results.create');
                Route::patch('requests/{labRequest}/cancel', [LabRequestController::class, 'cancel'])->name('requests.cancel')->middleware('can:lab.results.create');
            });

            // Lab Results
            Route::middleware('can:lab.results.view')->group(function () {
                Route::get('results', [LabResultController::class, 'index'])->name('results.index');
                Route::post('results/{item}', [LabResultController::class, 'store'])->name('results.store')->middleware('can:lab.results.create');
                Route::post('results/batch/{labRequest}', [LabResultController::class, 'batchStore'])->name('results.batch')->middleware('can:lab.results.create');
                Route::patch('results/{result}/verify', [LabResultController::class, 'verify'])->name('results.verify')->middleware('can:lab.results.create');
            });

            // Lab Test Catalog Management
            Route::middleware('can:lab.tests.manage')->group(function () {
                Route::get('tests', [LabTestController::class, 'index'])->name('tests.index');
                Route::post('tests', [LabTestController::class, 'storeTest'])->name('tests.store');
                Route::put('tests/{test}', [LabTestController::class, 'updateTest'])->name('tests.update');
                Route::patch('tests/{test}/toggle', [LabTestController::class, 'toggleTest'])->name('tests.toggle');
                Route::get('tests/category/{category}', [LabTestController::class, 'testsByCategory'])->name('tests.by-category');

                Route::post('categories', [LabTestController::class, 'storeCategory'])->name('categories.store');
                Route::put('categories/{category}', [LabTestController::class, 'updateCategory'])->name('categories.update');
                Route::delete('categories/{category}', [LabTestController::class, 'destroyCategory'])->name('categories.destroy');
            });
        });

        // Pharmacy
        Route::prefix('pharmacy')->name('pharmacy.')->group(function () {
            // Dispensing
            Route::middleware('can:pharmacy.dispensing.view')->group(function () {
                Route::get('dispensing', [DispensingController::class, 'index'])->name('dispensing.index');
                Route::get('dispensing/{prescription}', [DispensingController::class, 'show'])->name('dispensing.show');
                Route::post('dispensing/{item}/dispense', [DispensingController::class, 'dispenseItem'])->name('dispensing.dispense-item')->middleware('can:pharmacy.dispensing.create');
                Route::post('dispensing/{prescription}/batch', [DispensingController::class, 'batchDispense'])->name('dispensing.batch')->middleware('can:pharmacy.dispensing.create');
                Route::get('history', [DispensingController::class, 'history'])->name('history');
            });

            // Drug Catalog
            Route::middleware('can:pharmacy.drugs.manage')->group(function () {
                Route::get('drugs', [DrugController::class, 'index'])->name('drugs.index');
                Route::post('drugs', [DrugController::class, 'store'])->name('drugs.store');
                Route::put('drugs/{drug}', [DrugController::class, 'update'])->name('drugs.update');
                Route::patch('drugs/{drug}/toggle', [DrugController::class, 'toggle'])->name('drugs.toggle');
                Route::get('drugs/search', [DrugController::class, 'search'])->name('drugs.search');

                Route::post('drug-categories', [DrugController::class, 'storeCategory'])->name('drug-categories.store');
                Route::put('drug-categories/{category}', [DrugController::class, 'updateCategory'])->name('drug-categories.update');
                Route::delete('drug-categories/{category}', [DrugController::class, 'destroyCategory'])->name('drug-categories.destroy');
            });

            // Stock Management
            Route::middleware('can:pharmacy.stock.manage')->group(function () {
                Route::get('stock', [DrugStockController::class, 'index'])->name('stock.index');
                Route::post('stock', [DrugStockController::class, 'store'])->name('stock.store');
                Route::put('stock/{stock}', [DrugStockController::class, 'update'])->name('stock.update');
                Route::get('stock/alerts', [DrugStockController::class, 'alerts'])->name('stock.alerts');
            });
        });

        // Billing
        Route::prefix('billing')->name('billing.')->group(function () {
            // Invoices
            Route::middleware('can:invoices.view')->group(function () {
                Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
                Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create')->middleware('can:invoices.create');
                Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store')->middleware('can:invoices.create');
                Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
                Route::patch('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel')->middleware('can:invoices.edit');
                Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
            });

            // Payments
            Route::middleware('can:payments.view')->group(function () {
                Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
                Route::post('payments/{invoice}', [PaymentController::class, 'store'])->name('payments.store')->middleware('can:payments.create');
                Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
            });
        });

        // Service Catalog
        Route::middleware('can:services.manage')->group(function () {
            Route::get('services', [ServiceCatalogController::class, 'index'])->name('services.index');
            Route::post('services', [ServiceCatalogController::class, 'store'])->name('services.store');
            Route::put('services/{service}', [ServiceCatalogController::class, 'update'])->name('services.update');
            Route::patch('services/{service}/toggle', [ServiceCatalogController::class, 'toggle'])->name('services.toggle');
        });

        // Reports
        Route::middleware('can:reports.view')->prefix('reports')->name('reports.')->group(function () {
            Route::get('income', [ReportController::class, 'income'])->name('income');
            Route::get('patients', [ReportController::class, 'patients'])->name('patients');
            Route::get('visits', [ReportController::class, 'visits'])->name('visits');
            Route::get('nhis', [ReportController::class, 'nhis'])->name('nhis');
        });

        // Settings (Admin)
        Route::middleware('can:settings.manage')->prefix('settings')->name('settings.')->group(function () {
            Route::get('organization', [SettingsController::class, 'organization'])->name('organization');
            Route::put('organization', [SettingsController::class, 'updateOrganization'])->name('organization.update');
            Route::get('invoice', [SettingsController::class, 'invoice'])->name('invoice');
            Route::put('invoice', [SettingsController::class, 'updateInvoice'])->name('invoice.update');
            Route::get('payment-methods', [SettingsController::class, 'paymentMethods'])->name('payment-methods');
            Route::put('payment-methods', [SettingsController::class, 'updatePaymentMethods'])->name('payment-methods.update');
            Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log');
        });

        // Profile (All authenticated users)
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    });

    /*
    |----------------------------------------------------------------------
    | Doctor Routes
    |----------------------------------------------------------------------
    */
    Route::prefix('doctor')->name('doctor.')->group(function () {
        Route::get('dashboard', [DoctorDashboardController::class, 'index'])->name('dashboard');
    });
});
