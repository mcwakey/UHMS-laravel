<?php

use Illuminate\Support\Facades\Auth;
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
use App\Http\Controllers\Admin\InsuranceProviderController;
use App\Http\Controllers\Admin\InsuranceVerificationController;
use App\Http\Controllers\Admin\InsuranceTierController;
use App\Http\Controllers\Admin\PatientInsuranceController;
use App\Http\Controllers\Admin\EmergencyContactController;
use App\Http\Controllers\Admin\ConsultationTaskController;
use App\Http\Controllers\Admin\ClaimController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\StockTransferController;
use App\Http\Controllers\Admin\AccountCategoryController;
use App\Http\Controllers\Admin\FinancialEntryController;
use App\Http\Controllers\Admin\CashierShiftController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\LeaveController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\IcdCodeController;
use App\Http\Controllers\Admin\ProcedureController;
use App\Http\Controllers\Admin\AnalyzerController;
use App\Http\Controllers\Admin\InvestigationItemController;
use App\Http\Controllers\Doctor\DashboardController as DoctorDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest / Auth Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->middleware('throttle:5,1');

    Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('throttle:3,1');

    Route::get('reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update')->middleware('throttle:5,1');
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
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('Doctor')) {
            return redirect()->route('doctor.dashboard');
        }

        // Role-specific staff dashboards (single shared view, content varies by role)
        if ($user->hasAnyRole(['Nurse', 'Receptionist', 'Pharmacist', 'Lab Technician', 'Accountant', 'Cashier', 'HR Manager', 'Store Manager'])) {
            return redirect()->route('staff.dashboard');
        }

        // Default fallback
        return redirect()->route('admin.dashboard');
    })->name('dashboard');

    // Shared staff dashboard — role-aware content
    Route::get('staff/dashboard', [\App\Http\Controllers\StaffDashboardController::class, 'index'])
        ->name('staff.dashboard');

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

            // Patient Insurance Management
            Route::middleware('can:patients.edit')->group(function () {
                Route::post('patients/{patient}/insurances', [PatientInsuranceController::class, 'store'])->name('patients.insurances.store');
                Route::put('patients/{patient}/insurances/{insurance}', [PatientInsuranceController::class, 'update'])->name('patients.insurances.update');
                Route::delete('patients/{patient}/insurances/{insurance}', [PatientInsuranceController::class, 'destroy'])->name('patients.insurances.destroy');
                Route::patch('patients/{patient}/insurances/{insurance}/set-primary', [PatientInsuranceController::class, 'setPrimary'])->name('patients.insurances.set-primary');
            });

            // Emergency Contacts
            Route::middleware('can:patients.edit')->group(function () {
                Route::post('patients/{patient}/emergency-contacts', [EmergencyContactController::class, 'store'])->name('patients.emergency-contacts.store');
                Route::put('patients/{patient}/emergency-contacts/{contact}', [EmergencyContactController::class, 'update'])->name('patients.emergency-contacts.update');
                Route::delete('patients/{patient}/emergency-contacts/{contact}', [EmergencyContactController::class, 'destroy'])->name('patients.emergency-contacts.destroy');
            });
        });

        // Insurance Providers AJAX
        Route::get('insurance-providers/by-type', [PatientInsuranceController::class, 'providersByType'])->name('insurance-providers.by-type');

        // Visits
        Route::middleware('can:visits.view')->group(function () {
            Route::get('visits', [VisitController::class, 'index'])->name('visits.index');
            Route::get('visits/create', [VisitController::class, 'create'])->name('visits.create')->middleware('can:visits.create');
            Route::post('visits', [VisitController::class, 'store'])->name('visits.store')->middleware('can:visits.create');
            Route::get('visits/patient-search', [VisitController::class, 'patientSearch'])->name('visits.patient-search');
            Route::get('visits/patient-insurances', [VisitController::class, 'patientInsurances'])->name('visits.patient-insurances');
            Route::get('visits/department-services', [VisitController::class, 'departmentServices'])->name('visits.department-services');
            Route::get('visits/doctors-for-services', [VisitController::class, 'doctorsForServices'])->name('visits.doctors-for-services');
            Route::get('visits/services-for-doctor', [VisitController::class, 'servicesForDoctor'])->name('visits.services-for-doctor');
            Route::get('visits/service-price', [VisitController::class, 'servicePrice'])->name('visits.service-price');
            Route::get('visits/{visit}', [VisitController::class, 'show'])->name('visits.show');
            Route::get('visits/{visit}/edit', [VisitController::class, 'edit'])->name('visits.edit')->middleware('can:visits.edit');
            Route::put('visits/{visit}', [VisitController::class, 'update'])->name('visits.update')->middleware('can:visits.edit');
            Route::patch('visits/{visit}/transition', [VisitController::class, 'transition'])->name('visits.transition')->middleware('can:visits.transition');
            Route::patch('visits/{visit}/send-to-department', [VisitController::class, 'sendToDepartment'])->name('visits.send-to-department')->middleware('can:visits.transition');
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
            Route::post('admissions/{admission}/vitals', [AdmissionController::class, 'storeVital'])->name('admissions.vitals.store');
            Route::post('admissions/{admission}/services', [AdmissionController::class, 'storeService'])->name('admissions.services.store');
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

        // Insurance Providers & Tiers
        Route::middleware('can:claims.view')->group(function () {
            Route::get('insurance-providers', [InsuranceProviderController::class, 'index'])->name('insurance-providers.index');
            Route::post('insurance-providers', [InsuranceProviderController::class, 'store'])->name('insurance-providers.store')->middleware('can:claims.create');
            Route::put('insurance-providers/{provider}', [InsuranceProviderController::class, 'update'])->name('insurance-providers.update')->middleware('can:claims.create');
            Route::patch('insurance-providers/{provider}/toggle', [InsuranceProviderController::class, 'toggle'])->name('insurance-providers.toggle')->middleware('can:claims.create');

            // Tier management (nested under provider)
            Route::get('insurance-providers/{provider}/tiers', [InsuranceTierController::class, 'index'])->name('insurance-providers.tiers.index');
            Route::post('insurance-providers/{provider}/tiers', [InsuranceTierController::class, 'store'])->name('insurance-providers.tiers.store')->middleware('can:claims.create');
            Route::get('insurance-providers/{provider}/tiers/for-patient', [InsuranceTierController::class, 'tiersForProvider'])->name('insurance-providers.tiers.for-patient');

            // Tier update/delete (standalone, not nested under provider)
            Route::put('insurance-tiers/{tier}', [InsuranceTierController::class, 'update'])->name('insurance-tiers.update')->middleware('can:claims.create');
            Route::delete('insurance-tiers/{tier}', [InsuranceTierController::class, 'destroy'])->name('insurance-tiers.destroy')->middleware('can:claims.create');

            // Generic, provider-agnostic insurance verification endpoint.
            Route::post('insurance/verify', [InsuranceVerificationController::class, 'verify'])
                ->name('insurance.verify')
                ->middleware('can:claims.view');
        });

        // Claims
        Route::middleware('can:claims.view')->group(function () {
            Route::get('claims', [ClaimController::class, 'index'])->name('claims.index');
            Route::get('claims/create', [ClaimController::class, 'create'])->name('claims.create')->middleware('can:claims.create');
            Route::post('claims', [ClaimController::class, 'store'])->name('claims.store')->middleware('can:claims.create');
            Route::post('claims/from-invoice', [ClaimController::class, 'storeFromInvoice'])->name('claims.store-from-invoice')->middleware('can:claims.create');
            Route::get('claims/export', [ClaimController::class, 'export'])->name('claims.export')->middleware('can:claims.export');
            Route::get('claims/{claim}', [ClaimController::class, 'show'])->name('claims.show');
            Route::post('claims/{claim}/submit', [ClaimController::class, 'submit'])->name('claims.submit')->middleware('can:claims.create');
            Route::get('claims/{claim}/review', [ClaimController::class, 'review'])->name('claims.review')->middleware('can:claims.approve');
            Route::post('claims/{claim}/review-item/{item}', [ClaimController::class, 'reviewItem'])->name('claims.review-item')->middleware('can:claims.approve');
            Route::post('claims/{claim}/complete-review', [ClaimController::class, 'completeReview'])->name('claims.complete-review')->middleware('can:claims.approve');
            Route::post('claims/{claim}/mark-paid', [ClaimController::class, 'markPaid'])->name('claims.mark-paid')->middleware('can:claims.approve');
            Route::post('claims/{claim}/appeal', [ClaimController::class, 'appeal'])->name('claims.appeal')->middleware('can:claims.create');
            Route::post('claims/{claim}/add-item', [ClaimController::class, 'addItem'])->name('claims.add-item')->middleware('can:claims.create');
            Route::delete('claims/remove-item/{item}', [ClaimController::class, 'removeItem'])->name('claims.remove-item')->middleware('can:claims.create');
        });

        // Store & Procurement
        Route::prefix('store')->name('store.')->group(function () {
            // Suppliers
            Route::middleware('can:store.purchase.view')->group(function () {
                Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
                Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store')->middleware('can:store.purchase.create');
                Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update')->middleware('can:store.purchase.create');
                Route::patch('suppliers/{supplier}/toggle', [SupplierController::class, 'toggle'])->name('suppliers.toggle')->middleware('can:store.purchase.create');
            });

            // Purchase Orders
            Route::middleware('can:store.purchase.view')->group(function () {
                Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
                Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create')->middleware('can:store.purchase.create');
                Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store')->middleware('can:store.purchase.create');
                Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
                Route::post('purchase-orders/{purchaseOrder}/submit', [PurchaseOrderController::class, 'submit'])->name('purchase-orders.submit')->middleware('can:store.purchase.create');
                Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve')->middleware('can:store.purchase.approve');
                Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive')->middleware('can:store.purchase.create');
                Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel')->middleware('can:store.purchase.create');
                Route::post('purchase-orders/{purchaseOrder}/add-item', [PurchaseOrderController::class, 'addItem'])->name('purchase-orders.add-item')->middleware('can:store.purchase.create');
                Route::delete('purchase-orders/remove-item/{item}', [PurchaseOrderController::class, 'removeItem'])->name('purchase-orders.remove-item')->middleware('can:store.purchase.create');
            });

            // Stock Transfers
            Route::middleware('can:store.transfer.view')->group(function () {
                Route::get('transfers', [StockTransferController::class, 'index'])->name('transfers.index');
                Route::get('transfers/create', [StockTransferController::class, 'create'])->name('transfers.create')->middleware('can:store.transfer.create');
                Route::post('transfers', [StockTransferController::class, 'store'])->name('transfers.store')->middleware('can:store.transfer.create');
                Route::get('transfers/{transfer}', [StockTransferController::class, 'show'])->name('transfers.show');
                Route::post('transfers/{transfer}/approve', [StockTransferController::class, 'approve'])->name('transfers.approve')->middleware('can:store.purchase.approve');
                Route::post('transfers/{transfer}/complete', [StockTransferController::class, 'complete'])->name('transfers.complete')->middleware('can:store.transfer.create');
                Route::post('transfers/{transfer}/cancel', [StockTransferController::class, 'cancel'])->name('transfers.cancel')->middleware('can:store.transfer.create');
                Route::get('transfers/drug-stock', [StockTransferController::class, 'drugStock'])->name('transfers.drug-stock');
                Route::get('transfers/investigation-stock', [StockTransferController::class, 'investigationItemStock'])->name('transfers.investigation-stock');
            });
        });

        // Accounts & Finance
        Route::prefix('accounts')->name('accounts.')->group(function () {
            // Categories
            Route::middleware('can:accounts.manage')->group(function () {
                Route::get('categories', [AccountCategoryController::class, 'index'])->name('categories.index');
                Route::post('categories', [AccountCategoryController::class, 'store'])->name('categories.store');
                Route::put('categories/{category}', [AccountCategoryController::class, 'update'])->name('categories.update');
                Route::patch('categories/{category}/toggle', [AccountCategoryController::class, 'toggle'])->name('categories.toggle');
            });

            // Expenses
            Route::middleware('can:accounts.entries.view')->group(function () {
                Route::get('expenses', [FinancialEntryController::class, 'index'])->name('expenses.index');
                Route::get('expenses/create', [FinancialEntryController::class, 'create'])->name('expenses.create')->middleware('can:accounts.entries.create');
            });

            // Income
            Route::middleware('can:accounts.entries.view')->group(function () {
                Route::get('income', [FinancialEntryController::class, 'index'])->name('income.index');
                Route::get('income/create', [FinancialEntryController::class, 'create'])->name('income.create')->middleware('can:accounts.entries.create');
            });

            // Shared entry actions
            Route::post('entries', [FinancialEntryController::class, 'store'])->name('entries.store')->middleware('can:accounts.entries.create');
            Route::post('entries/{entry}/approve', [FinancialEntryController::class, 'approve'])->name('entries.approve')->middleware('can:accounts.entries.approve');
            Route::delete('entries/{entry}', [FinancialEntryController::class, 'destroy'])->name('entries.destroy')->middleware('can:accounts.entries.create');

            // Reports
            Route::middleware('can:accounts.entries.view')->group(function () {
                Route::get('daily-collection', [FinancialEntryController::class, 'dailyCollection'])->name('daily-collection');
                Route::get('reconciliation', [FinancialEntryController::class, 'reconciliation'])->name('reconciliation');
            });

            // Cashier Handover
            Route::middleware('can:accounts.cashier')->group(function () {
                Route::get('handover', [CashierShiftController::class, 'index'])->name('handover.index');
                Route::post('handover/open', [CashierShiftController::class, 'open'])->name('handover.open');
                Route::post('handover/{shift}/close', [CashierShiftController::class, 'close'])->name('handover.close');
                Route::post('handover/{shift}/verify', [CashierShiftController::class, 'verify'])->name('handover.verify')->middleware('can:accounts.entries.approve');
            });
        });

        // HR & Payroll
        Route::prefix('hr')->name('hr.')->group(function () {
            // Employees
            Route::middleware('can:hr.employees.view')->group(function () {
                Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
                Route::get('employees/create', [EmployeeController::class, 'create'])->name('employees.create')->middleware('can:hr.employees.create');
                Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store')->middleware('can:hr.employees.create');
                Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
                Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit')->middleware('can:hr.employees.edit');
                Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update')->middleware('can:hr.employees.edit');
            });

            // Attendance
            Route::middleware('can:hr.attendance.view')->group(function () {
                Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
                Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store')->middleware('can:hr.attendance.manage');
                Route::get('attendance/summary', [AttendanceController::class, 'summary'])->name('attendance.summary');
            });

            // Leave
            Route::middleware('can:hr.leave.view')->group(function () {
                Route::get('leave', [LeaveController::class, 'index'])->name('leave.index');
                Route::get('leave/create', [LeaveController::class, 'create'])->name('leave.create')->middleware('can:hr.leave.create');
                Route::post('leave', [LeaveController::class, 'store'])->name('leave.store')->middleware('can:hr.leave.create');
                Route::post('leave/{leave}/approve', [LeaveController::class, 'approve'])->name('leave.approve')->middleware('can:hr.leave.approve');
                Route::post('leave/{leave}/reject', [LeaveController::class, 'reject'])->name('leave.reject')->middleware('can:hr.leave.approve');
            });

            // Payroll
            Route::middleware('can:hr.payroll.view')->group(function () {
                Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
                Route::post('payroll/process', [PayrollController::class, 'process'])->name('payroll.process')->middleware('can:hr.payroll.process');
                Route::post('payroll/approve', [PayrollController::class, 'approve'])->name('payroll.approve')->middleware('can:hr.payroll.process');
                Route::post('payroll/mark-paid', [PayrollController::class, 'markPaid'])->name('payroll.mark-paid')->middleware('can:hr.payroll.process');
                Route::get('payroll/{record}/payslip', [PayrollController::class, 'payslip'])->name('payroll.payslip');
            });
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
            Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store')->middleware('can:departments.create');
            Route::put('departments/{department}', [DepartmentController::class, 'update'])->name('departments.update')->middleware('can:departments.edit');
            Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy')->middleware('can:departments.delete');
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

            // Referral to another department
            Route::post('consultations/{visit}/refer', [ConsultationController::class, 'refer'])->name('consultations.refer')->middleware('can:consultations.create');

            // Send to investigation department
            Route::post('consultations/{visit}/investigation', [ConsultationController::class, 'sendToInvestigation'])->name('consultations.investigation')->middleware('can:consultations.create');

            // Consultation sub-resources (complaints, diagnoses, investigations, treatments, prescriptions)
            Route::middleware('can:consultations.create')->group(function () {
                Route::post('consultations/{visit}/complaints', [ConsultationController::class, 'storeComplaint'])->name('consultations.complaints.store');
                Route::delete('consultations/complaints/{complaint}', [ConsultationController::class, 'destroyComplaint'])->name('consultations.complaints.destroy');

                Route::post('consultations/{visit}/diagnoses', [ConsultationController::class, 'storeDiagnosis'])->name('consultations.diagnoses.store');
                Route::patch('consultations/diagnoses/{diagnosis}', [ConsultationController::class, 'updateDiagnosis'])->name('consultations.diagnoses.update');
                Route::patch('consultations/diagnoses/{diagnosis}/primary', [ConsultationController::class, 'setPrimaryDiagnosis'])->name('consultations.diagnoses.primary');
                Route::delete('consultations/diagnoses/{diagnosis}', [ConsultationController::class, 'destroyDiagnosis'])->name('consultations.diagnoses.destroy');

                Route::post('consultations/{visit}/investigations', [ConsultationController::class, 'storeInvestigation'])->name('consultations.investigations.store');
                Route::delete('consultations/investigations/{investigation}', [ConsultationController::class, 'destroyInvestigation'])->name('consultations.investigations.destroy');

                Route::post('consultations/{visit}/treatments', [ConsultationController::class, 'storeTreatment'])->name('consultations.treatments.store');
                Route::delete('consultations/treatments/{treatment}', [ConsultationController::class, 'destroyTreatment'])->name('consultations.treatments.destroy');
            });

            Route::get('departments/{department}/investigation-services', [ConsultationController::class, 'getDepartmentServices'])->name('departments.investigation-services');
            Route::get('departments/{department}/investigation-info', [ConsultationController::class, 'getDepartmentInvestigationInfo'])->name('departments.investigation-info');

            Route::post('consultations/{visit}/prescriptions', [ConsultationController::class, 'storePrescription'])->name('consultations.prescriptions.store')->middleware('can:prescriptions.create');
            Route::delete('consultations/prescriptions/{prescription}', [ConsultationController::class, 'destroyPrescription'])->name('consultations.prescriptions.destroy')->middleware('can:prescriptions.create');
            Route::post('consultations/{visit}/procedures', [ConsultationController::class, 'storeProcedureRequest'])->name('consultations.procedures.store')->middleware('can:consultations.create');
            Route::post('consultations/{visit}/lab-request', [ConsultationController::class, 'storeLabRequest'])->name('consultations.lab-request.store')->middleware('can:lab.requests.create');

            // Suggestion endpoints
            Route::get('consultations/suggest/complaints', [ConsultationController::class, 'suggestComplaints'])->name('consultations.suggest.complaints');
            Route::get('consultations/suggest/diagnoses', [ConsultationController::class, 'suggestDiagnoses'])->name('consultations.suggest.diagnoses');

            // Consultation Tasks
            Route::middleware('can:consultations.create')->group(function () {
                Route::post('consultations/{visit}/tasks', [ConsultationTaskController::class, 'store'])->name('consultations.tasks.store');
                Route::put('consultations/tasks/{task}', [ConsultationTaskController::class, 'update'])->name('consultations.tasks.update');
                Route::patch('consultations/tasks/{task}/toggle', [ConsultationTaskController::class, 'toggleComplete'])->name('consultations.tasks.toggle');
                Route::delete('consultations/tasks/{task}', [ConsultationTaskController::class, 'destroy'])->name('consultations.tasks.destroy');
            });
        });

        // Triage Workflow
        Route::middleware('can:vitals.view')->group(function () {
            Route::get('triage', [\App\Http\Controllers\Admin\TriageController::class, 'index'])->name('triage.index');
            Route::get('triage/{visit}', [\App\Http\Controllers\Admin\TriageController::class, 'show'])->name('triage.show');
            Route::get('triage/{visit}/assess', [\App\Http\Controllers\Admin\TriageController::class, 'create'])->name('triage.create')->middleware('can:vitals.create');
            Route::post('triage/{visit}', [\App\Http\Controllers\Admin\TriageController::class, 'store'])->name('triage.store')->middleware('can:vitals.create');
        });

        // Vitals (Nurse Triage)
        Route::middleware('can:vitals.view')->group(function () {
            Route::get('vitals', [VitalController::class, 'create'])->name('vitals.create');
            Route::post('vitals', [VitalController::class, 'store'])->name('vitals.store')->middleware('can:vitals.create');
            Route::get('vitals/{visit}', [VitalController::class, 'show'])->name('vitals.show');
            Route::patch('vitals/{visit}/update-priority', [VitalController::class, 'updatePriority'])->name('vitals.update-priority')->middleware('can:vitals.create');
            Route::patch('vitals/{visit}/assign-consultation', [VitalController::class, 'assignConsultation'])->name('vitals.assign-consultation')->middleware('can:vitals.create');
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

        // Investigation Catalogue (services from investigation-type departments + per-service headers/criteria)
        Route::prefix('investigation-catalogue')->name('investigation-catalogue.')->middleware('can:lab.tests.manage')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\InvestigationCatalogueController::class, 'index'])->name('index');
            Route::get('/{service}', [\App\Http\Controllers\Admin\InvestigationCatalogueController::class, 'show'])->name('show');

            Route::post('/{service}/headers', [\App\Http\Controllers\Admin\InvestigationCatalogueController::class, 'storeHeader'])->name('headers.store');
            Route::put('/headers/{header}', [\App\Http\Controllers\Admin\InvestigationCatalogueController::class, 'updateHeader'])->name('headers.update');
            Route::delete('/headers/{header}', [\App\Http\Controllers\Admin\InvestigationCatalogueController::class, 'destroyHeader'])->name('headers.destroy');

            Route::post('/{service}/criteria', [\App\Http\Controllers\Admin\InvestigationCatalogueController::class, 'storeCriterion'])->name('criteria.store');
            Route::put('/criteria/{criterion}', [\App\Http\Controllers\Admin\InvestigationCatalogueController::class, 'updateCriterion'])->name('criteria.update');
            Route::delete('/criteria/{criterion}', [\App\Http\Controllers\Admin\InvestigationCatalogueController::class, 'destroyCriterion'])->name('criteria.destroy');
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
                Route::get('drugs/search', [DrugController::class, 'search'])->name('drugs.search');
                Route::get('drugs/{drug}/history', [DrugController::class, 'history'])->name('drugs.history');
                Route::post('drugs', [DrugController::class, 'store'])->name('drugs.store');
                Route::put('drugs/{drug}', [DrugController::class, 'update'])->name('drugs.update');
                Route::patch('drugs/{drug}/toggle', [DrugController::class, 'toggle'])->name('drugs.toggle');

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
                Route::get('payments/receive', [PaymentController::class, 'receive'])->name('payments.receive')->middleware('can:payments.create');
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
            Route::post('services/{service}/prices', [ServiceCatalogController::class, 'storePrices'])->name('services.prices.store');
            Route::delete('services/{service}/prices/{price}', [ServiceCatalogController::class, 'deletePrice'])->name('services.prices.delete');
        });

        // Specialties
        Route::middleware('can:services.manage')->group(function () {
            Route::get('specialties', [\App\Http\Controllers\Admin\SpecialtyController::class, 'index'])->name('specialties.index');
            Route::post('specialties', [\App\Http\Controllers\Admin\SpecialtyController::class, 'store'])->name('specialties.store');
            Route::put('specialties/{specialty}', [\App\Http\Controllers\Admin\SpecialtyController::class, 'update'])->name('specialties.update');
            Route::patch('specialties/{specialty}/toggle', [\App\Http\Controllers\Admin\SpecialtyController::class, 'toggle'])->name('specialties.toggle');
        });

        // Reports
        Route::middleware('can:reports.view')->prefix('reports')->name('reports.')->group(function () {
            Route::get('income', [ReportController::class, 'income'])->name('income');
            Route::get('patients', [ReportController::class, 'patients'])->name('patients');
            Route::get('visits', [ReportController::class, 'visits'])->name('visits');
            Route::get('insurance-claims', [ReportController::class, 'insuranceClaims'])->name('insurance-claims');
            Route::get('nhis', fn () => redirect()->route('admin.reports.insurance-claims', request()->query()))->name('nhis');

            // Financial Reports
            Route::get('pharmacy-sales', [ReportController::class, 'pharmacySales'])->name('pharmacy-sales');
            Route::get('pharmacy-sales-summary', [ReportController::class, 'pharmacySalesSummary'])->name('pharmacy-sales-summary');
            Route::get('investigation-revenue', [ReportController::class, 'investigationRevenue'])->name('investigation-revenue');
            Route::get('daily-collection', [ReportController::class, 'dailyCollection'])->name('daily-collection');
            Route::get('claims', [ReportController::class, 'claims'])->name('claims');

            // Clinical Reports
            Route::get('consultation-stats', [ReportController::class, 'consultationStats'])->name('consultation-stats');
            Route::get('admissions', [ReportController::class, 'admissions'])->name('admissions');
            Route::get('discharges', [ReportController::class, 'discharges'])->name('discharges');

            // Patient Statement
            Route::get('statement-search', [ReportController::class, 'statementSearch'])->name('statement-search');
            Route::get('patient-statement/{patient}', [ReportController::class, 'patientStatement'])->name('patient-statement');

            // HR Reports
            Route::get('leave', [ReportController::class, 'leave'])->name('leave');
            Route::get('payroll', [ReportController::class, 'payroll'])->name('payroll');

            // Inventory Reports
            Route::get('stock-valuation', [ReportController::class, 'stockValuation'])->name('stock-valuation');
            Route::get('expired-stock', [ReportController::class, 'expiredStock'])->name('expired-stock');

            // Printable Documents
            Route::get('print-consultation/{record}', [ReportController::class, 'printConsultation'])->name('print-consultation');
            Route::get('print-lab-report/{labRequest}', [ReportController::class, 'printLabReport'])->name('print-lab-report');
            Route::get('print-prescription/{prescription}', [ReportController::class, 'printPrescription'])->name('print-prescription');
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

        // Modules Management (Admin)
        Route::middleware('can:modules.manage')->prefix('modules')->name('modules.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ModuleController::class, 'index'])->name('index');
            Route::post('{module}/toggle', [\App\Http\Controllers\Admin\ModuleController::class, 'toggle'])->name('toggle');
            Route::post('flush', [\App\Http\Controllers\Admin\ModuleController::class, 'flushCache'])->name('flush');
        });

        // ICD-10 Code Database
        Route::middleware('can:icd.manage')->group(function () {
            Route::get('icd-codes', [IcdCodeController::class, 'index'])->name('icd-codes.index');
            Route::get('icd-codes/search', [IcdCodeController::class, 'search'])->name('icd-codes.search');
            Route::post('icd-codes', [IcdCodeController::class, 'store'])->name('icd-codes.store');
            Route::put('icd-codes/{icdCode}', [IcdCodeController::class, 'update'])->name('icd-codes.update');
            Route::delete('icd-codes/{icdCode}', [IcdCodeController::class, 'destroy'])->name('icd-codes.destroy');
        });

        // ICD-10 search (accessible to doctors / clinicians)
        Route::get('icd-search', [IcdCodeController::class, 'search'])->name('icd-search')->middleware('can:consultations.view');

        // Procedure Catalog & Patient Procedures
        Route::middleware('can:procedures.view')->group(function () {
            Route::get('procedures', [ProcedureController::class, 'index'])->name('procedures.index');
            Route::get('procedures/schedule', [ProcedureController::class, 'schedule'])->name('procedures.schedule');
            Route::post('procedures', [ProcedureController::class, 'store'])->name('procedures.store')->middleware('can:procedures.create');
            Route::put('procedures/{procedure}', [ProcedureController::class, 'update'])->name('procedures.update')->middleware('can:procedures.edit');
            Route::patch('procedures/{procedure}/toggle', [ProcedureController::class, 'toggle'])->name('procedures.toggle')->middleware('can:procedures.edit');
            Route::post('procedures/schedule', [ProcedureController::class, 'storeSchedule'])->name('procedures.schedule.store')->middleware('can:procedures.create');
            Route::patch('procedures/{patientProcedure}/start', [ProcedureController::class, 'startProcedure'])->name('procedures.start')->middleware('can:procedures.create');
            Route::patch('procedures/{patientProcedure}/complete', [ProcedureController::class, 'completeProcedure'])->name('procedures.complete')->middleware('can:procedures.create');
            Route::patch('procedures/{patientProcedure}/cancel', [ProcedureController::class, 'cancelProcedure'])->name('procedures.cancel')->middleware('can:procedures.create');
        });

        // Investigation Items (Catalog + Stock for Lab/Radiology/Investigation departments)
        Route::prefix('investigations')->name('investigations.')->group(function () {
            // Item Catalog
            Route::middleware('can:lab.tests.manage')->group(function () {
                Route::get('items', [InvestigationItemController::class, 'index'])->name('items.index');
                Route::post('items', [InvestigationItemController::class, 'store'])->name('items.store');
                Route::put('items/{investigationItem}', [InvestigationItemController::class, 'update'])->name('items.update');
                Route::patch('items/{investigationItem}/toggle', [InvestigationItemController::class, 'toggle'])->name('items.toggle');
                Route::get('items/search', [InvestigationItemController::class, 'search'])->name('items.search');
            });

            // Stock Management
            Route::middleware('can:pharmacy.stock.manage')->group(function () {
                Route::get('stock', [InvestigationItemController::class, 'stock'])->name('stock.index');
                Route::post('stock', [InvestigationItemController::class, 'storeStock'])->name('stock.store');
                Route::put('stock/{stock}', [InvestigationItemController::class, 'updateStock'])->name('stock.update');
                Route::get('stock/available', [InvestigationItemController::class, 'getStock'])->name('stock.available');
            });
        });

        // Analyzer Integration (Lab Instruments)
        Route::middleware('can:analyzer.manage')->prefix('analyzers')->name('analyzers.')->group(function () {            Route::get('/', [AnalyzerController::class, 'index'])->name('index');
            Route::post('/', [AnalyzerController::class, 'store'])->name('store');
            Route::get('/diagnostics', [AnalyzerController::class, 'diagnostics'])->name('diagnostics');
            Route::get('/{analyzer}', [AnalyzerController::class, 'show'])->name('show');
            Route::put('/{analyzer}', [AnalyzerController::class, 'update'])->name('update');
            Route::patch('/{analyzer}/toggle', [AnalyzerController::class, 'toggle'])->name('toggle');
            Route::delete('/{analyzer}', [AnalyzerController::class, 'destroy'])->name('destroy');
            Route::post('/{analyzer}/mappings', [AnalyzerController::class, 'storeMapping'])->name('mappings.store');
            Route::put('/mappings/{mapping}', [AnalyzerController::class, 'updateMapping'])->name('mappings.update');
            Route::delete('/mappings/{mapping}', [AnalyzerController::class, 'destroyMapping'])->name('mappings.destroy');
            Route::post('/messages/{message}/reprocess', [AnalyzerController::class, 'reprocess'])->name('reprocess');
        });

        // Notifications
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('/recent', [NotificationController::class, 'recent'])->name('recent');
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('mark-read');
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
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
