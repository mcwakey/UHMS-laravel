<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\ConsultationSpecialtyBillingApplication;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\Department;
use App\Models\User;
use App\Services\Consultation\Specialty\ConsultationSpecialtyAnalyticsService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultationSpecialtyReportController extends Controller
{
    public function __construct(
        private readonly ConsultationSpecialtyAnalyticsService $analytics,
        private readonly ConsultationSpecialtyReportExportService $exports,
    ) {}

    public function index(Request $request)
    {
        $filters = $this->filters($request);

        return view('admin.reports.consultation-specialties.index', [
            'payload' => $this->analytics->dashboardPayload($filters),
            'profiles' => ConsultationSpecialtyProfile::query()
                ->where('code', '!=', ConsultationSpecialtyProfile::GENERAL_MEDICINE)
                ->ordered()
                ->get(['id', 'code', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'doctors' => User::query()->orderBy('first_name')->orderBy('last_name')->limit(250)->get(['id', 'first_name', 'last_name']),
            'billingStatuses' => [
                ConsultationSpecialtyBillingApplication::STATUS_PREVIEWED,
                ConsultationSpecialtyBillingApplication::STATUS_SUGGESTED,
                ConsultationSpecialtyBillingApplication::STATUS_APPLIED,
                ConsultationSpecialtyBillingApplication::STATUS_SKIPPED_DUPLICATE,
                ConsultationSpecialtyBillingApplication::STATUS_FAILED,
                ConsultationSpecialtyBillingApplication::STATUS_UNSUPPORTED,
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->analytics->dashboardPayload($this->filters($request)));
    }

    public function export(Request $request)
    {
        abort_unless($request->user()?->can('reports.export'), 403);

        return $this->exports->csv($this->filters($request), (string) $request->query('dataset', 'all'));
    }

    private function filters(Request $request): array
    {
        return $request->only([
            'date_from',
            'date_to',
            'specialty_profile_id',
            'department_id',
            'doctor_id',
            'billing_status',
        ]);
    }
}
