<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmergencyCase;
use App\Services\EmergencyProcedureService;
use Illuminate\Http\Request;

class EmergencyProcedureController extends Controller
{
    public function __construct(private EmergencyProcedureService $procedures) {}

    public function store(Request $request, EmergencyCase $emergencyCase)
    {
        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'service_catalog_id' => ['required', 'exists:service_catalog,id'],
            'priority' => ['required', 'in:routine,urgent,emergency'],
            'indication' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'preferred_datetime' => ['nullable', 'date'],
        ]);

        $this->procedures->request($emergencyCase, $data, $request->user());

        return back()->with('success', 'Emergency procedure requested.');
    }
}
