<?php

namespace App\Services;

use App\Enums\ProductType;
use App\Models\Department;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductService
{
    public function listProducts(array $filters = [])
    {
        return Product::query()
            ->with('departments')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($qq) =>
                $qq->where('name', 'like', "%{$s}%")
                   ->orWhere('code', 'like', "%{$s}%")))
            ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('product_type', $t))
            ->when(($filters['department_id'] ?? null), fn ($q, $d) => $q->forDepartment((int) $d))
            ->when(array_key_exists('is_active', $filters), fn ($q) => $q->where('is_active', (bool) $filters['is_active']))
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
                'reorder_level', 'default_cost', 'is_active',
            ])))->save();

            if (array_key_exists('department_ids', $data)) {
                $this->syncDepartments($product, (array) $data['department_ids']);
            }
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
