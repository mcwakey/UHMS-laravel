<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmergencyCase;
use App\Services\EmergencyProcedureService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmergencyProcedureController extends Controller
{
    public function __construct(private EmergencyProcedureService $procedures) {}

    public function store(Request $request, EmergencyCase $emergencyCase)
    {
        // Accept a single service_catalog_id or an array (multi-select) interchangeably.
        $request->merge([
            'service_catalog_id' => array_values(array_filter(
                \Illuminate\Support\Arr::wrap($request->input('service_catalog_id')),
                fn ($v) => $v !== null && $v !== '',
            )),
        ]);

        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'service_catalog_id' => ['required', 'array', 'min:1'],
            'service_catalog_id.*' => [
                Rule::exists('service_catalog', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->where('department_id', $request->input('department_id'))),
            ],
            'priority' => ['required', 'in:routine,urgent,emergency'],
            'indication' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'preferred_datetime' => ['nullable', 'date'],
        ]);

        // Each selected procedure is its own request.
        foreach (array_unique($data['service_catalog_id']) as $serviceCatalogId) {
            $this->procedures->request(
                $emergencyCase,
                array_merge($data, ['service_catalog_id' => $serviceCatalogId]),
                $request->user(),
            );
        }

        return back()->with('success', __('messages.emergency.procedure_requested'));
    }
}
