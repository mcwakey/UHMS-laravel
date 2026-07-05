<?php

namespace App\Http\Controllers\Admin\Maternity;

use App\Enums\DeliveryMode;
use App\Enums\DeliveryOutcome;
use App\Enums\LogModule;
use App\Enums\MaternityRiskLevel;
use App\Enums\NewbornOutcome;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\Maternity\MaternityReportExportService;
use App\Services\Maternity\MaternityReportService;
use Illuminate\Http\Request;

class MaternityReportController extends Controller
{
    private const REPORTS = ['antenatal', 'labor', 'deliveries', 'newborns', 'postnatal', 'risk'];

    public function __construct(
        private MaternityReportService $reports,
        private MaternityReportExportService $exports,
        private ActivityLogService $logger,
    ) {}

    public function index(Request $request)
    {
        return view('maternity.reports.index', [
            'overview' => $this->reports->index($request->query()),
            'reports' => self::REPORTS,
        ]);
    }

    public function antenatal(Request $request) { return $this->show('antenatal', $request); }
    public function labor(Request $request) { return $this->show('labor', $request); }
    public function deliveries(Request $request) { return $this->show('deliveries', $request); }
    public function newborns(Request $request) { return $this->show('newborns', $request); }
    public function postnatal(Request $request) { return $this->show('postnatal', $request); }
    public function risk(Request $request) { return $this->show('risk', $request); }

    public function export(Request $request)
    {
        $report = (string) $request->query('report', 'antenatal');
        abort_unless(in_array($report, self::REPORTS, true), 404);

        $data = $this->reportData($report, $request->query(), true);

        $this->logger->log(LogModule::MATERNITY, 'MATERNITY_REPORT_EXPORTED', [
            'metadata' => [
                'report' => $report,
                'filters' => $this->reports->normaliseFilters($request->query()),
            ],
            'causer' => $request->user(),
        ], null, 'Maternity report exported');

        return $this->exports->csv('maternity-'.$report.'-report.csv', $data, $this->reports->normaliseFilters($request->query()), $request->user());
    }

    private function show(string $report, Request $request)
    {
        return view('maternity.reports.show', [
            'reportKey' => $report,
            'report' => $this->reportData($report, $request->query()),
            'filters' => $this->reports->normaliseFilters($request->query()),
            'departments' => Department::active()->orderBy('name')->get(),
            'staff' => User::query()->orderBy('first_name')->orderBy('last_name')->limit(200)->get(),
            'riskLevels' => MaternityRiskLevel::cases(),
            'deliveryModes' => DeliveryMode::cases(),
            'deliveryOutcomes' => DeliveryOutcome::cases(),
            'newbornOutcomes' => NewbornOutcome::cases(),
        ]);
    }

    private function reportData(string $report, array $filters, bool $export = false): array
    {
        return match ($report) {
            'antenatal' => $this->reports->antenatal($filters, $export),
            'labor' => $this->reports->labor($filters, $export),
            'deliveries' => $this->reports->deliveries($filters, $export),
            'newborns' => $this->reports->newborns($filters, $export),
            'postnatal' => $this->reports->postnatal($filters, $export),
            'risk' => $this->reports->risk($filters, $export),
        };
    }
}
