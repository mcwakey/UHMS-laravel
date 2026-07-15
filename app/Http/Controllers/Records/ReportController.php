<?php

namespace App\Http\Controllers\Records;

use App\Http\Controllers\Controller;
use App\Services\ModuleService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request, ModuleService $modules)
    {
        $reports = collect([
            ['route' => 'records.reports.patients', 'icon' => 'ti-users', 'key' => 'patients'],
            ['route' => 'records.reports.attendance', 'icon' => 'ti-clipboard-list', 'key' => 'attendance'],
            ['route' => 'records.reports.visits', 'icon' => 'ti-calendar-check', 'key' => 'visits'],
            ['route' => 'records.front-desk.reports.index', 'icon' => 'ti-building-reception', 'key' => 'front_desk', 'permissions' => ['front_desk.view', 'front_desk.reports.view']],
            ['route' => 'records.service-renderings.reports', 'icon' => 'ti-report-medical', 'key' => 'service_rendering', 'permissions' => ['service_rendering.view', 'service_rendering.reports']],
            ['route' => 'records.reports.claims', 'icon' => 'ti-file-dollar', 'key' => 'claims', 'module' => 'claims'],
            ['route' => 'records.reports.insurance-claims', 'icon' => 'ti-shield-check', 'key' => 'insurance_claims', 'module' => 'insurance'],
            ['route' => 'records.reports.daily-collection', 'icon' => 'ti-cash', 'key' => 'daily_collection'],
        ])->filter(function (array $report) use ($request, $modules): bool {
            return collect($report['permissions'] ?? [])->every(fn (string $permission) => $request->user()->can($permission))
                && (! isset($report['module']) || $modules->enabled($report['module']));
        })->values();

        return view('records.reports.index', compact('reports'));
    }
}
