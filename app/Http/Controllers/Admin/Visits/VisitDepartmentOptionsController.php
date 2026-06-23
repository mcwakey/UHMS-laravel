<?php

namespace App\Http\Controllers\Admin\Visits;

use App\Enums\DepartmentType;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ServiceCatalog;
use App\Services\VisitService;

class VisitDepartmentOptionsController extends Controller
{
    public function __construct(
        private readonly VisitService $visitService,
    ) {}

    public function show(Department $department)
    {
        $services = $this->visitService
            ->getServicesForDepartment($department->id)
            ->map(fn (ServiceCatalog $service) => $this->formatService($service, $department));

        $doctors = $this->visitService
            ->getDoctorsForDepartment($department->id)
            ->map(fn ($doctor) => [
                'id' => $doctor->id,
                'name' => 'Dr. '.$doctor->full_name,
                'specialties' => $doctor->specialties->pluck('name')->values()->all(),
            ])
            ->values();

        return response()->json([
            'services' => $services->values(),
            'doctors' => $doctors,
        ]);
    }

    private function formatService(ServiceCatalog $service, Department $department): array
    {
        $typePrices = [];
        $providerPrices = [];

        foreach ($service->prices as $price) {
            if ($price->insurance_provider_id === null) {
                $typePrices[$price->insurance_type] = (float) $price->price;
            } else {
                $providerPrices[$price->insurance_provider_id][$price->insurance_type] = (float) $price->price;
            }
        }

        $departmentType = $department->type instanceof DepartmentType
            ? $department->type->value
            : (string) $department->type;

        return [
            'id' => $service->id,
            'name' => $service->name,
            'code' => $service->code,
            'category' => $service->category,
            'price' => (float) $service->price,
            'base_price' => (float) $service->price,
            'formatted_price' => $service->formatted_price,
            'department_id' => $department->id,
            'department_type' => $departmentType,
            'type_prices' => $typePrices,
            'provider_prices' => $providerPrices,
        ];
    }
}
