<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PurchaseOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\ProcurementService;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private ProcurementService $procurementService,
    ) {}

    public function index(Request $request)
    {
        $stats = $this->procurementService->getStats();
        $purchaseOrders = $this->procurementService->list($request->all());
        $suppliers = Supplier::active()->orderBy('name')->get();
        $products = Product::active()->orderBy('name')->get(['id', 'name', 'code']);
        $statuses = PurchaseOrderStatus::cases();

        return view('store.purchase-orders.index', compact('purchaseOrders', 'stats', 'suppliers', 'products', 'statuses'));
    }

    public function create()
    {
        $suppliers = Supplier::active()->orderBy('name')->get();
        $products = \App\Models\Product::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'product_type', 'unit', 'default_cost', 'base_price']);

        return view('store.purchase-orders.create', compact('suppliers', 'products'));
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        try {
            $po = $this->procurementService->create($request->validated());
        } catch (\Throwable $e) {
            if (($request->expectsJson() || $request->ajax()) && ! $request->header('X-Inertia')) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
            return back()->withInput()->with('error', $e->getMessage());
        }

        if (($request->expectsJson() || $request->ajax()) && ! $request->header('X-Inertia')) {
            return response()->json([
                'success'  => 'Purchase order created successfully.',
                'redirect' => route('admin.store.purchase-orders.show', $po),
            ], 201);
        }

        return redirect()
            ->route('admin.store.purchase-orders.show', $po)
            ->with('success', 'Purchase order created successfully.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load([
            'supplier', 'items.product', 'createdByUser', 'approvedByUser',
        ]);

        return view('store.purchase-orders.show', compact('purchaseOrder'));
    }

    public function submit(PurchaseOrder $purchaseOrder)
    {
        try {
            $this->procurementService->submit($purchaseOrder);
            return back()->with('success', 'Purchase order submitted for approval.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        try {
            $this->procurementService->approve($purchaseOrder);
            return back()->with('success', 'Purchase order approved.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.quantity_received' => ['required', 'integer', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date' => ['nullable', 'date'],
        ]);

        try {
            $this->procurementService->receiveItems($purchaseOrder, $request->items);
            return back()->with('success', 'Items received successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        try {
            $this->procurementService->cancel($purchaseOrder);
            return back()->with('success', 'Purchase order cancelled.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function addItem(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'product_id'            => ['required', 'exists:products,id'],
            'quantity_ordered'      => ['required', 'integer', 'min:1'],
            'unit_cost'             => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $this->procurementService->addItem($purchaseOrder, array_merge(
                $request->only(['product_id', 'quantity_ordered', 'unit_cost']),
                ['item_type' => 'product']
            ));
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Item added to purchase order.');
    }

    public function removeItem(PurchaseOrderItem $item)
    {
        $po = $item->purchaseOrder;

        if (! $po->is_editable) {
            return back()->with('error', 'Cannot modify items on this purchase order.');
        }

        $this->procurementService->removeItem($item);

        return back()->with('success', 'Item removed from purchase order.');
    }
}
