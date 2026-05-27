<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmergencyCase;
use App\Models\ServiceCatalog;
use App\Services\EmergencyBillingService;
use Illuminate\Http\Request;

class EmergencyBillingController extends Controller
{
    public function __construct(private EmergencyBillingService $billing) {}

    public function storeService(Request $request, EmergencyCase $emergencyCase)
    {
        $data = $request->validate([
            'service_catalog_id' => ['required', 'exists:service_catalog,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $service = ServiceCatalog::findOrFail($data['service_catalog_id']);
        $this->billing->addService($emergencyCase, $service, (int) ($data['quantity'] ?? 1), $request->user(), $data['notes'] ?? null);

        return back()->with('success', 'Emergency billable service added.');
    }
}
