<?php

use App\Http\Controllers\Admin\AccountCategoryController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdmissionController;
use App\Http\Controllers\Admin\AdmissionMedicationBoardController;
use App\Http\Controllers\Admin\AnalyzerController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\BloodBankDashboardController;
use App\Http\Controllers\Admin\BloodBankReportController;
use App\Http\Controllers\Admin\BloodCrossmatchController;
use App\Http\Controllers\Admin\BloodDonationController;
use App\Http\Controllers\Admin\BloodDonorController;
use App\Http\Controllers\Admin\BloodIssueController;
use App\Http\Controllers\Admin\BloodRequestController;
use App\Http\Controllers\Admin\BloodUnitController;
use App\Http\Controllers\Admin\CashierShiftController;
use App\Http\Controllers\Admin\ClaimController;
use App\Http\Controllers\Admin\ComplaintCatalogueController;
use App\Http\Controllers\Admin\ComplaintSearchController;
use App\Http\Controllers\Admin\ConsultationTaskController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DepartmentConsumablesController;
use App\Http\Controllers\Admin\DesignationController;
use App\Http\Controllers\Admin\DrugController;
use App\Http\Controllers\Admin\EmergencyContactController;
use App\Http\Controllers\Admin\EmergencyBayController;
use App\Http\Controllers\Admin\EmergencyBillingController;
use App\Http\Controllers\Admin\EmergencyBoardController;
use App\Http\Controllers\Admin\EmergencyCaseController;
use App\Http\Controllers\Admin\EmergencyConsumableController;
use App\Http\Controllers\Admin\EmergencyPatientIdentityController;
use App\Http\Controllers\Admin\EmergencyDispositionController;
use App\Http\Controllers\Admin\EmergencyInvestigationController;
use App\Http\Controllers\Admin\EmergencyMedicationBoardController;
use App\Http\Controllers\Admin\EmergencyMedicationController;
use App\Http\Controllers\Admin\EmergencyNoteController;
use App\Http\Controllers\Admin\EmergencyProcedureController;
use App\Http\Controllers\Admin\EmergencyReportController;
use App\Http\Controllers\Admin\EmergencyTriageController;
use App\Http\Controllers\Admin\EmergencyVitalsController;
use App\Http\Controllers\Admin\EmergencyTaskController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\FinancialEntryController;
use App\Http\Controllers\Admin\IcdCodeController;
use App\Http\Controllers\Admin\InsuranceProviderController;
use App\Http\Controllers\Admin\InsuranceTierController;
use App\Http\Controllers\Admin\InsuranceVerificationController;
use App\Http\Controllers\Admin\InvestigationCatalogueController;
use App\Http\Controllers\Admin\InvestigationItemController;
use App\Http\Controllers\Admin\LabTestController;
use App\Http\Controllers\Admin\LeaveController;
use App\Http\Controllers\Admin\MedicationAdministrationController;
use App\Http\Controllers\Admin\MedicationAdministrationReportController;
use App\Http\Controllers\Admin\MarChartController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\PatientComplaintController;
use App\Http\Controllers\Admin\PatientInsuranceController;
use App\Http\Controllers\Admin\PatientMergeController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\ProcedureCatalogueController;
use App\Http\Controllers\Admin\ProcedureConsumablesController;
use App\Http\Controllers\Admin\ProcedureController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductPricingController;
use App\Http\Controllers\Admin\ProductStockController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\LogRetentionController;
use App\Http\Controllers\Admin\NotificationBroadcastController;
use App\Http\Controllers\Admin\NotificationPreferenceController;
use App\Http\Controllers\Admin\OperationalReportController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\PurchaseReturnController;
use App\Http\Controllers\Admin\QueueController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ServiceCatalogController;
use App\Http\Controllers\Admin\ServiceRenderingActionController;
use App\Http\Controllers\Admin\ServiceRenderingController;
use App\Http\Controllers\Admin\ServiceRenderingReportController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SpecialtyController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\StockLocationController;
use App\Http\Controllers\Admin\StockRequisitionController;
use App\Http\Controllers\Admin\StockTransferController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\TriageController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VisitController;
use App\Http\Controllers\Admin\VisitDepartmentOptionsController;
use App\Http\Controllers\Admin\VisitPreviewController;
use App\Http\Controllers\Admin\VitalController;
use App\Http\Controllers\Admin\WardController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PaymentController;
use App\Http\Controllers\Billing\CreditNoteController;
use App\Http\Controllers\Billing\SponsorController;
use App\Http\Controllers\Billing\BillingReportController;
use App\Http\Controllers\Doctor\ConsultationController;
use App\Http\Controllers\Doctor\DashboardController as DoctorDashboardController;
use App\Http\Controllers\Doctor\MedicalPatternController;
use App\Http\Controllers\Doctor\PrescriptionController;
use App\Http\Controllers\Lab\LabRequestController;
use App\Http\Controllers\Lab\LabResultController;
use App\Http\Controllers\Pharmacy\DispensingController;
use App\Http\Controllers\StaffDashboardController;
use App\Http\Controllers\Theatre\TheatreController;
use App\Http\Controllers\Theatre\TheatreRoomController;
use App\Http\Controllers\Theatre\TheatreScheduleController;
use App\Models\Department;
use App\Models\User;
use App\Services\ProcedureRequestService;
use Illuminate\Support\Facades\Auth;
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
        /** @var User $user */
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
    Route::get('staff/dashboard', [StaffDashboardController::class, 'index'])
        ->name('staff.dashboard');

    /*
    |----------------------------------------------------------------------
    | Admin Routes
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->group(function () {

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Complaint catalogue and patient complaint endpoints
        Route::get('complaints/search', ComplaintSearchController::class)->name('complaints.search')->middleware('can:complaints.view');

        Route::middleware('can:complaints.catalogue.view')->group(function () {
            Route::get('complaints/catalogue', [ComplaintCatalogueController::class, 'index'])->name('complaints.catalogue.index');
            Route::post('complaints/catalogue', [ComplaintCatalogueController::class, 'store'])->name('complaints.catalogue.store')->middleware('can:complaints.catalogue.create');
            Route::patch('complaints/catalogue/{complaint}', [ComplaintCatalogueController::class, 'update'])->name('complaints.catalogue.update')->middleware('can:complaints.catalogue.update');
            Route::patch('complaints/catalogue/{complaint}/toggle', [ComplaintCatalogueController::class, 'toggle'])->name('complaints.catalogue.toggle')->middleware('can:complaints.catalogue.deactivate');
        });

        Route::middleware('can:complaints.view')->group(function () {
            Route::post('medical-records/{medicalRecord}/complaints', [PatientComplaintController::class, 'store'])->name('medical-records.complaints.store')->middleware('can:complaints.create');
            Route::patch('patient-complaints/{complaint}', [PatientComplaintController::class, 'update'])->name('patient-complaints.update');
            Route::delete('patient-complaints/{complaint}', [PatientComplaintController::class, 'destroy'])->name('patient-complaints.destroy');
        });

        // User Management
        Route::middleware('can:users.view')->group(function () {
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::get('users/create', [UserController::class, 'create'])->name('users.create')->middleware('can:users.create');
            Route::post('users', [UserController::class, 'store'])->name('users.store')->middleware('can:users.create');
            Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit')->middleware('can:users.edit');
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('can:users.edit');
            Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status')->middleware('can:users.disable');
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

        // Permissions Dashboard (read-only audit / catalogue)
        Route::middleware('can:permissions.view')->group(function () {
            Route::get('permissions', [\App\Http\Controllers\Admin\PermissionDashboardController::class, 'index'])->name('permissions.index');
            Route::post('permissions/refresh', [\App\Http\Controllers\Admin\PermissionDashboardController::class, 'refresh'])->name('permissions.refresh')->middleware('can:permissions.assign');
        });

        // Per-user direct permission overrides
        Route::middleware('can:permissions.assign')->group(function () {
            Route::get('users/{user}/permissions', [\App\Http\Controllers\Admin\UserPermissionController::class, 'edit'])->name('users.permissions.edit');
            Route::put('users/{user}/permissions', [\App\Http\Controllers\Admin\UserPermissionController::class, 'update'])->name('users.permissions.update');
        });

        // Patients
        Route::middleware('can:patients.view')->group(function () {
            Route::get('patients', [PatientController::class, 'index'])->name('patients.index');
            Route::get('patients/merge', [PatientMergeController::class, 'index'])->name('patients.merge.index')->middleware('can:patients.merge.view');
            Route::get('patients/merge/compare', [PatientMergeController::class, 'compare'])->name('patients.merge.compare')->middleware('can:patients.merge.request');
            Route::post('patients/merge/requests', [PatientMergeController::class, 'store'])->name('patients.merge.requests.store')->middleware('can:patients.merge.request');
            Route::get('patients/merge/requests/{mergeRequest}', [PatientMergeController::class, 'show'])->name('patients.merge.requests.show')->middleware('can:patients.merge.view');
            Route::post('patients/merge/requests/{mergeRequest}/execute', [PatientMergeController::class, 'execute'])->name('patients.merge.requests.execute')->middleware('can:patients.merge.execute');
            Route::get('patients/merge/logs', [PatientMergeController::class, 'logs'])->name('patients.merge.logs')->middleware('can:patients.merge.view');
            Route::get('patients/create', [PatientController::class, 'create'])->name('patients.create')->middleware('can:patients.create');
            Route::post('patients', [PatientController::class, 'store'])->name('patients.store')->middleware('can:patients.create');
            Route::get('patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
            Route::get('patients/{patient}/edit', [PatientController::class, 'edit'])->name('patients.edit')->middleware('can:patients.edit');
            Route::put('patients/{patient}', [PatientController::class, 'update'])->name('patients.update')->middleware('can:patients.edit');
            Route::patch('patients/{patient}/toggle-status', [PatientController::class, 'toggleStatus'])->name('patients.toggle-status')->middleware('can:patients.edit');
            Route::patch('patients/{patient}/mark-deceased', [PatientController::class, 'markDeceased'])->name('patients.mark-deceased')->middleware('can:patients.mark_deceased');

            // Patient Insurance Management
            Route::middleware(['module:insurance', 'can:patients.edit'])->group(function () {
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
        Route::get('insurance-providers/by-type', [PatientInsuranceController::class, 'providersByType'])->name('insurance-providers.by-type')->middleware('module:insurance');

        // Visits
        Route::middleware('can:visits.view')->group(function () {
            Route::get('visits', [VisitController::class, 'index'])->name('visits.index');
            Route::get('visits/create', [VisitController::class, 'create'])->name('visits.create')->middleware('can:visits.create');
            Route::post('visits', [VisitController::class, 'store'])->name('visits.store')->middleware('can:visits.create');
            Route::get('visits/patient-search', [VisitController::class, 'patientSearch'])->name('visits.patient-search');
            Route::get('visits/patient-insurances', [VisitController::class, 'patientInsurances'])->name('visits.patient-insurances')->middleware('module:insurance');
            Route::get('visits/department-services', [VisitController::class, 'departmentServices'])->name('visits.department-services');
            Route::get('departments/{department}/visit-options', [VisitDepartmentOptionsController::class, 'show'])->name('departments.visit-options');
            Route::get('visits/doctors-for-services', [VisitController::class, 'doctorsForServices'])->name('visits.doctors-for-services');
            Route::get('visits/services-for-doctor', [VisitController::class, 'servicesForDoctor'])->name('visits.services-for-doctor');
            Route::get('visits/service-price', [VisitController::class, 'servicePrice'])->name('visits.service-price');
            Route::get('visits/{visit}', [VisitController::class, 'show'])->name('visits.show');
            Route::get('visits/{visit}/mar-chart', [MarChartController::class, 'visit'])->name('visits.mar-chart')->middleware('can:mar_chart.view');
            Route::get('visits/{visit}/preview', [VisitPreviewController::class, 'show'])->name('visits.preview')->middleware('can:visits.preview');
            Route::get('visits/{visit}/edit', [VisitController::class, 'edit'])->name('visits.edit')->middleware('can:visits.edit');
            Route::put('visits/{visit}', [VisitController::class, 'update'])->name('visits.update')->middleware('can:visits.edit');
            Route::patch('visits/{visit}/insurance', [VisitController::class, 'updateInsurance'])->name('visits.insurance.update')->middleware(['module:insurance', 'can:visits.edit']);
            Route::patch('visits/{visit}/transition', [VisitController::class, 'transition'])->name('visits.transition')->middleware('can:visits.transition');
            Route::patch('visits/{visit}/send-to-department', [VisitController::class, 'sendToDepartment'])->name('visits.send-to-department')->middleware('can:visits.transition');
        });

        Route::prefix('service-renderings')
            ->name('service-renderings.')
            ->middleware('can:service_rendering.view')
            ->group(function () {
                Route::get('/', [ServiceRenderingController::class, 'index'])->name('index');
                Route::get('/reports', [ServiceRenderingReportController::class, 'index'])->name('reports')->middleware('can:service_rendering.reports');
                Route::get('/{serviceRendering}', [ServiceRenderingController::class, 'show'])->name('show');
                Route::post('/{serviceRendering}/start', [ServiceRenderingActionController::class, 'start'])->name('start')->middleware('can:service_rendering.start');
                Route::post('/{serviceRendering}/mark-rendered', [ServiceRenderingActionController::class, 'markRendered'])->name('mark-rendered')->middleware('can:service_rendering.mark_rendered');
                Route::post('/{serviceRendering}/mark-not-rendered', [ServiceRenderingActionController::class, 'markNotRendered'])->name('mark-not-rendered')->middleware('can:service_rendering.mark_not_rendered');
                Route::post('/{serviceRendering}/cancel', [ServiceRenderingActionController::class, 'cancel'])->name('cancel')->middleware('can:service_rendering.cancel');
                Route::patch('/{serviceRendering}/notes', [ServiceRenderingActionController::class, 'updateNotes'])->name('notes')->middleware('can:service_rendering.edit_notes');
            });

        // Blood Bank
        Route::prefix('blood-bank')
            ->name('blood-bank.')
            ->middleware(['module:blood_bank', 'can:blood_bank.view'])
            ->group(function () {
                Route::get('/', [BloodBankDashboardController::class, 'index'])->name('dashboard');
                Route::get('donors', [BloodDonorController::class, 'index'])->name('donors.index');
                Route::post('donors', [BloodDonorController::class, 'store'])->name('donors.store')->middleware('can:blood_bank.donors.manage');
                // WHO donor screening workflow
                Route::post('donors/{donor}/screening/questionnaire', [BloodDonorController::class, 'questionnaire'])->name('donors.screening.questionnaire')->middleware('can:blood_bank.screening.perform');
                Route::post('donors/{donor}/screening/assessment', [BloodDonorController::class, 'assessment'])->name('donors.screening.assessment')->middleware('can:blood_bank.screening.perform');
                Route::post('donors/{donor}/screening/eligibility', [BloodDonorController::class, 'eligibility'])->name('donors.screening.eligibility')->middleware('can:blood_bank.screening.perform');
                Route::get('donations', [BloodDonationController::class, 'index'])->name('donations.index');
                Route::post('donations', [BloodDonationController::class, 'store'])->name('donations.store')->middleware('can:blood_bank.donations.record');
                Route::post('donations/{donation}/tests', [BloodDonationController::class, 'recordTest'])->name('donations.tests.store')->middleware('can:blood_bank.screening.perform');
                Route::patch('donation-tests/{test}/verify', [BloodDonationController::class, 'verifyTest'])->name('donations.tests.verify')->middleware('can:blood_bank.screening.verify');
                Route::patch('donations/{donation}/screening', [BloodDonationController::class, 'updateScreening'])->name('donations.screening')->middleware('can:blood_bank.screening.manage');
                Route::get('units', [BloodUnitController::class, 'index'])->name('units.index');
                Route::patch('units/{unit}/discard', [BloodUnitController::class, 'discard'])->name('units.discard')->middleware('can:blood_bank.units.discard');
                Route::get('requests', [BloodRequestController::class, 'index'])->name('requests.index');
                Route::post('requests', [BloodRequestController::class, 'store'])->name('requests.store')->middleware('can:blood_bank.requests.create');
                Route::patch('requests/{bloodRequest}/recipient', [BloodRequestController::class, 'updateRecipient'])->name('requests.recipient.update')->middleware('can:blood_bank.requests.create');
                Route::patch('requests/{bloodRequest}/approve', [BloodRequestController::class, 'approve'])->name('requests.approve')->middleware('can:blood_bank.requests.approve');
                Route::post('requests/{bloodRequest}/crossmatches', [BloodCrossmatchController::class, 'store'])->name('requests.crossmatches.store')->middleware('can:blood_bank.crossmatch.perform');
                Route::patch('crossmatches/{crossmatch}/verify', [BloodCrossmatchController::class, 'verify'])->name('crossmatches.verify')->middleware('can:blood_bank.crossmatch.verify');
                Route::post('requests/{bloodRequest}/issues', [BloodIssueController::class, 'store'])->name('requests.issues.store')->middleware('can:blood_bank.units.issue');
                Route::patch('issues/{bloodIssue}/transfuse', [BloodIssueController::class, 'transfuse'])->name('issues.transfuse')->middleware('can:blood_bank.transfusions.record');
                Route::patch('issues/{bloodIssue}/reaction', [BloodIssueController::class, 'reaction'])->name('issues.reaction')->middleware('can:blood_bank.transfusions.record');
                Route::get('reports', [BloodBankReportController::class, 'index'])->name('reports.index')->middleware('can:blood_bank.reports.view');
            });

        // Wards & Beds
        Route::middleware('can:ward.view')->group(function () {
            Route::get('wards', [WardController::class, 'index'])->name('wards.index');
            Route::get('wards/consumables', [DepartmentConsumablesController::class, 'ward'])->name('wards.consumables.index');
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
            Route::get('admissions/requests', [AdmissionController::class, 'admissionRequests'])->name('admissions.requests');
            Route::get('admissions/medication-board', [AdmissionMedicationBoardController::class, 'index'])->name('admissions.medication-board')->middleware('can:admission.medication_board.view');
            Route::get('admissions', [AdmissionController::class, 'index'])->name('admissions.index');
            Route::get('admissions/create', [AdmissionController::class, 'create'])->name('admissions.create')->middleware('can:ward.admit');
            Route::post('admissions', [AdmissionController::class, 'store'])->name('admissions.store')->middleware('can:ward.admit');
            Route::get('admissions/{admission}/medications', [AdmissionMedicationBoardController::class, 'show'])->name('admissions.medications.show')->middleware('can:admission.medication_board.view');
            Route::get('admissions/{admission}/mar-chart', [MarChartController::class, 'admission'])->name('admissions.mar-chart')->middleware('can:admission.mar_chart.view');
            Route::get('admissions/{admission}', [AdmissionController::class, 'show'])->name('admissions.show');
            Route::get('admissions/{admission}/discharge', [AdmissionController::class, 'discharge'])->name('admissions.discharge')->middleware('can:ward.discharge');
            Route::post('admissions/{admission}/discharge', [AdmissionController::class, 'processDischarge'])->name('admissions.process-discharge')->middleware('can:ward.discharge');
            Route::post('admissions/{admission}/rounds', [AdmissionController::class, 'storeRound'])->name('admissions.rounds.store');
            Route::post('admissions/{admission}/vitals', [AdmissionController::class, 'storeVital'])->name('admissions.vitals.store');
            Route::post('admissions/{admission}/services', [AdmissionController::class, 'storeService'])->name('admissions.services.store');
        });

        Route::middleware('can:medication_administration.view')->group(function () {
            Route::get('emergency/medication-board', [EmergencyMedicationBoardController::class, 'index'])->name('emergency.medication-board')->middleware('can:emergency.medication_board.view');
            Route::get('emergency/{visit}/mar-chart', [MarChartController::class, 'emergency'])->name('emergency.mar-chart')->middleware('can:emergency.mar_chart.view');
            Route::get('medication-administration/reports', [MedicationAdministrationReportController::class, 'index'])->name('medication-administration.reports')->middleware('can:medication_administration.view_reports');
            Route::post('medication-administration/schedules/{schedule}/administer', [MedicationAdministrationController::class, 'administerSchedule'])->name('medication-administration.schedules.administer')->middleware('can:medication_administration.administer');
            Route::post('medication-administration/orders/{order}/prn', [MedicationAdministrationController::class, 'administerPrn'])->name('medication-administration.orders.prn')->middleware('can:medication_administration.administer');
            Route::post('medication-administration/orders/{order}/hold', [MedicationAdministrationController::class, 'holdOrder'])->name('medication-administration.orders.hold')->middleware('can:medication_orders.hold');
            Route::post('medication-administration/orders/{order}/stop', [MedicationAdministrationController::class, 'stopOrder'])->name('medication-administration.orders.stop')->middleware('can:medication_orders.stop');
            Route::patch('medication-administration/records/{administration}/correct', [MedicationAdministrationController::class, 'correct'])->name('medication-administration.records.correct')->middleware('can:medication_administration.correct');
        });

        // Emergency
        Route::prefix('emergency')->name('emergency.')->group(function () {
            Route::get('board', [EmergencyBoardController::class, 'index'])->name('board')->middleware('can:emergency.board.view');
            Route::get('consumables', [DepartmentConsumablesController::class, 'emergency'])->name('consumables.index')->middleware('can:emergency.board.view');
            Route::get('cases/create', [EmergencyCaseController::class, 'create'])->name('cases.create')->middleware('can:emergency.case.create');
            Route::post('cases', [EmergencyCaseController::class, 'store'])->name('cases.store')->middleware('can:emergency.case.create');
            Route::get('cases/{emergencyCase}', [EmergencyCaseController::class, 'show'])->name('cases.show')->middleware('can:emergency.case.view');
            Route::patch('cases/{emergencyCase}', [EmergencyCaseController::class, 'update'])->name('cases.update')->middleware('can:emergency.case.update');
            Route::post('cases/{emergencyCase}/confirm-identity', [EmergencyPatientIdentityController::class, 'store'])->name('cases.confirm-identity')->middleware('can:patients.merge.confirm_identity');
            Route::post('cases/{emergencyCase}/register-identity', [EmergencyPatientIdentityController::class, 'register'])->name('cases.register-identity')->middleware('can:patients.merge.confirm_identity');

            Route::get('cases/{emergencyCase}/triage', fn (\App\Models\EmergencyCase $emergencyCase) => redirect()->route('admin.emergency.cases.show', $emergencyCase))->name('triage.show');
            Route::post('cases/{emergencyCase}/triage', [EmergencyTriageController::class, 'store'])->name('triage.store')->middleware('can:emergency.triage.perform');
            Route::post('cases/{emergencyCase}/assign-bay', [EmergencyBayController::class, 'assign'])->name('bay.assign')->middleware('can:emergency.bay.assign');
            Route::post('cases/{emergencyCase}/assign-ward-bed', [EmergencyBayController::class, 'assignWardBed'])->name('bay.assign-ward-bed')->middleware('can:emergency.bay.assign');
            Route::post('cases/{emergencyCase}/vitals', [EmergencyVitalsController::class, 'store'])->name('vitals.store')->middleware('can:emergency.vitals.record');
            Route::post('cases/{emergencyCase}/notes', [EmergencyNoteController::class, 'store'])->name('notes.store')->middleware('can:emergency.notes.create');
            Route::post('cases/{emergencyCase}/medications', [EmergencyMedicationController::class, 'store'])->name('medications.store')->middleware('can:emergency.medication.administer');
            Route::post('cases/{emergencyCase}/investigations', [EmergencyInvestigationController::class, 'store'])->name('investigations.store')->middleware('can:emergency.investigation.request');
            Route::post('cases/{emergencyCase}/procedures', [EmergencyProcedureController::class, 'store'])->name('procedures.store')->middleware('can:emergency.procedure.request');
            Route::post('cases/{emergencyCase}/consumables', [EmergencyConsumableController::class, 'store'])->name('consumables.store')->middleware('can:emergency.consumables.use');
            Route::post('cases/{emergencyCase}/services', [EmergencyBillingController::class, 'storeService'])->name('services.store')->middleware('can:invoices.create');
            Route::post('cases/{emergencyCase}/disposition', [EmergencyDispositionController::class, 'store'])->name('disposition.store')->middleware('can:emergency.disposition.manage');
            Route::post('cases/{emergencyCase}/tasks', [EmergencyTaskController::class, 'store'])->name('tasks.store')->middleware('can:emergency.case.update');
            Route::patch('cases/{emergencyCase}/tasks/{task}/complete', [EmergencyTaskController::class, 'complete'])->name('tasks.complete')->middleware('can:emergency.case.update');

            Route::get('bays', [EmergencyBayController::class, 'index'])->name('bays.index')->middleware('can:emergency.settings.manage');
            Route::post('bays', [EmergencyBayController::class, 'store'])->name('bays.store')->middleware('can:emergency.settings.manage');
            Route::get('reports', [EmergencyReportController::class, 'index'])->name('reports.index')->middleware('can:emergency.reports.view');
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
        Route::middleware(['module:insurance', 'can:claims.view'])->group(function () {
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
        Route::middleware(['module:insurance', 'module:claims', 'can:claims.view'])->group(function () {
            Route::get('claims', [ClaimController::class, 'index'])->name('claims.index');
            Route::get('claims/eligible-visits', [ClaimController::class, 'eligibleVisits'])->name('claims.eligible-visits');
            Route::post('claims/visits/{visit}/prepare', [ClaimController::class, 'prepareFromVisit'])->name('claims.prepare-from-visit')->middleware('can:claims.create');
            Route::get('claims/nhia', [ClaimController::class, 'nhiaIndex'])->name('claims.nhia.index');
            Route::get('claims/nhia/eligible-visits', [ClaimController::class, 'nhiaEligibleVisits'])->name('claims.nhia.eligible-visits');
            Route::post('claims/nhia/visits/{visit}/prepare', [ClaimController::class, 'prepareFromVisit'])->name('claims.nhia.prepare-from-visit')->middleware('can:claims.create');
            Route::get('claims/create', [ClaimController::class, 'create'])->name('claims.create')->middleware('can:claims.create');
            Route::post('claims', [ClaimController::class, 'store'])->name('claims.store')->middleware('can:claims.create');
            Route::post('claims/from-invoice', [ClaimController::class, 'storeFromInvoice'])->name('claims.store-from-invoice')->middleware('can:claims.create');
            Route::get('claims/export', [ClaimController::class, 'export'])->name('claims.export')->middleware('can:claims.export');
            Route::get('claims/{claim}', [ClaimController::class, 'show'])->name('claims.show');
            Route::post('claims/{claim}/verification-code', [ClaimController::class, 'updateVerificationCode'])->name('claims.verification-code')->middleware('can:claims.create');
            Route::post('claims/{claim}/validate', [ClaimController::class, 'validateClaim'])->name('claims.validate')->middleware('can:claims.create');
            Route::post('claims/{claim}/mark-ready', [ClaimController::class, 'markReady'])->name('claims.mark-ready')->middleware('can:claims.create');
            Route::post('claims/{claim}/submit', [ClaimController::class, 'submit'])->name('claims.submit')->middleware('can:claims.create');
            Route::get('claims/{claim}/export', [ClaimController::class, 'exportClaim'])->name('claims.export-one')->middleware('can:claims.export');
            Route::get('claims/{claim}/review', [ClaimController::class, 'review'])->name('claims.review')->middleware('can:claims.approve');
            Route::post('claims/{claim}/review-item/{item}', [ClaimController::class, 'reviewItem'])->name('claims.review-item')->middleware('can:claims.approve');
            Route::post('claims/{claim}/complete-review', [ClaimController::class, 'completeReview'])->name('claims.complete-review')->middleware('can:claims.approve');
            Route::post('claims/{claim}/mark-paid', [ClaimController::class, 'markPaid'])->name('claims.mark-paid')->middleware('can:claims.approve');
            Route::post('claims/{claim}/payments', [ClaimController::class, 'recordPayment'])->name('claims.payments.store')->middleware('can:claims.approve');
            Route::post('claims/{claim}/appeal', [ClaimController::class, 'appeal'])->name('claims.appeal')->middleware('can:claims.create');
            Route::post('claims/{claim}/add-item', [ClaimController::class, 'addItem'])->name('claims.add-item')->middleware('can:claims.create');
            Route::delete('claims/remove-item/{item}', [ClaimController::class, 'removeItem'])->name('claims.remove-item')->middleware('can:claims.create');
        });

        // Store & Procurement
        Route::prefix('store')->name('store.')->middleware('module:inventory')->group(function () {
            // Suppliers
            Route::middleware('can:store.purchase.view')->group(function () {
                Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
                Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store')->middleware('can:store.purchase.create');
                Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update')->middleware('can:store.purchase.create');
                Route::patch('suppliers/{supplier}/toggle', [SupplierController::class, 'toggle'])->name('suppliers.toggle')->middleware('can:store.purchase.create');

                Route::get('suppliers/{supplier}/ledger', [SupplierController::class, 'ledger'])->name('suppliers.ledger');
                Route::post('suppliers/{supplier}/ledger', [SupplierController::class, 'recordLedgerEntry'])
                    ->name('suppliers.ledger.store')->middleware('can:store.purchase.create');
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

            // Purchase Returns
            Route::middleware('can:store.return.view')->group(function () {
                Route::get('purchase-returns', [PurchaseReturnController::class, 'index'])->name('purchase-returns.index');
                Route::get('purchase-returns/create', [PurchaseReturnController::class, 'create'])->name('purchase-returns.create')->middleware('can:store.return.create');
                Route::post('purchase-returns', [PurchaseReturnController::class, 'store'])->name('purchase-returns.store')->middleware('can:store.return.create');
                Route::get('purchase-returns/{purchaseReturn}', [PurchaseReturnController::class, 'show'])->name('purchase-returns.show');
                Route::post('purchase-returns/{purchaseReturn}/approve', [PurchaseReturnController::class, 'approve'])->name('purchase-returns.approve')->middleware('can:store.return.approve');
                Route::post('purchase-returns/{purchaseReturn}/post', [PurchaseReturnController::class, 'post'])->name('purchase-returns.post')->middleware('can:store.return.create');
                Route::post('purchase-returns/{purchaseReturn}/cancel', [PurchaseReturnController::class, 'cancel'])->name('purchase-returns.cancel')->middleware('can:store.return.create');
            });

            // Department Stock Requisitions
            Route::middleware('can:store.requisition.view')->group(function () {
                Route::get('stock-requisitions', [StockRequisitionController::class, 'index'])->name('stock-requisitions.index');
                Route::get('stock-requisitions/create', [StockRequisitionController::class, 'create'])->name('stock-requisitions.create')->middleware('can:store.requisition.create');
                Route::post('stock-requisitions', [StockRequisitionController::class, 'store'])->name('stock-requisitions.store')->middleware('can:store.requisition.create');
                Route::get('stock-requisitions/{stockRequisition}', [StockRequisitionController::class, 'show'])->name('stock-requisitions.show');
                Route::post('stock-requisitions/{stockRequisition}/approve', [StockRequisitionController::class, 'approve'])->name('stock-requisitions.approve')->middleware('can:store.requisition.approve');
                Route::post('stock-requisitions/{stockRequisition}/issue', [StockRequisitionController::class, 'issue'])->name('stock-requisitions.issue')->middleware('can:store.requisition.issue');
                Route::post('stock-requisitions/{stockRequisition}/acknowledge', [StockRequisitionController::class, 'acknowledge'])->name('stock-requisitions.acknowledge')->middleware('can:store.requisition.acknowledge');
                Route::post('stock-requisitions/{stockRequisition}/cancel', [StockRequisitionController::class, 'cancel'])->name('stock-requisitions.cancel')->middleware('can:store.requisition.create');
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

            // Stock Movement Ledger / Balances / Adjustments / Returns / Locations
            Route::middleware('can:store.purchase.view')->group(function () {
                Route::get('stock/balances', [StockController::class, 'balances'])->name('stock.balances');
                Route::get('stock/ledger', [StockController::class, 'ledger'])->name('stock.ledger');

                Route::get('stock/locations', [StockController::class, 'locations'])->name('stock.locations.index');
                Route::post('stock/locations', [StockController::class, 'storeLocation'])
                    ->name('stock.locations.store')->middleware('can:store.purchase.create');
                Route::put('stock/locations/{location}', [StockController::class, 'updateLocation'])
                    ->name('stock.locations.update')->middleware('can:store.purchase.create');

                Route::middleware('can:store.purchase.create')->group(function () {
                    Route::get('stock/adjustments/create', [StockController::class, 'adjustmentForm'])->name('stock.adjustments.create');
                    Route::post('stock/adjustments', [StockController::class, 'storeAdjustment'])->name('stock.adjustments.store');

                    Route::get('stock/returns/create', [StockController::class, 'returnForm'])->name('stock.returns.create');
                    Route::post('stock/returns', [StockController::class, 'storeReturn'])->name('stock.returns.store');
                });
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
        Route::prefix('hr')->name('hr.')->middleware('module:hr')->group(function () {
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
            Route::middleware(['module:payroll', 'can:hr.payroll.view'])->group(function () {
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
        Route::middleware(['module:consultation', 'can:consultations.view'])->group(function () {
            Route::get('consultations', [ConsultationController::class, 'index'])->name('consultations.index');
            Route::get('consultations/{visit}', [ConsultationController::class, 'show'])->name('consultations.show');
            Route::get('consultations/{visit}/routes/{route}', [ConsultationController::class, 'show'])->name('consultations.routes.show');
            Route::get('consultations/{visit}/history', [ConsultationController::class, 'history'])->name('consultations.history');
            Route::patch('consultations/{visit}/transition', [ConsultationController::class, 'transitionVisit'])->name('consultations.transition')->middleware('can:visits.transition');
            Route::post('consultations/{visit}/start', [ConsultationController::class, 'startConsultation'])->name('consultations.start')->middleware('can:consultations.create');
            Route::post('consultations/{visit}/routes', [ConsultationController::class, 'storeRoute'])->name('consultations.routes.store')->middleware('can:consultations.create');
            Route::post('consultations/{visit}/routes/{route}/activate', [ConsultationController::class, 'activateRoute'])->name('consultations.routes.activate')->middleware('can:consultations.create');
            Route::post('consultations/{visit}/routes/{route}/complete', [ConsultationController::class, 'completeRoute'])->name('consultations.routes.complete')->middleware('can:consultations.create');
            Route::post('consultations/{visit}/routes/{route}/cancel', [ConsultationController::class, 'cancelRoute'])->name('consultations.routes.cancel')->middleware('can:consultations.create');
            Route::post('consultations/{visit}/routes/{route}/follow-up-appointments', [ConsultationController::class, 'storeFollowUpAppointment'])->name('consultations.routes.follow-up.store')->middleware('can:consultation.followup.create');
            Route::put('consultations/{visit}/routes/{route}/follow-up-appointments/{appointment}', [ConsultationController::class, 'updateFollowUpAppointment'])->name('consultations.routes.follow-up.update')->middleware('can:consultation.followup.update');
            Route::post('consultations/{visit}/routes/{route}/follow-up-appointments/{appointment}/cancel', [ConsultationController::class, 'cancelFollowUpAppointment'])->name('consultations.routes.follow-up.cancel')->middleware('can:consultation.followup.cancel');
            Route::post('consultations/{visit}/routes/{route}/next-patient/open', [ConsultationController::class, 'openNextPatient'])->name('consultations.routes.next-patient.open')->middleware('can:consultations.create');
            Route::post('consultations/{visit}/routes/{route}/next-patient/complete-and-open', [ConsultationController::class, 'completeAndOpenNextPatient'])->name('consultations.routes.next-patient.complete-open')->middleware('can:consultations.create');

            // Referral to another department
            Route::post('consultations/{visit}/refer', [ConsultationController::class, 'refer'])->name('consultations.refer')->middleware('can:consultations.create');

            // Send to investigation department
            Route::post('consultations/{visit}/investigation', [ConsultationController::class, 'sendToInvestigation'])->name('consultations.investigation')->middleware('can:consultations.create');

            // Consultation sub-resources (complaints, diagnoses, investigations, treatments, prescriptions)
            Route::middleware('can:consultations.create')->group(function () {
                Route::post('consultations/{visit}/complaints', [ConsultationController::class, 'storeComplaint'])->name('consultations.complaints.store');
                Route::patch('consultations/complaints/{complaint}', [ConsultationController::class, 'updateComplaint'])->name('consultations.complaints.update');
                Route::delete('consultations/complaints/{complaint}', [ConsultationController::class, 'destroyComplaint'])->name('consultations.complaints.destroy');

                Route::post('consultations/{visit}/history-of-presenting-complaints', [ConsultationController::class, 'storeHistoryOfPresentingComplaint'])->name('consultations.hopc.store');
                Route::patch('consultations/history-of-presenting-complaints/{hopc}', [ConsultationController::class, 'updateHistoryOfPresentingComplaint'])->name('consultations.hopc.update');
                Route::delete('consultations/history-of-presenting-complaints/{hopc}', [ConsultationController::class, 'destroyHistoryOfPresentingComplaint'])->name('consultations.hopc.destroy');

                Route::post('consultations/{visit}/examinations', [ConsultationController::class, 'storeExamination'])->name('consultations.examinations.store');
                Route::patch('consultations/examinations/{examination}', [ConsultationController::class, 'updateExamination'])->name('consultations.examinations.update');
                Route::delete('consultations/examinations/{examination}', [ConsultationController::class, 'destroyExamination'])->name('consultations.examinations.destroy');

                Route::post('consultations/{visit}/diagnoses', [ConsultationController::class, 'storeDiagnosis'])->name('consultations.diagnoses.store');
                Route::patch('consultations/diagnoses/{diagnosis}', [ConsultationController::class, 'updateDiagnosis'])->name('consultations.diagnoses.update');
                Route::patch('consultations/diagnoses/{diagnosis}/primary', [ConsultationController::class, 'setPrimaryDiagnosis'])->name('consultations.diagnoses.primary');
                Route::delete('consultations/diagnoses/{diagnosis}', [ConsultationController::class, 'destroyDiagnosis'])->name('consultations.diagnoses.destroy');

                Route::post('consultations/{visit}/investigations', [ConsultationController::class, 'storeInvestigation'])->name('consultations.investigations.store');
                Route::patch('consultations/investigations/{investigation}', [ConsultationController::class, 'updateInvestigation'])->name('consultations.investigations.update');
                Route::delete('consultations/investigations/{investigation}', [ConsultationController::class, 'destroyInvestigation'])->name('consultations.investigations.destroy');
                Route::delete('consultations/investigation-items/{item}', [ConsultationController::class, 'destroyInvestigationItem'])->name('consultations.investigation-items.destroy');

                Route::post('consultations/{visit}/treatments', [ConsultationController::class, 'storeTreatment'])->name('consultations.treatments.store');
                Route::patch('consultations/treatments/{treatment}', [ConsultationController::class, 'updateTreatment'])->name('consultations.treatments.update');
                Route::delete('consultations/treatments/{treatment}', [ConsultationController::class, 'destroyTreatment'])->name('consultations.treatments.destroy');
            });

            Route::get('consultations/{visit}/summary-fragment', [ConsultationController::class, 'summaryFragment'])->name('consultations.summary-fragment');

            Route::get('departments/{department}/investigation-services', [ConsultationController::class, 'getDepartmentServices'])->name('departments.investigation-services');
            Route::get('departments/{department}/investigation-info', [ConsultationController::class, 'getDepartmentInvestigationInfo'])->name('departments.investigation-info');

            Route::post('consultations/{visit}/prescriptions', [ConsultationController::class, 'storePrescription'])->name('consultations.prescriptions.store')->middleware('can:prescriptions.create');
            Route::patch('consultations/prescriptions/{prescription}', [ConsultationController::class, 'updatePrescription'])->name('consultations.prescriptions.update')->middleware('can:prescriptions.create');
            Route::delete('consultations/prescriptions/{prescription}', [ConsultationController::class, 'destroyPrescription'])->name('consultations.prescriptions.destroy')->middleware('can:prescriptions.create');
            Route::post('consultations/{visit}/procedures', [ConsultationController::class, 'storeProcedureRequest'])->name('consultations.procedures.store')->middleware('can:procedure.request');
            Route::patch('consultations/procedures/{procedureRequest}', [ConsultationController::class, 'updateProcedureRequest'])->name('consultations.procedures.update')->middleware('can:procedure.request');
            Route::post('consultations/{visit}/lab-request', [ConsultationController::class, 'storeLabRequest'])->name('consultations.lab-request.store')->middleware('can:lab.requests.create');
            Route::patch('consultations/lab-requests/{labRequest}', [ConsultationController::class, 'updateLabRequest'])->name('consultations.lab-request.update')->middleware('can:lab.requests.create');

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
            Route::get('triage', [TriageController::class, 'index'])->name('triage.index');
            Route::get('triage/{visit}', [TriageController::class, 'show'])->name('triage.show');
            Route::get('triage/{visit}/assess', [TriageController::class, 'create'])->name('triage.create')->middleware('can:vitals.create');
            Route::post('triage/{visit}', [TriageController::class, 'store'])->name('triage.store')->middleware('can:vitals.create');
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
        Route::middleware(['module:medical-patterns', 'can:consultations.view'])->group(function () {
            Route::get('patterns', [MedicalPatternController::class, 'index'])->name('patterns.index');
            Route::get('patterns/create', [MedicalPatternController::class, 'create'])->name('patterns.create')->middleware('can:consultations.create');
            Route::post('patterns', [MedicalPatternController::class, 'store'])->name('patterns.store')->middleware('can:consultations.create');
            Route::get('patterns/suggest', [MedicalPatternController::class, 'suggest'])->name('patterns.suggest');
            Route::get('patterns/{pattern}', [MedicalPatternController::class, 'show'])->name('patterns.show');
            Route::get('patterns/{pattern}/edit', [MedicalPatternController::class, 'edit'])->name('patterns.edit')->middleware('can:consultations.create');
            Route::put('patterns/{pattern}', [MedicalPatternController::class, 'update'])->name('patterns.update')->middleware('can:consultations.create');
            Route::patch('patterns/{pattern}/toggle', [MedicalPatternController::class, 'toggleActive'])->name('patterns.toggle')->middleware('can:consultations.create');
            Route::delete('patterns/{pattern}', [MedicalPatternController::class, 'destroy'])->name('patterns.destroy')->middleware('can:consultations.create');
            Route::post('patterns/{pattern}/apply', [MedicalPatternController::class, 'apply'])->name('patterns.apply')->middleware('can:consultations.create');
            Route::post('patterns/from-record/{visit}', [MedicalPatternController::class, 'storeFromRecord'])->name('patterns.from-record')->middleware('can:consultations.create');
        });

        // Laboratory
        Route::prefix('lab')->name('lab.')->middleware('module:investigations')->group(function () {
            // Lab Requests
            Route::middleware('can:lab.requests.view')->group(function () {
                Route::get('requests', [LabRequestController::class, 'index'])->name('requests.index');
                Route::get('requests/{labRequest}', [LabRequestController::class, 'show'])->name('requests.show');
                Route::patch('requests/{labRequest}/accept', [LabRequestController::class, 'accept'])->name('requests.accept')->middleware('can:lab.results.create');
                Route::post('requests/{labRequest}/accept-selected', [LabRequestController::class, 'acceptSelected'])->name('requests.accept-selected')->middleware('can:lab.results.create');
                Route::patch('requests/{labRequest}/cancel', [LabRequestController::class, 'cancel'])->name('requests.cancel')->middleware('can:lab.results.create');
            });

            // Lab Results
            Route::middleware('can:lab.results.view')->group(function () {
                Route::get('results', [LabResultController::class, 'index'])->name('results.index');
                Route::post('results/{item}', [LabResultController::class, 'store'])->name('results.store')->middleware('can:lab.results.create');
                Route::post('results/batch/{labRequest}', [LabResultController::class, 'batchStore'])->name('results.batch')->middleware('can:lab.results.create');
                Route::patch('results/{result}/verify', [LabResultController::class, 'verify'])->name('results.verify')->middleware('can:lab.results.verify');
                Route::get('results/{item}/view', [LabResultController::class, 'view'])->name('results.view');
                Route::get('results/{item}/print', [LabResultController::class, 'print'])->name('results.print');
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
        Route::prefix('investigation-catalogue')->name('investigation-catalogue.')->middleware(['module:investigations', 'can:lab.tests.manage'])->group(function () {
            Route::get('/', [InvestigationCatalogueController::class, 'index'])->name('index');
            Route::get('/{service}', [InvestigationCatalogueController::class, 'show'])->name('show');

            Route::post('/{service}/headers', [InvestigationCatalogueController::class, 'storeHeader'])->name('headers.store');
            Route::put('/headers/{header}', [InvestigationCatalogueController::class, 'updateHeader'])->name('headers.update');
            Route::delete('/headers/{header}', [InvestigationCatalogueController::class, 'destroyHeader'])->name('headers.destroy');

            Route::post('/{service}/criteria', [InvestigationCatalogueController::class, 'storeCriterion'])->name('criteria.store');
            Route::put('/criteria/{criterion}', [InvestigationCatalogueController::class, 'updateCriterion'])->name('criteria.update');
            Route::delete('/criteria/{criterion}', [InvestigationCatalogueController::class, 'destroyCriterion'])->name('criteria.destroy');

            // Default consumables
            Route::post('/{service}/consumables', [InvestigationCatalogueController::class, 'storeConsumable'])
                ->name('consumables.store')->middleware('can:service_consumable.manage');
            Route::delete('/{service}/consumables/{product}', [InvestigationCatalogueController::class, 'destroyConsumable'])
                ->name('consumables.destroy')->middleware('can:service_consumable.manage');
        });

        // Pharmacy
        Route::prefix('pharmacy')->name('pharmacy.')->middleware('module:pharmacy')->group(function () {
            // Dispensing
            Route::middleware('can:pharmacy.dispensing.view')->group(function () {
                Route::get('dispensing', [DispensingController::class, 'index'])->name('dispensing.index');
                Route::get('dispensing/{prescription}', [DispensingController::class, 'show'])->name('dispensing.show');
                Route::post('dispensing/{prescription}/bill-selected', [DispensingController::class, 'billSelected'])->name('dispensing.bill-selected')->middleware('can:pharmacy.dispensing.create');
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

            // Legacy per-batch Drug Stock UI removed. Stock is now managed via
            // admin.store.stock.* (single source of truth backed by stock_movements
            // and stock_balances). Batch records (DrugStock model) remain as an
            // internal table written by PO receive and consumed by dispense FEFO.
        });

        // Billing
        Route::prefix('billing')->name('billing.')->middleware('module:billing')->group(function () {
            // Billing dashboard
            Route::get('dashboard', [BillingReportController::class, 'dashboard'])
                ->name('dashboard')->middleware('can:invoices.view');

            // Invoices
            Route::middleware('can:invoices.view')->group(function () {
                Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
                Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create')->middleware('can:invoices.create');
                Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store')->middleware('can:invoices.create');
                Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
                Route::get('invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit')->middleware('can:invoices.edit');
                Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update')->middleware('can:invoices.edit');
                Route::patch('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel')->middleware('can:invoices.void');
                Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
                Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
                Route::post('invoices/{invoice}/items/{item}/discount', [InvoiceController::class, 'applyItemDiscount'])
                    ->name('invoices.items.discount')
                    ->middleware('can:billing.discount.apply');
                Route::delete('invoices/{invoice}/items/{item}/discount', [InvoiceController::class, 'removeItemDiscount'])
                    ->name('invoices.items.discount.remove')
                    ->middleware('can:billing.discount.remove');
            });

            // Payments
            Route::middleware('can:payments.view')->group(function () {
                Route::get('payments/receive', [PaymentController::class, 'receive'])->name('payments.receive')->middleware('can:payments.create');
                Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
                Route::post('payments/{invoice}', [PaymentController::class, 'store'])->name('payments.store')->middleware('can:payments.create');
                Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
                Route::get('payments/{payment}/receipt-pdf', [PaymentController::class, 'receiptPdf'])->name('payments.receipt-pdf');
                Route::post('payments/{payment}/reverse', [PaymentController::class, 'reverse'])
                    ->name('payments.reverse')->middleware('can:payments.refund');
            });

            // Credit notes & write-offs
            Route::middleware('can:credit_notes.view')->prefix('credit-notes')->name('credit-notes.')->group(function () {
                Route::get('/', [CreditNoteController::class, 'index'])->name('index');
                Route::get('create', [CreditNoteController::class, 'create'])->name('create')->middleware('can:credit_notes.create');
                Route::get('available', [CreditNoteController::class, 'available'])->name('available');
                Route::post('/', [CreditNoteController::class, 'store'])->name('store')->middleware('can:credit_notes.create');
                Route::patch('{creditNote}/cancel', [CreditNoteController::class, 'cancel'])->name('cancel')->middleware('can:credit_notes.create');
            });

            // Corporate sponsors
            Route::middleware('can:sponsors.manage')->prefix('sponsors')->name('sponsors.')->group(function () {
                Route::get('/', [SponsorController::class, 'index'])->name('index');
                Route::post('/', [SponsorController::class, 'store'])->name('store');
                Route::put('{sponsor}', [SponsorController::class, 'update'])->name('update');
                Route::patch('{sponsor}/toggle', [SponsorController::class, 'toggle'])->name('toggle');
            });

            // Billing reports (AR aging & statements)
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::middleware('can:invoices.view')->group(function () {
                    Route::get('aging', [BillingReportController::class, 'aging'])->name('aging');
                    Route::get('aging/pdf', [BillingReportController::class, 'agingPdf'])->name('aging.pdf');
                });
                Route::get('discounts', [BillingReportController::class, 'discounts'])
                    ->name('discounts')
                    ->middleware('can:billing.discount.report');
            });
            Route::middleware('can:invoices.view')->prefix('statements')->name('statements.')->group(function () {
                Route::get('/', [BillingReportController::class, 'statements'])->name('index');
                Route::get('{patient}', [BillingReportController::class, 'statementShow'])->name('show');
                Route::get('{patient}/pdf', [BillingReportController::class, 'statementPdf'])->name('pdf');
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
            Route::get('specialties', [SpecialtyController::class, 'index'])->name('specialties.index');
            Route::post('specialties', [SpecialtyController::class, 'store'])->name('specialties.store');
            Route::put('specialties/{specialty}', [SpecialtyController::class, 'update'])->name('specialties.update');
            Route::patch('specialties/{specialty}/toggle', [SpecialtyController::class, 'toggle'])->name('specialties.toggle');
        });

        // Statistical Reports / Analytics — per-page permission is enforced in the
        // controller so a user holding only a single statistics.*.view (e.g.
        // staff_performance) can still reach that page.
        Route::middleware('module:reports')->prefix('statistics')->name('statistics.')->group(function () {
            Route::get('/', [StatisticsController::class, 'dashboard'])->name('dashboard');
            foreach ([
                'activity', 'diagnoses', 'complaints', 'consultations', 'pharmacy',
                'investigations', 'procedures', 'emergency', 'admission', 'mar',
                'billing', 'claims', 'stock', 'blood-bank', 'staff-performance',
            ] as $statisticReport) {
                Route::get($statisticReport, [StatisticsController::class, 'show'])
                    ->defaults('report', $statisticReport)
                    ->name($statisticReport);
            }
        });

        // Reports
        Route::middleware(['module:reports', 'can:reports.view'])->prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [OperationalReportController::class, 'dashboard'])->name('dashboard');
            foreach ([
                'consultations',
                'diagnoses',
                'complaints',
                'pharmacy',
                'investigations',
                'procedures',
                'theatre',
                'emergency',
                'admission',
                'mar',
                'billing',
                'stock',
                'blood-bank',
            ] as $operationalReport) {
                Route::get($operationalReport, [OperationalReportController::class, 'show'])
                    ->defaults('report', $operationalReport)
                    ->name($operationalReport)
                    ->middleware('can:reports.'.str_replace('-', '_', $operationalReport));
            }

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
            Route::get('ward', [SettingsController::class, 'ward'])->name('ward');
            Route::put('ward', [SettingsController::class, 'updateWard'])->name('ward.update');
            Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log');
        });

        // Activity logs (dedicated viewer with permission scoping)
        Route::prefix('logs')->name('logs.')->group(function () {
            Route::get('/', [ActivityLogController::class, 'index'])
                ->middleware('can:logs.view')
                ->name('index');
            Route::get('export', [ActivityLogController::class, 'export'])
                ->middleware('can:logs.export')
                ->name('export');
            Route::get('retention', [LogRetentionController::class, 'index'])
                ->middleware('can:logs.manage_retention')
                ->name('retention.index');
            Route::put('retention', [LogRetentionController::class, 'update'])
                ->middleware('can:logs.manage_retention')
                ->name('retention.update');
            Route::get('{activityLog}', [ActivityLogController::class, 'show'])
                ->middleware('can:logs.view')
                ->name('show');
        });

        // Modules Management (Admin)
        Route::middleware('can:modules.manage')->prefix('modules')->name('modules.')->group(function () {
            Route::get('/', [ModuleController::class, 'index'])->name('index');
            Route::post('{module}/toggle', [ModuleController::class, 'toggle'])->name('toggle');
            Route::post('flush', [ModuleController::class, 'flushCache'])->name('flush');
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

        // ── Theatre / Procedure Workflow ───────────────────────────────
        Route::prefix('theatre')->name('theatre.')->group(function () {
            Route::middleware('can:theatre.rooms.view')->group(function () {
                Route::get('rooms', [TheatreRoomController::class, 'index'])->name('rooms.index');
            });
            Route::middleware('can:theatre.rooms.create')->group(function () {
                Route::post('rooms', [TheatreRoomController::class, 'store'])->name('rooms.store');
                Route::post('rooms/{theatreRoom}/blocks', [TheatreRoomController::class, 'storeBlock'])->name('rooms.blocks.store');
            });
            Route::middleware('can:theatre.rooms.update')->group(function () {
                Route::patch('rooms/{theatreRoom}', [TheatreRoomController::class, 'update'])->name('rooms.update');
                Route::patch('rooms/{theatreRoom}/status', [TheatreRoomController::class, 'status'])->name('rooms.status');
                Route::delete('room-blocks/{block}', [TheatreRoomController::class, 'destroyBlock'])->name('rooms.blocks.destroy');
            });

            // Dashboard + detail
            Route::middleware('can:procedure.view')->group(function () {
                Route::get('/', [TheatreController::class, 'index'])->name('index');
                Route::get('board', [TheatreController::class, 'index'])->name('board');
                Route::get('calendar', [TheatreScheduleController::class, 'calendar'])->name('calendar');
                // Procedure Consumables — read-only filtered product catalogue.
                Route::get('consumables', [ProcedureConsumablesController::class, 'index'])
                    ->name('consumables.index');
                Route::get('procedures/{procedure}', [TheatreController::class, 'show'])->name('show');
                Route::get('procedures/{procedure}/report', [TheatreController::class, 'fullReport'])
                    ->name('report')->middleware('can:procedure.view_report');
            });

            // Workflow actions
            Route::middleware('can:procedure.accept')->group(function () {
                Route::post('procedures/{procedure}/accept', [TheatreController::class, 'accept'])->name('accept');
            });
            Route::middleware('can:procedure.reject')->group(function () {
                Route::post('procedures/{procedure}/reject', [TheatreController::class, 'reject'])->name('reject');
            });
            Route::middleware('can:procedure.bill')->group(function () {
                Route::post('procedures/{procedure}/bill', [TheatreController::class, 'generateBilling'])->name('bill');
            });
            Route::middleware('can:procedure.schedule')->group(function () {
                Route::post('procedures/{procedure}/schedule', [TheatreController::class, 'schedule'])->name('schedule');
            });
            Route::middleware('can:procedure.reschedule')->group(function () {
                Route::post('procedures/{procedure}/reschedule', [TheatreController::class, 'reschedule'])->name('reschedule');
            });
            Route::middleware('can:procedure.record_preop')->group(function () {
                Route::post('procedures/{procedure}/preop', [TheatreController::class, 'preop'])->name('preop');
            });
            Route::middleware('can:procedure.record_anaesthesia')->group(function () {
                Route::post('procedures/{procedure}/anaesthesia', [TheatreController::class, 'anaesthesia'])->name('anaesthesia');
            });
            Route::middleware('can:procedure.record_surgery')->group(function () {
                Route::post('procedures/{procedure}/start-surgery', [TheatreController::class, 'startSurgery'])->name('start-surgery');
                Route::post('procedures/{procedure}/operative-note', [TheatreController::class, 'operativeNote'])->name('operative-note');
                Route::post('procedures/{procedure}/complete-surgery', [TheatreController::class, 'completeSurgery'])->name('complete-surgery');
            });
            Route::middleware('can:procedure.record_postop')->group(function () {
                Route::post('procedures/{procedure}/postop', [TheatreController::class, 'postop'])->name('postop');
            });
            Route::middleware('can:procedure.complete')->group(function () {
                Route::post('procedures/{procedure}/complete', [TheatreController::class, 'complete'])->name('complete');
            });
            Route::middleware('can:procedure.cancel')->group(function () {
                Route::post('procedures/{procedure}/cancel', [TheatreController::class, 'cancel'])->name('cancel');
            });

            // Doctor: request procedure from consultation page
            Route::middleware('can:procedure.request')->group(function () {
                Route::post('visits/{visit}/request', [TheatreController::class, 'requestStore'])->name('request');
                // Lookups for the consultation form
                Route::get('departments', function () {
                    return response()->json(app(ProcedureRequestService::class)->procedureDepartments());
                })->name('departments');
                Route::get('departments/{department}/services', function (Department $department) {
                    return response()->json(app(ProcedureRequestService::class)->servicesForDepartment($department->id));
                })->name('department-services');
            });
        });

        // ── Admin: Procedure Catalogue (templates + default consumables per procedure service) ──
        Route::prefix('procedure-catalogue')->name('procedure-catalogue.')->group(function () {
            Route::middleware('can:procedure_catalogue.view')->group(function () {
                Route::get('/', [ProcedureCatalogueController::class, 'index'])->name('index');
                Route::get('{service}', [ProcedureCatalogueController::class, 'show'])->name('show');
            });
            Route::middleware('can:procedure_catalogue.manage')->group(function () {
                // Sections
                Route::post('{service}/sections', [ProcedureCatalogueController::class, 'storeSection'])->name('sections.store');
                Route::put('sections/{section}', [ProcedureCatalogueController::class, 'updateSection'])->name('sections.update');
                Route::delete('sections/{section}', [ProcedureCatalogueController::class, 'destroySection'])->name('sections.destroy');
                // Fields
                Route::post('{service}/fields', [ProcedureCatalogueController::class, 'storeField'])->name('fields.store');
                Route::put('fields/{field}', [ProcedureCatalogueController::class, 'updateField'])->name('fields.update');
                Route::delete('fields/{field}', [ProcedureCatalogueController::class, 'destroyField'])->name('fields.destroy');
                // Default Consumables
                Route::post('{service}/consumables', [ProcedureCatalogueController::class, 'storeConsumable'])->name('consumables.store');
                Route::delete('{service}/consumables/{product}', [ProcedureCatalogueController::class, 'destroyConsumable'])->name('consumables.destroy');
            });
        });

        // ── Admin: Products (parallel store catalogue for consumables/reagents/supplies) ──
        Route::prefix('products')->name('products.')->middleware('module:inventory')->group(function () {
            Route::middleware('can:product.view')->group(function () {
                Route::get('/', [ProductController::class, 'index'])->name('index');
                Route::get('for-department/{department}', [ProductController::class, 'forDepartment'])->name('for-department');
                Route::get('{product}', [ProductController::class, 'show'])->name('show');
            });
            Route::middleware('can:product.create')->group(function () {
                Route::post('/', [ProductController::class, 'store'])->name('store');
            });
            Route::middleware('can:product.edit')->group(function () {
                Route::put('{product}', [ProductController::class, 'update'])->name('update');
                Route::patch('{product}/toggle', [ProductController::class, 'toggle'])->name('toggle');
            });
            // Pricing sub-routes — gated on product.pricing.manage
            Route::middleware('can:product.pricing.manage')->prefix('{product}/pricing')->name('pricing.')->group(function () {
                Route::patch('base', [ProductPricingController::class, 'updateBasePrice'])->name('base.update');

                // Bulk upsert (mirrors services.prices.store) + single-row delete
                Route::post('/', [ProductPricingController::class, 'storePrices'])->name('store');
                Route::delete('{price}/delete', [ProductPricingController::class, 'deletePrice'])->name('delete');

                // Insurance-type default prices (provider_id = NULL)
                Route::post('type', [ProductPricingController::class, 'storeTypePrice'])->name('type.store');
                Route::put('type/{price}', [ProductPricingController::class, 'updateTypePrice'])->name('type.update');
                Route::delete('type/{price}', [ProductPricingController::class, 'destroyTypePrice'])->name('type.destroy');

                // Provider-specific prices
                Route::post('provider', [ProductPricingController::class, 'storeProviderPrice'])->name('provider.store');
                Route::put('provider/{price}', [ProductPricingController::class, 'updateProviderPrice'])->name('provider.update');
                Route::delete('provider/{price}', [ProductPricingController::class, 'destroyProviderPrice'])->name('provider.destroy');
            });
        });

        // ── Admin: Stock Locations (Main Store + department stores) ──
        Route::prefix('stock-locations')->name('stock-locations.')->middleware(['module:inventory', 'can:stock.location.manage'])->group(function () {
            Route::get('/', [StockLocationController::class, 'index'])->name('index');
            Route::post('/', [StockLocationController::class, 'store'])->name('store');
            Route::put('{stockLocation}', [StockLocationController::class, 'update'])->name('update');
            Route::patch('{stockLocation}/toggle', [StockLocationController::class, 'toggle'])->name('toggle');
        });

        // ── Admin: Product Stock (balances, ledger, receive, transfer, adjust, return) ──
        Route::prefix('product-stock')->name('product-stock.')->middleware('module:inventory')->group(function () {
            Route::middleware('can:stock.view')->group(function () {
                Route::get('balances', [ProductStockController::class, 'balances'])->name('balances');
                Route::get('ledger', [ProductStockController::class, 'ledger'])->name('ledger');
            });
            Route::middleware('can:stock.adjust')->group(function () {
                Route::get('receive', [ProductStockController::class, 'receiveForm'])->name('receive.form');
                Route::post('receive', [ProductStockController::class, 'receive'])->name('receive');
                Route::get('adjust', [ProductStockController::class, 'adjustForm'])->name('adjust.form');
                Route::post('adjust', [ProductStockController::class, 'adjust'])->name('adjust');
            });
            Route::middleware('can:stock.transfer')->group(function () {
                Route::get('transfer', [ProductStockController::class, 'transferForm'])->name('transfer.form');
                Route::post('transfer', [ProductStockController::class, 'transfer'])->name('transfer');
            });
            Route::middleware('can:stock.return')->group(function () {
                Route::get('return', [ProductStockController::class, 'returnForm'])->name('return.form');
                Route::post('return', [ProductStockController::class, 'returnStock'])->name('return');
            });
        });

        // Investigation Items (Catalog + Stock for Lab/Radiology/Investigation departments)
        Route::prefix('investigations')->name('investigations.')->middleware('module:investigations')->group(function () {
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
        Route::middleware(['module:analyzer', 'can:analyzer.manage'])->prefix('analyzers')->name('analyzers.')->group(function () {
            Route::get('/', [AnalyzerController::class, 'index'])->name('index');
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
        Route::prefix('notifications')->name('notifications.')->middleware('module:notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index')->middleware('can:notifications.view');
            Route::get('/recent', [NotificationController::class, 'recent'])->name('recent')->middleware('can:notifications.view');
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('mark-read')->middleware('can:notifications.view');
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read')->middleware('can:notifications.view');
            Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy')->middleware('can:notifications.delete');

            // Broadcast (admin)
            Route::middleware('can:notifications.broadcast')->group(function () {
                Route::get('/broadcast', [NotificationBroadcastController::class, 'create'])->name('broadcast.create');
                Route::post('/broadcast', [NotificationBroadcastController::class, 'store'])->name('broadcast.store');
            });
        });

        // Profile (All authenticated users)
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

        // Per-user notification preferences (no extra permission required)
        Route::get('profile/notifications', [NotificationPreferenceController::class, 'index'])->name('notification-preferences.index');
        Route::put('profile/notifications', [NotificationPreferenceController::class, 'update'])->name('notification-preferences.update');
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
