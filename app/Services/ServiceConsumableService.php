<?php

namespace App\Services;

use App\Enums\DepartmentType;
use App\Models\Product;
use App\Models\ServiceCatalog;
use App\Models\ServiceConsumable;
use InvalidArgumentException;

class ServiceConsumableService
{
    /**
     * Create or update a default consumable for a service.
     */
    public function upsert(ServiceCatalog $service, array $data): ServiceConsumable
    {
        $productId = (int) ($data['product_id'] ?? 0);
        $qty       = (float) ($data['default_quantity'] ?? 1);
        if ($productId <= 0) throw new InvalidArgumentException('product_id is required.');
        if ($qty <= 0)       throw new InvalidArgumentException('default_quantity must be > 0.');

        $product = Product::findOrFail($productId);
        $this->assertProductAvailableToServiceDepartment($product, $service);

        return ServiceConsumable::updateOrCreate(
            ['service_id' => $service->id, 'product_id' => $productId],
            [
                'default_quantity' => $qty,
                'is_required'      => (bool) ($data['is_required'] ?? false),
                'notes'            => $data['notes'] ?? null,
            ],
        );
    }

    public function delete(ServiceCatalog $service, int $productId): void
    {
        ServiceConsumable::where('service_id', $service->id)
            ->where('product_id', $productId)
            ->delete();
    }

    /**
     * Products available to be added as consumables for this service:
     * - active
     * - linked to the service's department (or any procedure/investigation dept if dept not set)
     */
    public function availableProductsFor(ServiceCatalog $service)
    {
        $query = Product::query()->where('is_active', true);

        if ($service->department_id) {
            $query->forDepartment($service->department_id);
        }

        return $query->orderBy('name')->get();
    }

    protected function assertProductAvailableToServiceDepartment(Product $product, ServiceCatalog $service): void
    {
        if (! $service->department_id) return; // permissive when service has no department

        $linked = $product->departments()
            ->where('departments.id', $service->department_id)
            ->where('product_department.is_active', true)
            ->exists();

        if (! $linked) {
            throw new InvalidArgumentException(sprintf(
                'Product "%s" is not linked to this service\'s department.',
                $product->name,
            ));
        }
    }
}
