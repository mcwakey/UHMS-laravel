<?php

namespace App\Http\Controllers\Admin\FrontDesk;

use App\Enums\FrontDesk\CallCategory;
use App\Enums\FrontDesk\CallDirection;
use App\Enums\FrontDesk\CallFollowUpStatus;
use App\Enums\FrontDesk\CallOutcome;
use App\Enums\FrontDesk\CourierDirection;
use App\Enums\FrontDesk\CourierHandoverStatus;
use App\Enums\FrontDesk\CourierStatus;
use App\Enums\FrontDesk\CourierType;
use App\Enums\FrontDesk\VisitorContext;
use App\Enums\FrontDesk\VisitorStatus;
use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Ward;
use App\Services\ActivityLogService;
use App\Services\FrontDesk\FrontDeskReportExportService;
use App\Services\FrontDesk\FrontDeskReportService;
use Illuminate\Http\Request;

class FrontDeskReportController extends Controller
{
    public function __construct(
        private FrontDeskReportService $reports,
        private FrontDeskReportExportService $exports,
        private ActivityLogService $logger,
    ) {}

    public function index(Request $request)
    {
        $filters = $this->validated($request);

        return view('admin.front-desk.reports.index', array_merge([
            'payload' => $this->reports->dashboardPayload($filters),
            'filters' => $this->reports->normaliseFilters($filters),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'wards' => Ward::orderBy('name')->get(['id', 'name']),
        ], $this->filterOptions()));
    }

    /** JSON payload (same data as the page) for async refreshes. */
    public function data(Request $request)
    {
        return response()->json($this->reports->dashboardPayload($this->validated($request)));
    }

    public function export(Request $request)
    {
        $type = (string) $request->query('type', 'summary');
        abort_unless(in_array($type, FrontDeskReportService::EXPORT_TYPES, true), 404);

        $filters = $this->reports->normaliseFilters($this->validated($request));
        $dataset = $this->reports->exportDataset($type, $filters);

        $this->logger->log(LogModule::FRONT_DESK, 'FRONT_DESK_REPORT_EXPORTED', [
            'metadata' => ['export_type' => $type, 'filters' => $filters],
            'causer' => $request->user(),
        ], null, 'Front desk report exported');

        return $this->exports->csv("front-desk-{$type}-report.csv", $dataset, $filters, $request->user());
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'department_id' => ['nullable', 'integer'],
            'ward_id' => ['nullable', 'integer'],
            'visitor_context' => ['nullable', 'string'],
            'visitor_status' => ['nullable', 'string'],
            'call_direction' => ['nullable', 'string'],
            'call_category' => ['nullable', 'string'],
            'call_outcome' => ['nullable', 'string'],
            'follow_up_status' => ['nullable', 'string'],
            'courier_direction' => ['nullable', 'string'],
            'courier_type' => ['nullable', 'string'],
            'courier_status' => ['nullable', 'string'],
            'handover_status' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filterOptions(): array
    {
        return [
            'visitorContexts' => VisitorContext::cases(),
            'visitorStatuses' => VisitorStatus::cases(),
            'callDirections' => CallDirection::cases(),
            'callCategories' => CallCategory::cases(),
            'callOutcomes' => CallOutcome::cases(),
            'followUpStatuses' => CallFollowUpStatus::cases(),
            'courierDirections' => CourierDirection::cases(),
            'courierTypes' => CourierType::cases(),
            'courierStatuses' => CourierStatus::cases(),
            'handoverStatuses' => CourierHandoverStatus::cases(),
            'exportTypes' => FrontDeskReportService::EXPORT_TYPES,
        ];
    }
}
