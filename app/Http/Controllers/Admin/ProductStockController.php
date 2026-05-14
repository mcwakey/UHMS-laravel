<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStockBalance;
use App\Models\ProductStockMovement;
use App\Models\StockLocation;
use App\Services\ProductStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProductStockController extends Controller
{
    public function __construct(private ProductStockService $stock) {}

    // ---------- Balances ----------
    public function balances(Request $request)
    {
        $locations = StockLocation::active()->orderByDesc('is_main')->orderBy('name')->get();
        $locationId = (int) $request->get('location_id', $locations->first()?->id ?? 0);
        $search     = trim((string) $request->get('search'));
        $type       = $request->get('type');
        $lowOnly    = (bool) $request->get('low_only');

        $balances = ProductStockBalance::query()
            ->with('product')
            ->where('stock_location_id', $locationId)
            ->whereHas('product', function ($q) use ($search, $type) {
                if ($search !== '') {
                    $q->where(function ($qq) use ($search) {
                        $qq->where('name', 'like', "%{$search}%")
                           ->orWhere('code', 'like', "%{$search}%");
                    });
                }
                if ($type) $q->where('type', $type);
            })
            ->when($lowOnly, fn ($q) => $q->whereColumn('quantity_on_hand', '<=', DB::raw('(select reorder_level from products where products.id = product_stock_balances.product_id)')))
            ->orderBy('quantity_on_hand')
            ->paginate(25)
            ->withQueryString();

        return view('admin.product-stock.balances', [
            'locations'   => $locations,
            'locationId'  => $locationId,
            'balances'    => $balances,
            'search'      => $search,
            'type'        => $type,
            'lowOnly'     => $lowOnly,
            'typeOptions' => ProductType::options(),
        ]);
    }

    // ---------- Ledger ----------
    public function ledger(Request $request)
    {
        $movements = ProductStockMovement::with(['product', 'location', 'performer'])
            ->when($request->location_id, fn ($q, $v) => $q->where('stock_location_id', $v))
            ->when($request->product_id, fn ($q, $v) => $q->where('product_id', $v))
            ->when($request->movement_type, fn ($q, $v) => $q->where('movement_type', $v))
            ->when($request->from_date, fn ($q, $v) => $q->whereDate('movement_date', '>=', $v))
            ->when($request->to_date, fn ($q, $v) => $q->whereDate('movement_date', '<=', $v))
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.product-stock.ledger', [
            'movements'      => $movements,
            'locations'      => StockLocation::orderBy('name')->get(),
            'products'       => Product::orderBy('name')->get(['id','name','code']),
            'movementTypes'  => collect(StockMovementType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]),
            'filters'        => $request->only(['location_id','product_id','movement_type','from_date','to_date']),
        ]);
    }

    // ---------- Receive ----------
    public function receiveForm()
    {
        return view('admin.product-stock.receive', [
            'locations' => StockLocation::active()->orderBy('name')->get(),
            'products'  => Product::where('is_active', true)->orderBy('name')->get(['id','name','code','unit']),
        ]);
    }

    public function receive(Request $request)
    {
        $data = $request->validate([
            'stock_location_id'    => 'required|integer|exists:stock_locations,id',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|integer|exists:products,id',
            'items.*.quantity'     => 'required|numeric|gt:0',
            'items.*.unit_cost'    => 'nullable|numeric|min:0',
            'items.*.batch_no'     => 'nullable|string|max:100',
            'items.*.expiry_date'  => 'nullable|date',
            'notes'                => 'nullable|string|max:500',
        ]);

        try {
            foreach ($data['items'] as $item) {
                $this->stock->receive([
                    'product_id'        => (int) $item['product_id'],
                    'stock_location_id' => (int) $data['stock_location_id'],
                    'quantity'          => (float) $item['quantity'],
                    'unit_cost'         => $item['unit_cost']   ?? null,
                    'batch_no'          => $item['batch_no']    ?? null,
                    'expiry_date'       => $item['expiry_date'] ?? null,
                    'notes'             => $data['notes']       ?? null,
                ]);
            }
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('admin.product-stock.balances', ['location_id' => $data['stock_location_id']])
            ->with('success', 'Stock received.');
    }

    // ---------- Adjust ----------
    public function adjustForm()
    {
        return view('admin.product-stock.adjust', [
            'locations' => StockLocation::active()->orderBy('name')->get(),
            'products'  => Product::where('is_active', true)->orderBy('name')->get(['id','name','code','unit']),
            'types'     => [
                StockMovementType::ADJUSTMENT_IN->value  => 'Adjustment In (+)',
                StockMovementType::ADJUSTMENT_OUT->value => 'Adjustment Out (-)',
                StockMovementType::DAMAGED->value        => 'Damaged (-)',
                StockMovementType::EXPIRED->value        => 'Expired (-)',
            ],
        ]);
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'product_id'        => 'required|integer|exists:products,id',
            'stock_location_id' => 'required|integer|exists:stock_locations,id',
            'type'              => 'required|string',
            'quantity'          => 'required|numeric|gt:0',
            'reason'            => 'required|string|max:500',
            'allow_negative'    => 'nullable|boolean',
        ]);

        $type = StockMovementType::tryFrom($data['type']);
        if (! $type) return back()->withInput()->withErrors(['type' => 'Invalid movement type.']);

        // Convert UI quantity to signed delta based on direction.
        $qty = abs((float) $data['quantity']);
        $signed = $type->direction()->value === 'in' ? $qty : -$qty;

        try {
            $this->stock->adjust([
                'product_id'        => (int) $data['product_id'],
                'stock_location_id' => (int) $data['stock_location_id'],
                'quantity'          => $signed,
                'type'              => $type,
                'reason'            => $data['reason'],
                'allow_negative'    => (bool) ($data['allow_negative'] ?? false),
            ]);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('admin.product-stock.balances', ['location_id' => $data['stock_location_id']])
            ->with('success', 'Stock adjusted.');
    }

    // ---------- Transfer ----------
    public function transferForm()
    {
        return view('admin.product-stock.transfer', [
            'locations' => StockLocation::active()->orderBy('name')->get(),
            'products'  => Product::where('is_active', true)->orderBy('name')->get(['id','name','code','unit']),
        ]);
    }

    public function transfer(Request $request)
    {
        $data = $request->validate([
            'from_location_id' => 'required|integer|different:to_location_id|exists:stock_locations,id',
            'to_location_id'   => 'required|integer|exists:stock_locations,id',
            'product_id'       => 'required|integer|exists:products,id',
            'quantity'         => 'required|numeric|gt:0',
            'notes'            => 'nullable|string|max:500',
        ]);

        try {
            $this->stock->transfer($data);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
        return redirect()->route('admin.product-stock.ledger')->with('success', 'Transfer recorded.');
    }

    // ---------- Return ----------
    public function returnForm()
    {
        return view('admin.product-stock.return', [
            'locations' => StockLocation::active()->orderBy('name')->get(),
            'mainStore' => StockLocation::main()->first(),
            'products'  => Product::where('is_active', true)->orderBy('name')->get(['id','name','code','unit']),
        ]);
    }

    public function returnStock(Request $request)
    {
        $data = $request->validate([
            'from_location_id' => 'required|integer|different:to_location_id|exists:stock_locations,id',
            'to_location_id'   => 'required|integer|exists:stock_locations,id',
            'product_id'       => 'required|integer|exists:products,id',
            'quantity'         => 'required|numeric|gt:0',
            'notes'            => 'nullable|string|max:500',
        ]);

        // Implemented as RETURN_OUT (from) + RETURN_IN (to) using the same transfer service.
        try {
            $movements = app(\App\Services\ProductStockMovementService::class);
            DB::transaction(function () use ($movements, $data) {
                $out = $movements->createMovement([
                    'product_id'        => (int) $data['product_id'],
                    'stock_location_id' => (int) $data['from_location_id'],
                    'movement_type'     => StockMovementType::RETURN_OUT,
                    'quantity'          => (float) $data['quantity'],
                    'notes'             => $data['notes'] ?? null,
                    'source_type'       => 'stock_return',
                ]);
                $in = $movements->createMovement([
                    'product_id'        => (int) $data['product_id'],
                    'stock_location_id' => (int) $data['to_location_id'],
                    'movement_type'     => StockMovementType::RETURN_IN,
                    'quantity'          => (float) $data['quantity'],
                    'notes'             => $data['notes'] ?? null,
                    'source_type'       => 'stock_return',
                    'source_id'         => $out->id,
                ]);
                $out->source_id = $in->id;
                $out->save();
            });
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('admin.product-stock.ledger')->with('success', 'Return recorded.');
    }
}
