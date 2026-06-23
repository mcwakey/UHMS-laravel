<?php

namespace App\Http\Controllers\Admin\Reporting;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Services\ReportExportService;
use App\Services\ReportFilterService;
use App\Services\ReportPrintService;
use App\Services\OperationalReportService;
use Illuminate\Http\Request;

class OperationalReportController extends Controller
{
    public function __construct(
        private OperationalReportService $reports,
        private ReportFilterService $filters,
        private ReportExportService $exports,
        private ReportPrintService $prints,
    ) {}

    public function dashboard(Request $request)
    {
        return view('reports.dashboard', $this->reports->dashboard($request->only(['date_from', 'date_to'])));
    }

    public function show(Request $request, string $report)
    {
        $filters = $this->filters->operational($request);

        if ($request->query('export') === 'csv') {
            abort_unless($request->user()->can('reports.export'), 403);

            $data = $this->reports->build($report, $filters, true, $request->user());
            $filename = $report.'-report-'.now()->format('Ymd-His').'.csv';

            return $this->exports->csv($filename, $data, $data['filters'], $request->user());
        }

        if ($request->query('print')) {
            abort_unless($request->user()->can('reports.print'), 403);

            $data = $this->reports->build($report, $filters, true, $request->user());

            return view('reports.operational-print', array_merge($data, [
                'printMeta' => $this->prints->metadata($data, $data['filters'], $request->user()),
            ]));
        }

        return view('reports.operational', array_merge(
            $this->reports->build($report, $filters, false, $request->user()),
            [
                'catalogue' => $this->reports->catalogue(),
                'departments' => Department::orderBy('name')->get(['id', 'name']),
                'users' => User::orderBy('first_name')->orderBy('last_name')->limit(200)->get(['id', 'first_name', 'last_name']),
            ]
        ));
    }
}
