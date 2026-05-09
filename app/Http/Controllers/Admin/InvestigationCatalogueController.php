<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\InvestigationCriterion;
use App\Models\InvestigationHeader;
use App\Models\ServiceCatalog;
use App\Services\InvestigationCatalogueService;
use Illuminate\Http\Request;

class InvestigationCatalogueController extends Controller
{
    public function __construct(protected InvestigationCatalogueService $service) {}

    public function index(Request $request)
    {
        $services = $this->service->listServices($request->only(['search', 'department_id', 'active']));
        $departments = Department::query()
            ->whereIn('type', $this->service->investigationDepartmentTypes())
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return view('admin.investigation-catalogue.index', compact('services', 'departments'));
    }

    public function show(ServiceCatalog $service)
    {
        abort_unless($service->department && in_array(($service->department->type?->value ?? null), $this->service->investigationDepartmentTypes(), true),
            404, 'Service is not part of an investigation-type department.');

        $config = $this->service->getServiceConfig($service);
        return view('admin.investigation-catalogue.show', $config);
    }

    /*
    |--------------------------------------------------------------------------
    | Headers (AJAX)
    |--------------------------------------------------------------------------
    */

    public function storeHeader(Request $request, ServiceCatalog $service)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        $header = $this->service->addHeader($service, $data);

        return response()->json(['success' => true, 'header' => $header]);
    }

    public function updateHeader(Request $request, InvestigationHeader $header)
    {
        $data = $request->validate([
            'name'        => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $header = $this->service->updateHeader($header, $data);
        return response()->json(['success' => true, 'header' => $header]);
    }

    public function destroyHeader(InvestigationHeader $header)
    {
        $this->service->deleteHeader($header);
        return response()->json(['success' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | Criteria (AJAX)
    |--------------------------------------------------------------------------
    */

    public function storeCriterion(Request $request, ServiceCatalog $service)
    {
        $data = $request->validate([
            'header_id'       => ['nullable', 'integer', 'exists:investigation_headers,id'],
            'name'            => ['required', 'string', 'max:191'],
            'unit'            => ['nullable', 'string', 'max:50'],
            'reference_range' => ['nullable', 'string', 'max:191'],
            'default_value'   => ['nullable', 'string', 'max:191'],
            'input_type'      => ['nullable', 'in:text,number,select,textarea,boolean'],
            'options'         => ['nullable', 'array'],
            'options.*'       => ['string', 'max:100'],
            'sort_order'      => ['nullable', 'integer', 'min:0'],
            'is_required'     => ['nullable', 'boolean'],
        ]);

        $criterion = $this->service->addCriterion($service, $data);
        return response()->json(['success' => true, 'criterion' => $criterion]);
    }

    public function updateCriterion(Request $request, InvestigationCriterion $criterion)
    {
        $data = $request->validate([
            'header_id'       => ['nullable', 'integer', 'exists:investigation_headers,id'],
            'name'            => ['nullable', 'string', 'max:191'],
            'unit'            => ['nullable', 'string', 'max:50'],
            'reference_range' => ['nullable', 'string', 'max:191'],
            'default_value'   => ['nullable', 'string', 'max:191'],
            'input_type'      => ['nullable', 'in:text,number,select,textarea,boolean'],
            'options'         => ['nullable', 'array'],
            'options.*'       => ['string', 'max:100'],
            'sort_order'      => ['nullable', 'integer', 'min:0'],
            'is_required'     => ['nullable', 'boolean'],
            'is_active'       => ['nullable', 'boolean'],
        ]);

        $criterion = $this->service->updateCriterion($criterion, $data);
        return response()->json(['success' => true, 'criterion' => $criterion]);
    }

    public function destroyCriterion(InvestigationCriterion $criterion)
    {
        $this->service->deleteCriterion($criterion);
        return response()->json(['success' => true]);
    }
}
