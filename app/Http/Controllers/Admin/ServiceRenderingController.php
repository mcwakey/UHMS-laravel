<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ServiceCatalog;
use App\Models\ServiceRendering;
use App\Models\User;
use App\Services\ServiceRenderingQueryService;
use App\Services\ServiceRenderingReportService;
use App\Services\ServiceRenderingService;
use Illuminate\Http\Request;

class ServiceRenderingController extends Controller
{
    public function __construct(
        private ServiceRenderingQueryService $queries,
        private ServiceRenderingReportService $reports,
        private ServiceRenderingService $service,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only([
            'search',
            'status',
            'department_id',
            'service_id',
            'rendered_by',
            'payment_status',
            'source',
            'date_from',
            'date_to',
        ]);

        return view('service-renderings.index', [
            'renderings' => $this->queries->paginate($filters, $request->user()),
            'summary' => $this->reports->summary($filters, $request->user()),
            'filters' => $filters,
            'statuses' => ServiceRendering::statuses(),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'services' => ServiceCatalog::query()->orderBy('name')->get(['id', 'name']),
            'staff' => User::query()->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function show(Request $request, ServiceRendering $serviceRendering)
    {
        $serviceRendering->load($this->service->defaultRelations());
        $this->service->assertCan($request->user(), $serviceRendering, 'service_rendering.view');

        return view('service-renderings.show', [
            'rendering' => $serviceRendering,
        ]);
    }
}
