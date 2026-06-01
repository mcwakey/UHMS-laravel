<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRendering;
use App\Services\ServiceRenderingReportService;
use Illuminate\Http\Request;

class ServiceRenderingReportController extends Controller
{
    public function __construct(private ServiceRenderingReportService $reports) {}

    public function index(Request $request)
    {
        $filters = $request->only([
            'status',
            'department_id',
            'rendered_by',
            'payment_status',
            'source',
            'date_from',
            'date_to',
        ]);

        return view('service-renderings.reports', [
            'filters' => $filters,
            'summary' => $this->reports->summary($filters, $request->user()),
            'byDepartment' => $this->reports->byDepartment($filters, $request->user()),
            'byStaff' => $this->reports->byStaff($filters, $request->user()),
            'statuses' => ServiceRendering::statuses(),
        ]);
    }
}
