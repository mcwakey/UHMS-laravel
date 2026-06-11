<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseReturnStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\StockLocation;
use App\Models\Supplier;
use App\Services\PurchaseReturnService;
use Illuminate\Http\Request;
use Throwable;

class PurchaseReturnController extends Controller
{
    public function __construct(private PurchaseReturnService $returns) {}

    public function index(Request $request)
    {
        $purchaseReturns = $this->returns->list($request->all());
        $suppliers = Supplier::active()->orderBy('name')->get(['id', 'name']);
        $products = Product::active()->orderBy('name')->get(['id', 'name', 'code']);
        $statuses = PurchaseReturnStatus::cases();

        return view('store.purchase-returns.index', compact('purchaseReturns', 'suppliers', 'products', 'statuses'));
    }

    public function create()
    {
        return view('store.purchase-returns.create', [
            'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'name']),
            'purchaseOrders' => PurchaseOrder::query()
                ->with('supplier:id,name')
                ->whereIn('status', [
                    PurchaseOrderStatus::PARTIALLY_RECEIVED->value,
                    PurchaseOrderStatus::RECEIVED->value,
                ])
                ->whereHas('items', fn ($q) => $q->where('quantity_received', '>', 0))
                ->latest('order_date')
                ->get(['id', 'po_number', 'supplier_id', 'order_date']),
            'locations' => StockLocation::active()->orderByDesc('is_main')->orderBy('name')->get(['id', 'name', 'is_main']),
        ]);
    }

    /**
     * JSON: received line items of a purchase order, used to build the
     * "return from this PO" editable datatable. Only items with received
     * quantity are returnable.
     */
    public function poItems(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier:id,name', 'items.product:id,name,code,unit']);

        $items = $purchaseOrder->items
            ->filter(fn ($item) => (float) $item->quantity_received > 0 && $item->product_id)
            ->map(fn ($item) => [
                'purchase_order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? $item->item_name,
                'product_code' => $item->product?->code,
                'unit' => $item->product?->unit,
                'quantity_received' => (float) $item->quantity_received,
                'unit_cost' => (float) $item->unit_cost,
                'batch_no' => $item->batch_number,
                'expiry_date' => optional($item->expiry_date)->toDateString(),
            ])
            ->values();

        return response()->json([
            'supplier_id' => $purchaseOrder->supplier_id,
            'supplier_name' => $purchaseOrder->supplier?->name,
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'purchase_order_id' => 'required|integer|exists:purchase_orders,id',
            'goods_received_note_id' => 'nullable|integer|exists:goods_received_notes,id',
            'stock_location_id' => 'required|integer|exists:stock_locations,id',
            'return_date' => 'required|date',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'nullable|integer|exists:purchase_order_items,id',
            'items.*.product_id' => 'nullable|integer|exists:products,id',
            'items.*.stock_location_id' => 'nullable|integer|exists:stock_locations,id',
            'items.*.quantity' => 'nullable|numeric|gt:0',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.batch_no' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
        ]);

        try {
            $purchaseReturn = $this->returns->create($data);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('admin.store.purchase-returns.show', $purchaseReturn)
            ->with('success', __('messages.purchase_returns.created'));
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load([
            'supplier', 'purchaseOrder', 'goodsReceivedNote', 'stockLocation',
            'items.product', 'items.stockLocation', 'createdByUser', 'approvedByUser', 'postedByUser',
        ]);

        return view('store.purchase-returns.show', compact('purchaseReturn'));
    }

    public function approve(PurchaseReturn $purchaseReturn)
    {
        try {
            $this->returns->approve($purchaseReturn);
        } catch (Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', __('messages.purchase_returns.approved'));
    }

    public function post(PurchaseReturn $purchaseReturn)
    {
        try {
            $this->returns->post($purchaseReturn);
        } catch (Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', __('messages.purchase_returns.posted'));
    }

    public function cancel(PurchaseReturn $purchaseReturn)
    {
        try {
            $this->returns->cancel($purchaseReturn);
        } catch (Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', __('messages.purchase_returns.cancelled'));
    }
}
