<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InsuranceType;
use App\Http\Controllers\Controller;
use App\Models\InsuranceProvider;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Manages insurance-type and provider-specific pricing for Products.
 *
 * Routes map to this controller:
 *   POST   admin/products/{product}/pricing/type                  storeTypePrice
 *   PUT    admin/products/{product}/pricing/type/{price}          updateTypePrice
 *   DELETE admin/products/{product}/pricing/type/{price}          destroyTypePrice
 *   POST   admin/products/{product}/pricing/provider              storeProviderPrice
 *   PUT    admin/products/{product}/pricing/provider/{price}      updateProviderPrice
 *   DELETE admin/products/{product}/pricing/provider/{price}      destroyProviderPrice
 *
 *   PATCH  admin/products/{product}/pricing/base                  updateBasePrice
 */
class ProductPricingController extends Controller
{
    /* ──────────────────────────────────────────────────────────────────── */
    /* Bulk upsert — mirrors services.prices.store                          */
    /* Accepts:                                                             */
    /*   type_prices[<insurance_type>] = price                              */
    /*   provider_prices[idx][insurance_type|provider_id|price]             */
    /* ──────────────────────────────────────────────────────────────────── */

    public function storePrices(Request $request, Product $product): RedirectResponse
    {
        // Drop incomplete provider rows BEFORE validation so an unfinished
        // "Add Provider Override" click never blocks the save.
        $providerPrices = collect($request->input('provider_prices', []))
            ->filter(function ($row) {
                return is_array($row)
                    && ! empty($row['insurance_type'])
                    && ! empty($row['insurance_provider_id'])
                    && isset($row['price'])
                    && $row['price'] !== '';
            })
            ->values()
            ->all();

        $request->merge(['provider_prices' => $providerPrices]);

        $validTypes = array_column(InsuranceType::cases(), 'value');

        $data = $request->validate([
            'type_prices'                              => ['nullable', 'array'],
            'type_prices.*'                            => ['nullable', 'numeric', 'min:0'],
            'provider_prices'                          => ['nullable', 'array'],
            'provider_prices.*.insurance_type'         => ['required', 'string', Rule::in($validTypes)],
            'provider_prices.*.insurance_provider_id'  => ['required', 'integer', 'exists:insurance_providers,id'],
            'provider_prices.*.price'                  => ['required', 'numeric', 'min:0'],
        ]);

        // ── Upsert insurance-type defaults (provider_id = NULL) ───────────
        foreach (($data['type_prices'] ?? []) as $type => $price) {
            if (! InsuranceType::tryFrom($type)) {
                continue;
            }
            if ($price === null || $price === '') {
                // Blank input → remove existing type-default row
                ProductPrice::where('product_id', $product->id)
                    ->where('insurance_type', $type)
                    ->whereNull('insurance_provider_id')
                    ->delete();
                continue;
            }
            ProductPrice::updateOrCreate(
                [
                    'product_id'            => $product->id,
                    'insurance_type'        => $type,
                    'insurance_provider_id' => null,
                ],
                ['price' => $price, 'is_active' => true]
            );
        }

        // ── Upsert provider-specific overrides ────────────────────────────
        foreach (($data['provider_prices'] ?? []) as $row) {
            ProductPrice::updateOrCreate(
                [
                    'product_id'            => $product->id,
                    'insurance_type'        => $row['insurance_type'],
                    'insurance_provider_id' => $row['insurance_provider_id'],
                ],
                ['price' => $row['price'], 'is_active' => true]
            );
        }

        return back()->with('success', __('messages.product_pricing.prices_updated', ['name' => $product->name]));
    }

    /**
     * Delete a single price row (used by services-style "trash" button).
     */
    public function deletePrice(Product $product, ProductPrice $price): RedirectResponse
    {
        $this->authorizePrice($product, $price);
        $price->delete();
        return back()->with('success', __('messages.product_pricing.price_removed'));
    }

    /* ──────────────────────────────────────────────────────────────────── */
    /* Base price                                                           */
    /* ──────────────────────────────────────────────────────────────────── */

    public function updateBasePrice(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'base_price'  => 'nullable|numeric|min:0',
            'is_billable' => 'nullable|boolean',
        ]);

        $product->update([
            'base_price'  => $data['base_price'] ?? null,
            'is_billable' => (bool) ($data['is_billable'] ?? false),
        ]);

        return back()->with('success', __('messages.product_pricing.base_updated'));
    }

    /* ──────────────────────────────────────────────────────────────────── */
    /* Insurance-type prices  (no specific provider)                       */
    /* ──────────────────────────────────────────────────────────────────── */

    public function storeTypePrice(Request $request, Product $product): RedirectResponse
    {
        $validTypes = array_column(InsuranceType::cases(), 'value');

        $data = $request->validate([
            'insurance_type' => ['required', Rule::in($validTypes)],
            'price'          => 'required|numeric|min:0',
            'is_active'      => 'nullable|boolean',
        ]);

        // Prevent duplicate type-default (provider_id IS NULL) for this product.
        $exists = ProductPrice::where('product_id', $product->id)
            ->where('insurance_type', $data['insurance_type'])
            ->whereNull('insurance_provider_id')
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'insurance_type' => 'A default price for this insurance type already exists. Edit the existing entry instead.',
            ])->withInput();
        }

        $product->prices()->create([
            'insurance_type'        => $data['insurance_type'],
            'insurance_provider_id' => null,
            'price'                 => $data['price'],
            'is_active'             => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', __('messages.product_pricing.type_price_added'));
    }

    public function updateTypePrice(Request $request, Product $product, ProductPrice $price): RedirectResponse
    {
        $this->authorizePrice($product, $price);

        $data = $request->validate([
            'price'     => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $price->update([
            'price'     => $data['price'],
            'is_active' => (bool) ($data['is_active'] ?? $price->is_active),
        ]);

        return back()->with('success', __('messages.product_pricing.type_price_updated'));
    }

    public function destroyTypePrice(Product $product, ProductPrice $price): RedirectResponse
    {
        $this->authorizePrice($product, $price);
        $price->delete();
        return back()->with('success', __('messages.product_pricing.type_price_removed'));
    }

    /* ──────────────────────────────────────────────────────────────────── */
    /* Provider-specific prices                                            */
    /* ──────────────────────────────────────────────────────────────────── */

    public function storeProviderPrice(Request $request, Product $product): RedirectResponse
    {
        $validTypes = array_column(InsuranceType::cases(), 'value');

        $data = $request->validate([
            'insurance_type'        => ['required', Rule::in($validTypes)],
            'insurance_provider_id' => 'required|integer|exists:insurance_providers,id',
            'price'                 => 'required|numeric|min:0',
            'is_active'             => 'nullable|boolean',
        ]);

        $exists = ProductPrice::where('product_id', $product->id)
            ->where('insurance_type', $data['insurance_type'])
            ->where('insurance_provider_id', $data['insurance_provider_id'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'insurance_provider_id' => 'A price for this provider already exists. Edit the existing entry instead.',
            ])->withInput();
        }

        $product->prices()->create([
            'insurance_type'        => $data['insurance_type'],
            'insurance_provider_id' => $data['insurance_provider_id'],
            'price'                 => $data['price'],
            'is_active'             => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', __('messages.product_pricing.provider_price_added'));
    }

    public function updateProviderPrice(Request $request, Product $product, ProductPrice $price): RedirectResponse
    {
        $this->authorizePrice($product, $price);

        $data = $request->validate([
            'price'     => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $price->update([
            'price'     => $data['price'],
            'is_active' => (bool) ($data['is_active'] ?? $price->is_active),
        ]);

        return back()->with('success', __('messages.product_pricing.provider_price_updated'));
    }

    public function destroyProviderPrice(Product $product, ProductPrice $price): RedirectResponse
    {
        $this->authorizePrice($product, $price);
        $price->delete();
        return back()->with('success', __('messages.product_pricing.provider_price_removed'));
    }

    /* ── Guard ──────────────────────────────────────────────────────────── */

    private function authorizePrice(Product $product, ProductPrice $price): void
    {
        if ((int) $price->product_id !== (int) $product->id) {
            abort(404, 'Price not found for this product.');
        }
    }
}
