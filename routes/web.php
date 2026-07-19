<?php

use App\Http\Controllers\Accounting\AccountController as AccountingAccountController;
use App\Http\Controllers\Accounting\AccountingAccountMappingController;
use App\Http\Controllers\Accounting\AccountingCloseReadinessController;
use App\Http\Controllers\Accounting\AccountingDashboardController;
use App\Http\Controllers\Accounting\AccountingPeriodController;
use App\Http\Controllers\Accounting\AccountingPostingAttemptController;
use App\Http\Controllers\Accounting\AccountingPostingController;
use App\Http\Controllers\Accounting\AccountingPostingTemplateController;
use App\Http\Controllers\Accounting\AccountingReportController;
use App\Http\Controllers\Accounting\AccountingSettingsController;
use App\Http\Controllers\Accounting\AccountsPayableController;
use App\Http\Controllers\Accounting\BankAccountController;
use App\Http\Controllers\Accounting\BankReconciliationAdjustmentController;
use App\Http\Controllers\Accounting\BankReconciliationController;
use App\Http\Controllers\Accounting\BankStatementImportController;
use App\Http\Controllers\Accounting\BasicAccountingBridgeController;
use App\Http\Controllers\Accounting\BudgetController;
use App\Http\Controllers\Accounting\FailedPostingWorkbenchController;
use App\Http\Controllers\Accounting\FiscalYearController;
use App\Http\Controllers\Accounting\FixedAssetController;
use App\Http\Controllers\Accounting\JournalEntryController;
use App\Http\Controllers\Accounting\PayrollPostingController;
use App\Http\Controllers\Accounting\ReceivableWorkbenchController;
use App\Http\Controllers\Accounting\SubledgerReconciliationController;
use App\Http\Controllers\Accounting\TaxAccountingController;
use App\Http\Controllers\Admin\AdmissionsWard\AdmissionBedWorkflowController;
use App\Http\Controllers\Admin\AdmissionsWard\AdmissionController;
use App\Http\Controllers\Admin\AdmissionsWard\AdmissionDischargeWorkflowController;
use App\Http\Controllers\Admin\AdmissionsWard\AdmissionMedicationBoardController;
use App\Http\Controllers\Admin\AdmissionsWard\AdmissionNursingCareController;
use App\Http\Controllers\Admin\AdmissionsWard\AdmissionRequestController;
use App\Http\Controllers\Admin\AdmissionsWard\MarChartController;
use App\Http\Controllers\Admin\AdmissionsWard\VitalController;
use App\Http\Controllers\Admin\AdmissionsWard\WardController;
use App\Http\Controllers\Admin\Appointments\AppointmentController;
use App\Http\Controllers\Admin\Appointments\ConsultationTaskController;
use App\Http\Controllers\Admin\Appointments\QueueController;
use App\Http\Controllers\Admin\Billing\AccountCategoryController;
use App\Http\Controllers\Admin\Billing\CashierShiftController;
use App\Http\Controllers\Admin\Billing\ClaimController;
use App\Http\Controllers\Admin\Billing\FinancialEntryController;
use App\Http\Controllers\Admin\Billing\FinancialRiskController;
use App\Http\Controllers\Admin\Billing\PaymentTimingCutoverController;
use App\Http\Controllers\Admin\Billing\VisitFinancialClearanceController;
use App\Http\Controllers\Admin\Billing\VisitPaymentArrangementController;
use App\Http\Controllers\Admin\Billing\VisitPaymentPolicyController;
use App\Http\Controllers\Admin\BloodBank\BloodBankDashboardController;
use App\Http\Controllers\Admin\BloodBank\BloodBankReportController;
use App\Http\Controllers\Admin\BloodBank\BloodCrossmatchController;
use App\Http\Controllers\Admin\BloodBank\BloodDonationController;
use App\Http\Controllers\Admin\BloodBank\BloodDonorController;
use App\Http\Controllers\Admin\BloodBank\BloodIssueController;
use App\Http\Controllers\Admin\BloodBank\BloodRequestController;
use App\Http\Controllers\Admin\BloodBank\BloodStorageLocationController;
use App\Http\Controllers\Admin\BloodBank\BloodUnitController;
use App\Http\Controllers\Admin\ConsultationSpecialtyFavoriteController;
use App\Http\Controllers\Admin\ConsultationSpecialtyMappingController;
use App\Http\Controllers\Admin\ConsultationSpecialtyOrderSetController as AdminConsultationSpecialtyOrderSetController;
use App\Http\Controllers\Admin\ConsultationSpecialtyOrderSetItemController;
use App\Http\Controllers\Admin\ConsultationSpecialtyProfileController;
use App\Http\Controllers\Admin\ConsultationSpecialtySectionController;
use App\Http\Controllers\Admin\ConsultationSpecialtyServiceMappingController;
use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\Dashboard\DepartmentContextController;
use App\Http\Controllers\Admin\Dashboard\DepartmentDashboardController;
use App\Http\Controllers\Admin\Dashboard\PermissionDashboardController;
use App\Http\Controllers\Admin\Dashboard\ProfileController;
use App\Http\Controllers\Admin\Dashboard\RoleDashboardController;
use App\Http\Controllers\Admin\DoctorConsultationPreferenceAdminController;
use App\Http\Controllers\Admin\Emergency\EmergencyBayController;
use App\Http\Controllers\Admin\Emergency\EmergencyBillingController;
use App\Http\Controllers\Admin\Emergency\EmergencyBoardController;
use App\Http\Controllers\Admin\Emergency\EmergencyCaseController;
use App\Http\Controllers\Admin\Emergency\EmergencyConsumableController;
use App\Http\Controllers\Admin\Emergency\EmergencyContactController;
use App\Http\Controllers\Admin\Emergency\EmergencyDispositionController;
use App\Http\Controllers\Admin\Emergency\EmergencyInvestigationController;
use App\Http\Controllers\Admin\Emergency\EmergencyMedicationBoardController;
use App\Http\Controllers\Admin\Emergency\EmergencyMedicationController;
use App\Http\Controllers\Admin\Emergency\EmergencyNoteController;
use App\Http\Controllers\Admin\Emergency\EmergencyPatientIdentityController;
use App\Http\Controllers\Admin\Emergency\EmergencyProcedureController;
use App\Http\Controllers\Admin\Emergency\EmergencyReportController;
use App\Http\Controllers\Admin\Emergency\EmergencyTaskController;
use App\Http\Controllers\Admin\Emergency\EmergencyTriageController;
use App\Http\Controllers\Admin\Emergency\EmergencyVitalsController;
use App\Http\Controllers\Admin\FrontDesk\CallLogController;
use App\Http\Controllers\Admin\FrontDesk\CourierLogController;
use App\Http\Controllers\Admin\FrontDesk\FrontDeskDashboardController;
use App\Http\Controllers\Admin\FrontDesk\FrontDeskReportController;
use App\Http\Controllers\Admin\FrontDesk\IncidentLogController;
use App\Http\Controllers\Admin\FrontDesk\LostFoundController;
use App\Http\Controllers\Admin\FrontDesk\ShiftHandoverController;
use App\Http\Controllers\Admin\FrontDesk\VisitorLogController;
use App\Http\Controllers\Admin\Hr\AttendanceController;
use App\Http\Controllers\Admin\Hr\DesignationController;
use App\Http\Controllers\Admin\Hr\EmployeeController;
use App\Http\Controllers\Admin\Hr\HrConfigurationController;
use App\Http\Controllers\Admin\Hr\LeaveController;
use App\Http\Controllers\Admin\Hr\PayrollController;
use App\Http\Controllers\Admin\Insurance\InsuranceProviderController;
use App\Http\Controllers\Admin\Insurance\InsuranceTierController;
use App\Http\Controllers\Admin\Insurance\InsuranceVerificationController;
use App\Http\Controllers\Admin\Integrations\GoLiveChecklistController;
use App\Http\Controllers\Admin\Integrations\InvoiceGatewayPaymentController;
use App\Http\Controllers\Admin\Integrations\PaymentCallbackController;
use App\Http\Controllers\Admin\Integrations\PaymentProviderController;
use App\Http\Controllers\Admin\Integrations\PaymentReconciliationController;
use App\Http\Controllers\Admin\Integrations\PaymentRefundController;
use App\Http\Controllers\Admin\Integrations\PaymentRequestLinkController;
use App\Http\Controllers\Admin\Integrations\PaymentTransactionController;
use App\Http\Controllers\Admin\Integrations\ProviderHealthController;
use App\Http\Controllers\Admin\Integrations\SchedulerStatusController;
use App\Http\Controllers\Admin\Integrations\SmsDeliveryReportController;
use App\Http\Controllers\Admin\Integrations\SmsEventController;
use App\Http\Controllers\Admin\Integrations\SmsMessageController;
use App\Http\Controllers\Admin\Integrations\SmsProviderController;
use App\Http\Controllers\Admin\Integrations\SmsQueueController;
use App\Http\Controllers\Admin\Integrations\SmsTemplateController;
use App\Http\Controllers\Admin\Journey\JourneyAnalyticsController;
use App\Http\Controllers\Admin\Journey\JourneyHandoffAssignmentController;
use App\Http\Controllers\Admin\Journey\JourneyWorklistController;
use App\Http\Controllers\Admin\Lab\AnalyzerController;
use App\Http\Controllers\Admin\Lab\InvestigationCatalogueController;
use App\Http\Controllers\Admin\Lab\InvestigationItemController;
use App\Http\Controllers\Admin\Lab\LabTestController;
use App\Http\Controllers\Admin\Maternity\AntenatalVisitController;
use App\Http\Controllers\Admin\Maternity\DeliveryRecordController;
use App\Http\Controllers\Admin\Maternity\LaborEpisodeController;
use App\Http\Controllers\Admin\Maternity\MaternityBillingReadinessController;
use App\Http\Controllers\Admin\Maternity\MaternityCaseController;
use App\Http\Controllers\Admin\Maternity\MaternityDashboardController;
use App\Http\Controllers\Admin\Maternity\MaternityReportController;
use App\Http\Controllers\Admin\Maternity\NewbornRecordController;
use App\Http\Controllers\Admin\Maternity\PostnatalCaseController;
use App\Http\Controllers\Admin\Maternity\PregnancyProfileController;
use App\Http\Controllers\Admin\Patients\PatientComplaintController;
use App\Http\Controllers\Admin\Patients\PatientController;
use App\Http\Controllers\Admin\Patients\PatientFinancialRiskController;
use App\Http\Controllers\Admin\Patients\PatientInsuranceController;
use App\Http\Controllers\Admin\Patients\PatientMergeController;
use App\Http\Controllers\Admin\Patients\PatientPrivacyController;
use App\Http\Controllers\Admin\Patients\TriageController;
use App\Http\Controllers\Admin\Pharmacy\CounterSaleController;
use App\Http\Controllers\Admin\Pharmacy\DrugController;
use App\Http\Controllers\Admin\Pharmacy\MedicationAdministrationController;
use App\Http\Controllers\Admin\Pharmacy\MedicationAdministrationReportController;
use App\Http\Controllers\Admin\Pharmacy\ProductController;
use App\Http\Controllers\Admin\Pharmacy\ProductPricingController;
use App\Http\Controllers\Admin\Procedures\ProcedureCatalogueController;
use App\Http\Controllers\Admin\Procedures\ProcedureConsumablesController;
use App\Http\Controllers\Admin\Procedures\ProcedureController;
use App\Http\Controllers\Admin\Procedures\ServiceCatalogController;
use App\Http\Controllers\Admin\Procedures\ServiceRenderingActionController;
use App\Http\Controllers\Admin\Procedures\ServiceRenderingController;
use App\Http\Controllers\Admin\Procedures\ServiceRenderingReportController;
use App\Http\Controllers\Admin\Reporting\ActivityLogController;
use App\Http\Controllers\Admin\Reporting\DepartmentComparisonController;
use App\Http\Controllers\Admin\Reporting\DepartmentMetricsController;
use App\Http\Controllers\Admin\Reporting\LogRetentionController;
use App\Http\Controllers\Admin\Reporting\OperationalReportController;
use App\Http\Controllers\Admin\Reporting\ReportController;
use App\Http\Controllers\Admin\Reporting\ReportsHubController;
use App\Http\Controllers\Admin\Reporting\StatisticsController;
use App\Http\Controllers\Admin\Reports\ConsultationSpecialtyReportController;
use App\Http\Controllers\Admin\Settings\ComplaintCatalogueController;
use App\Http\Controllers\Admin\Settings\ComplaintSearchController;
use App\Http\Controllers\Admin\Settings\DepartmentController;
use App\Http\Controllers\Admin\Settings\IcdCodeController;
use App\Http\Controllers\Admin\Settings\JourneyNotificationPreferenceController;
use App\Http\Controllers\Admin\Settings\LocationController;
use App\Http\Controllers\Admin\Settings\ModuleController;
use App\Http\Controllers\Admin\Settings\NotificationBroadcastController;
use App\Http\Controllers\Admin\Settings\NotificationController;
use App\Http\Controllers\Admin\Settings\NotificationPreferenceController;
use App\Http\Controllers\Admin\Settings\RoleController;
use App\Http\Controllers\Admin\Settings\SettingsController;
use App\Http\Controllers\Admin\Settings\SpecialtyController;
use App\Http\Controllers\Admin\Settings\UserController;
use App\Http\Controllers\Admin\Settings\UserDepartmentAssignmentController;
use App\Http\Controllers\Admin\Settings\UserPermissionController;
use App\Http\Controllers\Admin\Store\DepartmentConsumablesController;
use App\Http\Controllers\Admin\Store\ProductStockController;
use App\Http\Controllers\Admin\Store\PurchaseOrderController;
use App\Http\Controllers\Admin\Store\PurchaseReturnController;
use App\Http\Controllers\Admin\Store\StockController;
use App\Http\Controllers\Admin\Store\StockLocationController;
use App\Http\Controllers\Admin\Store\StockRequisitionController;
use App\Http\Controllers\Admin\Store\SupplierController;
use App\Http\Controllers\Admin\Visits\VisitController;
use App\Http\Controllers\Admin\Visits\VisitDepartmentOptionsController;
use App\Http\Controllers\Admin\Visits\VisitPreviewController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Billing\BillingReportController;
use App\Http\Controllers\Billing\CreditNoteController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PaymentController;
use App\Http\Controllers\Billing\PreviousBalanceController;
use App\Http\Controllers\Billing\ReceivableController;
use App\Http\Controllers\Billing\SponsorController;
use App\Http\Controllers\Dev\DebugPermissionGrantController;
use App\Http\Controllers\Doctor\Consultations\ConsultationClinicalEntryController;
use App\Http\Controllers\Doctor\Consultations\ConsultationOrderController;
use App\Http\Controllers\Doctor\Consultations\ConsultationPlanningController;
use App\Http\Controllers\Doctor\Consultations\ConsultationPrescriptionController;
use App\Http\Controllers\Doctor\Consultations\ConsultationSessionController;
use App\Http\Controllers\Doctor\Consultations\ConsultationSpecialtyBillingController;
use App\Http\Controllers\Doctor\Consultations\ConsultationSpecialtyEntryController;
use App\Http\Controllers\Doctor\Consultations\ConsultationSpecialtyOrderSetController;
use App\Http\Controllers\Doctor\Consultations\ConsultationSpecialtySummaryController;
use App\Http\Controllers\Doctor\Consultations\ConsultationWorkspaceController;
use App\Http\Controllers\Doctor\Consultations\DoctorConsultationPreferenceController;
use App\Http\Controllers\Doctor\DashboardController as DoctorDashboardController;
use App\Http\Controllers\Doctor\MedicalPatternController;
use App\Http\Controllers\Doctor\PrescriptionController;
use App\Http\Controllers\Inpatient\ClinicalWorklistController as InpatientClinicalWorklistController;
use App\Http\Controllers\Inpatient\ReadmissionController;
use App\Http\Controllers\Lab\LabRequestController;
use App\Http\Controllers\Lab\LabResultController;
use App\Http\Controllers\Lab\SampleController;
use App\Http\Controllers\Nursing\ConsultationController;
use App\Http\Controllers\Nursing\OpdController;
use App\Http\Controllers\Nursing\TaskController;
use App\Http\Controllers\Nursing\TreatmentController;
use App\Http\Controllers\Pharmacy\DispensingController;
use App\Http\Controllers\PublicPaymentController;
use App\Http\Controllers\StaffDashboardController;
use App\Http\Controllers\Theatre\TheatreController;
use App\Http\Controllers\Theatre\TheatreRoomController;
use App\Http\Controllers\Theatre\TheatreScheduleController;
use App\Http\Middleware\SetLocale;
use App\Models\Department;
use App\Models\EmergencyCase;
use App\Models\User;
use App\Models\Visit;
use App\Services\ProcedureRequestService;
use App\Services\WorkspaceRouteResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

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

// Language switcher — available to guests (session) and users (persisted to profile).
Route::post('locale', function (Request $request) {
    $validated = $request->validate([
        'locale' => ['required', 'string', Rule::in(SetLocale::SUPPORTED)],
    ]);

    $request->session()->put('locale', $validated['locale']);

    if ($request->user()) {
        $request->user()->forceFill(['locale' => $validated['locale']])->save();
    }

    // Flash in the newly selected language, not the one the request started with.
    app()->setLocale($validated['locale']);

    return back()->with('success', __('common.language_updated'));
})->name('locale.switch');

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
| Public Payment-Link Portal (no auth) — External Integrations Phase 3
|--------------------------------------------------------------------------
| Unauthenticated, rate-limited, resolved by random public token only (never an
| internal id). Module-gated so the portal disappears when payment_gateway is off.
*/
Route::middleware(['throttle:public-payments', 'module:payment_gateway'])
    ->prefix('pay')->name('public.payments.')->group(function () {
        Route::get('{token}', [PublicPaymentController::class, 'show'])->name('show')->where('token', '[A-Za-z0-9]+');
        Route::post('{token}/initiate', [PublicPaymentController::class, 'initiate'])->name('initiate')->where('token', '[A-Za-z0-9]+');
        Route::post('{token}/verify', [PublicPaymentController::class, 'verify'])->name('verify')->where('token', '[A-Za-z0-9]+');
        Route::get('{token}/status', [PublicPaymentController::class, 'status'])->name('status')->where('token', '[A-Za-z0-9]+');
        Route::get('{token}/receipt', [PublicPaymentController::class, 'receipt'])->name('receipt')->where('token', '[A-Za-z0-9]+');
    });

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Dev-only shortcut surfaced on the 403 debug panel (APP_DEBUG=true only):
    // lets the signed-in user grant themselves — or one of their own roles —
    // a permission they were just blocked on, without leaving the error page.
    // Hard-gated in the controller regardless of how this route was reached.
    Route::post('_debug/grant-permission', [DebugPermissionGrantController::class, 'store'])->name('debug.grant-permission');

    // Emergency Department workspace. Browser URLs stay under /emergency while
    // reusing the existing Emergency controllers/services for clinical safety.
    Route::prefix('emergency')->name('emergency.')->middleware('department.type:emergency')->group(function () {
        Route::get('/', App\Http\Controllers\Nursing\WorkspaceDashboardController::class)->name('dashboard')->middleware('can:emergency.board.view');
        Route::get('dashboard', App\Http\Controllers\Nursing\WorkspaceDashboardController::class)->name('dashboard.expanded')->middleware('can:emergency.board.view');
        Route::get('board', [EmergencyBoardController::class, 'index'])->name('board')->middleware('can:emergency.board.view');

        Route::get('queue', [EmergencyBoardController::class, 'index'])->name('queue.index')->middleware('can:emergency.board.view');
        Route::get('queue/critical', [EmergencyBoardController::class, 'index'])->defaults('triage_category', EmergencyCase::TRIAGE_RED)->name('queue.critical')->middleware('can:emergency.board.view');
        Route::get('queue/resuscitation', [EmergencyBoardController::class, 'index'])->defaults('triage_category', EmergencyCase::TRIAGE_RED)->name('queue.resuscitation')->middleware('can:emergency.board.view');
        Route::get('queue/urgent', [EmergencyBoardController::class, 'index'])->defaults('triage_category', EmergencyCase::TRIAGE_ORANGE)->name('queue.urgent')->middleware('can:emergency.board.view');
        Route::get('queue/observation', [EmergencyBoardController::class, 'index'])->defaults('status', EmergencyCase::STATUS_OBSERVATION)->name('queue.observation')->middleware('can:emergency.board.view');
        Route::get('queue/awaiting-disposition', [EmergencyBoardController::class, 'index'])->defaults('status', EmergencyCase::STATUS_READY_FOR_DISPOSITION)->name('queue.awaiting-disposition')->middleware('can:emergency.board.view');

        Route::middleware(['module:patients', 'can:patients.view'])->prefix('patients')->name('patients.')->group(function () {
            Route::get('/', [PatientController::class, 'index'])->name('index');
            Route::get('{patient}', [PatientController::class, 'show'])->name('show');
            Route::patch('{patient}/medical-summary', [PatientController::class, 'updateMedicalSummary'])->name('medical-summary.update')->middleware('can:patients.edit');
            Route::post('{patient}/insurances', [PatientInsuranceController::class, 'store'])->name('insurances.store')->middleware(['module:insurance', 'can:patients.insurance.create']);
        });

        Route::middleware(['module:visits', 'can:visits.view'])->prefix('visits')->name('visits.')->group(function () {
            Route::get('/', [VisitController::class, 'index'])->name('index');
            Route::get('patient-search', [VisitController::class, 'patientSearch'])->name('patient-search');
            Route::get('attendance-preview', [VisitController::class, 'attendancePreview'])->name('attendance-preview');
            Route::get('patient-insurances', [VisitController::class, 'patientInsurances'])->name('patient-insurances')->middleware('module:insurance');
            Route::get('department-services', [VisitController::class, 'departmentServices'])->name('department-services');
            Route::get('doctors-for-services', [VisitController::class, 'doctorsForServices'])->name('doctors-for-services');
            Route::get('services-for-doctor', [VisitController::class, 'servicesForDoctor'])->name('services-for-doctor');
            Route::get('service-price', [VisitController::class, 'servicePrice'])->name('service-price');
            Route::get('{visit}', [VisitController::class, 'show'])->name('show');
            Route::get('{visit}/preview', [VisitPreviewController::class, 'show'])->name('preview')->middleware('can:visits.preview');
            Route::get('{visit}/edit', [VisitController::class, 'edit'])->name('edit')->middleware('can:visits.edit');
            Route::put('{visit}', [VisitController::class, 'update'])->name('update')->middleware('can:visits.edit');
            Route::patch('{visit}/insurance', [VisitController::class, 'updateInsurance'])->name('insurance.update')->middleware(['module:insurance', 'can:visits.edit']);
            Route::patch('{visit}/transition', [VisitController::class, 'transition'])->name('transition')->middleware('can:visits.transition');
            Route::patch('{visit}/send-to-department', [VisitController::class, 'sendToDepartment'])->name('send-to-department')->middleware('can:visits.transition');
        });

        Route::get('cases/create', [EmergencyCaseController::class, 'create'])->name('cases.create')->middleware('can:emergency.case.create');
        Route::post('cases', [EmergencyCaseController::class, 'store'])->name('cases.store')->middleware('can:emergency.case.create');
        Route::get('cases', [EmergencyBoardController::class, 'index'])->name('cases.index')->middleware('can:emergency.board.view');
        Route::get('cases/{emergencyCase}', [EmergencyCaseController::class, 'show'])->name('cases.show')->middleware('can:emergency.case.view');
        Route::patch('cases/{emergencyCase}', [EmergencyCaseController::class, 'update'])->name('cases.update')->middleware('can:emergency.case.update');
        Route::post('cases/{emergencyCase}/confirm-identity', [EmergencyPatientIdentityController::class, 'store'])->name('cases.confirm-identity')->middleware('can:patients.merge.confirm_identity');
        Route::post('cases/{emergencyCase}/register-identity', [EmergencyPatientIdentityController::class, 'register'])->name('cases.register-identity')->middleware('can:patients.merge.confirm_identity');

        Route::middleware(['module:triage', 'can:vitals.view'])->prefix('triage')->name('triage.')->group(function () {
            Route::get('/', [TriageController::class, 'index'])->name('index');
            Route::get('{visit}', [TriageController::class, 'show'])->name('show');
            Route::get('{visit}/edit', [TriageController::class, 'create'])->name('edit')->middleware('can:vitals.create');
        });

        Route::get('cases/{emergencyCase}/triage', fn (EmergencyCase $emergencyCase) => redirect()->route(app(WorkspaceRouteResolver::class)->routeName('admin.emergency.cases.show'), $emergencyCase))->name('case-triage.show');
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

        Route::get('resuscitation', [EmergencyBoardController::class, 'index'])->defaults('triage_category', EmergencyCase::TRIAGE_RED)->name('resuscitation.index')->middleware('can:emergency.board.view');
        Route::get('resuscitation/{emergencyCase}', [EmergencyCaseController::class, 'show'])->name('resuscitation.show')->middleware('can:emergency.case.view');
        Route::get('observations', [EmergencyBoardController::class, 'index'])->defaults('status', EmergencyCase::STATUS_OBSERVATION)->name('observations.index')->middleware('can:emergency.board.view');
        Route::get('observations/{emergencyCase}', [EmergencyCaseController::class, 'show'])->name('observations.show')->middleware('can:emergency.case.view');

        Route::get('medications', [EmergencyMedicationBoardController::class, 'index'])->name('medications.index')->middleware('can:emergency.medication_board.view');
        Route::get('medication-board', [EmergencyMedicationBoardController::class, 'index'])->name('medication-board')->middleware('can:emergency.medication_board.view');
        Route::get('{visit}/mar-chart', [MarChartController::class, 'emergency'])->name('mar-chart')->middleware('can:emergency.mar_chart.view');
        Route::get('consumables', [DepartmentConsumablesController::class, 'emergency'])->name('consumables.index')->middleware('can:emergency.board.view');

        Route::middleware(['module:consultation', 'can:consultations.view'])->prefix('consultations')->name('consultations.')->group(function () {
            Route::get('/', [ConsultationWorkspaceController::class, 'index'])->name('index');
            Route::get('{visit}', [ConsultationWorkspaceController::class, 'show'])->name('show');
            Route::get('{visit}/routes/{route}', [ConsultationWorkspaceController::class, 'show'])->name('routes.show');
            Route::get('{visit}/history', [ConsultationWorkspaceController::class, 'history'])->name('history')->middleware('can:consultation.preview');
        });

        Route::prefix('lab')->name('lab.')->middleware('module:investigations')->group(function () {
            Route::middleware('can:lab.requests.view')->prefix('requests')->name('requests.')->group(function () {
                Route::get('/', [LabRequestController::class, 'index'])->name('index');
                Route::get('{labRequest}', [LabRequestController::class, 'show'])->name('show');
            });
        });

        Route::middleware('can:procedure.view')->prefix('theatre')->name('theatre.')->group(function () {
            Route::get('/', [TheatreController::class, 'index'])->name('index');
            Route::get('board', [TheatreController::class, 'index'])->name('board');
            Route::get('procedures/{procedure}', [TheatreController::class, 'show'])->name('show');
        });

        Route::middleware('can:ward.view')->prefix('admissions')->name('admissions.')->group(function () {
            Route::get('/', [AdmissionController::class, 'index'])->name('index');
            Route::get('{admission}', [AdmissionController::class, 'show'])->name('show');
        });

        Route::get('handoffs', [JourneyWorklistController::class, 'index'])->name('journey.worklist');
        Route::get('handoffs/refresh', [JourneyWorklistController::class, 'refresh'])->name('journey.worklist.refresh');

        Route::get('bays', [EmergencyBayController::class, 'index'])->name('bays.index')->middleware('can:emergency.settings.manage');
        Route::post('bays', [EmergencyBayController::class, 'store'])->name('bays.store')->middleware('can:emergency.settings.manage');
        Route::get('reports', [EmergencyReportController::class, 'index'])->name('reports.index')->middleware('can:emergency.reports.view');
    });

    // Normal-admission ward workspace. These are browser adapters over the
    // existing admission, bed, consultation, nursing and discharge services.
    Route::prefix('inpatient')->name('inpatient.')->middleware(['department.type:inpatient', 'inpatient.scope'])->group(function () {
        Route::get('/', App\Http\Controllers\Nursing\WorkspaceDashboardController::class)->name('dashboard');
        Route::get('dashboard', fn () => redirect()->route('inpatient.dashboard'))->name('dashboard.redirect');

        Route::middleware(['module:patients', 'can:patients.view'])->prefix('patients')->name('patients.')->group(function () {
            Route::get('/', [PatientController::class, 'index'])->name('index');
            Route::get('{patient}', [PatientController::class, 'show'])->name('show');
        });

        Route::middleware(['module:visits', 'can:visits.view'])->prefix('visits')->name('visits.')->group(function () {
            Route::get('/', [VisitController::class, 'index'])->name('index');
            Route::get('{visit}', [VisitController::class, 'show'])->name('show');
            Route::get('{visit}/preview', [VisitPreviewController::class, 'show'])->name('preview')->middleware('can:visits.preview');
        });

        Route::middleware('can:ward.view')->group(function () {
            Route::get('wards', [WardController::class, 'index'])->name('wards.index');
            Route::get('wards/{ward}', [WardController::class, 'show'])->whereNumber('ward')->name('wards.show');
            Route::post('wards', [WardController::class, 'store'])->name('wards.store')->middleware('can:ward.manage');
            Route::put('wards/{ward}', [WardController::class, 'update'])->name('wards.update')->middleware('can:ward.manage');
            Route::patch('wards/{ward}/toggle', [WardController::class, 'toggle'])->name('wards.toggle')->middleware('can:ward.manage');

            Route::get('beds', [WardController::class, 'beds'])->name('beds.index');
            Route::get('beds/availability', [WardController::class, 'bedMap'])->name('beds.availability');
            Route::post('beds', [WardController::class, 'storeBed'])->name('beds.store')->middleware('can:beds.manage');
            Route::put('beds/{bed}', [WardController::class, 'updateBed'])->name('beds.update')->middleware('can:beds.manage');
            Route::patch('beds/{bed}/status', [AdmissionBedWorkflowController::class, 'updateBedStatus'])->name('beds.status')->middleware('can:beds.status.manage');
            Route::get('consumables', [DepartmentConsumablesController::class, 'ward'])->name('consumables.index');

            Route::get('admissions/requests', [AdmissionRequestController::class, 'index'])->name('admissions.requests')->middleware('can:admission.requests.view');
            Route::get('admissions/requests/create', [AdmissionRequestController::class, 'create'])->name('admissions.requests.create')->middleware('can:admission.requests.create');
            Route::post('admissions/requests', [AdmissionRequestController::class, 'store'])->name('admissions.requests.store')->middleware('can:admission.requests.create');
            Route::get('admissions/requests/{admissionRequest}', [AdmissionRequestController::class, 'show'])->name('admissions.requests.show')->middleware('can:admission.requests.view');
            Route::patch('admissions/requests/{admissionRequest}/accept', [AdmissionRequestController::class, 'accept'])->name('admissions.requests.accept')->middleware('can:admission.requests.accept');
            Route::patch('admissions/requests/{admissionRequest}/reject', [AdmissionRequestController::class, 'reject'])->name('admissions.requests.reject')->middleware('can:admission.requests.reject');
            Route::patch('admissions/requests/{admissionRequest}/cancel', [AdmissionRequestController::class, 'cancel'])->name('admissions.requests.cancel')->middleware('can:admission.requests.cancel');
            Route::patch('admissions/requests/{admissionRequest}/bed-pending', [AdmissionRequestController::class, 'bedPending'])->name('admissions.requests.bed-pending')->middleware('can:admission.requests.bed_pending');
            Route::patch('admissions/requests/{admissionRequest}/reserve-bed', [AdmissionRequestController::class, 'reserveBed'])->name('admissions.requests.reserve-bed')->middleware('can:admission.requests.reserve_bed');
            Route::post('admissions/requests/{admissionRequest}/convert', [AdmissionRequestController::class, 'convert'])->name('admissions.requests.convert')->middleware('can:admission.requests.convert');

            Route::get('admissions', [AdmissionController::class, 'index'])->name('admissions.index');
            Route::get('admissions/pending', [AdmissionRequestController::class, 'index'])->defaults('status', 'requested')->name('admissions.pending')->middleware('can:admission.requests.view');
            Route::get('admissions/active', [AdmissionController::class, 'index'])->defaults('status', 'admitted')->name('admissions.active');
            Route::get('admissions/discharged', [AdmissionController::class, 'index'])->defaults('status', 'discharged')->name('admissions.discharged');
            Route::get('admissions/create', [AdmissionController::class, 'create'])->name('admissions.create')->middleware('can:ward.admit');
            Route::post('admissions', [AdmissionController::class, 'store'])->name('admissions.store')->middleware('can:ward.admit');
            Route::get('admissions/medication-board', [AdmissionMedicationBoardController::class, 'index'])->name('admissions.medication-board')->middleware('can:admission.medication_board.view');
            Route::get('admissions/{admission}/medications', [AdmissionMedicationBoardController::class, 'show'])->name('admissions.medications.show')->middleware('can:admission.medication_board.view');
            Route::get('admissions/{admission}/mar-chart', [MarChartController::class, 'admission'])->name('admissions.mar-chart')->middleware('can:admission.mar_chart.view');
            Route::get('admissions/{admission}', [AdmissionController::class, 'show'])->name('admissions.show');
            Route::get('admissions/{admission}/discharge', [AdmissionController::class, 'discharge'])->name('admissions.discharge')->middleware('can:ward.discharge');
            Route::post('admissions/{admission}/discharge', [AdmissionController::class, 'processDischarge'])->name('admissions.process-discharge')->middleware('can:ward.discharge');
            Route::post('admissions/{admission}/extend', [AdmissionController::class, 'extend'])->name('admissions.extend')->middleware('can:admissions.extend');
            Route::post('admissions/{admission}/rounds', [AdmissionController::class, 'storeRound'])->name('admissions.rounds.store');
            Route::post('admissions/{admission}/vitals', [AdmissionController::class, 'storeVital'])->name('admissions.vitals.store');
            Route::post('admissions/{admission}/services', [AdmissionController::class, 'storeService'])->name('admissions.services.store');
            Route::post('admissions/{admission}/transfer-bed', [AdmissionBedWorkflowController::class, 'transfer'])->name('admissions.transfer-bed')->middleware('can:beds.transfer');
            Route::post('admissions/{admission}/nursing-notes', [AdmissionNursingCareController::class, 'storeNote'])->name('admissions.nursing-notes.store')->middleware('can:admission.nursing.notes.create');
            Route::patch('admissions/{admission}/nursing-notes/{nursingNote}', [AdmissionNursingCareController::class, 'updateNote'])->name('admissions.nursing-notes.update')->middleware('can:admission.nursing.notes.update');
            Route::post('admissions/{admission}/nursing-tasks', [AdmissionNursingCareController::class, 'storeTask'])->name('admissions.nursing-tasks.store')->middleware('can:admission.nursing.tasks.create');
            Route::patch('admissions/{admission}/nursing-tasks/{nursingTask}', [AdmissionNursingCareController::class, 'updateTask'])->name('admissions.nursing-tasks.update')->middleware('can:admission.nursing.tasks.update');
            Route::patch('admissions/{admission}/nursing-tasks/{nursingTask}/complete', [AdmissionNursingCareController::class, 'completeTask'])->name('admissions.nursing-tasks.complete')->middleware('can:admission.nursing.tasks.complete');
            Route::patch('admissions/{admission}/care-flags', [AdmissionNursingCareController::class, 'updateCareFlags'])->name('admissions.care-flags.update')->middleware('can:admission.care_flags.manage');
            Route::post('admissions/{admission}/discharge-planning', [AdmissionDischargeWorkflowController::class, 'startPlanning'])->name('admissions.discharge-planning.start')->middleware('can:admission.discharge.plan');
            Route::patch('admissions/{admission}/discharge-planning', [AdmissionDischargeWorkflowController::class, 'updatePlanning'])->name('admissions.discharge-planning.update')->middleware('can:admission.discharge.plan');
            Route::patch('admissions/{admission}/discharge-clearances/{clearance}', [AdmissionDischargeWorkflowController::class, 'updateClearance'])->name('admissions.discharge-clearances.update')->middleware('can:admission.discharge.clearance.manage');
            Route::patch('admissions/{admission}/discharge-clearances/{clearance}/revoke', [AdmissionDischargeWorkflowController::class, 'revokeClearance'])->name('admissions.discharge-clearances.revoke')->middleware('can:admission.discharge.clearance.manage');
            Route::post('admissions/{admission}/discharge-summary', [AdmissionDischargeWorkflowController::class, 'saveSummary'])->name('admissions.discharge-summary.save')->middleware('can:admission.discharge.summary.create');
            Route::patch('admissions/{admission}/discharge-summary', [AdmissionDischargeWorkflowController::class, 'saveSummary'])->name('admissions.discharge-summary.update')->middleware('can:admission.discharge.summary.update');
            Route::patch('admissions/{admission}/discharge-summary/{summary}/prepare', [AdmissionDischargeWorkflowController::class, 'prepareSummary'])->name('admissions.discharge-summary.prepare')->middleware('can:admission.discharge.summary.update');
            Route::patch('admissions/{admission}/discharge-summary/{summary}/approve', [AdmissionDischargeWorkflowController::class, 'approveSummary'])->name('admissions.discharge-summary.approve')->middleware('can:admission.discharge.summary.approve');
            Route::get('admissions/{admission}/discharge-summary/{summary}/print', [AdmissionDischargeWorkflowController::class, 'printSummary'])->name('admissions.discharge-summary.print')->middleware('can:admission.discharge.summary.view');
        });

        Route::middleware('can:ward.view')->group(function () {
            Route::get('rounds', [AdmissionController::class, 'index'])->defaults('status', 'admitted')->name('rounds.index');
            Route::get('rounds/{admission}', [AdmissionController::class, 'show'])->name('rounds.show');
            Route::post('rounds/{admission}', [AdmissionController::class, 'storeRound'])->name('rounds.store');
            Route::get('vitals', [AdmissionController::class, 'index'])->defaults('status', 'admitted')->name('vitals.index');
            Route::get('vitals/{admission}', [AdmissionController::class, 'show'])->name('vitals.show');
            Route::post('vitals/{admission}', [AdmissionController::class, 'storeVital'])->name('vitals.store')->middleware('can:vitals.create');
            Route::get('tasks', [InpatientClinicalWorklistController::class, 'tasks'])->name('tasks.index')->middleware('can:clinical_tasks.view');
            Route::get('tasks/{task}', [InpatientClinicalWorklistController::class, 'task'])->name('tasks.show')->middleware('can:clinical_tasks.view');
            Route::patch('tasks/{task}', [InpatientClinicalWorklistController::class, 'updateTask'])->name('tasks.update')->middleware('can:clinical_tasks.complete');
            Route::get('transfers', [AdmissionController::class, 'index'])->defaults('status', 'admitted')->name('transfers.index');
            Route::get('transfers/{admission}', [AdmissionController::class, 'show'])->name('transfers.show');
            Route::post('transfers/{admission}', [AdmissionBedWorkflowController::class, 'transfer'])->name('transfers.store')->middleware('can:beds.transfer');
            Route::get('discharges', [AdmissionController::class, 'index'])->defaults('status', 'discharged')->name('discharges.index');
            Route::get('discharges/readiness', [AdmissionController::class, 'index'])->defaults('status', 'admitted')->name('discharges.readiness');
            Route::get('discharges/{admission}', [AdmissionController::class, 'discharge'])->name('discharges.show')->middleware('can:ward.discharge');
            Route::post('discharges/{admission}', [AdmissionController::class, 'processDischarge'])->name('discharges.store')->middleware('can:ward.discharge');
            Route::get('readmissions', [AdmissionController::class, 'index'])->defaults('status', 'discharged')->name('readmissions.index')->middleware('can:admissions.readmit');
            Route::get('readmissions/{admission}/create', [ReadmissionController::class, 'create'])->name('readmissions.create')->middleware('can:admissions.readmit');
            Route::post('readmissions/{admission}', [ReadmissionController::class, 'store'])->name('readmissions.store')->middleware('can:admissions.readmit');
            Route::get('medications', [AdmissionMedicationBoardController::class, 'index'])->name('medications.index')->middleware('can:admission.medication_board.view');
            Route::get('medications/{admission}', [AdmissionMedicationBoardController::class, 'show'])->name('medications.show')->middleware('can:admission.medication_board.view');
            Route::get('mar-chart/{admission}', [MarChartController::class, 'admission'])->name('mar-chart.show')->middleware('can:admission.mar_chart.view');
        });

        Route::post('medication-administration/schedules/{schedule}/administer', [MedicationAdministrationController::class, 'administerSchedule'])->name('medication-administration.schedules.administer')->middleware('can:medication_administration.administer');
        Route::post('medication-administration/orders/{order}/prn', [MedicationAdministrationController::class, 'administerPrn'])->name('medication-administration.orders.prn')->middleware('can:medication_administration.administer');
        Route::post('medication-administration/orders/{order}/hold', [MedicationAdministrationController::class, 'holdOrder'])->name('medication-administration.orders.hold')->middleware('can:medication_orders.hold');
        Route::post('medication-administration/orders/{order}/stop', [MedicationAdministrationController::class, 'stopOrder'])->name('medication-administration.orders.stop')->middleware('can:medication_orders.stop');
        Route::patch('medication-administration/records/{administration}/correct', [MedicationAdministrationController::class, 'correct'])->name('medication-administration.records.correct')->middleware('can:medication_administration.correct');

        Route::middleware(['module:consultation', 'can:consultations.view'])->prefix('sessions')->name('sessions.')->group(function () {
            Route::get('/', [ConsultationWorkspaceController::class, 'index'])->name('index');
            Route::get('{visit}', [ConsultationWorkspaceController::class, 'show'])->name('show');
            Route::get('{visit}/routes/{route}', [ConsultationWorkspaceController::class, 'show'])->name('routes.show');
            Route::get('{visit}/history', [ConsultationWorkspaceController::class, 'history'])->name('history')->middleware('can:consultation.preview');
            Route::patch('{visit}/transition', [ConsultationSessionController::class, 'transitionVisit'])->name('transition')->middleware('can:visits.transition');
            Route::post('{visit}/start', [ConsultationSessionController::class, 'startConsultation'])->name('start')->middleware('can:consultations.create');
            Route::post('{visit}/routes', [ConsultationSessionController::class, 'storeRoute'])->name('routes.store')->withoutMiddleware('can:consultations.view')->middleware('can:consultations.create');
            Route::post('{visit}/routes/{route}/activate', [ConsultationSessionController::class, 'activateRoute'])->name('routes.activate')->middleware('can:consultations.create');
            Route::post('{visit}/routes/{route}/complete', [ConsultationSessionController::class, 'completeRoute'])->name('routes.complete')->middleware('can:consultations.create');
            Route::post('{visit}/routes/{route}/cancel', [ConsultationSessionController::class, 'cancelRoute'])->name('routes.cancel')->middleware('can:consultations.create');
            Route::post('{visit}/routes/{route}/reopen', [ConsultationSessionController::class, 'reopenRoute'])->name('routes.reopen')->middleware('can:consultations.reopen');
            Route::post('{visit}/routes/{route}/follow-up-appointments', [ConsultationPlanningController::class, 'storeFollowUpAppointment'])->name('routes.follow-up.store')->middleware('can:consultation.followup.create');
            Route::put('{visit}/routes/{route}/follow-up-appointments/{appointment}', [ConsultationPlanningController::class, 'updateFollowUpAppointment'])->name('routes.follow-up.update')->middleware('can:consultation.followup.update');
            Route::post('{visit}/routes/{route}/follow-up-appointments/{appointment}/cancel', [ConsultationPlanningController::class, 'cancelFollowUpAppointment'])->name('routes.follow-up.cancel')->middleware('can:consultation.followup.cancel');
            Route::post('{visit}/routes/{route}/next-patient/open', [ConsultationPlanningController::class, 'openNextPatient'])->name('routes.next-patient.open')->middleware('can:consultations.create');
            Route::post('{visit}/routes/{route}/next-patient/complete-and-open', [ConsultationPlanningController::class, 'completeAndOpenNextPatient'])->name('routes.next-patient.complete-open')->middleware('can:consultations.create');
            Route::post('{visit}/refer', [ConsultationPlanningController::class, 'refer'])->name('refer')->middleware('can:consultations.create');
            Route::post('{visit}/investigation', [ConsultationOrderController::class, 'sendToInvestigation'])->name('investigation')->middleware('can:consultations.create');

            Route::middleware('can:consultations.create')->group(function () {
                Route::post('{visit}/complaints', [ConsultationClinicalEntryController::class, 'storeComplaint'])->name('complaints.store');
                Route::patch('complaints/{complaint}', [ConsultationClinicalEntryController::class, 'updateComplaint'])->name('complaints.update');
                Route::delete('complaints/{complaint}', [ConsultationClinicalEntryController::class, 'destroyComplaint'])->name('complaints.destroy');
                Route::post('{visit}/history-of-presenting-complaints', [ConsultationClinicalEntryController::class, 'storeHistoryOfPresentingComplaint'])->name('hopc.store');
                Route::patch('history-of-presenting-complaints/{hopc}', [ConsultationClinicalEntryController::class, 'updateHistoryOfPresentingComplaint'])->name('hopc.update');
                Route::delete('history-of-presenting-complaints/{hopc}', [ConsultationClinicalEntryController::class, 'destroyHistoryOfPresentingComplaint'])->name('hopc.destroy');
                Route::post('{visit}/examinations', [ConsultationClinicalEntryController::class, 'storeExamination'])->name('examinations.store');
                Route::patch('examinations/{examination}', [ConsultationClinicalEntryController::class, 'updateExamination'])->name('examinations.update');
                Route::delete('examinations/{examination}', [ConsultationClinicalEntryController::class, 'destroyExamination'])->name('examinations.destroy');
                Route::post('{visit}/diagnoses', [ConsultationClinicalEntryController::class, 'storeDiagnosis'])->name('diagnoses.store');
                Route::patch('diagnoses/{diagnosis}', [ConsultationClinicalEntryController::class, 'updateDiagnosis'])->name('diagnoses.update');
                Route::patch('diagnoses/{diagnosis}/primary', [ConsultationClinicalEntryController::class, 'setPrimaryDiagnosis'])->name('diagnoses.primary');
                Route::delete('diagnoses/{diagnosis}', [ConsultationClinicalEntryController::class, 'destroyDiagnosis'])->name('diagnoses.destroy');
                Route::post('{visit}/investigations', [ConsultationOrderController::class, 'storeInvestigation'])->name('investigations.store');
                Route::patch('investigations/{investigation}', [ConsultationOrderController::class, 'updateInvestigation'])->name('investigations.update');
                Route::delete('investigations/{investigation}', [ConsultationOrderController::class, 'destroyInvestigation'])->name('investigations.destroy');
                Route::delete('investigation-items/{item}', [ConsultationOrderController::class, 'destroyInvestigationItem'])->name('investigation-items.destroy');
                Route::post('{visit}/investigation-departments/{department}/send-to-department', [ConsultationOrderController::class, 'sendInvestigationDepartmentToDepartment'])->name('investigation-departments.send-to-department');
                Route::post('{visit}/lab-requests/{labRequest}/send-to-department', [ConsultationOrderController::class, 'sendLabRequestToDepartment'])->name('lab-requests.send-to-department');
                Route::post('{visit}/treatments', [ConsultationClinicalEntryController::class, 'storeTreatment'])->name('treatments.store');
                Route::patch('treatments/{treatment}', [ConsultationClinicalEntryController::class, 'updateTreatment'])->name('treatments.update');
                Route::delete('treatments/{treatment}', [ConsultationClinicalEntryController::class, 'destroyTreatment'])->name('treatments.destroy');
                Route::post('{visit}/specialty-entries/{sectionKey}', [ConsultationSpecialtyEntryController::class, 'store'])->name('specialty-entries.store');
                Route::delete('{visit}/specialty-entries/{sectionKey}', [ConsultationSpecialtyEntryController::class, 'destroy'])->name('specialty-entries.destroy');
                Route::get('{visit}/specialty-order-sets', [ConsultationSpecialtyOrderSetController::class, 'index'])->name('specialty-order-sets.index');
                Route::get('{visit}/specialty-order-sets/{orderSet}/preview', [ConsultationSpecialtyOrderSetController::class, 'preview'])->name('specialty-order-sets.preview');
                Route::post('{visit}/specialty-order-sets/{orderSet}/apply', [ConsultationSpecialtyOrderSetController::class, 'apply'])->name('specialty-order-sets.apply');
                Route::get('{visit}/specialty-billing/preview', [ConsultationSpecialtyBillingController::class, 'preview'])->name('specialty-billing.preview');
                Route::post('{visit}/specialty-billing/apply', [ConsultationSpecialtyBillingController::class, 'apply'])->name('specialty-billing.apply')->middleware('can:invoices.create');
            });

            Route::get('{visit}/summary-fragment', [ConsultationWorkspaceController::class, 'summaryFragment'])->name('summary-fragment');
            Route::get('{visit}/readiness-fragment', [ConsultationWorkspaceController::class, 'readinessFragment'])->name('readiness-fragment');
            Route::get('{visit}/specialty-summary/preview', [ConsultationSpecialtySummaryController::class, 'preview'])->name('specialty-summary.preview');
            Route::patch('{visit}/final-note', [ConsultationWorkspaceController::class, 'updateFinalNote'])->name('final-note.update')->middleware('can:consultations.create');
            Route::patch('preferences/pinned-actions', [DoctorConsultationPreferenceController::class, 'updatePinnedActions'])->name('preferences.pinned-actions.update')->middleware('can:consultations.create');
            Route::patch('preferences/layout', [DoctorConsultationPreferenceController::class, 'updateLayout'])->name('preferences.layout.update')->middleware('can:consultations.create');
            Route::post('{visit}/prescriptions', [ConsultationPrescriptionController::class, 'storePrescription'])->name('prescriptions.store')->middleware('can:prescriptions.create');
            Route::patch('prescriptions/{prescription}', [ConsultationPrescriptionController::class, 'updatePrescription'])->name('prescriptions.update')->middleware('can:prescriptions.create');
            Route::delete('prescriptions/{prescription}', [ConsultationPrescriptionController::class, 'destroyPrescription'])->name('prescriptions.destroy')->middleware('can:prescriptions.create');
            Route::post('{visit}/prescription-departments/{department}/send-to-department', [ConsultationPrescriptionController::class, 'sendPrescriptionDepartmentToDepartment'])->name('prescription-departments.send-to-department')->middleware('can:prescriptions.create');
            Route::post('{visit}/procedures', [ConsultationOrderController::class, 'storeProcedureRequest'])->name('procedures.store')->middleware('can:procedure.request');
            Route::patch('procedures/{procedureRequest}', [ConsultationOrderController::class, 'updateProcedureRequest'])->name('procedures.update')->middleware('can:procedure.request');
            Route::post('{visit}/procedure-departments/{department}/send-to-department', [ConsultationOrderController::class, 'sendProcedureDepartmentToDepartment'])->name('procedure-departments.send-to-department')->middleware('can:procedure.request');
            Route::post('{visit}/lab-request', [ConsultationOrderController::class, 'storeLabRequest'])->name('lab-request.store')->middleware('can:lab.requests.create');
            Route::patch('lab-requests/{labRequest}', [ConsultationOrderController::class, 'updateLabRequest'])->name('lab-request.update')->middleware('can:lab.requests.create');
            Route::get('suggest/complaints', [ConsultationClinicalEntryController::class, 'suggestComplaints'])->name('suggest.complaints');
            Route::get('suggest/diagnoses', [ConsultationClinicalEntryController::class, 'suggestDiagnoses'])->name('suggest.diagnoses');
            Route::post('{visit}/tasks', [ConsultationTaskController::class, 'store'])->name('tasks.store')->middleware('can:consultations.create');
            Route::put('tasks/{task}', [ConsultationTaskController::class, 'update'])->name('tasks.update')->middleware('can:consultations.create');
            Route::patch('tasks/{task}/toggle', [ConsultationTaskController::class, 'toggleComplete'])->name('tasks.toggle')->middleware('can:consultations.create');
            Route::delete('tasks/{task}', [ConsultationTaskController::class, 'destroy'])->name('tasks.destroy')->middleware('can:consultations.create');
        });

        Route::middleware(['module:consultation', 'can:consultations.view'])->prefix('consultations')->name('consultations.')->group(function () {
            Route::get('/', [ConsultationWorkspaceController::class, 'index'])->name('index');
            Route::get('{visit}', [ConsultationWorkspaceController::class, 'show'])->name('show');
        });
        Route::get('departments/{department}/investigation-services', [ConsultationOrderController::class, 'getDepartmentServices'])->name('departments.investigation-services')->middleware('can:consultations.view');
        Route::get('departments/{department}/investigation-info', [ConsultationOrderController::class, 'getDepartmentInvestigationInfo'])->name('departments.investigation-info')->middleware('can:consultations.view');

        Route::get('handoffs', [JourneyWorklistController::class, 'index'])->name('handoffs.index');
        Route::get('handoffs/refresh', [JourneyWorklistController::class, 'refresh'])->name('handoffs.refresh');
        Route::prefix('handoffs/actions')->name('handoffs.')->group(function () {
            Route::post('claim', [JourneyHandoffAssignmentController::class, 'claim'])->name('claim')->middleware('can:journey.handoffs.claim');
            Route::post('assign', [JourneyHandoffAssignmentController::class, 'assign'])->name('assign')->middleware('can:journey.handoffs.assign');
            Route::post('{assignment}/acknowledge', [JourneyHandoffAssignmentController::class, 'acknowledge'])->name('acknowledge')->middleware('can:journey.handoffs.acknowledge');
            Route::post('{assignment}/resolve', [JourneyHandoffAssignmentController::class, 'resolve'])->name('resolve')->middleware('can:journey.handoffs.resolve');
        });

        Route::middleware(['module:investigations', 'can:lab.requests.view'])->group(function () {
            Route::get('investigations', [LabRequestController::class, 'index'])->name('investigations.index');
            Route::get('investigations/{labRequest}', [LabRequestController::class, 'show'])->name('investigations.show');
            Route::get('lab/requests', [LabRequestController::class, 'index'])->name('lab.requests.index');
            Route::get('lab/requests/{labRequest}', [LabRequestController::class, 'show'])->name('lab.requests.show');
        });

        Route::middleware('can:procedure.view')->group(function () {
            Route::get('procedures', [TheatreController::class, 'index'])->name('procedures.index');
            Route::get('procedures/{procedure}', [TheatreController::class, 'show'])->name('procedures.show');
            Route::get('theatre', [TheatreController::class, 'index'])->name('theatre.index');
            Route::get('theatre/board', [TheatreController::class, 'index'])->name('theatre.board');
            Route::get('theatre/procedures/{procedure}', [TheatreController::class, 'show'])->name('theatre.show');
        });

        Route::get('treatments', [InpatientClinicalWorklistController::class, 'treatments'])->name('treatments.index')->middleware(['module:visits', 'can:visits.view']);
        Route::get('treatments/{visit}', [InpatientClinicalWorklistController::class, 'treatment'])->name('treatments.show')->middleware(['module:visits', 'can:visits.view']);
        Route::get('reports', [ReportController::class, 'admissions'])->name('reports.index')->middleware(['module:reports', 'can:reports.view']);
        Route::get('reports/discharges', [ReportController::class, 'discharges'])->name('reports.discharges')->middleware(['module:reports', 'can:reports.view']);
    });

    // Investigations (diagnostics) workspace. Browser adapters over the existing
    // lab request/sample/result, catalogue, journey and reporting services —
    // queries are bounded to the active performing (target) department by
    // InvestigationWorkspaceScope inside the shared lab controllers.
    Route::prefix('investigations')->name('investigations.')->middleware('department.type:investigation')->group(function () {
        Route::get('/', App\Http\Controllers\Investigations\DashboardController::class)->name('dashboard');
        Route::get('dashboard', fn () => redirect()->route('investigations.dashboard'))->name('dashboard.redirect');

        Route::middleware('module:investigations')->group(function () {
            Route::middleware('can:lab.requests.view')->prefix('requests')->name('lab.requests.')->group(function () {
                Route::get('/', [LabRequestController::class, 'index'])->name('index');
                Route::get('{labRequest}', [LabRequestController::class, 'show'])->name('show');
                Route::patch('{labRequest}/accept', [LabRequestController::class, 'accept'])->name('accept')->middleware('can:lab.results.create');
                Route::post('{labRequest}/accept-selected', [LabRequestController::class, 'acceptSelected'])->name('accept-selected')->middleware('can:lab.results.create');
                Route::patch('{labRequest}/cancel', [LabRequestController::class, 'cancel'])->name('cancel')->middleware('can:lab.results.create');
            });

            Route::middleware('can:lab.samples.view')->prefix('specimens')->name('lab.samples.')->group(function () {
                Route::get('/', [SampleController::class, 'index'])->name('index');
                Route::post('requests/{labRequest}/generate', [SampleController::class, 'generate'])->name('generate')->middleware('can:lab.samples.manage');
                Route::patch('{sample}/collect', [SampleController::class, 'collect'])->name('collect')->middleware('can:lab.samples.collect');
                Route::patch('{sample}/receive', [SampleController::class, 'receive'])->name('receive')->middleware('can:lab.samples.receive');
                Route::patch('{sample}/reject', [SampleController::class, 'reject'])->name('reject')->middleware('can:lab.samples.manage');
                Route::patch('{sample}/dispose', [SampleController::class, 'dispose'])->name('dispose')->middleware('can:lab.samples.manage');
            });

            Route::middleware('can:lab.results.view')->prefix('results')->name('lab.results.')->group(function () {
                Route::get('/', [LabResultController::class, 'index'])->name('index');
                Route::get('requests/{labRequest}', [LabResultController::class, 'showRequest'])->name('show');
                Route::post('batch/{labRequest}', [LabResultController::class, 'batchStore'])->name('batch')->middleware('can:lab.results.create');
                Route::patch('{result}/verify', [LabResultController::class, 'verify'])->name('verify')->middleware('can:lab.results.verify');
                Route::get('{item}/view', [LabResultController::class, 'view'])->name('view');
                Route::get('requests/{labRequest}/print', [LabResultController::class, 'printRequest'])->name('print-request');
                Route::get('{item}/print', [LabResultController::class, 'print'])->name('print');
                Route::post('{item}', [LabResultController::class, 'store'])->name('store')->middleware('can:lab.results.create');
            });

            Route::middleware('can:lab.tests.manage')->group(function () {
                Route::get('tests', [LabTestController::class, 'index'])->name('lab.tests.index');
                Route::get('services', [InvestigationCatalogueController::class, 'index'])->name('investigation-catalogue.index');
                Route::get('services/{service}', [InvestigationCatalogueController::class, 'show'])->name('investigation-catalogue.show');
                Route::get('items', [InvestigationItemController::class, 'index'])->name('items.index');
            });
            Route::get('stock', [InvestigationItemController::class, 'stock'])->name('stock.index')->middleware('can:pharmacy.stock.manage');
        });

        Route::middleware(['module:patients', 'can:patients.view'])->prefix('patients')->name('patients.')->group(function () {
            Route::get('/', [PatientController::class, 'index'])->name('index');
            Route::get('{patient}', [PatientController::class, 'show'])->name('show');
        });

        Route::get('handoffs', [JourneyWorklistController::class, 'index'])->name('handoffs.index');
        Route::get('handoffs/refresh', [JourneyWorklistController::class, 'refresh'])->name('handoffs.refresh');
        Route::prefix('handoffs/actions')->name('handoffs.')->group(function () {
            Route::post('claim', [JourneyHandoffAssignmentController::class, 'claim'])->name('claim')->middleware('can:journey.handoffs.claim');
            Route::post('assign', [JourneyHandoffAssignmentController::class, 'assign'])->name('assign')->middleware('can:journey.handoffs.assign');
            Route::post('{assignment}/acknowledge', [JourneyHandoffAssignmentController::class, 'acknowledge'])->name('acknowledge')->middleware('can:journey.handoffs.acknowledge');
            Route::post('{assignment}/resolve', [JourneyHandoffAssignmentController::class, 'resolve'])->name('resolve')->middleware('can:journey.handoffs.resolve');
        });

        Route::get('reports', [OperationalReportController::class, 'show'])
            ->defaults('report', 'investigations')
            ->name('reports.index')
            ->middleware(['module:reports', 'can:reports.investigations']);
    });

    // Pharmacy (medication fulfilment) workspace. Browser adapters over the
    // existing prescription, dispensing, drug-catalogue, stock, journey and
    // reporting services — billing gates, FEFO batch selection and audit rules
    // stay inside those services.
    Route::prefix('pharmacy')->name('pharmacy.')->middleware('department.type:pharmacy')->group(function () {
        Route::get('/', App\Http\Controllers\Pharmacy\DashboardController::class)->name('dashboard');
        Route::get('dashboard', fn () => redirect()->route('pharmacy.dashboard'))->name('dashboard.redirect');

        Route::middleware('module:pharmacy')->group(function () {
            Route::middleware('can:prescriptions.view')->prefix('prescriptions')->name('prescriptions.')->group(function () {
                Route::get('/', [PrescriptionController::class, 'index'])->name('index');
                Route::get('{prescription}', [PrescriptionController::class, 'show'])->name('show');
                Route::post('{prescription}/bill', [PrescriptionController::class, 'bill'])->name('bill')->middleware('can:pharmacy.dispensing.create');
                Route::patch('{prescription}/cancel', [PrescriptionController::class, 'cancel'])->name('cancel')->middleware('can:prescriptions.create');
            });

            Route::middleware('can:pharmacy.dispensing.view')->group(function () {
                Route::get('dispensing', [DispensingController::class, 'index'])->name('dispensing.index');
                Route::get('dispensing/{prescription}', [DispensingController::class, 'show'])->name('dispensing.show');
                Route::get('dispensing/items/{item}/dosage-print', [DispensingController::class, 'printDosage'])->name('dispensing.print-dosage');
                Route::post('dispensing/{item}/dispense', [DispensingController::class, 'dispenseItem'])->name('dispensing.dispense-item')->middleware('can:pharmacy.dispensing.create');
                Route::post('dispensing/{prescription}/batch', [DispensingController::class, 'batchDispense'])->name('dispensing.batch')->middleware('can:pharmacy.dispensing.create');
                Route::get('history', [DispensingController::class, 'history'])->name('history');
            });

            Route::middleware('can:pharmacy.drugs.manage')->group(function () {
                Route::get('drugs', [DrugController::class, 'index'])->name('drugs.index');
                Route::get('drugs/search', [DrugController::class, 'search'])->name('drugs.search');
                Route::get('drugs/{drug}/history', [DrugController::class, 'history'])->name('drugs.history');
            });
        });

        Route::middleware(['module:inventory', 'can:stock.view'])->group(function () {
            Route::get('stock', [ProductStockController::class, 'balances'])->name('product-stock.balances');
            Route::get('stock/ledger', [ProductStockController::class, 'ledger'])->name('product-stock.ledger');
        });

        Route::middleware(['module:patients', 'can:patients.view'])->prefix('patients')->name('patients.')->group(function () {
            Route::get('/', [PatientController::class, 'index'])->name('index');
            Route::get('{patient}', [PatientController::class, 'show'])->name('show');
        });

        Route::get('handoffs', [JourneyWorklistController::class, 'index'])->name('handoffs.index');
        Route::get('handoffs/refresh', [JourneyWorklistController::class, 'refresh'])->name('handoffs.refresh');
        Route::prefix('handoffs/actions')->name('handoffs.')->group(function () {
            Route::post('claim', [JourneyHandoffAssignmentController::class, 'claim'])->name('claim')->middleware('can:journey.handoffs.claim');
            Route::post('assign', [JourneyHandoffAssignmentController::class, 'assign'])->name('assign')->middleware('can:journey.handoffs.assign');
            Route::post('{assignment}/acknowledge', [JourneyHandoffAssignmentController::class, 'acknowledge'])->name('acknowledge')->middleware('can:journey.handoffs.acknowledge');
            Route::post('{assignment}/resolve', [JourneyHandoffAssignmentController::class, 'resolve'])->name('resolve')->middleware('can:journey.handoffs.resolve');
        });

        Route::get('reports', [OperationalReportController::class, 'show'])
            ->defaults('report', 'pharmacy')
            ->name('reports.index')
            ->middleware(['module:reports', 'can:reports.pharmacy']);
    });

    // Stores (inventory operations) workspace. Browser adapters over the
    // existing requisition, purchase, stock-ledger, supplier, journey and
    // reporting services — approvals, movement posting and audit rules stay
    // inside those services.
    Route::prefix('stores')->name('stores.')->middleware('department.type:stores')->group(function () {
        Route::get('/', App\Http\Controllers\Stores\DashboardController::class)->name('dashboard');
        Route::get('dashboard', fn () => redirect()->route('stores.dashboard'))->name('dashboard.redirect');

        Route::middleware('module:inventory')->group(function () {
            Route::middleware('can:store.requisition.view')->prefix('requisitions')->name('stock-requisitions.')->group(function () {
                Route::get('/', [StockRequisitionController::class, 'index'])->name('index');
                Route::get('create', [StockRequisitionController::class, 'create'])->name('create')->middleware('can:store.requisition.create');
                Route::post('/', [StockRequisitionController::class, 'store'])->name('store')->middleware('can:store.requisition.create');
                Route::get('{stockRequisition}', [StockRequisitionController::class, 'show'])->name('show');
                Route::post('{stockRequisition}/approve', [StockRequisitionController::class, 'approve'])->name('approve')->middleware('can:store.requisition.approve');
                Route::post('{stockRequisition}/issue', [StockRequisitionController::class, 'issue'])->name('issue')->middleware('can:store.requisition.issue');
                Route::post('{stockRequisition}/acknowledge', [StockRequisitionController::class, 'acknowledge'])->name('acknowledge')->middleware('can:store.requisition.acknowledge');
                Route::post('{stockRequisition}/cancel', [StockRequisitionController::class, 'cancel'])->name('cancel')->middleware('can:store.requisition.create');
            });

            Route::middleware('can:store.purchase.view')->group(function () {
                Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
                Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create')->middleware('can:store.purchase.create');
                Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');

                Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
                Route::get('suppliers/{supplier}/ledger', [SupplierController::class, 'ledger'])->name('suppliers.ledger');

                Route::get('stock', [StockController::class, 'balances'])->name('stock.balances');
                Route::get('stock/valuation', [StockController::class, 'valuation'])->name('stock.valuation')->middleware('can:reports.inventory_valuation.view');
                Route::get('stock/ledger', [StockController::class, 'ledger'])->name('stock.ledger');
                Route::get('stock/adjustments', [StockController::class, 'adjustmentsIndex'])->name('stock.adjustments.index');
                Route::get('stock/adjustments/create', [StockController::class, 'adjustmentForm'])->name('stock.adjustments.create')->middleware('can:store.purchase.create');
                Route::get('stock/returns', [StockController::class, 'returnsIndex'])->name('stock.returns.index');
                Route::get('stock/returns/create', [StockController::class, 'returnForm'])->name('stock.returns.create')->middleware('can:store.purchase.create');
                Route::get('stock/transfers', [StockController::class, 'transfersIndex'])->name('stock.transfers.index');
                Route::get('stock/transfers/create', [StockController::class, 'transferForm'])->name('stock.transfers.create')->middleware('can:store.purchase.create');
                Route::get('stock/batches/{batch}', [StockController::class, 'batchShow'])->name('stock.batches.show');
                Route::get('stock/movements/{movement}', [StockController::class, 'movementShow'])->name('stock.movements.show');
                Route::get('stock/locations', [StockController::class, 'locations'])->name('stock.locations.index');
            });

            Route::middleware('can:store.return.view')->prefix('purchase-returns')->name('purchase-returns.')->group(function () {
                Route::get('/', [PurchaseReturnController::class, 'index'])->name('index');
                Route::get('create', [PurchaseReturnController::class, 'create'])->name('create')->middleware('can:store.return.create');
                Route::get('{purchaseReturn}', [PurchaseReturnController::class, 'show'])->name('show');
            });

            Route::middleware('can:product.view')->prefix('products')->name('products.')->group(function () {
                Route::get('/', [ProductController::class, 'index'])->name('index');
                Route::get('{product}', [ProductController::class, 'show'])->whereNumber('product')->name('show');
            });
        });

        Route::get('handoffs', [JourneyWorklistController::class, 'index'])->name('handoffs.index');
        Route::get('handoffs/refresh', [JourneyWorklistController::class, 'refresh'])->name('handoffs.refresh');
        Route::prefix('handoffs/actions')->name('handoffs.')->group(function () {
            Route::post('claim', [JourneyHandoffAssignmentController::class, 'claim'])->name('claim')->middleware('can:journey.handoffs.claim');
            Route::post('assign', [JourneyHandoffAssignmentController::class, 'assign'])->name('assign')->middleware('can:journey.handoffs.assign');
            Route::post('{assignment}/acknowledge', [JourneyHandoffAssignmentController::class, 'acknowledge'])->name('acknowledge')->middleware('can:journey.handoffs.acknowledge');
            Route::post('{assignment}/resolve', [JourneyHandoffAssignmentController::class, 'resolve'])->name('resolve')->middleware('can:journey.handoffs.resolve');
        });

        Route::get('reports', [OperationalReportController::class, 'show'])
            ->defaults('report', 'stock')
            ->name('reports.index')
            ->middleware(['module:reports', 'can:reports.stock']);
    });

    // Finance (billing / cashiering / receivables / accounting) workspace.
    // Browser adapters over the existing invoice, payment, claims, cashier,
    // accounting, journey and reporting services — payment posting, journal
    // balancing and audit rules stay inside those services. Workspace route
    // names mirror the full admin names so the resolver maps them generically.
    Route::prefix('finance')->name('finance.')->middleware('department.type:finance')->group(function () {
        Route::get('/', App\Http\Controllers\Finance\DashboardController::class)->name('dashboard');
        Route::get('dashboard', fn () => redirect()->route('finance.dashboard'))->name('dashboard.redirect');

        Route::middleware('module:billing')->name('billing.')->group(function () {
            Route::middleware('can:invoices.view')->group(function () {
                Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
                Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create')->middleware('can:invoices.create');
                Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store')->middleware('can:invoices.create');
                Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
                Route::patch('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel')->middleware('can:invoices.void');
            });

            Route::middleware('can:payments.view')->group(function () {
                Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
                Route::get('payments/receive', [PaymentController::class, 'receive'])->name('payments.receive')->middleware('can:payments.create');
                Route::post('payments/{invoice}', [PaymentController::class, 'store'])->name('payments.store')->middleware('can:payments.create');
                Route::post('payments/{payment}/reverse', [PaymentController::class, 'reverse'])->name('payments.reverse')->middleware('can:payments.refund');
                Route::post('previous-balance/{patient}/allocate', [PreviousBalanceController::class, 'allocate'])
                    ->name('previous-balance.allocate')
                    ->middleware('can:billing.payment.allocate_cross_visit');
            });

            Route::middleware('can:credit_notes.view')->prefix('credit-notes')->name('credit-notes.')->group(function () {
                Route::get('/', [CreditNoteController::class, 'index'])->name('index');
                Route::get('create', [CreditNoteController::class, 'create'])->name('create')->middleware('can:credit_notes.create');
                Route::post('/', [CreditNoteController::class, 'store'])->name('store')->middleware('can:credit_notes.create');
            });

            Route::get('sponsors', [SponsorController::class, 'index'])->name('sponsors.index')->middleware('can:sponsors.view');

            Route::middleware('can:invoices.view')->prefix('statements')->name('statements.')->group(function () {
                Route::get('/', [BillingReportController::class, 'statements'])->name('index');
                Route::get('{patient}', [BillingReportController::class, 'statementShow'])->name('show');
            });

            Route::get('receivables/aging', [BillingReportController::class, 'aging'])
                ->name('reports.aging')
                ->middleware('can:reports.ar_aging.view');
        });

        Route::middleware(['module:insurance', 'module:claims', 'can:claims.view'])->prefix('claims')->name('claims.')->group(function () {
            Route::get('/', [ClaimController::class, 'index'])->name('index');
            Route::get('create', [ClaimController::class, 'create'])->name('create')->middleware('can:claims.create');
            Route::get('{claim}', [ClaimController::class, 'show'])->whereNumber('claim')->name('show');
            Route::get('{claim}/review', [ClaimController::class, 'review'])->whereNumber('claim')->name('review')->middleware('can:claims.approve');
            Route::post('{claim}/validate', [ClaimController::class, 'validateClaim'])->name('validate')->middleware('can:claims.create');
            Route::post('{claim}/mark-ready', [ClaimController::class, 'markReady'])->name('mark-ready')->middleware('can:claims.create');
            Route::post('{claim}/submit', [ClaimController::class, 'submit'])->name('submit')->middleware('can:claims.create');
            Route::post('{claim}/payments', [ClaimController::class, 'recordPayment'])->name('payments.store')->middleware('can:claims.approve');
        });

        Route::middleware('module:accounting_basic')->name('accounts.')->group(function () {
            Route::middleware('can:accounts.cashier')->group(function () {
                Route::get('cashier', [CashierShiftController::class, 'index'])->name('handover.index');
                Route::post('cashier/open', [CashierShiftController::class, 'open'])->name('handover.open');
                Route::post('cashier/{shift}/close', [CashierShiftController::class, 'close'])->name('handover.close');
                Route::post('cashier/{shift}/verify', [CashierShiftController::class, 'verify'])->name('handover.verify')->middleware('can:accounts.entries.approve');
            });
            Route::middleware('can:accounts.entries.view')->group(function () {
                Route::get('daily-collection', [FinancialEntryController::class, 'dailyCollection'])->name('daily-collection');
                Route::get('reconciliation', [FinancialEntryController::class, 'reconciliation'])->name('reconciliation');
            });
        });

        Route::middleware('module:accounting_advanced')->name('accounting.')->group(function () {
            Route::get('journals', [JournalEntryController::class, 'index'])->name('journals.index')->middleware('can:accounting.journals.view');
            Route::get('journals/{journal}', [JournalEntryController::class, 'show'])->whereNumber('journal')->name('journals.show')->middleware('can:accounting.journals.view');
            Route::get('trial-balance', [AccountingReportController::class, 'trialBalance'])->name('trial-balance')->middleware('can:accounting.reports.trial_balance');
            Route::get('general-ledger', [AccountingReportController::class, 'generalLedger'])->name('general-ledger')->middleware('can:accounting.reports.general_ledger');
            Route::get('chart-of-accounts', [AccountingAccountController::class, 'index'])->name('accounts.index')->middleware('can:accounting.accounts.view');
        });

        Route::middleware(['module:patients', 'can:patients.view'])->prefix('patients')->name('patients.')->group(function () {
            Route::get('/', [PatientController::class, 'index'])->name('index');
            Route::get('{patient}', [PatientController::class, 'show'])->name('show');
        });

        Route::get('handoffs', [JourneyWorklistController::class, 'index'])->name('handoffs.index');
        Route::get('handoffs/refresh', [JourneyWorklistController::class, 'refresh'])->name('handoffs.refresh');
        Route::prefix('handoffs/actions')->name('handoffs.')->group(function () {
            Route::post('claim', [JourneyHandoffAssignmentController::class, 'claim'])->name('claim')->middleware('can:journey.handoffs.claim');
            Route::post('assign', [JourneyHandoffAssignmentController::class, 'assign'])->name('assign')->middleware('can:journey.handoffs.assign');
            Route::post('{assignment}/acknowledge', [JourneyHandoffAssignmentController::class, 'acknowledge'])->name('acknowledge')->middleware('can:journey.handoffs.acknowledge');
            Route::post('{assignment}/resolve', [JourneyHandoffAssignmentController::class, 'resolve'])->name('resolve')->middleware('can:journey.handoffs.resolve');
        });

        Route::get('reports', [OperationalReportController::class, 'show'])
            ->defaults('report', 'billing')
            ->name('reports.index')
            ->middleware(['module:reports', 'can:reports.billing']);
    });

    // Maternity (pregnancy / labour / delivery / postnatal) workspace. Mounts
    // the same maternity module route definitions as /admin/maternity (single
    // source of truth in routes/partials/maternity.php) plus workspace
    // navigation — clinical services and audit rules stay in the module.
    Route::prefix('maternity')->name('maternity.')->middleware('department.type:maternity')->group(function () {
        Route::middleware('can:maternity.view')->group(base_path('routes/partials/maternity.php'));
        Route::get('dashboard', fn () => redirect()->route('maternity.dashboard'))->name('dashboard.redirect');

        Route::middleware(['module:patients', 'can:patients.view'])->prefix('patients')->name('patients.')->group(function () {
            Route::get('/', [PatientController::class, 'index'])->name('index');
            Route::get('{patient}', [PatientController::class, 'show'])->name('show');
        });

        Route::get('handoffs', [JourneyWorklistController::class, 'index'])->name('handoffs.index');
        Route::get('handoffs/refresh', [JourneyWorklistController::class, 'refresh'])->name('handoffs.refresh');
        Route::prefix('handoffs/actions')->name('handoffs.')->group(function () {
            Route::post('claim', [JourneyHandoffAssignmentController::class, 'claim'])->name('claim')->middleware('can:journey.handoffs.claim');
            Route::post('assign', [JourneyHandoffAssignmentController::class, 'assign'])->name('assign')->middleware('can:journey.handoffs.assign');
            Route::post('{assignment}/acknowledge', [JourneyHandoffAssignmentController::class, 'acknowledge'])->name('acknowledge')->middleware('can:journey.handoffs.acknowledge');
            Route::post('{assignment}/resolve', [JourneyHandoffAssignmentController::class, 'resolve'])->name('resolve')->middleware('can:journey.handoffs.resolve');
        });
    });

    // Administrative (hospital operations / governance) workspace. Browser
    // adapters over the existing department, user, HR, audit-log, journey and
    // reporting services — oversight only, specialist workspaces stay
    // authoritative for their own operations.
    Route::prefix('administrative')->name('administrative.')->middleware('department.type:administrative')->group(function () {
        Route::get('/', App\Http\Controllers\Administrative\DashboardController::class)->name('dashboard');
        Route::get('dashboard', fn () => redirect()->route('administrative.dashboard'))->name('dashboard.redirect');

        Route::middleware('can:departments.view')->group(function () {
            Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
            Route::get('designations', [DesignationController::class, 'index'])->name('designations.index');
        });

        Route::get('users', [UserController::class, 'index'])->name('users.index')->middleware('can:users.view');
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index')->middleware('can:roles.manage');

        Route::middleware('module:hr')->prefix('hr')->name('hr.')->group(function () {
            Route::middleware('can:hr.employees.view')->group(function () {
                Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
                Route::get('employees/{employee}', [EmployeeController::class, 'show'])->whereNumber('employee')->name('employees.show');
            });
            Route::middleware('can:hr.attendance.view')->group(function () {
                Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
                Route::get('attendance/summary', [AttendanceController::class, 'summary'])->name('attendance.summary');
            });
            Route::get('leave', [LeaveController::class, 'index'])->name('leave.index')->middleware('can:hr.leave.view');
        });

        Route::middleware('can:logs.view')->prefix('audit')->name('logs.')->group(function () {
            Route::get('/', [ActivityLogController::class, 'index'])->name('index');
            Route::get('{activityLog}', [ActivityLogController::class, 'show'])->whereNumber('activityLog')->name('show');
        });

        Route::get('announcements', [NotificationBroadcastController::class, 'create'])
            ->name('notifications.broadcast.create')
            ->middleware(['module:notifications', 'can:notifications.broadcast']);

        Route::middleware(['module:reports', 'can:reports.view'])->name('reports.')->prefix('reports')->group(function () {
            Route::get('/', [ReportsHubController::class, 'index'])->name('index');
            Route::get('department-metrics', [DepartmentMetricsController::class, 'index'])->name('department-metrics');
            Route::get('department-comparison', [DepartmentComparisonController::class, 'index'])
                ->name('department-comparison.index')
                ->middleware('can:reports.department_comparison.view');
        });
        Route::get('analytics', [JourneyAnalyticsController::class, 'index'])->name('journey.analytics');

        Route::get('handoffs', [JourneyWorklistController::class, 'index'])->name('handoffs.index');
        Route::get('handoffs/refresh', [JourneyWorklistController::class, 'refresh'])->name('handoffs.refresh');
        Route::prefix('handoffs/actions')->name('handoffs.')->group(function () {
            Route::post('claim', [JourneyHandoffAssignmentController::class, 'claim'])->name('claim')->middleware('can:journey.handoffs.claim');
            Route::post('assign', [JourneyHandoffAssignmentController::class, 'assign'])->name('assign')->middleware('can:journey.handoffs.assign');
            Route::post('{assignment}/acknowledge', [JourneyHandoffAssignmentController::class, 'acknowledge'])->name('acknowledge')->middleware('can:journey.handoffs.acknowledge');
            Route::post('{assignment}/resolve', [JourneyHandoffAssignmentController::class, 'resolve'])->name('resolve')->middleware('can:journey.handoffs.resolve');
        });
    });

    // Nursing Department OPD workspace. Clinical writes are delegated to the
    // existing triage/vitals controllers so validation, safety and audit rules
    // remain identical to the generic workflow.
    Route::prefix('nursing')->name('nursing.')->middleware(['department.type:nursing', 'nursing.opd.scope'])->group(function () {
        Route::get('/', fn () => redirect()->route('nursing.dashboard'))->name('dashboard.redirect');
        Route::get('dashboard', App\Http\Controllers\Nursing\DashboardController::class)->middleware(['module:visits', 'can:visits.view'])->name('dashboard');

        Route::middleware(['module:patients', 'can:patients.view'])->prefix('patients')->name('patients.')->group(function () {
            Route::get('/', [PatientController::class, 'index'])->name('index');
            Route::get('{patient}', [PatientController::class, 'show'])->name('show');
        });

        Route::middleware(['module:visits', 'can:visits.view'])->group(function () {
            Route::get('opd', [OpdController::class, 'index'])->name('opd.index');
            Route::get('opd/queue', [OpdController::class, 'index'])->name('opd.queue');
            Route::get('opd/active', [OpdController::class, 'index'])->name('opd.active')->defaults('category', 'active');
            Route::get('opd/completed', [OpdController::class, 'index'])->name('opd.completed')->defaults('category', 'completed_today');
            Route::get('opd/{visit}', [OpdController::class, 'show'])->name('opd.show');
            Route::get('visits', [VisitController::class, 'index'])->name('visits.index');
            Route::get('visits/{visit}', [VisitController::class, 'show'])->name('visits.show');
        });

        Route::middleware(['module:triage', 'can:vitals.view'])->group(function () {
            Route::get('triage', [TriageController::class, 'index'])->name('triage.index');
            Route::get('triage/{visit}', [TriageController::class, 'show'])->name('triage.show');
            Route::get('triage/{visit}/assess', [TriageController::class, 'create'])->name('triage.create')->middleware('can:vitals.create');
            Route::get('triage/{visit}/edit', [TriageController::class, 'create'])->name('triage.edit')->middleware('can:vitals.create');
            Route::post('triage/{visit}', [TriageController::class, 'store'])->name('triage.store')->middleware('can:vitals.create');
            Route::put('triage/{visit}/assess', [TriageController::class, 'update'])->name('triage.update')->middleware('can:vitals.create');

            Route::get('vitals/worklist', [VitalController::class, 'create'])->name('vitals.index');
            Route::get('vitals', [VitalController::class, 'create'])->name('vitals.create');
            Route::post('vitals', [VitalController::class, 'store'])->name('vitals.store')->middleware('can:vitals.create');
            Route::get('vitals/{visit}', [VitalController::class, 'show'])->name('vitals.show');
            Route::patch('vitals/{visit}/update-priority', [VitalController::class, 'updatePriority'])->name('vitals.update-priority')->middleware('can:vitals.create');
            Route::patch('vitals/{visit}/assign-consultation', [VitalController::class, 'assignConsultation'])->name('vitals.assign-consultation')->middleware('can:vitals.create');
        });

        Route::middleware(['module:consultation', 'can:consultations.view'])->prefix('consultations')->name('consultations.')->group(function () {
            Route::get('/', [ConsultationController::class, 'index'])->name('index');
            Route::get('{visit}', [ConsultationController::class, 'show'])->name('show');
            Route::get('{visit}/history', [ConsultationController::class, 'history'])->name('history');
        });

        Route::prefix('service-renderings')
            ->name('service-renderings.')
            ->middleware('can:service_rendering.view')
            ->group(function () {
                Route::get('/', [ServiceRenderingController::class, 'index'])->name('index');
                Route::get('/reports', [ServiceRenderingReportController::class, 'index'])->name('reports')->middleware('can:service_rendering.reports');
                Route::post('/', [ServiceRenderingController::class, 'store'])->name('store')->middleware('can:invoices.create');
                Route::get('/visit-search', [ServiceRenderingController::class, 'visitSearch'])->name('visit-search')->middleware('can:invoices.create');
                Route::get('/service-search', [ServiceRenderingController::class, 'serviceSearch'])->name('service-search')->middleware('can:invoices.create');
                Route::get('/{serviceRendering}', [ServiceRenderingController::class, 'show'])->name('show');
                Route::post('/{serviceRendering}/start', [ServiceRenderingActionController::class, 'start'])->name('start')->middleware('can:service_rendering.start');
                Route::post('/{serviceRendering}/mark-rendered', [ServiceRenderingActionController::class, 'markRendered'])->name('mark-rendered')->middleware('can:service_rendering.mark_rendered');
                Route::post('/{serviceRendering}/mark-not-rendered', [ServiceRenderingActionController::class, 'markNotRendered'])->name('mark-not-rendered')->middleware('can:service_rendering.mark_not_rendered');
                Route::post('/{serviceRendering}/cancel', [ServiceRenderingActionController::class, 'cancel'])->name('cancel')->middleware('can:service_rendering.cancel');
                Route::patch('/{serviceRendering}/notes', [ServiceRenderingActionController::class, 'updateNotes'])->name('notes')->middleware('can:service_rendering.edit_notes');
            });

        Route::middleware(['module:visits', 'can:visits.view'])->group(function () {
            Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index')->middleware('can:clinical_tasks.view');
            Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show')->middleware('can:clinical_tasks.view');
            Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update')->middleware('can:clinical_tasks.complete');
            Route::get('treatments', [TreatmentController::class, 'index'])->name('treatments.index');
            Route::get('treatments/{visit}', [TreatmentController::class, 'show'])->name('treatments.show');
        });

        Route::get('handoffs', [JourneyWorklistController::class, 'index'])->name('handoffs.index');
        Route::get('handoffs/refresh', [JourneyWorklistController::class, 'refresh'])->name('handoffs.refresh');
        Route::prefix('handoffs/actions')->name('handoffs.')->group(function () {
            Route::post('claim', [JourneyHandoffAssignmentController::class, 'claim'])->name('claim')->middleware('can:journey.handoffs.claim');
            Route::post('assign', [JourneyHandoffAssignmentController::class, 'assign'])->name('assign')->middleware('can:journey.handoffs.assign');
            Route::post('{assignment}/acknowledge', [JourneyHandoffAssignmentController::class, 'acknowledge'])->name('acknowledge')->middleware('can:journey.handoffs.acknowledge');
            Route::post('{assignment}/resolve', [JourneyHandoffAssignmentController::class, 'resolve'])->name('resolve')->middleware('can:journey.handoffs.resolve');
        });

        Route::get('reports', [App\Http\Controllers\Nursing\ReportController::class, 'index'])
            ->middleware(['module:reports', 'can:reports.view'])->name('reports.index');
    });

    // Records Department browser workspace. These routes reuse existing domain logic.
    Route::prefix('records')->name('records.')->middleware('department.type:records')->group(function () {
        Route::get('/', App\Http\Controllers\Records\DashboardController::class)->name('dashboard');
        Route::get('dashboard', fn () => redirect()->route('records.dashboard'))->name('dashboard.redirect');

        Route::middleware(['module:patients', 'can:patients.view'])->prefix('patients')->name('patients.')->group(function () {
            Route::get('/', [PatientController::class, 'index'])->name('index');
            Route::get('merge', [PatientMergeController::class, 'index'])->name('merge.index')->middleware('can:patients.merge.view');
            Route::get('merge/search', [PatientMergeController::class, 'search'])->name('merge.search')->middleware('can:patients.merge.view');
            Route::get('merge/compare', [PatientMergeController::class, 'compare'])->name('merge.compare')->middleware('can:patients.merge.request');
            Route::post('merge/requests', [PatientMergeController::class, 'store'])->name('merge.requests.store')->middleware('can:patients.merge.request');
            Route::get('merge/requests/{mergeRequest}', [PatientMergeController::class, 'show'])->name('merge.requests.show')->middleware('can:patients.merge.view');
            Route::post('merge/requests/{mergeRequest}/execute', [PatientMergeController::class, 'execute'])->name('merge.requests.execute')->middleware('can:patients.merge.execute');
            Route::get('merge/logs', [PatientMergeController::class, 'logs'])->name('merge.logs')->middleware('can:patients.merge.view');
            Route::get('create', [PatientController::class, 'create'])->name('create')->middleware('can:patients.create');
            Route::post('/', [PatientController::class, 'store'])->name('store')->middleware('can:patients.create');
            Route::get('{patient}', [PatientController::class, 'show'])->name('show');
            Route::patch('{patient}/medical-summary', [PatientController::class, 'updateMedicalSummary'])->name('medical-summary.update');
            Route::post('{patient}/privacy/break-glass', [PatientPrivacyController::class, 'startBreakGlass'])->name('privacy.break-glass.start')->middleware('can:patients.privacy.break_glass');
            Route::post('privacy/break-glass/{override}/revoke', [PatientPrivacyController::class, 'revokeBreakGlass'])->name('privacy.break-glass.revoke')->middleware('can:patients.privacy.break_glass');
            Route::post('{patient}/privacy/directives', [PatientPrivacyController::class, 'storeDirective'])->name('privacy-directives.store')->middleware('can:patients.privacy_directives.manage');
            Route::get('{patient}/edit', [PatientController::class, 'edit'])->name('edit')->middleware('can:patients.edit');
            Route::put('{patient}', [PatientController::class, 'update'])->name('update')->middleware('can:patients.edit');
            Route::patch('{patient}/toggle-status', [PatientController::class, 'toggleStatus'])->name('toggle-status')->middleware('can:patients.edit');
            Route::patch('{patient}/mark-deceased', [PatientController::class, 'markDeceased'])->name('mark-deceased')->middleware('can:patients.mark_deceased');

            Route::prefix('{patient}/financial-risk')->name('financial-risk.')->group(function () {
                Route::post('/', [PatientFinancialRiskController::class, 'store'])->name('store')->middleware('can:patients.financial_risk.manage');
                Route::put('{profile}', [PatientFinancialRiskController::class, 'update'])->name('update')->middleware('can:patients.financial_risk.manage');
                Route::post('{profile}/submit-review', [PatientFinancialRiskController::class, 'submitForReview'])->name('submit-review')->middleware('can:patients.financial_risk.review');
                Route::post('{profile}/complete-review', [PatientFinancialRiskController::class, 'completeReview'])->name('complete-review')->middleware('can:patients.financial_risk.review');
                Route::post('{profile}/suspend', [PatientFinancialRiskController::class, 'suspend'])->name('suspend')->middleware('can:patients.financial_risk.review');
                Route::post('{profile}/reactivate', [PatientFinancialRiskController::class, 'reactivate'])->name('reactivate')->middleware('can:patients.financial_risk.review');
                Route::post('{profile}/clear', [PatientFinancialRiskController::class, 'clear'])->name('clear')->middleware('can:patients.financial_risk.clear');
            });

            Route::post('{patient}/insurances', [PatientInsuranceController::class, 'store'])
                ->name('insurances.store')
                ->middleware(['module:insurance', 'can:patients.insurance.create']);

            Route::middleware(['module:insurance', 'can:patients.edit'])->group(function () {
                Route::put('{patient}/insurances/{insurance}', [PatientInsuranceController::class, 'update'])->name('insurances.update');
                Route::delete('{patient}/insurances/{insurance}', [PatientInsuranceController::class, 'destroy'])->name('insurances.destroy');
                Route::patch('{patient}/insurances/{insurance}/set-primary', [PatientInsuranceController::class, 'setPrimary'])->name('insurances.set-primary');
            });

            Route::middleware('can:patients.edit')->group(function () {
                Route::post('{patient}/emergency-contacts', [EmergencyContactController::class, 'store'])->name('emergency-contacts.store');
                Route::put('{patient}/emergency-contacts/{contact}', [EmergencyContactController::class, 'update'])->name('emergency-contacts.update');
                Route::delete('{patient}/emergency-contacts/{contact}', [EmergencyContactController::class, 'destroy'])->name('emergency-contacts.destroy');
            });
        });

        Route::middleware(['module:visits', 'can:visits.view'])->prefix('visits')->name('visits.')->group(function () {
            Route::get('/', [VisitController::class, 'index'])->name('index');
            Route::get('create', [VisitController::class, 'create'])->name('create')->middleware('can:visits.create');
            Route::post('/', [VisitController::class, 'store'])->name('store')->middleware('can:visits.create');
            Route::get('patient-search', [VisitController::class, 'patientSearch'])->name('patient-search');
            Route::get('attendance-preview', [VisitController::class, 'attendancePreview'])->name('attendance-preview');
            Route::get('patient-insurances', [VisitController::class, 'patientInsurances'])->name('patient-insurances')->middleware('module:insurance');
            Route::get('department-services', [VisitController::class, 'departmentServices'])->name('department-services');
            Route::get('doctors-for-services', [VisitController::class, 'doctorsForServices'])->name('doctors-for-services');
            Route::get('services-for-doctor', [VisitController::class, 'servicesForDoctor'])->name('services-for-doctor');
            Route::get('service-price', [VisitController::class, 'servicePrice'])->name('service-price');
            Route::get('{visit}', [VisitController::class, 'show'])->name('show');
            Route::get('{visit}/preview', [VisitPreviewController::class, 'show'])->name('preview')->middleware('can:visits.preview');
            Route::get('{visit}/edit', [VisitController::class, 'edit'])->name('edit')->middleware('can:visits.edit');
            Route::put('{visit}', [VisitController::class, 'update'])->name('update')->middleware('can:visits.edit');
            Route::patch('{visit}/insurance', [VisitController::class, 'updateInsurance'])->name('insurance.update')->middleware(['module:insurance', 'can:visits.edit']);
            Route::patch('{visit}/transition', [VisitController::class, 'transition'])->name('transition')->middleware('can:visits.transition');
            Route::patch('{visit}/send-to-department', [VisitController::class, 'sendToDepartment'])->name('send-to-department')->middleware('can:visits.transition');
        });

        Route::middleware(['module:appointments', 'can:appointments.view'])->prefix('appointments')->name('appointments.')->group(function () {
            Route::get('/', [AppointmentController::class, 'index'])->name('index');
            Route::get('create', [AppointmentController::class, 'create'])->name('create')->middleware('can:appointments.create');
            Route::post('/', [AppointmentController::class, 'store'])->name('store')->middleware('can:appointments.create');
            Route::get('patient-search', [AppointmentController::class, 'patientSearch'])->name('patient-search')->middleware('can:appointments.create');
            Route::get('patient-insurances', [AppointmentController::class, 'patientInsurances'])->name('patient-insurances')->middleware(['module:insurance', 'can:appointments.create']);
            Route::get('department-services', [AppointmentController::class, 'departmentServices'])->name('department-services')->middleware('can:appointments.create');
            Route::get('doctors-for-services', [AppointmentController::class, 'doctorsForServices'])->name('doctors-for-services')->middleware('can:appointments.create');
            Route::get('services-for-doctor', [AppointmentController::class, 'servicesForDoctor'])->name('services-for-doctor')->middleware('can:appointments.create');
            Route::get('service-price', [AppointmentController::class, 'servicePrice'])->name('service-price')->middleware('can:appointments.create');
            Route::get('calendar', [AppointmentController::class, 'calendar'])->name('calendar');
            Route::get('{appointment}', [AppointmentController::class, 'show'])->name('show');
            Route::get('{appointment}/edit', [AppointmentController::class, 'edit'])->name('edit')->middleware('can:appointments.edit');
            Route::put('{appointment}', [AppointmentController::class, 'update'])->name('update')->middleware('can:appointments.edit');
            Route::post('{appointment}/check-in', [AppointmentController::class, 'checkIn'])->name('check-in')->middleware('can:appointments.checkin');
            Route::patch('{appointment}/transition', [AppointmentController::class, 'transition'])->name('transition')->middleware('can:appointments.edit');
            Route::post('{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('cancel')->middleware('can:appointments.edit');
            Route::post('{appointment}/no-show', [AppointmentController::class, 'noShow'])->name('no-show')->middleware('can:appointments.edit');
        });

        Route::middleware(['module:triage', 'can:vitals.view'])->group(function () {
            Route::get('triage', [TriageController::class, 'index'])->name('triage.index');
            Route::get('triage/{visit}', [TriageController::class, 'show'])->name('triage.show');
            Route::get('triage/{visit}/assess', [TriageController::class, 'create'])->name('triage.create')->middleware('can:vitals.create');
            Route::post('triage/{visit}', [TriageController::class, 'store'])->name('triage.store')->middleware('can:vitals.create');
            Route::put('triage/{visit}/assess', [TriageController::class, 'update'])->name('triage.update')->middleware('can:vitals.create');

            Route::get('vitals', [VitalController::class, 'create'])->name('vitals.create');
            Route::post('vitals', [VitalController::class, 'store'])->name('vitals.store')->middleware('can:vitals.create');
            Route::get('vitals/{visit}', [VitalController::class, 'show'])->name('vitals.show');
            Route::patch('vitals/{visit}/update-priority', [VitalController::class, 'updatePriority'])->name('vitals.update-priority')->middleware('can:vitals.create');
            Route::patch('vitals/{visit}/assign-consultation', [VitalController::class, 'assignConsultation'])->name('vitals.assign-consultation')->middleware('can:vitals.create');
        });

        Route::prefix('service-renderings')
            ->name('service-renderings.')
            ->middleware('can:service_rendering.view')
            ->group(function () {
                Route::get('/', [ServiceRenderingController::class, 'index'])->name('index');
                Route::get('/reports', [ServiceRenderingReportController::class, 'index'])->name('reports')->middleware('can:service_rendering.reports');
                Route::post('/', [ServiceRenderingController::class, 'store'])->name('store')->middleware('can:invoices.create');
                Route::get('/visit-search', [ServiceRenderingController::class, 'visitSearch'])->name('visit-search')->middleware('can:invoices.create');
                Route::get('/service-search', [ServiceRenderingController::class, 'serviceSearch'])->name('service-search')->middleware('can:invoices.create');
                Route::get('/{serviceRendering}', [ServiceRenderingController::class, 'show'])->name('show');
                Route::post('/{serviceRendering}/start', [ServiceRenderingActionController::class, 'start'])->name('start')->middleware('can:service_rendering.start');
                Route::post('/{serviceRendering}/mark-rendered', [ServiceRenderingActionController::class, 'markRendered'])->name('mark-rendered')->middleware('can:service_rendering.mark_rendered');
                Route::post('/{serviceRendering}/mark-not-rendered', [ServiceRenderingActionController::class, 'markNotRendered'])->name('mark-not-rendered')->middleware('can:service_rendering.mark_not_rendered');
                Route::post('/{serviceRendering}/cancel', [ServiceRenderingActionController::class, 'cancel'])->name('cancel')->middleware('can:service_rendering.cancel');
                Route::patch('/{serviceRendering}/notes', [ServiceRenderingActionController::class, 'updateNotes'])->name('notes')->middleware('can:service_rendering.edit_notes');
            });

        Route::middleware(['module:insurance', 'module:claims', 'can:claims.view', 'records.redirect'])->group(function () {
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

        Route::prefix('front-desk')->name('front-desk.')->middleware('can:front_desk.view')->group(function () {
            Route::get('/', [FrontDeskDashboardController::class, 'index'])->name('index')->middleware('can:front_desk.dashboard.view');

            Route::middleware('can:front_desk.visitors.view')->group(function () {
                Route::get('visitors', [VisitorLogController::class, 'index'])->name('visitors.index');
                Route::get('visitors/create', [VisitorLogController::class, 'create'])->name('visitors.create')->middleware('can:front_desk.visitors.create');
                Route::post('visitors', [VisitorLogController::class, 'store'])->name('visitors.store')->middleware('can:front_desk.visitors.create');
                Route::get('visitors/patient/{patient}', [VisitorLogController::class, 'patientHistory'])->name('visitors.patient-history');
                Route::get('visitors/admission/{admission}', [VisitorLogController::class, 'admissionHistory'])->name('visitors.admission-history');
                Route::get('visitors/{visitor}', [VisitorLogController::class, 'show'])->name('visitors.show');
                Route::get('visitors/{visitor}/edit', [VisitorLogController::class, 'edit'])->name('visitors.edit')->middleware('can:front_desk.visitors.update');
                Route::put('visitors/{visitor}', [VisitorLogController::class, 'update'])->name('visitors.update')->middleware('can:front_desk.visitors.update');
                Route::get('visitors/{visitor}/pass', [VisitorLogController::class, 'pass'])->name('visitors.pass')->middleware('can:front_desk.visitors.print_pass');
                Route::post('visitors/{visitor}/check-out', [VisitorLogController::class, 'checkOut'])->name('visitors.check-out')->middleware('can:front_desk.visitors.checkout');
            });

            Route::middleware('can:front_desk.calls.view')->group(function () {
                Route::get('calls', [CallLogController::class, 'index'])->name('calls.index');
                Route::get('calls/create', [CallLogController::class, 'create'])->name('calls.create')->middleware('can:front_desk.calls.create');
                Route::post('calls', [CallLogController::class, 'store'])->name('calls.store')->middleware('can:front_desk.calls.create');
                Route::get('calls/follow-ups', [CallLogController::class, 'followUps'])->name('calls.follow-ups')->middleware('can:front_desk.calls.followups.view');
                Route::get('calls/{call}', [CallLogController::class, 'show'])->name('calls.show');
                Route::get('calls/{call}/edit', [CallLogController::class, 'edit'])->name('calls.edit')->middleware('can:front_desk.calls.update');
                Route::put('calls/{call}', [CallLogController::class, 'update'])->name('calls.update')->middleware('can:front_desk.calls.update');
                Route::post('calls/{call}/assign-follow-up', [CallLogController::class, 'assignFollowUp'])->name('calls.assign-follow-up')->middleware('can:front_desk.calls.followups.assign');
                Route::post('calls/{call}/complete-follow-up', [CallLogController::class, 'completeFollowUp'])->name('calls.complete-follow-up')->middleware('can:front_desk.calls.followups.complete');
                Route::post('calls/{call}/cancel-follow-up', [CallLogController::class, 'cancelFollowUp'])->name('calls.cancel-follow-up')->middleware('can:front_desk.calls.followups.complete');
                Route::post('calls/{call}/transfer', [CallLogController::class, 'transfer'])->name('calls.transfer')->middleware('can:front_desk.calls.transfer');
                Route::post('calls/{call}/follow-up-complete', [CallLogController::class, 'completeFollowUp'])->name('calls.follow-up-complete')->middleware('can:front_desk.calls.update');
            });

            Route::middleware('can:front_desk.couriers.view')->group(function () {
                Route::get('couriers', [CourierLogController::class, 'index'])->name('couriers.index');
                Route::get('couriers/create', [CourierLogController::class, 'create'])->name('couriers.create')->middleware('can:front_desk.couriers.create');
                Route::post('couriers', [CourierLogController::class, 'store'])->name('couriers.store')->middleware('can:front_desk.couriers.create');
                Route::get('couriers/workflow', [CourierLogController::class, 'workflow'])->name('couriers.workflow')->middleware('can:front_desk.couriers.workflow.view');
                Route::get('couriers/{courier}', [CourierLogController::class, 'show'])->name('couriers.show');
                Route::get('couriers/{courier}/edit', [CourierLogController::class, 'edit'])->name('couriers.edit')->middleware('can:front_desk.couriers.update');
                Route::put('couriers/{courier}', [CourierLogController::class, 'update'])->name('couriers.update')->middleware('can:front_desk.couriers.update');
                Route::post('couriers/{courier}/dispatch', [CourierLogController::class, 'dispatchItem'])->name('couriers.dispatch')->middleware('can:front_desk.couriers.dispatch');
                Route::post('couriers/{courier}/handover', [CourierLogController::class, 'handover'])->name('couriers.handover')->middleware('can:front_desk.couriers.handover');
                Route::post('couriers/{courier}/mark-delivered', [CourierLogController::class, 'markDelivered'])->name('couriers.mark-delivered')->middleware('can:front_desk.couriers.deliver');
                Route::post('couriers/{courier}/mark-returned', [CourierLogController::class, 'markReturned'])->name('couriers.mark-returned')->middleware('can:front_desk.couriers.return');
            });

            Route::middleware('can:front_desk.handovers.view')->group(function () {
                Route::get('handovers', [ShiftHandoverController::class, 'index'])->name('handovers.index');
                Route::get('handovers/create', [ShiftHandoverController::class, 'create'])->name('handovers.create')->middleware('can:front_desk.handovers.create');
                Route::post('handovers', [ShiftHandoverController::class, 'store'])->name('handovers.store')->middleware('can:front_desk.handovers.create');
                Route::get('handovers/{handover}', [ShiftHandoverController::class, 'show'])->name('handovers.show');
                Route::get('handovers/{handover}/edit', [ShiftHandoverController::class, 'edit'])->name('handovers.edit')->middleware('can:front_desk.handovers.update');
                Route::put('handovers/{handover}', [ShiftHandoverController::class, 'update'])->name('handovers.update')->middleware('can:front_desk.handovers.update');
                Route::post('handovers/{handover}/submit', [ShiftHandoverController::class, 'submit'])->name('handovers.submit')->middleware('can:front_desk.handovers.submit');
                Route::post('handovers/{handover}/accept', [ShiftHandoverController::class, 'accept'])->name('handovers.accept')->middleware('can:front_desk.handovers.accept');
                Route::post('handovers/{handover}/cancel', [ShiftHandoverController::class, 'cancel'])->name('handovers.cancel')->middleware('can:front_desk.handovers.cancel');
            });

            Route::middleware('can:front_desk.lost_found.view')->group(function () {
                Route::get('lost-found', [LostFoundController::class, 'index'])->name('lost-found.index');
                Route::get('lost-found/create', [LostFoundController::class, 'create'])->name('lost-found.create')->middleware('can:front_desk.lost_found.create');
                Route::post('lost-found', [LostFoundController::class, 'store'])->name('lost-found.store')->middleware('can:front_desk.lost_found.create');
                Route::get('lost-found/{lostFound}', [LostFoundController::class, 'show'])->name('lost-found.show');
                Route::get('lost-found/{lostFound}/edit', [LostFoundController::class, 'edit'])->name('lost-found.edit')->middleware('can:front_desk.lost_found.update');
                Route::put('lost-found/{lostFound}', [LostFoundController::class, 'update'])->name('lost-found.update')->middleware('can:front_desk.lost_found.update');
                Route::post('lost-found/{lostFound}/claim', [LostFoundController::class, 'claim'])->name('lost-found.claim')->middleware('can:front_desk.lost_found.claim');
                Route::post('lost-found/{lostFound}/release', [LostFoundController::class, 'release'])->name('lost-found.release')->middleware('can:front_desk.lost_found.release');
                Route::post('lost-found/{lostFound}/cancel', [LostFoundController::class, 'cancel'])->name('lost-found.cancel')->middleware('can:front_desk.lost_found.cancel');
            });

            Route::middleware('can:front_desk.incidents.view')->group(function () {
                Route::get('incidents', [IncidentLogController::class, 'index'])->name('incidents.index');
                Route::get('incidents/create', [IncidentLogController::class, 'create'])->name('incidents.create')->middleware('can:front_desk.incidents.create');
                Route::post('incidents', [IncidentLogController::class, 'store'])->name('incidents.store')->middleware('can:front_desk.incidents.create');
                Route::get('incidents/{incident}', [IncidentLogController::class, 'show'])->name('incidents.show');
                Route::get('incidents/{incident}/edit', [IncidentLogController::class, 'edit'])->name('incidents.edit')->middleware('can:front_desk.incidents.update');
                Route::put('incidents/{incident}', [IncidentLogController::class, 'update'])->name('incidents.update')->middleware('can:front_desk.incidents.update');
                Route::post('incidents/{incident}/assign', [IncidentLogController::class, 'assign'])->name('incidents.assign')->middleware('can:front_desk.incidents.assign');
                Route::post('incidents/{incident}/escalate', [IncidentLogController::class, 'escalate'])->name('incidents.escalate')->middleware('can:front_desk.incidents.escalate');
                Route::post('incidents/{incident}/resolve', [IncidentLogController::class, 'resolve'])->name('incidents.resolve')->middleware('can:front_desk.incidents.resolve');
                Route::post('incidents/{incident}/cancel', [IncidentLogController::class, 'cancel'])->name('incidents.cancel')->middleware('can:front_desk.incidents.cancel');
            });

            Route::middleware('can:front_desk.reports.view')->group(function () {
                Route::get('reports', [FrontDeskReportController::class, 'index'])->name('reports.index');
                Route::get('reports/data', [FrontDeskReportController::class, 'data'])->name('reports.data');
                Route::get('reports/export', [FrontDeskReportController::class, 'export'])->name('reports.export')->middleware('can:front_desk.reports.export');
            });
        });

        Route::middleware(['module:reports', 'can:reports.view'])->prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [App\Http\Controllers\Records\ReportController::class, 'index'])->name('index');
            Route::get('patients', [ReportController::class, 'patients'])->name('patients');
            Route::get('visits', [ReportController::class, 'visits'])->name('visits');
            Route::get('attendance', [ReportController::class, 'visits'])->name('attendance');
            Route::get('insurance-claims', [ReportController::class, 'insuranceClaims'])->name('insurance-claims')->middleware('module:insurance');
            Route::get('claims', [ReportController::class, 'claims'])->name('claims')->middleware(['module:insurance', 'module:claims']);
            Route::get('daily-collection', [ReportController::class, 'dailyCollection'])->name('daily-collection');
        });
    });

    // Generic dashboard entry — routes every user to their resolved department dashboard.
    Route::get('dashboard', fn () => redirect()->route('admin.my-dashboard'))->name('dashboard');

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
        Route::get('dashboard', [DashboardController::class, 'index'])
            ->name('dashboard')
            ->middleware('can:patients.view');

        // Department-type dashboard — resolves the right dashboard for the user.
        Route::get('my-dashboard', [DepartmentDashboardController::class, 'index'])->name('my-dashboard');
        Route::post('my-dashboard/context', [DepartmentContextController::class, 'store'])
            ->name('my-dashboard.context.store')
            ->middleware('can:departments.context.switch');
        Route::delete('my-dashboard/context', [DepartmentContextController::class, 'destroy'])
            ->name('my-dashboard.context.destroy')
            ->middleware('can:departments.context.switch');

        // Modern role dashboards (Preclinic design language) — read-only, role-gated in controller.
        Route::prefix('dashboards')->name('dashboards.')->group(function () {
            Route::get('/', [RoleDashboardController::class, 'index'])->name('index');
            Route::get('receptionist', [RoleDashboardController::class, 'receptionist'])->name('receptionist');
            Route::get('doctor', [RoleDashboardController::class, 'doctor'])->name('doctor');
            Route::get('nurse', [RoleDashboardController::class, 'nurse'])->name('nurse');
            Route::get('pharmacist', [RoleDashboardController::class, 'pharmacist'])->name('pharmacist');
        });

        // Phase 9.3 — patient flow worklist (capability-gated in the controller).
        Route::get('journey/worklist', [JourneyWorklistController::class, 'index'])->name('journey.worklist');
        // Phase 9.5 — live refresh (returns the rows + summary partial).
        Route::get('journey/worklist/refresh', [JourneyWorklistController::class, 'refresh'])->name('journey.worklist.refresh');
        // Phase 9.7 — per-user journey notification preferences.
        Route::get('settings/journey-notifications', [JourneyNotificationPreferenceController::class, 'show'])->name('settings.journey-notifications');
        Route::put('settings/journey-notifications', [JourneyNotificationPreferenceController::class, 'update'])
            ->name('settings.journey-notifications.update')
            ->middleware('can:settings.journey_notifications.update');

        // Phase 9.8 — journey SLA / operational performance analytics (capability-gated).
        Route::get('journey/analytics', [JourneyAnalyticsController::class, 'index'])->name('journey.analytics');
        Route::get('journey/analytics/export', [JourneyAnalyticsController::class, 'export'])->name('journey.analytics.export');

        // Phase 9.5 — handoff coordination actions (authorised in the service).
        Route::prefix('journey/handoffs')->name('journey.handoffs.')->group(function () {
            Route::post('claim', [JourneyHandoffAssignmentController::class, 'claim'])
                ->name('claim')
                ->middleware('can:journey.handoffs.claim');
            Route::post('assign', [JourneyHandoffAssignmentController::class, 'assign'])
                ->name('assign')
                ->middleware('can:journey.handoffs.assign');
            Route::post('{assignment}/acknowledge', [JourneyHandoffAssignmentController::class, 'acknowledge'])
                ->name('acknowledge')
                ->middleware('can:journey.handoffs.acknowledge');
            Route::post('{assignment}/resolve', [JourneyHandoffAssignmentController::class, 'resolve'])
                ->name('resolve')
                ->middleware('can:journey.handoffs.resolve');
        });

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
        Route::middleware(['can:users.view', 'records.redirect'])->group(function () {
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::get('users/create', [UserController::class, 'create'])->name('users.create')->middleware('can:users.create');
            Route::post('users', [UserController::class, 'store'])->name('users.store')->middleware('can:users.create');
            Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit')->middleware('can:users.edit');
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('can:users.edit');
            Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status')->middleware('can:users.disable');
            Route::get('users/{user}/departments', [UserDepartmentAssignmentController::class, 'index'])
                ->name('users.departments.index')
                ->middleware('can:users.departments.view');
            Route::post('users/{user}/departments', [UserDepartmentAssignmentController::class, 'store'])
                ->name('users.departments.store')
                ->middleware('can:users.departments.manage');
            Route::patch('users/{user}/departments/{department}/primary', [UserDepartmentAssignmentController::class, 'setPrimary'])
                ->name('users.departments.primary')
                ->middleware('can:users.departments.manage');
            Route::delete('users/{user}/departments/{department}', [UserDepartmentAssignmentController::class, 'destroy'])
                ->name('users.departments.destroy')
                ->middleware('can:users.departments.manage');
        });

        // Roles & Permissions
        Route::middleware(['can:roles.manage', 'records.redirect'])->group(function () {
            Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
            Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
            Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
            Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
            Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');
        });

        // Permissions Dashboard (read-only audit / catalogue)
        Route::middleware('can:permissions.view')->group(function () {
            Route::get('permissions', [PermissionDashboardController::class, 'index'])->name('permissions.index');
            Route::post('permissions/refresh', [PermissionDashboardController::class, 'refresh'])->name('permissions.refresh')->middleware('can:permissions.assign');
        });

        // Per-user direct permission overrides
        Route::middleware('can:permissions.assign')->group(function () {
            Route::get('users/{user}/permissions', [UserPermissionController::class, 'edit'])->name('users.permissions.edit');
            Route::put('users/{user}/permissions', [UserPermissionController::class, 'update'])->name('users.permissions.update');
        });

        // Patients
        Route::middleware(['can:patients.view', 'records.redirect'])->group(function () {
            Route::get('patients', [PatientController::class, 'index'])->name('patients.index');
            Route::get('patients/merge', [PatientMergeController::class, 'index'])->name('patients.merge.index')->middleware('can:patients.merge.view');
            Route::get('patients/merge/search', [PatientMergeController::class, 'search'])->name('patients.merge.search')->middleware('can:patients.merge.view');
            Route::get('patients/merge/compare', [PatientMergeController::class, 'compare'])->name('patients.merge.compare')->middleware('can:patients.merge.request');
            Route::post('patients/merge/requests', [PatientMergeController::class, 'store'])->name('patients.merge.requests.store')->middleware('can:patients.merge.request');
            Route::get('patients/merge/requests/{mergeRequest}', [PatientMergeController::class, 'show'])->name('patients.merge.requests.show')->middleware('can:patients.merge.view');
            Route::post('patients/merge/requests/{mergeRequest}/execute', [PatientMergeController::class, 'execute'])->name('patients.merge.requests.execute')->middleware('can:patients.merge.execute');
            Route::get('patients/merge/logs', [PatientMergeController::class, 'logs'])->name('patients.merge.logs')->middleware('can:patients.merge.view');
            Route::get('patients/create', [PatientController::class, 'create'])->name('patients.create')->middleware('can:patients.create');
            Route::post('patients', [PatientController::class, 'store'])->name('patients.store')->middleware('can:patients.create');
            Route::get('patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
            Route::patch('patients/{patient}/medical-summary', [PatientController::class, 'updateMedicalSummary'])->name('patients.medical-summary.update');
            Route::post('patients/{patient}/privacy/break-glass', [PatientPrivacyController::class, 'startBreakGlass'])
                ->name('patients.privacy.break-glass.start')
                ->middleware('can:patients.privacy.break_glass');
            Route::post('patients/privacy/break-glass/{override}/revoke', [PatientPrivacyController::class, 'revokeBreakGlass'])
                ->name('patients.privacy.break-glass.revoke')
                ->middleware('can:patients.privacy.break_glass');
            Route::post('patients/{patient}/privacy/directives', [PatientPrivacyController::class, 'storeDirective'])
                ->name('patients.privacy-directives.store')
                ->middleware('can:patients.privacy_directives.manage');
            Route::get('patients/{patient}/edit', [PatientController::class, 'edit'])->name('patients.edit')->middleware('can:patients.edit');
            Route::put('patients/{patient}', [PatientController::class, 'update'])->name('patients.update')->middleware('can:patients.edit');
            Route::patch('patients/{patient}/toggle-status', [PatientController::class, 'toggleStatus'])->name('patients.toggle-status')->middleware('can:patients.edit');
            Route::patch('patients/{patient}/mark-deceased', [PatientController::class, 'markDeceased'])->name('patients.mark-deceased')->middleware('can:patients.mark_deceased');

            // Patient Financial-Risk profiles (Payment Timing Policy Phase 5) — sensitive administrative data
            Route::prefix('patients/{patient}/financial-risk')->name('patients.financial-risk.')->group(function () {
                Route::post('/', [PatientFinancialRiskController::class, 'store'])->name('store')->middleware('can:patients.financial_risk.manage');
                Route::put('{profile}', [PatientFinancialRiskController::class, 'update'])->name('update')->middleware('can:patients.financial_risk.manage');
                Route::post('{profile}/submit-review', [PatientFinancialRiskController::class, 'submitForReview'])->name('submit-review')->middleware('can:patients.financial_risk.review');
                Route::post('{profile}/complete-review', [PatientFinancialRiskController::class, 'completeReview'])->name('complete-review')->middleware('can:patients.financial_risk.review');
                Route::post('{profile}/suspend', [PatientFinancialRiskController::class, 'suspend'])->name('suspend')->middleware('can:patients.financial_risk.review');
                Route::post('{profile}/reactivate', [PatientFinancialRiskController::class, 'reactivate'])->name('reactivate')->middleware('can:patients.financial_risk.review');
                Route::post('{profile}/clear', [PatientFinancialRiskController::class, 'clear'])->name('clear')->middleware('can:patients.financial_risk.clear');
            });

            // Patient Insurance Management
            Route::post('patients/{patient}/insurances', [PatientInsuranceController::class, 'store'])
                ->name('patients.insurances.store')
                ->middleware(['module:insurance', 'can:patients.insurance.create']);

            Route::middleware(['module:insurance', 'can:patients.edit'])->group(function () {
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
        Route::get('insurance-providers/{provider}/tiers/for-patient', [InsuranceTierController::class, 'tiersForProvider'])->name('insurance-providers.tiers.for-patient')->middleware('module:insurance');
        Route::get('locations/regions', [LocationController::class, 'regions'])->name('locations.regions');
        Route::get('locations/cities', [LocationController::class, 'cities'])->name('locations.cities');
        Route::get('locations/towns', [LocationController::class, 'towns'])->name('locations.towns');

        // Visits
        Route::middleware(['can:visits.view', 'records.redirect'])->group(function () {
            Route::get('visits', [VisitController::class, 'index'])->name('visits.index');
            Route::get('visits/create', [VisitController::class, 'create'])->name('visits.create')->middleware('can:visits.create');
            Route::post('visits', [VisitController::class, 'store'])->name('visits.store')->middleware('can:visits.create');
            Route::get('visits/patient-search', [VisitController::class, 'patientSearch'])->name('visits.patient-search');
            Route::get('visits/attendance-preview', [VisitController::class, 'attendancePreview'])->name('visits.attendance-preview');
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
            ->middleware(['can:service_rendering.view', 'records.redirect'])
            ->group(function () {
                Route::get('/', [ServiceRenderingController::class, 'index'])->name('index');
                Route::get('/reports', [ServiceRenderingReportController::class, 'index'])->name('reports')->middleware('can:service_rendering.reports');
                // Bill an extra service from the rendering board (creates a rendering task)
                Route::post('/', [ServiceRenderingController::class, 'store'])->name('store')->middleware('can:invoices.create');
                Route::get('/visit-search', [ServiceRenderingController::class, 'visitSearch'])->name('visit-search')->middleware('can:invoices.create');
                Route::get('/service-search', [ServiceRenderingController::class, 'serviceSearch'])->name('service-search')->middleware('can:invoices.create');
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
                Route::get('donors/{donor}', [BloodDonorController::class, 'show'])->name('donors.show');
                // Storage location management
                Route::get('storage-locations', [BloodStorageLocationController::class, 'index'])->name('storage.index')->middleware('can:blood_bank.settings.manage');
                Route::post('storage-locations', [BloodStorageLocationController::class, 'store'])->name('storage.store')->middleware('can:blood_bank.settings.manage');
                Route::put('storage-locations/{location}', [BloodStorageLocationController::class, 'update'])->name('storage.update')->middleware('can:blood_bank.settings.manage');
                Route::patch('storage-locations/{location}/toggle', [BloodStorageLocationController::class, 'toggle'])->name('storage.toggle')->middleware('can:blood_bank.settings.manage');
                // WHO donor screening workflow
                Route::post('donors/{donor}/screening/questionnaire', [BloodDonorController::class, 'questionnaire'])->name('donors.screening.questionnaire')->middleware('can:blood_bank.screening.perform');
                Route::post('donors/{donor}/screening/assessment', [BloodDonorController::class, 'assessment'])->name('donors.screening.assessment')->middleware('can:blood_bank.screening.perform');
                Route::post('donors/{donor}/screening/eligibility', [BloodDonorController::class, 'eligibility'])->name('donors.screening.eligibility')->middleware('can:blood_bank.screening.perform');
                Route::get('donations', [BloodDonationController::class, 'index'])->name('donations.index');
                Route::post('donations', [BloodDonationController::class, 'store'])->name('donations.store')->middleware('can:blood_bank.donations.record');
                Route::get('donations/{donation}', [BloodDonationController::class, 'show'])->name('donations.show');
                Route::post('donations/{donation}/tests', [BloodDonationController::class, 'recordTest'])->name('donations.tests.store')->middleware('can:blood_bank.screening.perform');
                Route::patch('donation-tests/{test}/verify', [BloodDonationController::class, 'verifyTest'])->name('donations.tests.verify')->middleware('can:blood_bank.screening.verify');
                Route::patch('donations/{donation}/screening', [BloodDonationController::class, 'updateScreening'])->name('donations.screening')->middleware('can:blood_bank.screening.manage');
                Route::get('units', [BloodUnitController::class, 'index'])->name('units.index');
                Route::patch('units/{unit}/discard', [BloodUnitController::class, 'discard'])->name('units.discard')->middleware('can:blood_bank.units.discard');
                Route::get('requests', [BloodRequestController::class, 'index'])->name('requests.index');
                Route::get('requests/visit-search', [BloodRequestController::class, 'visitSearch'])->name('requests.visit-search')->middleware('can:blood_bank.requests.create');
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
        Route::middleware(['can:ward.view', 'records.redirect'])->group(function () {
            Route::get('wards', [WardController::class, 'index'])->name('wards.index');
            Route::get('wards/consumables', [DepartmentConsumablesController::class, 'ward'])->name('wards.consumables.index');
            Route::get('wards/{ward}', [WardController::class, 'show'])->whereNumber('ward')->name('wards.show');
            Route::post('wards', [WardController::class, 'store'])->name('wards.store')->middleware('can:ward.manage');
            Route::put('wards/{ward}', [WardController::class, 'update'])->name('wards.update')->middleware('can:ward.manage');
            Route::patch('wards/{ward}/toggle', [WardController::class, 'toggle'])->name('wards.toggle')->middleware('can:ward.manage');
            Route::get('beds', [WardController::class, 'beds'])->name('wards.beds');
            Route::post('beds', [WardController::class, 'storeBed'])->name('wards.beds.store')->middleware('can:beds.manage');
            Route::put('beds/{bed}', [WardController::class, 'updateBed'])->name('wards.beds.update')->middleware('can:beds.manage');
            Route::patch('beds/{bed}/status', [AdmissionBedWorkflowController::class, 'updateBedStatus'])->name('wards.beds.status')->middleware('can:beds.status.manage');
            Route::get('bed-map', [WardController::class, 'bedMap'])->name('wards.bed-map');
        });

        // Admissions
        Route::middleware(['can:ward.view', 'records.redirect'])->group(function () {
            Route::get('admissions/requests', [AdmissionRequestController::class, 'index'])->name('admissions.requests')->middleware('can:admission.requests.view');
            Route::get('admissions/requests/create', [AdmissionRequestController::class, 'create'])->name('admissions.requests.create')->middleware('can:admission.requests.create');
            Route::post('admissions/requests', [AdmissionRequestController::class, 'store'])->name('admissions.requests.store')->middleware('can:admission.requests.create');
            Route::get('admissions/requests/{admissionRequest}', [AdmissionRequestController::class, 'show'])->name('admissions.requests.show')->middleware('can:admission.requests.view');
            Route::patch('admissions/requests/{admissionRequest}/accept', [AdmissionRequestController::class, 'accept'])->name('admissions.requests.accept')->middleware('can:admission.requests.accept');
            Route::patch('admissions/requests/{admissionRequest}/reject', [AdmissionRequestController::class, 'reject'])->name('admissions.requests.reject')->middleware('can:admission.requests.reject');
            Route::patch('admissions/requests/{admissionRequest}/cancel', [AdmissionRequestController::class, 'cancel'])->name('admissions.requests.cancel')->middleware('can:admission.requests.cancel');
            Route::patch('admissions/requests/{admissionRequest}/bed-pending', [AdmissionRequestController::class, 'bedPending'])->name('admissions.requests.bed-pending')->middleware('can:admission.requests.bed_pending');
            Route::patch('admissions/requests/{admissionRequest}/reserve-bed', [AdmissionRequestController::class, 'reserveBed'])->name('admissions.requests.reserve-bed')->middleware('can:admission.requests.reserve_bed');
            Route::post('admissions/requests/{admissionRequest}/convert', [AdmissionRequestController::class, 'convert'])->name('admissions.requests.convert')->middleware('can:admission.requests.convert');
            Route::get('admissions/medication-board', [AdmissionMedicationBoardController::class, 'index'])->name('admissions.medication-board')->middleware('can:admission.medication_board.view');
            Route::get('admissions', [AdmissionController::class, 'index'])->name('admissions.index');
            Route::get('admissions/create', [AdmissionController::class, 'create'])->name('admissions.create')->middleware('can:ward.admit');
            Route::post('admissions', [AdmissionController::class, 'store'])->name('admissions.store')->middleware('can:ward.admit');
            Route::get('admissions/{admission}/medications', [AdmissionMedicationBoardController::class, 'show'])->name('admissions.medications.show')->middleware('can:admission.medication_board.view');
            Route::get('admissions/{admission}/mar-chart', [MarChartController::class, 'admission'])->name('admissions.mar-chart')->middleware('can:admission.mar_chart.view');
            Route::get('admissions/{admission}', [AdmissionController::class, 'show'])->name('admissions.show');
            Route::get('admissions/{admission}/discharge', [AdmissionController::class, 'discharge'])->name('admissions.discharge')->middleware('can:ward.discharge');
            Route::post('admissions/{admission}/discharge', [AdmissionController::class, 'processDischarge'])->name('admissions.process-discharge')->middleware('can:ward.discharge');
            Route::post('admissions/{admission}/extend', [AdmissionController::class, 'extend'])->name('admissions.extend')->middleware('can:admissions.extend');
            Route::post('admissions/{admission}/rounds', [AdmissionController::class, 'storeRound'])->name('admissions.rounds.store');
            Route::post('admissions/{admission}/vitals', [AdmissionController::class, 'storeVital'])->name('admissions.vitals.store');
            Route::post('admissions/{admission}/services', [AdmissionController::class, 'storeService'])->name('admissions.services.store');
            Route::post('admissions/{admission}/transfer-bed', [AdmissionBedWorkflowController::class, 'transfer'])->name('admissions.transfer-bed')->middleware('can:beds.transfer');
            Route::post('admissions/{admission}/nursing-notes', [AdmissionNursingCareController::class, 'storeNote'])->name('admissions.nursing-notes.store')->middleware('can:admission.nursing.notes.create');
            Route::patch('admissions/{admission}/nursing-notes/{nursingNote}', [AdmissionNursingCareController::class, 'updateNote'])->name('admissions.nursing-notes.update')->middleware('can:admission.nursing.notes.update');
            Route::post('admissions/{admission}/nursing-tasks', [AdmissionNursingCareController::class, 'storeTask'])->name('admissions.nursing-tasks.store')->middleware('can:admission.nursing.tasks.create');
            Route::patch('admissions/{admission}/nursing-tasks/{nursingTask}', [AdmissionNursingCareController::class, 'updateTask'])->name('admissions.nursing-tasks.update')->middleware('can:admission.nursing.tasks.update');
            Route::patch('admissions/{admission}/nursing-tasks/{nursingTask}/complete', [AdmissionNursingCareController::class, 'completeTask'])->name('admissions.nursing-tasks.complete')->middleware('can:admission.nursing.tasks.complete');
            Route::patch('admissions/{admission}/care-flags', [AdmissionNursingCareController::class, 'updateCareFlags'])->name('admissions.care-flags.update')->middleware('can:admission.care_flags.manage');
            Route::post('admissions/{admission}/discharge-planning', [AdmissionDischargeWorkflowController::class, 'startPlanning'])->name('admissions.discharge-planning.start')->middleware('can:admission.discharge.plan');
            Route::patch('admissions/{admission}/discharge-planning', [AdmissionDischargeWorkflowController::class, 'updatePlanning'])->name('admissions.discharge-planning.update')->middleware('can:admission.discharge.plan');
            Route::patch('admissions/{admission}/discharge-clearances/{clearance}', [AdmissionDischargeWorkflowController::class, 'updateClearance'])->name('admissions.discharge-clearances.update')->middleware('can:admission.discharge.clearance.manage');
            Route::patch('admissions/{admission}/discharge-clearances/{clearance}/revoke', [AdmissionDischargeWorkflowController::class, 'revokeClearance'])->name('admissions.discharge-clearances.revoke')->middleware('can:admission.discharge.clearance.manage');
            Route::post('admissions/{admission}/discharge-summary', [AdmissionDischargeWorkflowController::class, 'saveSummary'])->name('admissions.discharge-summary.save')->middleware('can:admission.discharge.summary.create');
            Route::patch('admissions/{admission}/discharge-summary', [AdmissionDischargeWorkflowController::class, 'saveSummary'])->name('admissions.discharge-summary.update')->middleware('can:admission.discharge.summary.update');
            Route::patch('admissions/{admission}/discharge-summary/{summary}/prepare', [AdmissionDischargeWorkflowController::class, 'prepareSummary'])->name('admissions.discharge-summary.prepare')->middleware('can:admission.discharge.summary.update');
            Route::patch('admissions/{admission}/discharge-summary/{summary}/approve', [AdmissionDischargeWorkflowController::class, 'approveSummary'])->name('admissions.discharge-summary.approve')->middleware('can:admission.discharge.summary.approve');
            Route::get('admissions/{admission}/discharge-summary/{summary}/print', [AdmissionDischargeWorkflowController::class, 'printSummary'])->name('admissions.discharge-summary.print')->middleware('can:admission.discharge.summary.view');
        });

        // Maternity foundation
        // Maternity module — shared route definitions (see routes/partials/maternity.php),
        // also mounted as the /maternity workspace below.
        Route::middleware(['can:maternity.view', 'records.redirect'])->prefix('maternity')->name('maternity.')->group(base_path('routes/partials/maternity.php'));

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
        Route::prefix('emergency')->name('emergency.')->middleware('records.redirect')->group(function () {
            Route::get('board', [EmergencyBoardController::class, 'index'])->name('board')->middleware('can:emergency.board.view');
            Route::get('consumables', [DepartmentConsumablesController::class, 'emergency'])->name('consumables.index')->middleware('can:emergency.board.view');
            Route::get('cases/create', [EmergencyCaseController::class, 'create'])->name('cases.create')->middleware('can:emergency.case.create');
            Route::post('cases', [EmergencyCaseController::class, 'store'])->name('cases.store')->middleware('can:emergency.case.create');
            Route::get('cases/{emergencyCase}', [EmergencyCaseController::class, 'show'])->name('cases.show')->middleware('can:emergency.case.view');
            Route::patch('cases/{emergencyCase}', [EmergencyCaseController::class, 'update'])->name('cases.update')->middleware('can:emergency.case.update');
            Route::post('cases/{emergencyCase}/confirm-identity', [EmergencyPatientIdentityController::class, 'store'])->name('cases.confirm-identity')->middleware('can:patients.merge.confirm_identity');
            Route::post('cases/{emergencyCase}/register-identity', [EmergencyPatientIdentityController::class, 'register'])->name('cases.register-identity')->middleware('can:patients.merge.confirm_identity');

            Route::get('cases/{emergencyCase}/triage', fn (EmergencyCase $emergencyCase) => redirect()->route(app(WorkspaceRouteResolver::class)->routeName('admin.emergency.cases.show'), $emergencyCase))->name('triage.show');
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
        Route::middleware(['can:appointments.view', 'records.redirect'])->group(function () {
            Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
            Route::get('appointments/create', [AppointmentController::class, 'create'])->name('appointments.create')->middleware('can:appointments.create');
            Route::post('appointments', [AppointmentController::class, 'store'])->name('appointments.store')->middleware('can:appointments.create');
            Route::get('appointments/patient-search', [AppointmentController::class, 'patientSearch'])->name('appointments.patient-search')->middleware('can:appointments.create');
            Route::get('appointments/patient-insurances', [AppointmentController::class, 'patientInsurances'])->name('appointments.patient-insurances')->middleware(['module:insurance', 'can:appointments.create']);
            Route::get('appointments/department-services', [AppointmentController::class, 'departmentServices'])->name('appointments.department-services')->middleware('can:appointments.create');
            Route::get('appointments/doctors-for-services', [AppointmentController::class, 'doctorsForServices'])->name('appointments.doctors-for-services')->middleware('can:appointments.create');
            Route::get('appointments/services-for-doctor', [AppointmentController::class, 'servicesForDoctor'])->name('appointments.services-for-doctor')->middleware('can:appointments.create');
            Route::get('appointments/service-price', [AppointmentController::class, 'servicePrice'])->name('appointments.service-price')->middleware('can:appointments.create');
            Route::get('appointments/calendar', [AppointmentController::class, 'calendar'])->name('appointments.calendar');
            Route::get('appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
            Route::get('appointments/{appointment}/edit', [AppointmentController::class, 'edit'])->name('appointments.edit')->middleware('can:appointments.edit');
            Route::put('appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update')->middleware('can:appointments.edit');
            Route::post('appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn'])->name('appointments.check-in')->middleware('can:appointments.checkin');
            Route::patch('appointments/{appointment}/transition', [AppointmentController::class, 'transition'])->name('appointments.transition')->middleware('can:appointments.edit');
            Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel')->middleware('can:appointments.edit');
            Route::post('appointments/{appointment}/no-show', [AppointmentController::class, 'noShow'])->name('appointments.no-show')->middleware('can:appointments.edit');
        });

        // Provider-agnostic insurance verification used during visit intake.
        Route::post('insurance/verify', [InsuranceVerificationController::class, 'verify'])
            ->name('insurance.verify')
            ->middleware(['module:insurance', 'can:patients.insurance.verify']);

        // Insurance Providers & Tiers
        Route::middleware(['module:insurance', 'can:claims.view'])->group(function () {
            Route::get('insurance-providers', [InsuranceProviderController::class, 'index'])->name('insurance-providers.index');
            Route::post('insurance-providers', [InsuranceProviderController::class, 'store'])->name('insurance-providers.store')->middleware('can:claims.create');
            Route::put('insurance-providers/{provider}', [InsuranceProviderController::class, 'update'])->name('insurance-providers.update')->middleware('can:claims.create');
            Route::patch('insurance-providers/{provider}/toggle', [InsuranceProviderController::class, 'toggle'])->name('insurance-providers.toggle')->middleware('can:claims.create');

            // Tier management (nested under provider)
            Route::get('insurance-providers/{provider}/tiers', [InsuranceTierController::class, 'index'])->name('insurance-providers.tiers.index');
            Route::post('insurance-providers/{provider}/tiers', [InsuranceTierController::class, 'store'])->name('insurance-providers.tiers.store')->middleware('can:claims.create');
            // Tier update/delete (standalone, not nested under provider)
            Route::put('insurance-tiers/{tier}', [InsuranceTierController::class, 'update'])->name('insurance-tiers.update')->middleware('can:claims.create');
            Route::delete('insurance-tiers/{tier}', [InsuranceTierController::class, 'destroy'])->name('insurance-tiers.destroy')->middleware('can:claims.create');

        });

        // Claims
        Route::middleware(['module:insurance', 'module:claims', 'can:claims.view', 'records.redirect'])->group(function () {
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
        Route::prefix('store')->name('store.')->middleware(['module:inventory', 'records.redirect'])->group(function () {
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
                Route::get('purchase-returns/po/{purchaseOrder}/items', [PurchaseReturnController::class, 'poItems'])->name('purchase-returns.po-items')->middleware('can:store.return.create');
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

            // Stock Movement Ledger / Balances / Adjustments / Returns / Locations
            Route::middleware('can:store.purchase.view')->group(function () {
                Route::get('stock/balances', [StockController::class, 'balances'])->name('stock.balances');
                Route::get('stock/valuation', [StockController::class, 'valuation'])->name('stock.valuation')->middleware('can:reports.inventory_valuation.view');
                Route::get('stock/ledger', [StockController::class, 'ledger'])->name('stock.ledger');

                // Stock movement list pages + per-movement detail
                Route::get('stock/adjustments', [StockController::class, 'adjustmentsIndex'])->name('stock.adjustments.index');
                Route::get('stock/returns', [StockController::class, 'returnsIndex'])->name('stock.returns.index');
                Route::get('stock/transfers', [StockController::class, 'transfersIndex'])->name('stock.transfers.index');
                Route::get('stock/batches/{batch}', [StockController::class, 'batchShow'])->name('stock.batches.show');
                Route::get('stock/movements/{movement}', [StockController::class, 'movementShow'])->name('stock.movements.show');

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

                    Route::get('stock/transfers/create', [StockController::class, 'transferForm'])->name('stock.transfers.create');
                    Route::post('stock/transfers', [StockController::class, 'storeTransfer'])->name('stock.transfers.store');
                });
            });
        });

        // Accounts & Finance
        Route::prefix('accounts')->name('accounts.')->middleware(['module:accounting_basic', 'records.redirect'])->group(function () {
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
            Route::middleware('module:accounting_advanced')->group(function () {
                Route::post('entries/{entry}/post-to-gl', [FinancialEntryController::class, 'postToGl'])
                    ->name('entries.post-to-gl')
                    ->middleware('can:accounting.basic.post');
                Route::post('entries/{entry}/reverse-gl', [FinancialEntryController::class, 'reverseGl'])
                    ->name('entries.reverse-gl')
                    ->middleware('can:accounting.basic.reverse');
            });

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

        // Double-entry Accounting Foundation
        Route::prefix('accounting')->name('accounting.')->middleware(['module:accounting_advanced', 'records.redirect'])->group(function () {
            Route::get('/', AccountingDashboardController::class)
                ->name('dashboard')
                ->middleware('can:accounting.dashboard.view');

            Route::middleware('can:accounting.accounts.view')->prefix('accounts')->name('accounts.')->group(function () {
                Route::get('/', [AccountingAccountController::class, 'index'])->name('index');
                Route::get('create', [AccountingAccountController::class, 'create'])->name('create')->middleware('can:accounting.accounts.create');
                Route::post('/', [AccountingAccountController::class, 'store'])->name('store')->middleware('can:accounting.accounts.create');
                Route::get('{account}/edit', [AccountingAccountController::class, 'edit'])->name('edit')->middleware('can:accounting.accounts.edit');
                Route::put('{account}', [AccountingAccountController::class, 'update'])->name('update')->middleware('can:accounting.accounts.edit');
                Route::patch('{account}/disable', [AccountingAccountController::class, 'disable'])->name('disable')->middleware('can:accounting.accounts.disable');
                Route::patch('{account}/activate', [AccountingAccountController::class, 'activate'])->name('activate')->middleware('can:accounting.accounts.edit');
            });

            Route::middleware('can:accounting.journals.view')->prefix('journals')->name('journals.')->group(function () {
                Route::get('/', [JournalEntryController::class, 'index'])->name('index');
                Route::get('create', [JournalEntryController::class, 'create'])->name('create')->middleware('can:accounting.journals.create');
                Route::post('/', [JournalEntryController::class, 'store'])->name('store')->middleware('can:accounting.journals.create');
                Route::get('{journal}', [JournalEntryController::class, 'show'])->name('show');
                Route::get('{journal}/edit', [JournalEntryController::class, 'edit'])->name('edit')->middleware('can:accounting.journals.edit');
                Route::put('{journal}', [JournalEntryController::class, 'update'])->name('update')->middleware('can:accounting.journals.edit');
                Route::post('{journal}/post', [JournalEntryController::class, 'post'])->name('post')->middleware('can:accounting.journals.post');
                Route::post('{journal}/reverse', [JournalEntryController::class, 'reverse'])->name('reverse')->middleware('can:accounting.journals.reverse');
                Route::patch('{journal}/cancel', [JournalEntryController::class, 'cancel'])->name('cancel')->middleware('can:accounting.journals.cancel');
            });

            Route::get('trial-balance', [AccountingReportController::class, 'trialBalance'])
                ->name('trial-balance')
                ->middleware('can:accounting.reports.trial_balance');
            Route::get('trial-balance/export', [AccountingReportController::class, 'trialBalanceExport'])
                ->name('trial-balance.export')
                ->middleware(['can:accounting.reports.trial_balance', 'can:accounting.exports']);
            Route::get('general-ledger', [AccountingReportController::class, 'generalLedger'])
                ->name('general-ledger')
                ->middleware('can:accounting.reports.general_ledger');
            Route::get('general-ledger/export', [AccountingReportController::class, 'generalLedgerExport'])
                ->name('general-ledger.export')
                ->middleware(['can:accounting.reports.general_ledger', 'can:accounting.exports']);

            // Financial statements (Phase 7)
            Route::name('reports.')->group(function () {
                Route::get('profit-loss', [AccountingReportController::class, 'profitLoss'])->name('profit-loss')->middleware('can:accounting.reports.profit_loss');
                Route::get('balance-sheet', [AccountingReportController::class, 'balanceSheet'])->name('balance-sheet')->middleware('can:accounting.reports.balance_sheet');
                Route::get('cashbook', [AccountingReportController::class, 'cashbook'])->name('cashbook')->middleware('can:accounting.reports.cashbook');
                Route::get('cash-flow', [AccountingReportController::class, 'cashFlow'])->name('cash-flow')->middleware('can:accounting.reports.cash_flow');
                Route::get('cash-flow/export', [AccountingReportController::class, 'cashFlowExport'])->name('cash-flow.export')->middleware(['can:accounting.reports.cash_flow', 'can:accounting.exports']);
                Route::get('revenue-by-department', [AccountingReportController::class, 'revenueByDepartment'])->name('revenue-by-department')->middleware('can:accounting.reports.revenue_by_department');
                Route::get('expense-by-department', [AccountingReportController::class, 'expenseByDepartment'])->name('expense-by-department')->middleware('can:accounting.reports.expense_by_department');
            });

            Route::middleware('module:budgets')->group(function () {
                Route::get('budgets', [BudgetController::class, 'index'])->name('budgets.index')->middleware('can:accounting.budgets.view');
                Route::post('budgets', [BudgetController::class, 'store'])->name('budgets.store')->middleware('can:accounting.budgets.manage');
                Route::post('budgets/{budget}/lines', [BudgetController::class, 'addLine'])->name('budgets.lines.store')->middleware('can:accounting.budgets.manage');
                Route::post('budgets/{budget}/submit', [BudgetController::class, 'submit'])->name('budgets.submit')->middleware('can:accounting.budgets.submit');
                Route::post('budgets/{budget}/approve', [BudgetController::class, 'approve'])->name('budgets.approve')->middleware('can:accounting.budgets.approve');
                Route::get('commitments', [BudgetController::class, 'commitments'])->name('commitments.index')->middleware('can:accounting.commitments.view');
                Route::post('commitments', [BudgetController::class, 'storeCommitment'])->name('commitments.store')->middleware('can:accounting.commitments.manage');
                Route::post('commitments/{commitment}/release', [BudgetController::class, 'releaseCommitment'])->name('commitments.release')->middleware('can:accounting.commitments.manage');
                Route::post('commitments/{commitment}/cancel', [BudgetController::class, 'cancelCommitment'])->name('commitments.cancel')->middleware('can:accounting.commitments.manage');
            });

            Route::middleware('module:fixed_assets')->prefix('fixed-assets')->name('fixed-assets.')->group(function () {
                Route::get('/', [FixedAssetController::class, 'index'])->name('index')->middleware('can:accounting.fixed_assets.view');
                Route::post('/', [FixedAssetController::class, 'store'])->name('store')->middleware('can:accounting.fixed_assets.manage');
                Route::post('categories', [FixedAssetController::class, 'storeCategory'])->name('categories.store')->middleware('can:accounting.fixed_assets.manage');
                Route::post('locations', [FixedAssetController::class, 'storeLocation'])->name('locations.store')->middleware('can:accounting.fixed_assets.manage');
                Route::post('{asset}/capitalize', [FixedAssetController::class, 'capitalize'])->name('capitalize')->middleware('can:accounting.fixed_assets.capitalize');
                Route::post('depreciation/run', [FixedAssetController::class, 'runDepreciation'])->name('depreciation.run')->middleware('can:accounting.fixed_assets.depreciate');
                Route::post('{asset}/dispose', [FixedAssetController::class, 'dispose'])->name('dispose')->middleware('can:accounting.fixed_assets.dispose');
                Route::post('{asset}/verify', [FixedAssetController::class, 'verify'])->name('verify')->middleware('can:accounting.fixed_assets.verify');
            });

            Route::middleware('module:tax_accounting')->prefix('tax')->name('tax.')->group(function () {
                Route::get('/', [TaxAccountingController::class, 'index'])->name('index')->middleware('can:accounting.tax_ledgers.view');
                Route::post('returns/prepare', [TaxAccountingController::class, 'prepareReturn'])->name('returns.prepare')->middleware('can:accounting.tax_returns.prepare');
                Route::post('returns/{return}/approve', [TaxAccountingController::class, 'approveReturn'])->name('returns.approve')->middleware('can:accounting.tax_returns.approve');
                Route::post('payments', [TaxAccountingController::class, 'recordPayment'])->name('payments.store')->middleware('can:accounting.tax_payments.record');
                Route::post('payments/{payment}/allocate', [TaxAccountingController::class, 'allocatePayment'])->name('payments.allocate')->middleware('can:accounting.tax_payments.record');
            });

            Route::middleware('module:accounting_advanced')->prefix('receivables')->name('receivables.')->group(function () {
                Route::get('/', [ReceivableWorkbenchController::class, 'index'])->name('index')->middleware('can:receivables.workbench.view');
                Route::post('cases', [ReceivableWorkbenchController::class, 'openCase'])->name('cases.store')->middleware('can:receivables.cases.manage');
                Route::post('cases/{case}/assign', [ReceivableWorkbenchController::class, 'assign'])->name('cases.assign')->middleware('can:receivables.cases.assign');
                Route::post('cases/{case}/followups', [ReceivableWorkbenchController::class, 'followup'])->name('cases.followups.store')->middleware('can:receivables.followups.create');
                Route::post('cases/{case}/promises', [ReceivableWorkbenchController::class, 'promise'])->name('cases.promises.store')->middleware('can:receivables.promises.manage');
                Route::post('cases/{case}/disputes', [ReceivableWorkbenchController::class, 'dispute'])->name('cases.disputes.store')->middleware('can:receivables.disputes.manage');
                Route::post('cases/{case}/dunning', [ReceivableWorkbenchController::class, 'dunning'])->name('cases.dunning.store')->middleware('can:receivables.dunning.generate');
                Route::post('cases/{case}/recommend-writeoff', [ReceivableWorkbenchController::class, 'recommendWriteoff'])->name('cases.recommend-writeoff')->middleware('can:receivables.recommendations.writeoff');
                Route::post('cases/{case}/recommend-credit-note', [ReceivableWorkbenchController::class, 'recommendCreditNote'])->name('cases.recommend-credit-note')->middleware('can:receivables.recommendations.creditnote');
                Route::post('statements', [ReceivableWorkbenchController::class, 'statement'])->name('statements.store')->middleware('can:receivables.statements.generate');
                Route::post('statements/{statement}/approve', [ReceivableWorkbenchController::class, 'approveStatement'])->name('statements.approve')->middleware('can:receivables.statements.approve');
            });

            Route::middleware('can:accounting.fiscal_years.view')->prefix('fiscal-years')->name('fiscal-years.')->group(function () {
                Route::get('/', [FiscalYearController::class, 'index'])->name('index');
                Route::post('/', [FiscalYearController::class, 'store'])->name('store')->middleware('can:accounting.fiscal_years.manage');
                Route::patch('{fiscalYear}/close', [FiscalYearController::class, 'close'])->name('close')->middleware('can:accounting.fiscal_years.manage');
                Route::patch('{fiscalYear}/reopen', [FiscalYearController::class, 'reopen'])->name('reopen')->middleware('can:accounting.fiscal_years.reopen');
                Route::post('{fiscalYear}/year-end-close', [FiscalYearController::class, 'yearEndClose'])->name('year-end-close')->middleware('can:accounting.fiscal_years.manage');
            });

            Route::middleware('can:accounting.periods.view')->prefix('periods')->name('periods.')->group(function () {
                Route::get('/', [AccountingPeriodController::class, 'index'])->name('index');
                Route::post('/', [AccountingPeriodController::class, 'store'])->name('store')->middleware('can:accounting.periods.manage');
                Route::patch('{period}/close', [AccountingPeriodController::class, 'close'])->name('close')->middleware('can:accounting.periods.manage');
                Route::patch('{period}/reopen', [AccountingPeriodController::class, 'reopen'])->name('reopen')->middleware('can:accounting.periods.reopen');
            });

            Route::get('settings', [AccountingSettingsController::class, 'index'])
                ->name('settings.index')
                ->middleware('can:accounting.settings.view');
            Route::put('settings', [AccountingSettingsController::class, 'update'])
                ->name('settings.update')
                ->middleware('can:accounting.settings.manage');

            Route::post('postings/retry', [AccountingPostingController::class, 'retry'])
                ->name('postings.retry')
                ->middleware('can:accounting.posting.retry');

            Route::middleware('can:accounting.failed_postings.view')
                ->prefix('posting-attempts')
                ->name('posting-attempts.')
                ->group(function () {
                    Route::get('/', [AccountingPostingAttemptController::class, 'index'])->name('index');
                    Route::get('{postingAttempt}', [AccountingPostingAttemptController::class, 'show'])->name('show');
                });

            Route::middleware(['module:accounting_basic', 'can:accounting.failed_postings.view'])
                ->prefix('failed-postings')
                ->name('failed-postings.')
                ->group(function () {
                    $workbench = FailedPostingWorkbenchController::class;
                    Route::get('/', [$workbench, 'index'])->name('index');
                    Route::post('retry-selected', [$workbench, 'retrySelected'])->name('retry-selected')->middleware('can:accounting.failed_postings.retry');
                    Route::get('{failedPosting}', [$workbench, 'show'])->name('show');
                    Route::post('{failedPosting}/retry', [$workbench, 'retry'])->name('retry')->middleware('can:accounting.failed_postings.retry');
                    Route::post('{failedPosting}/resolve', [$workbench, 'resolve'])->name('resolve')->middleware('can:accounting.failed_postings.resolve');
                    Route::post('{failedPosting}/waive', [$workbench, 'waive'])->name('waive')->middleware('can:accounting.failed_postings.waive');
                });

            Route::middleware(['module:accounting_basic', 'can:accounting.subledger_reconciliation.view'])
                ->prefix('subledger-reconciliation')
                ->name('subledger-reconciliation.')
                ->group(function () {
                    $controller = SubledgerReconciliationController::class;
                    Route::get('/', [$controller, 'index'])->name('index');
                    Route::get('create', [$controller, 'create'])->name('create')->middleware('can:accounting.subledger_reconciliation.run');
                    Route::post('/', [$controller, 'store'])->name('store')->middleware('can:accounting.subledger_reconciliation.run');
                    Route::get('history', [$controller, 'history'])->name('history');
                    Route::get('{reconciliationRun}', [$controller, 'show'])->name('show');
                    Route::get('{reconciliationRun}/approval', [$controller, 'approval'])->name('approval')->middleware('can:accounting.subledger_reconciliation.approve');
                    Route::post('{reconciliationRun}/approve', [$controller, 'approve'])->name('approve')->middleware('can:accounting.subledger_reconciliation.approve');
                    Route::post('{reconciliationRun}/cancel', [$controller, 'cancel'])->name('cancel')->middleware('can:accounting.subledger_reconciliation.cancel');
                    Route::get('{reconciliationRun}/items/{item}', [$controller, 'item'])->name('items.show');
                    Route::post('{reconciliationRun}/items/{item}/resolve', [$controller, 'resolve'])->name('items.resolve')->middleware('can:accounting.subledger_reconciliation.resolve');
                });

            Route::middleware('can:accounting.payroll_posting.view')
                ->prefix('payroll-posting')
                ->name('payroll-posting.')
                ->group(function () {
                    Route::get('/', [PayrollPostingController::class, 'index'])->name('index');
                    Route::post('{payrollRun}/post', [PayrollPostingController::class, 'post'])->name('post')->middleware('can:accounting.payroll_posting.post');
                    Route::post('{payrollRun}/reverse', [PayrollPostingController::class, 'reverse'])->name('reverse')->middleware('can:accounting.payroll_posting.reverse');
                    Route::post('{payrollRun}/settle', [PayrollPostingController::class, 'settle'])->name('settle')->middleware('can:accounting.payroll_posting.settle');
                    Route::post('{payrollRun}/statutory-settle', [PayrollPostingController::class, 'settleStatutory'])->name('statutory-settle')->middleware('can:accounting.payroll_posting.settle');
                    Route::post('settlements/{settlement}/reverse', [PayrollPostingController::class, 'reverseSettlement'])->name('settlements.reverse')->middleware('can:accounting.payroll_posting.reverse');
                    Route::post('statutory-settlements/{settlement}/reverse', [PayrollPostingController::class, 'reverseStatutory'])->name('statutory-settlements.reverse')->middleware('can:accounting.payroll_posting.reverse');
                });

            Route::middleware('can:accounting.mappings.view')
                ->prefix('mappings')
                ->name('mappings.')
                ->group(function () {
                    Route::get('/', [AccountingAccountMappingController::class, 'index'])->name('index');
                    Route::get('create', [AccountingAccountMappingController::class, 'create'])->name('create')->middleware('can:accounting.mappings.manage');
                    Route::post('/', [AccountingAccountMappingController::class, 'store'])->name('store')->middleware('can:accounting.mappings.manage');
                    Route::get('{mapping}/edit', [AccountingAccountMappingController::class, 'edit'])->name('edit')->middleware('can:accounting.mappings.manage');
                    Route::put('{mapping}', [AccountingAccountMappingController::class, 'update'])->name('update')->middleware('can:accounting.mappings.manage');
                    Route::patch('{mapping}/disable', [AccountingAccountMappingController::class, 'disable'])->name('disable')->middleware('can:accounting.mappings.manage');
                });

            Route::get('close-readiness', AccountingCloseReadinessController::class)
                ->name('close-readiness')
                ->middleware('can:accounting.close_readiness.view');

            // ── Bank Accounts, Statement Import & Reconciliation (Phase B) ──
            Route::middleware('module:accounting_basic')->prefix('bank')->name('bank.')->group(function () {
                $bankAccounts = BankAccountController::class;
                $imports = BankStatementImportController::class;
                $recons = BankReconciliationController::class;
                $adjustments = BankReconciliationAdjustmentController::class;

                Route::middleware('can:accounting.bank_accounts.view')->prefix('accounts')->name('accounts.')->group(function () use ($bankAccounts) {
                    Route::get('/', [$bankAccounts, 'index'])->name('index');
                    Route::get('create', [$bankAccounts, 'create'])->name('create')->middleware('can:accounting.bank_accounts.manage');
                    Route::post('/', [$bankAccounts, 'store'])->name('store')->middleware('can:accounting.bank_accounts.manage');
                    Route::get('{bankAccount}/edit', [$bankAccounts, 'edit'])->name('edit')->middleware('can:accounting.bank_accounts.manage');
                    Route::put('{bankAccount}', [$bankAccounts, 'update'])->name('update')->middleware('can:accounting.bank_accounts.manage');
                    Route::patch('{bankAccount}/disable', [$bankAccounts, 'disable'])->name('disable')->middleware('can:accounting.bank_accounts.manage');
                    Route::patch('{bankAccount}/activate', [$bankAccounts, 'activate'])->name('activate')->middleware('can:accounting.bank_accounts.manage');
                });

                Route::middleware('can:accounting.bank_statements.view')->prefix('imports')->name('imports.')->group(function () use ($imports) {
                    Route::get('/', [$imports, 'index'])->name('index');
                    Route::get('create', [$imports, 'create'])->name('create')->middleware('can:accounting.bank_statements.import');
                    Route::post('preview', [$imports, 'preview'])->name('preview')->middleware('can:accounting.bank_statements.import');
                    Route::post('/', [$imports, 'store'])->name('store')->middleware('can:accounting.bank_statements.import');
                    Route::get('{import}', [$imports, 'show'])->name('show');
                    Route::post('{import}/reject', [$imports, 'reject'])->name('reject')->middleware('can:accounting.bank_statements.reject');
                });

                Route::middleware('can:accounting.bank_reconciliation.view')->prefix('reconciliations')->name('reconciliations.')->group(function () use ($recons, $adjustments) {
                    Route::get('/', [$recons, 'index'])->name('index');
                    Route::get('create', [$recons, 'create'])->name('create')->middleware('can:accounting.bank_reconciliation.manage');
                    Route::post('/', [$recons, 'prepare'])->name('prepare')->middleware('can:accounting.bank_reconciliation.manage');
                    Route::get('{reconciliation}', [$recons, 'show'])->name('show');
                    Route::get('{reconciliation}/statement', [$recons, 'statement'])->name('statement');
                    Route::get('{reconciliation}/lines/{line}/suggestions', [$recons, 'suggestions'])->name('suggestions')->middleware('can:accounting.bank_reconciliation.match');
                    Route::post('{reconciliation}/lines/{line}/match', [$recons, 'match'])->name('match')->middleware('can:accounting.bank_reconciliation.match');
                    Route::post('{reconciliation}/matches/{match}/unmatch', [$recons, 'unmatch'])->name('unmatch')->middleware('can:accounting.bank_reconciliation.match');
                    Route::post('{reconciliation}/approve', [$recons, 'approve'])->name('approve')->middleware('can:accounting.bank_reconciliation.approve');
                    Route::post('{reconciliation}/reopen', [$recons, 'reopen'])->name('reopen')->middleware('can:accounting.bank_reconciliation.reopen');
                    Route::post('{reconciliation}/reverse', [$recons, 'reverse'])->name('reverse')->middleware('can:accounting.bank_reconciliation.reverse');

                    Route::post('{reconciliation}/adjustments', [$adjustments, 'store'])->name('adjustments.store')->middleware('can:accounting.bank_adjustments.propose');
                    Route::post('{reconciliation}/adjustments/{adjustment}/approve', [$adjustments, 'approve'])->name('adjustments.approve')->middleware('can:accounting.bank_adjustments.approve');
                    Route::post('{reconciliation}/adjustments/{adjustment}/post', [$adjustments, 'post'])->name('adjustments.post')->middleware('can:accounting.bank_adjustments.post');
                    Route::post('{reconciliation}/adjustments/{adjustment}/reject', [$adjustments, 'reject'])->name('adjustments.reject')->middleware('can:accounting.bank_adjustments.approve');
                });
            });

            Route::middleware(['module:accounting_basic', 'can:accounting.basic.batch.view'])
                ->prefix('basic-bridge')
                ->name('basic-bridge.')
                ->group(function () {
                    Route::get('/', [BasicAccountingBridgeController::class, 'index'])->name('index');
                    Route::post('execute', [BasicAccountingBridgeController::class, 'execute'])
                        ->name('execute')
                        ->middleware('can:accounting.basic.batch.execute');
                });

            Route::middleware('can:accounting.posting_templates.view')
                ->prefix('posting-templates')
                ->name('posting-templates.')
                ->group(function () {
                    Route::get('/', [AccountingPostingTemplateController::class, 'index'])->name('index');
                    Route::get('create', [AccountingPostingTemplateController::class, 'create'])->name('create')->middleware('can:accounting.posting_templates.manage');
                    Route::post('/', [AccountingPostingTemplateController::class, 'store'])->name('store')->middleware('can:accounting.posting_templates.manage');
                    Route::get('{postingTemplate}/edit', [AccountingPostingTemplateController::class, 'edit'])->name('edit')->middleware('can:accounting.posting_templates.manage');
                    Route::put('{postingTemplate}', [AccountingPostingTemplateController::class, 'update'])->name('update')->middleware('can:accounting.posting_templates.manage');
                    Route::patch('{postingTemplate}/approve', [AccountingPostingTemplateController::class, 'approve'])->name('approve')->middleware('can:accounting.posting_templates.approve');
                    Route::patch('{postingTemplate}/disable', [AccountingPostingTemplateController::class, 'disable'])->name('disable')->middleware('can:accounting.posting_templates.manage');
                });
        });

        // HR & Payroll
        Route::prefix('hr')->name('hr.')->middleware(['module:hr', 'records.redirect'])->group(function () {
            Route::get('configuration', [HrConfigurationController::class, 'index'])
                ->name('configuration.index')->middleware('can:hr.shifts.view');
            Route::post('configuration/shifts', [HrConfigurationController::class, 'storeShift'])
                ->name('configuration.shifts.store')->middleware('can:hr.shifts.manage');
            Route::put('configuration/policies/{policy}', [HrConfigurationController::class, 'updatePolicy'])
                ->name('configuration.policies.update')->middleware('can:hr.shifts.manage');
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
                Route::post('attendance/{attendance}/approve', [AttendanceController::class, 'approve'])->name('attendance.approve')->middleware('can:hr.attendance.approve');
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
                Route::post('payroll/process', [PayrollController::class, 'process'])->name('payroll.process')->middleware('can:hr.payroll.generate');
                Route::post('payroll/review', [PayrollController::class, 'review'])->name('payroll.review')->middleware('can:hr.payroll.review');
                Route::post('payroll/approve', [PayrollController::class, 'approve'])->name('payroll.approve')->middleware('can:hr.payroll.approve');
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
        Route::middleware(['can:departments.view', 'records.redirect'])->group(function () {
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
        Route::middleware(['module:consultation', 'can:consultations.view', 'records.redirect'])->group(function () {
            Route::get('consultations', [ConsultationWorkspaceController::class, 'index'])->name('consultations.index');
            Route::get('consultations/{visit}', [ConsultationWorkspaceController::class, 'show'])->name('consultations.show');
            Route::get('consultations/{visit}/routes/{route}', [ConsultationWorkspaceController::class, 'show'])->name('consultations.routes.show');
            Route::get('consultations/{visit}/history', [ConsultationWorkspaceController::class, 'history'])->name('consultations.history')->middleware('can:consultation.preview');
            Route::patch('consultations/{visit}/transition', [ConsultationSessionController::class, 'transitionVisit'])->name('consultations.transition')->middleware('can:visits.transition');
            Route::post('consultations/{visit}/start', [ConsultationSessionController::class, 'startConsultation'])->name('consultations.start')->middleware('can:consultations.create');
            // Queuing a session never redirects into the consultation record (see
            // storeRoute()) — it only needs consultations.create, not the group's
            // consultations.view gate.
            Route::post('consultations/{visit}/routes', [ConsultationSessionController::class, 'storeRoute'])->name('consultations.routes.store')->withoutMiddleware('can:consultations.view')->middleware('can:consultations.create');
            Route::post('consultations/{visit}/routes/{route}/activate', [ConsultationSessionController::class, 'activateRoute'])->name('consultations.routes.activate')->middleware('can:consultations.create');
            Route::post('consultations/{visit}/routes/{route}/complete', [ConsultationSessionController::class, 'completeRoute'])->name('consultations.routes.complete')->middleware('can:consultations.create');
            Route::post('consultations/{visit}/routes/{route}/cancel', [ConsultationSessionController::class, 'cancelRoute'])->name('consultations.routes.cancel')->middleware('can:consultations.create');
            Route::post('consultations/{visit}/routes/{route}/reopen', [ConsultationSessionController::class, 'reopenRoute'])->name('consultations.routes.reopen')->middleware('can:consultations.reopen');
            Route::post('consultations/{visit}/routes/{route}/follow-up-appointments', [ConsultationPlanningController::class, 'storeFollowUpAppointment'])->name('consultations.routes.follow-up.store')->middleware('can:consultation.followup.create');
            Route::put('consultations/{visit}/routes/{route}/follow-up-appointments/{appointment}', [ConsultationPlanningController::class, 'updateFollowUpAppointment'])->name('consultations.routes.follow-up.update')->middleware('can:consultation.followup.update');
            Route::post('consultations/{visit}/routes/{route}/follow-up-appointments/{appointment}/cancel', [ConsultationPlanningController::class, 'cancelFollowUpAppointment'])->name('consultations.routes.follow-up.cancel')->middleware('can:consultation.followup.cancel');
            Route::post('consultations/{visit}/routes/{route}/next-patient/open', [ConsultationPlanningController::class, 'openNextPatient'])->name('consultations.routes.next-patient.open')->middleware('can:consultations.create');
            Route::post('consultations/{visit}/routes/{route}/next-patient/complete-and-open', [ConsultationPlanningController::class, 'completeAndOpenNextPatient'])->name('consultations.routes.next-patient.complete-open')->middleware('can:consultations.create');

            // Referral to another department
            Route::post('consultations/{visit}/refer', [ConsultationPlanningController::class, 'refer'])->name('consultations.refer')->middleware('can:consultations.create');

            // Send to investigation department
            Route::post('consultations/{visit}/investigation', [ConsultationOrderController::class, 'sendToInvestigation'])->name('consultations.investigation')->middleware('can:consultations.create');

            // Consultation sub-resources (complaints, diagnoses, investigations, treatments, prescriptions)
            Route::middleware('can:consultations.create')->group(function () {
                Route::post('consultations/{visit}/complaints', [ConsultationClinicalEntryController::class, 'storeComplaint'])->name('consultations.complaints.store');
                Route::patch('consultations/complaints/{complaint}', [ConsultationClinicalEntryController::class, 'updateComplaint'])->name('consultations.complaints.update');
                Route::delete('consultations/complaints/{complaint}', [ConsultationClinicalEntryController::class, 'destroyComplaint'])->name('consultations.complaints.destroy');

                Route::post('consultations/{visit}/history-of-presenting-complaints', [ConsultationClinicalEntryController::class, 'storeHistoryOfPresentingComplaint'])->name('consultations.hopc.store');
                Route::patch('consultations/history-of-presenting-complaints/{hopc}', [ConsultationClinicalEntryController::class, 'updateHistoryOfPresentingComplaint'])->name('consultations.hopc.update');
                Route::delete('consultations/history-of-presenting-complaints/{hopc}', [ConsultationClinicalEntryController::class, 'destroyHistoryOfPresentingComplaint'])->name('consultations.hopc.destroy');

                Route::post('consultations/{visit}/examinations', [ConsultationClinicalEntryController::class, 'storeExamination'])->name('consultations.examinations.store');
                Route::patch('consultations/examinations/{examination}', [ConsultationClinicalEntryController::class, 'updateExamination'])->name('consultations.examinations.update');
                Route::delete('consultations/examinations/{examination}', [ConsultationClinicalEntryController::class, 'destroyExamination'])->name('consultations.examinations.destroy');

                Route::post('consultations/{visit}/diagnoses', [ConsultationClinicalEntryController::class, 'storeDiagnosis'])->name('consultations.diagnoses.store');
                Route::patch('consultations/diagnoses/{diagnosis}', [ConsultationClinicalEntryController::class, 'updateDiagnosis'])->name('consultations.diagnoses.update');
                Route::patch('consultations/diagnoses/{diagnosis}/primary', [ConsultationClinicalEntryController::class, 'setPrimaryDiagnosis'])->name('consultations.diagnoses.primary');
                Route::delete('consultations/diagnoses/{diagnosis}', [ConsultationClinicalEntryController::class, 'destroyDiagnosis'])->name('consultations.diagnoses.destroy');

                Route::post('consultations/{visit}/investigations', [ConsultationOrderController::class, 'storeInvestigation'])->name('consultations.investigations.store');
                Route::patch('consultations/investigations/{investigation}', [ConsultationOrderController::class, 'updateInvestigation'])->name('consultations.investigations.update');
                Route::delete('consultations/investigations/{investigation}', [ConsultationOrderController::class, 'destroyInvestigation'])->name('consultations.investigations.destroy');
                Route::delete('consultations/investigation-items/{item}', [ConsultationOrderController::class, 'destroyInvestigationItem'])->name('consultations.investigation-items.destroy');
                Route::post('consultations/{visit}/investigation-departments/{department}/send-to-department', [ConsultationOrderController::class, 'sendInvestigationDepartmentToDepartment'])->name('consultations.investigation-departments.send-to-department');
                Route::post('consultations/{visit}/lab-requests/{labRequest}/send-to-department', [ConsultationOrderController::class, 'sendLabRequestToDepartment'])->name('consultations.lab-requests.send-to-department');

                Route::post('consultations/{visit}/treatments', [ConsultationClinicalEntryController::class, 'storeTreatment'])->name('consultations.treatments.store');
                Route::patch('consultations/treatments/{treatment}', [ConsultationClinicalEntryController::class, 'updateTreatment'])->name('consultations.treatments.update');
                Route::delete('consultations/treatments/{treatment}', [ConsultationClinicalEntryController::class, 'destroyTreatment'])->name('consultations.treatments.destroy');

                Route::post('consultations/{visit}/specialty-entries/{sectionKey}', [ConsultationSpecialtyEntryController::class, 'store'])->name('consultations.specialty-entries.store');
                Route::delete('consultations/{visit}/specialty-entries/{sectionKey}', [ConsultationSpecialtyEntryController::class, 'destroy'])->name('consultations.specialty-entries.destroy');

                Route::get('consultations/{visit}/specialty-order-sets', [ConsultationSpecialtyOrderSetController::class, 'index'])->name('consultations.specialty-order-sets.index');
                Route::get('consultations/{visit}/specialty-order-sets/{orderSet}/preview', [ConsultationSpecialtyOrderSetController::class, 'preview'])->name('consultations.specialty-order-sets.preview');
                Route::post('consultations/{visit}/specialty-order-sets/{orderSet}/apply', [ConsultationSpecialtyOrderSetController::class, 'apply'])->name('consultations.specialty-order-sets.apply');
                Route::get('consultations/{visit}/specialty-billing/preview', [ConsultationSpecialtyBillingController::class, 'preview'])->name('consultations.specialty-billing.preview');
                Route::post('consultations/{visit}/specialty-billing/apply', [ConsultationSpecialtyBillingController::class, 'apply'])->name('consultations.specialty-billing.apply')->middleware('can:invoices.create');
            });

            Route::get('consultations/{visit}/summary-fragment', [ConsultationWorkspaceController::class, 'summaryFragment'])->name('consultations.summary-fragment');
            Route::get('consultations/{visit}/readiness-fragment', [ConsultationWorkspaceController::class, 'readinessFragment'])->name('consultations.readiness-fragment');
            Route::get('consultations/{visit}/specialty-summary/preview', [ConsultationSpecialtySummaryController::class, 'preview'])->name('consultations.specialty-summary.preview');
            Route::patch('consultations/{visit}/final-note', [ConsultationWorkspaceController::class, 'updateFinalNote'])->name('consultations.final-note.update')->middleware('can:consultations.create');
            Route::patch('consultations/preferences/pinned-actions', [DoctorConsultationPreferenceController::class, 'updatePinnedActions'])->name('consultations.preferences.pinned-actions.update')->middleware('can:consultations.create');
            Route::patch('consultations/preferences/layout', [DoctorConsultationPreferenceController::class, 'updateLayout'])->name('consultations.preferences.layout.update')->middleware('can:consultations.create');

            Route::get('departments/{department}/investigation-services', [ConsultationOrderController::class, 'getDepartmentServices'])->name('departments.investigation-services');
            Route::get('departments/{department}/investigation-info', [ConsultationOrderController::class, 'getDepartmentInvestigationInfo'])->name('departments.investigation-info');

            Route::post('consultations/{visit}/prescriptions', [ConsultationPrescriptionController::class, 'storePrescription'])->name('consultations.prescriptions.store')->middleware('can:prescriptions.create');
            Route::patch('consultations/prescriptions/{prescription}', [ConsultationPrescriptionController::class, 'updatePrescription'])->name('consultations.prescriptions.update')->middleware('can:prescriptions.create');
            Route::delete('consultations/prescriptions/{prescription}', [ConsultationPrescriptionController::class, 'destroyPrescription'])->name('consultations.prescriptions.destroy')->middleware('can:prescriptions.create');
            Route::post('consultations/{visit}/prescription-departments/{department}/send-to-department', [ConsultationPrescriptionController::class, 'sendPrescriptionDepartmentToDepartment'])->name('consultations.prescription-departments.send-to-department')->middleware('can:prescriptions.create');
            Route::post('consultations/{visit}/procedures', [ConsultationOrderController::class, 'storeProcedureRequest'])->name('consultations.procedures.store')->middleware('can:procedure.request');
            Route::patch('consultations/procedures/{procedureRequest}', [ConsultationOrderController::class, 'updateProcedureRequest'])->name('consultations.procedures.update')->middleware('can:procedure.request');
            Route::post('consultations/{visit}/procedure-departments/{department}/send-to-department', [ConsultationOrderController::class, 'sendProcedureDepartmentToDepartment'])->name('consultations.procedure-departments.send-to-department')->middleware('can:procedure.request');
            Route::post('consultations/{visit}/lab-request', [ConsultationOrderController::class, 'storeLabRequest'])->name('consultations.lab-request.store')->middleware('can:lab.requests.create');
            Route::patch('consultations/lab-requests/{labRequest}', [ConsultationOrderController::class, 'updateLabRequest'])->name('consultations.lab-request.update')->middleware('can:lab.requests.create');

            // Suggestion endpoints
            Route::get('consultations/suggest/complaints', [ConsultationClinicalEntryController::class, 'suggestComplaints'])->name('consultations.suggest.complaints');
            Route::get('consultations/suggest/diagnoses', [ConsultationClinicalEntryController::class, 'suggestDiagnoses'])->name('consultations.suggest.diagnoses');

            // Consultation Tasks
            Route::middleware('can:consultations.create')->group(function () {
                Route::post('consultations/{visit}/tasks', [ConsultationTaskController::class, 'store'])->name('consultations.tasks.store');
                Route::put('consultations/tasks/{task}', [ConsultationTaskController::class, 'update'])->name('consultations.tasks.update');
                Route::patch('consultations/tasks/{task}/toggle', [ConsultationTaskController::class, 'toggleComplete'])->name('consultations.tasks.toggle');
                Route::delete('consultations/tasks/{task}', [ConsultationTaskController::class, 'destroy'])->name('consultations.tasks.destroy');
            });
        });

        // Triage Workflow
        Route::middleware(['can:vitals.view', 'records.redirect'])->group(function () {
            Route::get('triage', [TriageController::class, 'index'])->name('triage.index');
            Route::get('triage/{visit}', [TriageController::class, 'show'])->name('triage.show');
            Route::get('triage/{visit}/assess', [TriageController::class, 'create'])->name('triage.create')->middleware('can:vitals.create');
            Route::post('triage/{visit}', [TriageController::class, 'store'])->name('triage.store')->middleware('can:vitals.create');
            Route::put('triage/{visit}/assess', [TriageController::class, 'update'])->name('triage.update')->middleware('can:vitals.create');
        });

        // Vitals (Nurse Triage)
        Route::middleware(['can:vitals.view', 'records.redirect'])->group(function () {
            Route::get('vitals', [VitalController::class, 'create'])->name('vitals.create');
            Route::post('vitals', [VitalController::class, 'store'])->name('vitals.store')->middleware('can:vitals.create');
            Route::get('vitals/{visit}', [VitalController::class, 'show'])->name('vitals.show');
            Route::patch('vitals/{visit}/update-priority', [VitalController::class, 'updatePriority'])->name('vitals.update-priority')->middleware('can:vitals.create');
            Route::patch('vitals/{visit}/assign-consultation', [VitalController::class, 'assignConsultation'])->name('vitals.assign-consultation')->middleware('can:vitals.create');
        });

        // Prescriptions
        Route::middleware(['can:prescriptions.view', 'records.redirect'])->group(function () {
            Route::get('prescriptions', [PrescriptionController::class, 'index'])->name('prescriptions.index');
            Route::get('prescriptions/{prescription}', [PrescriptionController::class, 'show'])->name('prescriptions.show');
            Route::get('prescriptions/{prescription}/print', [PrescriptionController::class, 'print'])->name('prescriptions.print');
            Route::post('prescriptions/{prescription}/another', [PrescriptionController::class, 'storeAnother'])->name('prescriptions.another')->middleware('can:prescriptions.create');
            Route::post('prescriptions/{prescription}/bill', [PrescriptionController::class, 'bill'])->name('prescriptions.bill')->middleware('can:pharmacy.dispensing.create');
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
        Route::prefix('lab')->name('lab.')->middleware(['module:investigations', 'records.redirect'])->group(function () {
            // Lab Requests
            Route::middleware('can:lab.requests.view')->group(function () {
                Route::get('requests', [LabRequestController::class, 'index'])->name('requests.index');
                Route::get('requests/{labRequest}', [LabRequestController::class, 'show'])->name('requests.show');
                Route::patch('requests/{labRequest}/accept', [LabRequestController::class, 'accept'])->name('requests.accept')->middleware('can:lab.results.create');
                Route::post('requests/{labRequest}/accept-selected', [LabRequestController::class, 'acceptSelected'])->name('requests.accept-selected')->middleware('can:lab.results.create');
                Route::patch('requests/{labRequest}/cancel', [LabRequestController::class, 'cancel'])->name('requests.cancel')->middleware('can:lab.results.create');
            });

            // Specimen / Sample Tracking
            Route::middleware('can:lab.samples.view')->group(function () {
                Route::get('samples', [SampleController::class, 'index'])->name('samples.index');
                Route::post('requests/{labRequest}/samples/generate', [SampleController::class, 'generate'])->name('samples.generate')->middleware('can:lab.samples.manage');
                Route::patch('samples/{sample}/collect', [SampleController::class, 'collect'])->name('samples.collect')->middleware('can:lab.samples.collect');
                Route::patch('samples/{sample}/receive', [SampleController::class, 'receive'])->name('samples.receive')->middleware('can:lab.samples.receive');
                Route::patch('samples/{sample}/reject', [SampleController::class, 'reject'])->name('samples.reject')->middleware('can:lab.samples.manage');
                Route::patch('samples/{sample}/dispose', [SampleController::class, 'dispose'])->name('samples.dispose')->middleware('can:lab.samples.manage');
            });

            // Lab Results
            Route::middleware('can:lab.results.view')->group(function () {
                Route::get('results', [LabResultController::class, 'index'])->name('results.index');
                Route::get('results/requests/{labRequest}', [LabResultController::class, 'showRequest'])->name('results.show');
                Route::post('results/batch/{labRequest}', [LabResultController::class, 'batchStore'])->name('results.batch')->middleware('can:lab.results.create');
                Route::patch('results/{result}/verify', [LabResultController::class, 'verify'])->name('results.verify')->middleware('can:lab.results.verify');
                Route::get('results/{item}/view', [LabResultController::class, 'view'])->name('results.view');
                Route::get('results/requests/{labRequest}/print', [LabResultController::class, 'printRequest'])->name('results.print-request');
                Route::get('results/{item}/print', [LabResultController::class, 'print'])->name('results.print');
                Route::post('results/{item}', [LabResultController::class, 'store'])->name('results.store')->middleware('can:lab.results.create');
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
        Route::prefix('investigation-catalogue')->name('investigation-catalogue.')->middleware(['module:investigations', 'can:lab.tests.manage', 'records.redirect'])->group(function () {
            Route::get('/', [InvestigationCatalogueController::class, 'index'])->name('index');
            Route::get('/{service}', [InvestigationCatalogueController::class, 'show'])->name('show');
            Route::put('/{service}/overall-result', [InvestigationCatalogueController::class, 'updateOverallResult'])->name('overall-result.update');

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
        Route::prefix('pharmacy')->name('pharmacy.')->middleware(['module:pharmacy', 'records.redirect'])->group(function () {
            // Dispensing
            Route::middleware('can:pharmacy.dispensing.view')->group(function () {
                Route::get('dispensing', [DispensingController::class, 'index'])->name('dispensing.index');
                Route::get('dispensing/{prescription}', [DispensingController::class, 'show'])->name('dispensing.show');
                Route::get('dispensing/items/{item}/dosage-print', [DispensingController::class, 'printDosage'])->name('dispensing.print-dosage');
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
        Route::prefix('billing')->name('billing.')->middleware(['module:billing', 'records.redirect'])->group(function () {
            // Billing dashboard
            Route::get('dashboard', [BillingReportController::class, 'dashboard'])
                ->name('dashboard')->middleware('can:invoices.view');

            // Patient financial-risk worklist & report (Payment Timing Policy Phase 5)
            Route::prefix('financial-risk')->name('financial-risk.')->group(function () {
                Route::get('/', [FinancialRiskController::class, 'index'])->name('index')->middleware('can:patients.financial_risk.view');
                Route::get('report', [FinancialRiskController::class, 'report'])->name('report')->middleware('can:patients.financial_risk.report');
                Route::get('export', [FinancialRiskController::class, 'export'])->name('export')->middleware('can:patients.financial_risk.report');
            });

            // Visit payment-policy worklist, detail, history & controlled refresh (Payment Timing Policy Phase 6)
            Route::prefix('visit-payment-policies')->name('visit-payment-policies.')->group(function () {
                Route::get('/', [VisitPaymentPolicyController::class, 'index'])->name('index')->middleware('can:visits.payment_policy.view');
                Route::get('report', [VisitPaymentPolicyController::class, 'report'])->name('report')->middleware('can:visits.payment_policy.report');
                Route::get('visit/{visit}', [VisitPaymentPolicyController::class, 'show'])->name('show')->middleware('can:visits.payment_policy.view');
                Route::post('visit/{visit}/refresh', [VisitPaymentPolicyController::class, 'refresh'])->name('refresh')->middleware('can:visits.payment_policy.refresh');
            });

            // Per-visit payment arrangements — request/approval workflow (Payment Timing Policy Phase 7)
            Route::prefix('visit-payment-arrangements')->name('visit-payment-arrangements.')->group(function () {
                Route::get('/', [VisitPaymentArrangementController::class, 'index'])->name('index')->middleware('can:visits.payment_arrangement.view');
                Route::get('report', [VisitPaymentArrangementController::class, 'report'])->name('report')->middleware('can:visits.payment_arrangement.report');
                Route::get('{arrangement}', [VisitPaymentArrangementController::class, 'show'])->name('show')->middleware('can:visits.payment_arrangement.view');
                Route::put('{arrangement}', [VisitPaymentArrangementController::class, 'update'])->name('update')->middleware('can:visits.payment_arrangement.request');
                Route::post('{arrangement}/approve', [VisitPaymentArrangementController::class, 'approve'])->name('approve')->middleware('can:visits.payment_arrangement.approve');
                Route::post('{arrangement}/reject', [VisitPaymentArrangementController::class, 'reject'])->name('reject')->middleware('can:visits.payment_arrangement.reject');
                Route::post('{arrangement}/withdraw', [VisitPaymentArrangementController::class, 'withdraw'])->name('withdraw')->middleware('can:visits.payment_arrangement.withdraw');
                Route::post('{arrangement}/revoke', [VisitPaymentArrangementController::class, 'revoke'])->name('revoke')->middleware('can:visits.payment_arrangement.revoke');
            });
            Route::post('visits/{visit}/payment-arrangements', [VisitPaymentArrangementController::class, 'store'])->name('visits.payment-arrangements.store')->middleware('can:visits.payment_arrangement.request');
            Route::post('visits/{visit}/payment-arrangements/restore-baseline', [VisitPaymentArrangementController::class, 'restoreBaseline'])->name('visits.payment-arrangements.restore-baseline')->middleware('can:visits.payment_arrangement.restore_baseline');

            // Operational payment-timing cutover (Payment Timing Policy Phase 8)
            Route::prefix('payment-timing-cutover')->name('payment-timing-cutover.')->group(function () {
                Route::get('/', [PaymentTimingCutoverController::class, 'index'])->name('index')->middleware('can:billing.payment_timing.cutover.view');
                Route::put('master', [PaymentTimingCutoverController::class, 'updateMaster'])->name('master')->middleware('can:billing.payment_timing.cutover.manage');
                Route::put('operation', [PaymentTimingCutoverController::class, 'updateOperation'])->name('operation')->middleware('can:billing.payment_timing.cutover.manage');
                Route::post('rollback', [PaymentTimingCutoverController::class, 'rollback'])->name('rollback')->middleware('can:billing.payment_timing.cutover.rollback');
            });

            Route::prefix('visit-financial-clearances')->name('visit-financial-clearances.')->group(function () {
                Route::get('/', [VisitFinancialClearanceController::class, 'index'])->name('index')->middleware('can:visits.financial_clearance.view');
                Route::get('report', [VisitFinancialClearanceController::class, 'report'])->name('report')->middleware('can:visits.financial_clearance.report');
                Route::get('export', [VisitFinancialClearanceController::class, 'export'])->name('export')->middleware('can:visits.financial_clearance.report');
                Route::get('settings', [VisitFinancialClearanceController::class, 'settings'])->name('settings')->middleware('can:billing.financial_clearance.settings.view');
                Route::put('settings', [VisitFinancialClearanceController::class, 'updateSettings'])->name('settings.update')->middleware('can:billing.financial_clearance.settings.manage');
                Route::post('settings/rollback', [VisitFinancialClearanceController::class, 'rollback'])->name('settings.rollback')->middleware('can:billing.financial_clearance.settings.rollback');
                Route::get('visit/{visit}', [VisitFinancialClearanceController::class, 'show'])->name('show')->middleware('can:visits.financial_clearance.view');
                Route::post('visit/{visit}/assess', [VisitFinancialClearanceController::class, 'assess'])->name('assess')->middleware('can:visits.financial_clearance.assess');
                Route::post('visit/{visit}/close', [VisitFinancialClearanceController::class, 'close'])->name('close')->middleware('can:visits.financial_clearance.close');
                Route::post('visit/{visit}/reopen', [VisitFinancialClearanceController::class, 'reopen'])->name('reopen')->middleware('can:visits.financial_clearance.close');
                Route::post('visit/{visit}/exceptions', [VisitFinancialClearanceController::class, 'requestException'])->name('exceptions.request')->middleware('can:visits.financial_clearance_exception.request');
                Route::post('exceptions/{exception}/approve', [VisitFinancialClearanceController::class, 'approve'])->name('exceptions.approve')->middleware('can:visits.financial_clearance_exception.approve');
                Route::post('exceptions/{exception}/reject', [VisitFinancialClearanceController::class, 'reject'])->name('exceptions.reject')->middleware('can:visits.financial_clearance_exception.reject');
                Route::post('exceptions/{exception}/withdraw', [VisitFinancialClearanceController::class, 'withdraw'])->name('exceptions.withdraw')->middleware('can:visits.financial_clearance_exception.withdraw');
                Route::post('exceptions/{exception}/revoke', [VisitFinancialClearanceController::class, 'revoke'])->name('exceptions.revoke')->middleware('can:visits.financial_clearance_exception.revoke');
            });

            // Walk-in counter sale (drugs + investigations, no visit)
            Route::middleware('can:invoices.create')->group(function () {
                Route::get('counter-sale', [CounterSaleController::class, 'create'])->name('counter-sale.create');
                Route::post('counter-sale', [CounterSaleController::class, 'store'])->name('counter-sale.store');
                Route::get('counter-sale/drug-search', [CounterSaleController::class, 'drugSearch'])->name('counter-sale.drug-search');
                Route::get('counter-sale/service-search', [CounterSaleController::class, 'serviceSearch'])->name('counter-sale.service-search');
                Route::get('counter-sale/procedure-search', [CounterSaleController::class, 'procedureSearch'])->name('counter-sale.procedure-search');
            });

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
                    ->name('invoices.items.discount.remove');
                Route::post('invoices/{invoice}/receivables/reallocate', [ReceivableController::class, 'reallocate'])
                    ->name('invoices.receivables.reallocate')
                    ->middleware('can:receivables.reallocate');
            });

            // Payments
            Route::middleware('can:payments.view')->group(function () {
                Route::get('payments/receive', [PaymentController::class, 'receive'])->name('payments.receive')->middleware('can:payments.create');
                Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
                Route::post('payments/{invoice}', [PaymentController::class, 'store'])->name('payments.store')->middleware('can:payments.create');
                Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
                Route::get('payments/{payment}/receipt-thermal', [PaymentController::class, 'receiptThermal'])->name('payments.receipt-thermal');
                Route::get('payments/{payment}/receipt-pdf', [PaymentController::class, 'receiptPdf'])->name('payments.receipt-pdf');
                Route::post('payments/{payment}/reverse', [PaymentController::class, 'reverse'])
                    ->name('payments.reverse')->middleware('can:payments.refund');

                // Cross-visit payment allocation (oldest-first / current-visit / manual).
                Route::post('previous-balance/{patient}/allocate', [PreviousBalanceController::class, 'allocate'])
                    ->name('previous-balance.allocate')
                    ->middleware('can:billing.payment.allocate_cross_visit');
            });

            // Previous-visit outstanding balance OPD override.
            Route::post('previous-balance/{visit}/override', [PreviousBalanceController::class, 'override'])
                ->name('previous-balance.override')
                ->middleware('can:billing.previous_balance.override');

            // Credit notes & write-offs
            Route::middleware('can:credit_notes.view')->prefix('credit-notes')->name('credit-notes.')->group(function () {
                Route::get('/', [CreditNoteController::class, 'index'])->name('index');
                Route::get('create', [CreditNoteController::class, 'create'])->name('create')->middleware('can:credit_notes.create');
                Route::get('available', [CreditNoteController::class, 'available'])->name('available');
                Route::post('/', [CreditNoteController::class, 'store'])->name('store')->middleware('can:credit_notes.create');
                Route::post('{creditNote}/reverse', [CreditNoteController::class, 'reverse'])->name('reverse');
                Route::patch('{creditNote}/cancel', [CreditNoteController::class, 'cancel'])->name('cancel');
            });

            // Corporate sponsors
            Route::prefix('sponsors')->name('sponsors.')->group(function () {
                Route::get('/', [SponsorController::class, 'index'])->name('index')->middleware('can:sponsors.view');
                Route::post('/', [SponsorController::class, 'store'])->name('store')->middleware('can:sponsors.create');
                Route::put('{sponsor}', [SponsorController::class, 'update'])->name('update')->middleware('can:sponsors.edit');
                Route::patch('{sponsor}/toggle', [SponsorController::class, 'toggle'])->name('toggle')->middleware('can:sponsors.edit');
            });

            // Billing reports (AR aging & statements)
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::middleware('can:reports.ar_aging.view')->group(function () {
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

        // Accounts Payable (Accounting Phase 5)
        Route::prefix('accounts-payable')->name('accounts-payable.')->middleware(['module:accounting_advanced', 'can:accounts_payable.view'])->group(function () {
            Route::get('/', [AccountsPayableController::class, 'payables'])->name('payables');
            Route::get('aging', [AccountsPayableController::class, 'aging'])->name('aging')->middleware('can:reports.ap_aging.view');
            Route::get('payments', [AccountsPayableController::class, 'payments'])->name('payments');
            Route::post('payments', [AccountsPayableController::class, 'recordPayment'])->name('payments.store')->middleware('can:supplier_payments.create');
            Route::post('payments/{payment}/reverse', [AccountsPayableController::class, 'reversePayment'])->name('payments.reverse')->middleware('can:supplier_payments.reverse');
            Route::get('statement/{supplier}', [AccountsPayableController::class, 'statement'])->name('statement')->middleware('can:reports.supplier_statement.view');
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

        // Consultation specialty configuration
        Route::middleware('can:consultation-specialties.view')->prefix('consultation-specialties')->name('consultation-specialties.')->group(function () {
            Route::get('/', [ConsultationSpecialtyProfileController::class, 'index'])->name('index');
            Route::get('create', [ConsultationSpecialtyProfileController::class, 'create'])->name('create')->middleware('can:consultation-specialties.create');
            Route::post('/', [ConsultationSpecialtyProfileController::class, 'store'])->name('store')->middleware('can:consultation-specialties.create');
            Route::post('reorder', [ConsultationSpecialtyProfileController::class, 'reorder'])->name('reorder')->middleware('can:consultation-specialties.configure');

            Route::get('mappings', [ConsultationSpecialtyMappingController::class, 'index'])->name('mappings.index');
            Route::post('mappings', [ConsultationSpecialtyMappingController::class, 'store'])->name('mappings.store')->middleware('can:consultation-specialties.configure');
            Route::patch('mappings/{mapping}', [ConsultationSpecialtyMappingController::class, 'update'])->name('mappings.update')->middleware('can:consultation-specialties.configure');
            Route::delete('mappings/{mapping}', [ConsultationSpecialtyMappingController::class, 'destroy'])->name('mappings.destroy')->middleware('can:consultation-specialties.delete');

            Route::get('service-mappings', [ConsultationSpecialtyServiceMappingController::class, 'index'])->name('service-mappings.index')->middleware('can:consultation-specialties.configure');
            Route::post('service-mappings', [ConsultationSpecialtyServiceMappingController::class, 'store'])->name('service-mappings.store')->middleware('can:consultation-specialties.configure');
            Route::patch('service-mappings/{mapping}', [ConsultationSpecialtyServiceMappingController::class, 'update'])->name('service-mappings.update')->middleware('can:consultation-specialties.configure');
            Route::delete('service-mappings/{mapping}', [ConsultationSpecialtyServiceMappingController::class, 'destroy'])->name('service-mappings.destroy')->middleware('can:consultation-specialties.delete');
            Route::post('service-mappings/reorder', [ConsultationSpecialtyServiceMappingController::class, 'reorder'])->name('service-mappings.reorder')->middleware('can:consultation-specialties.configure');

            Route::get('doctor-preferences', [DoctorConsultationPreferenceAdminController::class, 'index'])->name('doctor-preferences.index')->middleware('can:consultation-specialties.configure');
            Route::delete('doctor-preferences/{preference}', [DoctorConsultationPreferenceAdminController::class, 'destroy'])->name('doctor-preferences.destroy')->middleware('can:consultation-specialties.configure');

            Route::get('{profile}', [ConsultationSpecialtyProfileController::class, 'show'])->name('show');
            Route::get('{profile}/edit', [ConsultationSpecialtyProfileController::class, 'edit'])->name('edit')->middleware('can:consultation-specialties.update');
            Route::patch('{profile}', [ConsultationSpecialtyProfileController::class, 'update'])->name('update')->middleware('can:consultation-specialties.update');
            Route::delete('{profile}', [ConsultationSpecialtyProfileController::class, 'destroy'])->name('destroy')->middleware('can:consultation-specialties.delete');

            Route::get('{profile}/sections', [ConsultationSpecialtySectionController::class, 'index'])->name('sections.index');
            Route::post('{profile}/sections', [ConsultationSpecialtySectionController::class, 'store'])->name('sections.store')->middleware('can:consultation-specialties.configure');
            Route::patch('{profile}/sections/{section}', [ConsultationSpecialtySectionController::class, 'update'])->name('sections.update')->middleware('can:consultation-specialties.configure');
            Route::delete('{profile}/sections/{section}', [ConsultationSpecialtySectionController::class, 'destroy'])->name('sections.destroy')->middleware('can:consultation-specialties.delete');
            Route::post('{profile}/sections/reorder', [ConsultationSpecialtySectionController::class, 'reorder'])->name('sections.reorder')->middleware('can:consultation-specialties.configure');

            Route::get('{profile}/favorites', [ConsultationSpecialtyFavoriteController::class, 'index'])->name('favorites.index');
            Route::post('{profile}/favorites', [ConsultationSpecialtyFavoriteController::class, 'store'])->name('favorites.store')->middleware('can:consultation-specialties.configure');
            Route::patch('{profile}/favorites/{favorite}', [ConsultationSpecialtyFavoriteController::class, 'update'])->name('favorites.update')->middleware('can:consultation-specialties.configure');
            Route::delete('{profile}/favorites/{favorite}', [ConsultationSpecialtyFavoriteController::class, 'destroy'])->name('favorites.destroy')->middleware('can:consultation-specialties.delete');
            Route::post('{profile}/favorites/reorder', [ConsultationSpecialtyFavoriteController::class, 'reorder'])->name('favorites.reorder')->middleware('can:consultation-specialties.configure');

            Route::get('{profile}/order-sets', [AdminConsultationSpecialtyOrderSetController::class, 'index'])->name('order-sets.index');
            Route::get('{profile}/order-sets/create', [AdminConsultationSpecialtyOrderSetController::class, 'create'])->name('order-sets.create')->middleware('can:consultation-specialties.configure');
            Route::post('{profile}/order-sets', [AdminConsultationSpecialtyOrderSetController::class, 'store'])->name('order-sets.store')->middleware('can:consultation-specialties.configure');
            Route::get('{profile}/order-sets/{orderSet}', [AdminConsultationSpecialtyOrderSetController::class, 'show'])->name('order-sets.show');
            Route::get('{profile}/order-sets/{orderSet}/edit', [AdminConsultationSpecialtyOrderSetController::class, 'edit'])->name('order-sets.edit')->middleware('can:consultation-specialties.configure');
            Route::patch('{profile}/order-sets/{orderSet}', [AdminConsultationSpecialtyOrderSetController::class, 'update'])->name('order-sets.update')->middleware('can:consultation-specialties.configure');
            Route::delete('{profile}/order-sets/{orderSet}', [AdminConsultationSpecialtyOrderSetController::class, 'destroy'])->name('order-sets.destroy')->middleware('can:consultation-specialties.delete');
            Route::post('{profile}/order-sets/reorder', [AdminConsultationSpecialtyOrderSetController::class, 'reorder'])->name('order-sets.reorder')->middleware('can:consultation-specialties.configure');

            Route::get('{profile}/order-sets/{orderSet}/items', [ConsultationSpecialtyOrderSetItemController::class, 'index'])->name('order-sets.items.index');
            Route::post('{profile}/order-sets/{orderSet}/items', [ConsultationSpecialtyOrderSetItemController::class, 'store'])->name('order-sets.items.store')->middleware('can:consultation-specialties.configure');
            Route::patch('{profile}/order-sets/{orderSet}/items/{item}', [ConsultationSpecialtyOrderSetItemController::class, 'update'])->name('order-sets.items.update')->middleware('can:consultation-specialties.configure');
            Route::delete('{profile}/order-sets/{orderSet}/items/{item}', [ConsultationSpecialtyOrderSetItemController::class, 'destroy'])->name('order-sets.items.destroy')->middleware('can:consultation-specialties.delete');
            Route::post('{profile}/order-sets/{orderSet}/items/reorder', [ConsultationSpecialtyOrderSetItemController::class, 'reorder'])->name('order-sets.items.reorder')->middleware('can:consultation-specialties.configure');
        });

        // Statistical Reports / Analytics — per-page permission is enforced in the
        // controller so a user holding only a single statistics.*.view (e.g.
        // staff_performance) can still reach that page.
        Route::middleware('module:reports')->prefix('statistics')->name('statistics.')->group(function () {
            Route::get('/', [StatisticsController::class, 'dashboard'])->name('dashboard');
            Route::get('investigation-results', function (Request $request) {
                return redirect()->route('admin.statistics.investigations', $request->query());
            })->name('investigation-results');
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
        Route::middleware(['module:reports', 'can:reports.view', 'records.redirect'])->prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportsHubController::class, 'index'])->name('index');
            Route::get('/dashboard', [OperationalReportController::class, 'dashboard'])->name('dashboard');
            Route::get('consultation-specialties', [ConsultationSpecialtyReportController::class, 'index'])->name('consultation-specialties.index');
            Route::get('consultation-specialties/data', [ConsultationSpecialtyReportController::class, 'data'])->name('consultation-specialties.data');
            Route::get('consultation-specialties/export', [ConsultationSpecialtyReportController::class, 'export'])->name('consultation-specialties.export');
            Route::get('department-metrics', [DepartmentMetricsController::class, 'index'])->name('department-metrics');
            Route::get('department-comparison', [DepartmentComparisonController::class, 'index'])
                ->name('department-comparison.index')
                ->middleware('can:reports.department_comparison.view');
            Route::get('department-comparison/export', [DepartmentComparisonController::class, 'export'])
                ->name('department-comparison.export')
                ->middleware('can:reports.department_comparison.export');
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
            Route::get('payment-timing', [SettingsController::class, 'paymentTiming'])->name('payment-timing');
            Route::put('payment-timing', [SettingsController::class, 'updatePaymentTiming'])->name('payment-timing.update');
            // Departmental payment enforcement (Payment Timing Policy Phase 4)
            Route::get('payment-gate-operations', [SettingsController::class, 'paymentGateOperations'])->name('payment-gate-operations');
            Route::put('payment-gate-operations', [SettingsController::class, 'updatePaymentGateOperations'])->name('payment-gate-operations.update');
            Route::get('ward', [SettingsController::class, 'ward'])->name('ward');
            Route::put('ward', [SettingsController::class, 'updateWard'])->name('ward.update');
            Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log');
        });

        // Activity logs (dedicated viewer with permission scoping)
        Route::prefix('logs')->name('logs.')->middleware('records.redirect')->group(function () {
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

        Route::prefix('patient-privacy')->name('patient-privacy.')->middleware('can:patients.privacy_audit.view')->group(function () {
            Route::get('audit', [PatientPrivacyController::class, 'audit'])->name('audit');
            Route::get('historical-log-dry-run', [PatientPrivacyController::class, 'historicalLogDryRun'])->name('historical-log-dry-run');
        });

        // Modules Management (Admin)
        Route::middleware('can:modules.manage')->prefix('modules')->name('modules.')->group(function () {
            Route::get('/', [ModuleController::class, 'index'])->name('index');
            Route::post('{module}/toggle', [ModuleController::class, 'toggle'])->name('toggle');
            Route::post('flush', [ModuleController::class, 'flushCache'])->name('flush');
        });

        /*
        |--------------------------------------------------------------------
        | External Integrations — SMS & Payment Gateways (Phase 1)
        |--------------------------------------------------------------------
        | Provider configuration is admin/IT controlled. Public provider
        | callbacks live in routes/api.php (session-less, CSRF-exempt).
        */
        Route::prefix('integrations')->name('integrations.')->group(function () {

            // ── SMS Gateway ──────────────────────────────────────────────
            Route::middleware(['module:sms_gateway', 'can:integrations.sms.view'])
                ->prefix('sms')->name('sms.')->group(function () {
                    Route::get('providers', [SmsProviderController::class, 'index'])->name('providers.index');
                    Route::get('providers/create', [SmsProviderController::class, 'create'])->name('providers.create')->middleware('can:integrations.sms.providers.manage');
                    Route::post('providers', [SmsProviderController::class, 'store'])->name('providers.store')->middleware('can:integrations.sms.providers.manage');
                    Route::get('providers/{provider}/edit', [SmsProviderController::class, 'edit'])->name('providers.edit')->middleware('can:integrations.sms.providers.manage');
                    Route::put('providers/{provider}', [SmsProviderController::class, 'update'])->name('providers.update')->middleware('can:integrations.sms.providers.manage');
                    Route::put('providers/{provider}/credentials', [SmsProviderController::class, 'updateCredentials'])->name('providers.credentials.update')->middleware('can:integrations.sms.credentials.manage');
                    Route::post('providers/{provider}/activate', [SmsProviderController::class, 'activate'])->name('providers.activate')->middleware('can:integrations.sms.providers.activate');
                    Route::post('providers/{provider}/deactivate', [SmsProviderController::class, 'deactivate'])->name('providers.deactivate')->middleware('can:integrations.sms.providers.activate');
                    Route::post('providers/{provider}/test', [SmsProviderController::class, 'test'])->name('providers.test')->middleware('can:integrations.sms.test');

                    Route::get('messages', [SmsMessageController::class, 'index'])->name('messages.index');
                    Route::get('messages/create', [SmsMessageController::class, 'create'])->name('messages.create')->middleware('can:integrations.sms.send');
                    Route::post('messages', [SmsMessageController::class, 'store'])->name('messages.store')->middleware('can:integrations.sms.send');
                    Route::get('messages/{message}', [SmsMessageController::class, 'show'])->name('messages.show');
                    Route::post('messages/{message}/resend', [SmsMessageController::class, 'resend'])->name('messages.resend')->middleware('can:integrations.sms.send');

                    Route::middleware('can:integrations.sms.templates.manage')->group(function () {
                        Route::get('templates', [SmsTemplateController::class, 'index'])->name('templates.index');
                        Route::post('templates', [SmsTemplateController::class, 'store'])->name('templates.store');
                        Route::put('templates/{template}', [SmsTemplateController::class, 'update'])->name('templates.update');
                    });

                    Route::get('delivery-reports', [SmsDeliveryReportController::class, 'index'])->name('delivery-reports.index')->middleware('can:integrations.sms.reports.view');

                    // Phase 2 — queue, events, status reconciliation, template preview
                    Route::get('queue', [SmsQueueController::class, 'index'])->name('queue.index')->middleware('can:integrations.sms.queue.view');
                    Route::post('messages/{message}/retry', [SmsQueueController::class, 'retry'])->name('queue.retry')->middleware('can:integrations.sms.queue.retry');
                    Route::post('status-reconcile', [SmsQueueController::class, 'reconcile'])->name('status.reconcile')->middleware('can:integrations.sms.status.reconcile');
                    Route::get('events', [SmsEventController::class, 'index'])->name('events.index')->middleware('can:integrations.sms.events.manage');
                    Route::put('events', [SmsEventController::class, 'update'])->name('events.update')->middleware('can:integrations.sms.events.manage');
                    Route::post('templates/preview', [SmsTemplateController::class, 'preview'])->name('templates.preview')->middleware('can:integrations.sms.templates.manage');
                });

            // ── Payment Gateway ──────────────────────────────────────────
            Route::middleware(['module:payment_gateway', 'can:integrations.payments.view'])
                ->prefix('payments')->name('payments.')->group(function () {
                    Route::get('providers', [PaymentProviderController::class, 'index'])->name('providers.index');
                    Route::get('providers/create', [PaymentProviderController::class, 'create'])->name('providers.create')->middleware('can:integrations.payments.providers.manage');
                    Route::post('providers', [PaymentProviderController::class, 'store'])->name('providers.store')->middleware('can:integrations.payments.providers.manage');
                    Route::get('providers/{provider}/edit', [PaymentProviderController::class, 'edit'])->name('providers.edit')->middleware('can:integrations.payments.providers.manage');
                    Route::put('providers/{provider}', [PaymentProviderController::class, 'update'])->name('providers.update')->middleware('can:integrations.payments.providers.manage');
                    Route::put('providers/{provider}/credentials', [PaymentProviderController::class, 'updateCredentials'])->name('providers.credentials.update')->middleware('can:integrations.payments.credentials.manage');
                    Route::post('providers/{provider}/activate', [PaymentProviderController::class, 'activate'])->name('providers.activate')->middleware('can:integrations.payments.providers.activate');
                    Route::post('providers/{provider}/deactivate', [PaymentProviderController::class, 'deactivate'])->name('providers.deactivate')->middleware('can:integrations.payments.providers.activate');
                    Route::post('providers/{provider}/test', [PaymentProviderController::class, 'test'])->name('providers.test')->middleware('can:integrations.payments.test');

                    Route::get('transactions', [PaymentTransactionController::class, 'index'])->name('transactions.index')->middleware('can:integrations.payments.transactions.view');
                    Route::get('transactions/create', [PaymentTransactionController::class, 'create'])->name('transactions.create')->middleware('can:integrations.payments.transactions.initiate');
                    Route::post('transactions', [PaymentTransactionController::class, 'store'])->name('transactions.store')->middleware('can:integrations.payments.transactions.initiate');
                    Route::get('transactions/{transaction}', [PaymentTransactionController::class, 'show'])->name('transactions.show')->middleware('can:integrations.payments.transactions.view');
                    Route::post('transactions/{transaction}/verify', [PaymentTransactionController::class, 'verify'])->name('transactions.verify')->middleware('can:integrations.payments.transactions.verify');
                    Route::post('transactions/{transaction}/refunds', [PaymentRefundController::class, 'store'])->name('refunds.store')->middleware('can:integrations.payments.refunds.manage');

                    Route::get('callbacks', [PaymentCallbackController::class, 'index'])->name('callbacks.index')->middleware('can:integrations.payments.callbacks.view');

                    // Phase 2 — reconciliation dashboard, recheck/expire, refund bridge, request links
                    Route::get('reconciliation', [PaymentReconciliationController::class, 'index'])->name('reconciliation.index')->middleware('can:integrations.payments.reconciliation.view');
                    Route::get('reconciliation/export', [PaymentReconciliationController::class, 'export'])->name('reconciliation.export')->middleware('can:integrations.payments.reconciliation.view');
                    Route::post('transactions/{transaction}/recheck', [PaymentReconciliationController::class, 'recheck'])->name('reconciliation.recheck')->middleware('can:integrations.payments.reconciliation.verify');
                    Route::post('transactions/{transaction}/expire', [PaymentReconciliationController::class, 'markExpired'])->name('transactions.expire')->middleware('can:integrations.payments.reconciliation.expire');
                    Route::post('transactions/{transaction}/cancel', [PaymentReconciliationController::class, 'cancel'])->name('transactions.cancel')->middleware('can:integrations.payments.reconciliation.expire');
                    Route::post('transactions/{transaction}/refund-bridge', [PaymentRefundController::class, 'bridge'])->name('refunds.bridge')->middleware('can:integrations.payments.refunds.prepare');

                    Route::get('request-links', [PaymentRequestLinkController::class, 'index'])->name('request-links.index')->middleware('can:integrations.payments.request_links.manage');
                    Route::post('request-links', [PaymentRequestLinkController::class, 'store'])->name('request-links.store')->middleware('can:integrations.payments.request_links.manage');
                    Route::post('request-links/{link}/expire', [PaymentRequestLinkController::class, 'expire'])->name('request-links.expire')->middleware('can:integrations.payments.request_links.manage');
                    Route::post('invoices/{invoice}/payment-request-sms', [PaymentRequestLinkController::class, 'sendSms'])->name('invoices.payment-request-sms')->middleware('can:integrations.payments.request_links.manage');

                    // Inline mobile-money payment from the invoice screen (third path)
                    Route::post('invoices/{invoice}/charge', [InvoiceGatewayPaymentController::class, 'charge'])->name('invoices.charge')->middleware('can:integrations.payments.transactions.initiate');
                    Route::post('transactions/{transaction}/verify-inline', [InvoiceGatewayPaymentController::class, 'verify'])->name('transactions.verify-inline')->middleware('can:integrations.payments.transactions.verify');
                    Route::get('transactions/{transaction}/status', [InvoiceGatewayPaymentController::class, 'status'])->name('transactions.status')->middleware('can:integrations.payments.transactions.verify');
                });

            // ── Provider Health (spans both modules; admin/IT) ───────────
            Route::get('health', [ProviderHealthController::class, 'index'])->name('health.index')->middleware('can:integrations.payments.reconciliation.view');

            // ── Provider go-live checklists + scheduler status (Phase 3) ──
            Route::prefix('golive')->name('golive.')->group(function () {
                Route::get('/', [GoLiveChecklistController::class, 'index'])->name('index');
                Route::get('providers/{provider}', [GoLiveChecklistController::class, 'show'])->name('show');
                Route::post('checklists/{checklist}/items', [GoLiveChecklistController::class, 'updateItem'])->name('items.update')->middleware('can:integrations.payments.golive.manage');
                Route::post('checklists/{checklist}/signoff', [GoLiveChecklistController::class, 'signoff'])->name('signoff')->middleware('can:integrations.payments.golive.approve');
                Route::post('checklists/{checklist}/approve', [GoLiveChecklistController::class, 'approve'])->name('approve')->middleware('can:integrations.payments.golive.approve');
            });

            Route::get('scheduler', [SchedulerStatusController::class, 'index'])->name('scheduler.index')->middleware('can:integrations.scheduler.view');
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
                Route::get('departments/{department}/services', function (Request $request, Department $department) {
                    $visit = $request->integer('visit_id')
                        ? Visit::find($request->integer('visit_id'))
                        : null;

                    return response()->json(app(ProcedureRequestService::class)->servicesForDepartment($department->id, $visit));
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
        Route::prefix('products')->name('products.')->middleware(['module:inventory', 'records.redirect'])->group(function () {
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
        Route::prefix('product-stock')->name('product-stock.')->middleware(['module:inventory', 'records.redirect'])->group(function () {
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
        Route::prefix('investigations')->name('investigations.')->middleware(['module:investigations', 'records.redirect'])->group(function () {
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

        // Front Desk Operations (Phase 18A) — non-clinical reception / facility desk.
        Route::prefix('front-desk')->name('front-desk.')->middleware(['can:front_desk.view', 'records.redirect'])->group(function () {
            Route::get('/', [FrontDeskDashboardController::class, 'index'])
                ->name('index')->middleware('can:front_desk.dashboard.view');

            // Visitor logs
            Route::middleware('can:front_desk.visitors.view')->group(function () {
                Route::get('visitors', [VisitorLogController::class, 'index'])->name('visitors.index');
                Route::get('visitors/create', [VisitorLogController::class, 'create'])->name('visitors.create')->middleware('can:front_desk.visitors.create');
                Route::post('visitors', [VisitorLogController::class, 'store'])->name('visitors.store')->middleware('can:front_desk.visitors.create');
                // History (static segments registered before the {visitor} param routes)
                Route::get('visitors/patient/{patient}', [VisitorLogController::class, 'patientHistory'])->name('visitors.patient-history');
                Route::get('visitors/admission/{admission}', [VisitorLogController::class, 'admissionHistory'])->name('visitors.admission-history');
                Route::get('visitors/{visitor}', [VisitorLogController::class, 'show'])->name('visitors.show');
                Route::get('visitors/{visitor}/edit', [VisitorLogController::class, 'edit'])->name('visitors.edit')->middleware('can:front_desk.visitors.update');
                Route::put('visitors/{visitor}', [VisitorLogController::class, 'update'])->name('visitors.update')->middleware('can:front_desk.visitors.update');
                Route::get('visitors/{visitor}/pass', [VisitorLogController::class, 'pass'])->name('visitors.pass')->middleware('can:front_desk.visitors.print_pass');
                Route::post('visitors/{visitor}/check-out', [VisitorLogController::class, 'checkOut'])->name('visitors.check-out')->middleware('can:front_desk.visitors.checkout');
            });

            // Call logs
            Route::middleware('can:front_desk.calls.view')->group(function () {
                Route::get('calls', [CallLogController::class, 'index'])->name('calls.index');
                Route::get('calls/create', [CallLogController::class, 'create'])->name('calls.create')->middleware('can:front_desk.calls.create');
                Route::post('calls', [CallLogController::class, 'store'])->name('calls.store')->middleware('can:front_desk.calls.create');
                // Callback queue (Phase 18C) — static segment before {call}
                Route::get('calls/follow-ups', [CallLogController::class, 'followUps'])->name('calls.follow-ups')->middleware('can:front_desk.calls.followups.view');
                Route::get('calls/{call}', [CallLogController::class, 'show'])->name('calls.show');
                Route::get('calls/{call}/edit', [CallLogController::class, 'edit'])->name('calls.edit')->middleware('can:front_desk.calls.update');
                Route::put('calls/{call}', [CallLogController::class, 'update'])->name('calls.update')->middleware('can:front_desk.calls.update');
                // Follow-up workflow (Phase 18C)
                Route::post('calls/{call}/assign-follow-up', [CallLogController::class, 'assignFollowUp'])->name('calls.assign-follow-up')->middleware('can:front_desk.calls.followups.assign');
                Route::post('calls/{call}/complete-follow-up', [CallLogController::class, 'completeFollowUp'])->name('calls.complete-follow-up')->middleware('can:front_desk.calls.followups.complete');
                Route::post('calls/{call}/cancel-follow-up', [CallLogController::class, 'cancelFollowUp'])->name('calls.cancel-follow-up')->middleware('can:front_desk.calls.followups.complete');
                Route::post('calls/{call}/transfer', [CallLogController::class, 'transfer'])->name('calls.transfer')->middleware('can:front_desk.calls.transfer');
                // Backward-compatible Phase 18A route.
                Route::post('calls/{call}/follow-up-complete', [CallLogController::class, 'completeFollowUp'])->name('calls.follow-up-complete')->middleware('can:front_desk.calls.update');
            });

            // Courier logs
            Route::middleware('can:front_desk.couriers.view')->group(function () {
                Route::get('couriers', [CourierLogController::class, 'index'])->name('couriers.index');
                Route::get('couriers/create', [CourierLogController::class, 'create'])->name('couriers.create')->middleware('can:front_desk.couriers.create');
                Route::post('couriers', [CourierLogController::class, 'store'])->name('couriers.store')->middleware('can:front_desk.couriers.create');
                // Courier workflow board (Phase 18C) — static segment before {courier}
                Route::get('couriers/workflow', [CourierLogController::class, 'workflow'])->name('couriers.workflow')->middleware('can:front_desk.couriers.workflow.view');
                Route::get('couriers/{courier}', [CourierLogController::class, 'show'])->name('couriers.show');
                Route::get('couriers/{courier}/edit', [CourierLogController::class, 'edit'])->name('couriers.edit')->middleware('can:front_desk.couriers.update');
                Route::put('couriers/{courier}', [CourierLogController::class, 'update'])->name('couriers.update')->middleware('can:front_desk.couriers.update');
                // Dispatch / handover / delivery / return workflow (Phase 18C)
                Route::post('couriers/{courier}/dispatch', [CourierLogController::class, 'dispatchItem'])->name('couriers.dispatch')->middleware('can:front_desk.couriers.dispatch');
                Route::post('couriers/{courier}/handover', [CourierLogController::class, 'handover'])->name('couriers.handover')->middleware('can:front_desk.couriers.handover');
                Route::post('couriers/{courier}/mark-delivered', [CourierLogController::class, 'markDelivered'])->name('couriers.mark-delivered')->middleware('can:front_desk.couriers.deliver');
                Route::post('couriers/{courier}/mark-returned', [CourierLogController::class, 'markReturned'])->name('couriers.mark-returned')->middleware('can:front_desk.couriers.return');
            });

            // Shift handovers (Phase 18E)
            Route::middleware('can:front_desk.handovers.view')->group(function () {
                Route::get('handovers', [ShiftHandoverController::class, 'index'])->name('handovers.index');
                Route::get('handovers/create', [ShiftHandoverController::class, 'create'])->name('handovers.create')->middleware('can:front_desk.handovers.create');
                Route::post('handovers', [ShiftHandoverController::class, 'store'])->name('handovers.store')->middleware('can:front_desk.handovers.create');
                Route::get('handovers/{handover}', [ShiftHandoverController::class, 'show'])->name('handovers.show');
                Route::get('handovers/{handover}/edit', [ShiftHandoverController::class, 'edit'])->name('handovers.edit')->middleware('can:front_desk.handovers.update');
                Route::put('handovers/{handover}', [ShiftHandoverController::class, 'update'])->name('handovers.update')->middleware('can:front_desk.handovers.update');
                Route::post('handovers/{handover}/submit', [ShiftHandoverController::class, 'submit'])->name('handovers.submit')->middleware('can:front_desk.handovers.submit');
                Route::post('handovers/{handover}/accept', [ShiftHandoverController::class, 'accept'])->name('handovers.accept')->middleware('can:front_desk.handovers.accept');
                Route::post('handovers/{handover}/cancel', [ShiftHandoverController::class, 'cancel'])->name('handovers.cancel')->middleware('can:front_desk.handovers.cancel');
            });

            // Lost & found (Phase 18E)
            Route::middleware('can:front_desk.lost_found.view')->group(function () {
                Route::get('lost-found', [LostFoundController::class, 'index'])->name('lost-found.index');
                Route::get('lost-found/create', [LostFoundController::class, 'create'])->name('lost-found.create')->middleware('can:front_desk.lost_found.create');
                Route::post('lost-found', [LostFoundController::class, 'store'])->name('lost-found.store')->middleware('can:front_desk.lost_found.create');
                Route::get('lost-found/{lostFound}', [LostFoundController::class, 'show'])->name('lost-found.show');
                Route::get('lost-found/{lostFound}/edit', [LostFoundController::class, 'edit'])->name('lost-found.edit')->middleware('can:front_desk.lost_found.update');
                Route::put('lost-found/{lostFound}', [LostFoundController::class, 'update'])->name('lost-found.update')->middleware('can:front_desk.lost_found.update');
                Route::post('lost-found/{lostFound}/claim', [LostFoundController::class, 'claim'])->name('lost-found.claim')->middleware('can:front_desk.lost_found.claim');
                Route::post('lost-found/{lostFound}/release', [LostFoundController::class, 'release'])->name('lost-found.release')->middleware('can:front_desk.lost_found.release');
                Route::post('lost-found/{lostFound}/cancel', [LostFoundController::class, 'cancel'])->name('lost-found.cancel')->middleware('can:front_desk.lost_found.cancel');
            });

            // Incident / security desk (Phase 18E)
            Route::middleware('can:front_desk.incidents.view')->group(function () {
                Route::get('incidents', [IncidentLogController::class, 'index'])->name('incidents.index');
                Route::get('incidents/create', [IncidentLogController::class, 'create'])->name('incidents.create')->middleware('can:front_desk.incidents.create');
                Route::post('incidents', [IncidentLogController::class, 'store'])->name('incidents.store')->middleware('can:front_desk.incidents.create');
                Route::get('incidents/{incident}', [IncidentLogController::class, 'show'])->name('incidents.show');
                Route::get('incidents/{incident}/edit', [IncidentLogController::class, 'edit'])->name('incidents.edit')->middleware('can:front_desk.incidents.update');
                Route::put('incidents/{incident}', [IncidentLogController::class, 'update'])->name('incidents.update')->middleware('can:front_desk.incidents.update');
                Route::post('incidents/{incident}/assign', [IncidentLogController::class, 'assign'])->name('incidents.assign')->middleware('can:front_desk.incidents.assign');
                Route::post('incidents/{incident}/escalate', [IncidentLogController::class, 'escalate'])->name('incidents.escalate')->middleware('can:front_desk.incidents.escalate');
                Route::post('incidents/{incident}/resolve', [IncidentLogController::class, 'resolve'])->name('incidents.resolve')->middleware('can:front_desk.incidents.resolve');
                Route::post('incidents/{incident}/cancel', [IncidentLogController::class, 'cancel'])->name('incidents.cancel')->middleware('can:front_desk.incidents.cancel');
            });

            // Reports & CSV exports (Phase 18D)
            Route::middleware('can:front_desk.reports.view')->group(function () {
                Route::get('reports', [FrontDeskReportController::class, 'index'])->name('reports.index');
                Route::get('reports/data', [FrontDeskReportController::class, 'data'])->name('reports.data');
                Route::get('reports/export', [FrontDeskReportController::class, 'export'])->name('reports.export')->middleware('can:front_desk.reports.export');
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
        // Doctor / consultation workspace. The modern workflow-first dashboard
        // is canonical at /doctor; /doctor/dashboard remains as a compatibility
        // redirect for existing bookmarks.
        Route::get('/', [RoleDashboardController::class, 'doctor'])->name('dashboard');
        Route::get('dashboard', fn () => redirect()->route('doctor.dashboard'))->name('dashboard.redirect');
        Route::get('dashboard/legacy', [DoctorDashboardController::class, 'index'])->name('dashboard.legacy');

        Route::middleware(['module:patients', 'can:consultation.view_patient'])->prefix('patients')->name('patients.')->group(function () {
            Route::get('/', [PatientController::class, 'index'])->name('index');
            Route::get('{patient}', [PatientController::class, 'show'])->name('show');
        });

        Route::middleware(['module:appointments', 'can:appointments.view'])->prefix('appointments')->name('appointments.')->group(function () {
            Route::get('/', [AppointmentController::class, 'index'])->name('index');
            Route::get('calendar', [AppointmentController::class, 'calendar'])->name('calendar');
            Route::get('{appointment}', [AppointmentController::class, 'show'])->name('show');
        });

        Route::middleware(['module:visits', 'can:visits.view'])->prefix('visits')->name('visits.')->group(function () {
            Route::get('/', [VisitController::class, 'index'])->name('index');
            Route::get('{visit}', [VisitController::class, 'show'])->name('show');
        });

        Route::middleware(['module:consultation', 'can:consultations.view'])->prefix('consultations')->name('consultations.')->group(function () {
            Route::get('/', [ConsultationWorkspaceController::class, 'index'])->name('index');
            Route::get('{visit}', [ConsultationWorkspaceController::class, 'show'])->name('show');
            Route::get('{visit}/routes/{route}', [ConsultationWorkspaceController::class, 'show'])->name('routes.show');
            Route::get('{visit}/history', [ConsultationWorkspaceController::class, 'history'])->name('history')->middleware('can:consultation.preview');
        });

        Route::middleware('can:ward.view')->prefix('admissions')->name('admissions.')->group(function () {
            Route::get('/', [AdmissionController::class, 'index'])->name('index');
            Route::get('{admission}', [AdmissionController::class, 'show'])->name('show');
        });

        Route::middleware('can:prescriptions.view')->prefix('prescriptions')->name('prescriptions.')->group(function () {
            Route::get('/', [PrescriptionController::class, 'index'])->name('index');
            Route::get('{prescription}', [PrescriptionController::class, 'show'])->name('show');
            Route::get('{prescription}/print', [PrescriptionController::class, 'print'])->name('print');
        });

        Route::prefix('lab')->name('lab.')->middleware('module:investigations')->group(function () {
            Route::middleware('can:lab.requests.view')->prefix('requests')->name('requests.')->group(function () {
                Route::get('/', [LabRequestController::class, 'index'])->name('index');
                Route::get('{labRequest}', [LabRequestController::class, 'show'])->name('show');
            });
            Route::middleware('can:lab.results.view')->prefix('results')->name('results.')->group(function () {
                Route::get('/', [LabResultController::class, 'index'])->name('index');
                Route::get('requests/{labRequest}', [LabResultController::class, 'showRequest'])->name('show');
                Route::get('requests/{labRequest}/print', [LabResultController::class, 'printRequest'])->name('print-request');
                Route::get('{item}/view', [LabResultController::class, 'view'])->name('view');
                Route::get('{item}/print', [LabResultController::class, 'print'])->name('print');
            });
        });

        Route::middleware('can:procedure.view')->prefix('theatre')->name('theatre.')->group(function () {
            Route::get('/', [TheatreController::class, 'index'])->name('index');
            Route::get('board', [TheatreController::class, 'index'])->name('board');
            Route::get('calendar', [TheatreScheduleController::class, 'calendar'])->name('calendar');
            Route::get('procedures/{procedure}', [TheatreController::class, 'show'])->name('show');
        });

        Route::get('handoffs', [JourneyWorklistController::class, 'index'])->name('journey.worklist');
        Route::get('handoffs/refresh', [JourneyWorklistController::class, 'refresh'])->name('journey.worklist.refresh');

        Route::middleware('can:icd.view')->prefix('icd-codes')->name('icd-codes.')->group(function () {
            Route::get('/', [IcdCodeController::class, 'index'])->name('index');
            Route::get('search', [IcdCodeController::class, 'search'])->name('search');
        });

        Route::middleware('can:procedure_catalogue.view')->prefix('procedure-catalogue')->name('procedure-catalogue.')->group(function () {
            Route::get('/', [ProcedureCatalogueController::class, 'index'])->name('index');
            Route::get('{service}', [ProcedureCatalogueController::class, 'show'])->name('show');
        });

        Route::middleware(['module:investigations', 'can:investigation.catalogue.view'])->prefix('investigation-catalogue')->name('investigation-catalogue.')->group(function () {
            Route::get('/', [InvestigationCatalogueController::class, 'index'])->name('index');
            Route::get('{service}', [InvestigationCatalogueController::class, 'show'])->name('show');
        });

        Route::middleware(['module:medical-patterns', 'can:consultations.view'])->prefix('patterns')->name('patterns.')->group(function () {
            Route::get('/', [MedicalPatternController::class, 'index'])->name('index');
            Route::get('suggest', [MedicalPatternController::class, 'suggest'])->name('suggest');
            Route::get('{pattern}', [MedicalPatternController::class, 'show'])->name('show');
        });

        Route::middleware(['module:reports', 'can:reports.view'])->prefix('reports')->name('reports.')->group(function () {
            Route::get('visits', [ReportController::class, 'visits'])->name('visits');
            Route::get('consultation-stats', [ReportController::class, 'consultationStats'])->name('consultation-stats');
        });
    });
});
