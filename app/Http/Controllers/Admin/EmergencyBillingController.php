<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EmergencyCase;
use App\Models\ServiceCatalog;
use App\Services\EmergencyBillingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmergencyBillingController extends Controller
{
    public function __construct(private EmergencyBillingService $billing) {}

    public function storeService(Request $request, EmergencyCase $emergencyCase)
    {
        $emergencyDepartmentId = $this->emergencyDepartmentId($emergencyCase);

        if (! $emergencyDepartmentId) {
            return back()->withErrors(['service_catalog_id' => 'No emergency department is linked to this case.']);
        }

        $data = $request->validate([
            'service_catalog_id' => [
                'required',
                Rule::exists('service_catalog', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->where('department_id', $emergencyDepartmentId)),
            ],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $service = ServiceCatalog::findOrFail($data['service_catalog_id']);
        $this->billing->addService($emergencyCase, $service, (int) ($data['quantity'] ?? 1), $request->user(), $data['notes'] ?? null);

        return back()->with('success', __('messages.emergency.billing_service_added'));
    }

    private function emergencyDepartmentId(EmergencyCase $case): ?int
    {
        $case->loadMissing(['visit', 'activeEmergencySession']);

        return $case->visit?->current_department_id
            ?: $case->activeEmergencySession?->department_id
            ?: Department::query()
                ->whereIn('code', ['ER', 'EMR'])
                ->orWhere('name', 'like', '%Emergency%')
                ->value('id');
    }
}
