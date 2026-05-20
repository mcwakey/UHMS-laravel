<?php

namespace App\Services;

use App\Enums\ProductType;
use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductService
{
    /**
     * Return active products linked to the given department, optionally
     * filtered by one or more product types. Departments only ever
     * **view** products linked to them — they never create new ones.
     *
     * @param  Department  $department
     * @param  array<int, ProductType|string>|null  $types
     */
    public function getProductsForDepartment(Department $department, ?array $types = null)
    {
        return $this->queryProductsForDepartment($department, $types)->get();
    }

    public function queryProductsForDepartment(Department $department, ?array $types = null): Builder
    {
        $typeValues = collect($types ?? [])
            ->map(fn ($t) => $t instanceof ProductType ? $t->value : (string) $t)
            ->filter()
            ->all();

        return Product::query()
            ->with(['departments:id,name,type'])
            ->where('is_active', true)
            ->forDepartment($department->id)
            ->when(! empty($typeValues), fn ($q) => $q->whereIn('product_type', $typeValues))
            ->orderBy('name');
    }

    public function queryProductsForDepartmentTypes(array $departmentTypes, ?array $types = null): Builder
    {
        $departmentTypeValues = collect($departmentTypes)
            ->map(fn ($t) => $t instanceof DepartmentType ? $t->value : (string) $t)
            ->filter()
            ->values()
            ->all();

        $typeValues = collect($types ?? [])
            ->map(fn ($t) => $t instanceof ProductType ? $t->value : (string) $t)
            ->filter()
            ->values()
            ->all();

        return Product::query()
            ->with(['departments:id,name,type'])
            ->where('is_active', true)
            ->whereHas('departments', function ($q) use ($departmentTypeValues) {
                $q->whereIn('departments.type', $departmentTypeValues)
                  ->where('product_department.is_active', true);
            })
            ->when(! empty($typeValues), fn ($q) => $q->whereIn('product_type', $typeValues))
            ->orderBy('name');
    }

    public function listProducts(array $filters = [])
    {
        $type = $filters['product_type'] ?? ($filters['type'] ?? null);
        $status = $filters['status'] ?? null;

        return Product::query()
            ->with('departments')
            ->withCount([
                'prices as insurance_type_prices_count' => fn ($q) => $q->whereNull('insurance_provider_id')->where('is_active', true),
                'prices as provider_prices_count' => fn ($q) => $q->whereNotNull('insurance_provider_id')->where('is_active', true),
            ])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($qq) =>
                $qq->where('name', 'like', "%{$s}%")
                   ->orWhere('code', 'like', "%{$s}%")))
            ->when($type, fn ($q, $t) => $q->where('product_type', $t))
            ->when(($filters['department_id'] ?? null), fn ($q, $d) => $q->forDepartment((int) $d))
            ->when($status !== null && $status !== '', fn ($q) => $q->where('is_active', $status === 'active'))
            ->when(array_key_exists('is_active', $filters) && $filters['is_active'] !== '', fn ($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->when(array_key_exists('is_billable', $filters) && $filters['is_billable'] !== '', fn ($q) => $q->where('is_billable', (bool) $filters['is_billable']))
            ->when(($filters['has_insurance_prices'] ?? '') !== '', function ($q) use ($filters) {
                $hasPrices = (bool) $filters['has_insurance_prices'];
                $hasPrices
                    ? $q->whereHas('prices', fn ($pq) => $pq->where('is_active', true))
                    : $q->whereDoesntHave('prices', fn ($pq) => $pq->where('is_active', true));
            })
            ->when($filters['supplier_id'] ?? null, function ($q, $supplierId) {
                $q->whereHas('purchaseOrderItems.purchaseOrder', fn ($pq) => $pq->where('supplier_id', $supplierId));
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();
    }

    public function create(array $data): Product
    {
        $this->validatePayload($data);

        return DB::transaction(function () use ($data) {
            $product = Product::create([
                'name'          => $data['name'],
                'code'          => $data['code'] ?? null,
                'product_type'  => $data['product_type'],
                'unit'          => $data['unit'] ?? 'unit',
                'description'   => $data['description'] ?? null,
                'reorder_level' => $data['reorder_level'] ?? null,
                'default_cost'  => $data['default_cost'] ?? null,
                'base_price'    => $data['base_price'] ?? null,
                'is_billable'   => (bool) ($data['is_billable'] ?? false),
                'is_active'     => (bool) ($data['is_active'] ?? true),
                'created_by'    => Auth::id(),
            ]);

            $this->syncDepartments($product, $data['department_ids'] ?? []);
            return $product;
        });
    }

    public function update(Product $product, array $data): Product
    {
        $this->validatePayload($data, $product->id);

        return DB::transaction(function () use ($product, $data) {
            $product->fill(array_intersect_key($data, array_flip([
                'name', 'code', 'product_type', 'unit', 'description',
                'reorder_level', 'default_cost', 'base_price', 'is_billable', 'is_active',
            ])))->save();

            $this->syncDepartments($product, (array) ($data['department_ids'] ?? []));
            return $product;
        });
    }

    public function syncDepartments(Product $product, array $departmentIds): void
    {
        $valid = Department::whereIn('id', array_filter($departmentIds))->pluck('id')->all();
        $payload = [];
        foreach ($valid as $id) {
            $payload[$id] = ['is_active' => true];
        }
        $product->departments()->sync($payload);
    }

    public function toggle(Product $product): Product
    {
        $product->is_active = ! $product->is_active;
        $product->save();
        return $product;
    }

    protected function validatePayload(array $data, ?int $ignoreId = null): void
    {
        if (empty($data['name'])) throw new InvalidArgumentException('name is required.');
        $type = $data['product_type'] ?? null;
        if ($type instanceof ProductType) $type = $type->value;
        if (! $type || ! ProductType::tryFrom($type)) {
            throw new InvalidArgumentException('product_type is invalid.');
        }
        if (! empty($data['code'])) {
            $exists = Product::where('code', $data['code'])
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists();
            if ($exists) throw new InvalidArgumentException('Product code must be unique.');
        }
    }
}
