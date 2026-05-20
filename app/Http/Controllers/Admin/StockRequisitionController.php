<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockRequisitionStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Product;
use App\Models\StockRequisition;
use App\Services\StockRequisitionService;
use Illuminate\Http\Request;
use Throwable;

class StockRequisitionController extends Controller
{
    public function __construct(private StockRequisitionService $requisitions) {}

    public function index(Request $request)
    {
        $stockRequisitions = $this->requisitions->list($request->all());
        $departments = Department::active()->stockManaged()->orderBy('name')->get(['id', 'name']);
        $products = Product::active()->orderBy('name')->get(['id', 'name', 'code']);
        $statuses = StockRequisitionStatus::cases();

        return view('store.stock-requisitions.index', compact('stockRequisitions', 'departments', 'products', 'statuses'));
    }

    public function create()
    {
        return view('store.stock-requisitions.create', [
            'departments' => Department::active()->stockManaged()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()
                ->active()
                ->with('departments:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'unit']),
            'defaultDepartmentId' => auth()->user()?->department_id,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'department_id' => 'required|integer|exists:departments,id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|integer|exists:products,id',
            'items.*.quantity_requested' => 'nullable|numeric|gt:0',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        try {
            $stockRequisition = $this->requisitions->create($data);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('admin.store.stock-requisitions.show', $stockRequisition)
            ->with('success', 'Stock requisition submitted.');
    }

    public function show(StockRequisition $stockRequisition)
    {
        $stockRequisition->load([
            'department', 'items.product', 'requestedByUser', 'approvedByUser', 'issuedByUser', 'acknowledgedByUser',
        ]);

        return view('store.stock-requisitions.show', compact('stockRequisition'));
    }

    public function approve(Request $request, StockRequisition $stockRequisition)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.approved_quantity' => 'nullable|numeric|min:0',
        ]);

        $approvedItems = collect($data['items'])
            ->mapWithKeys(fn ($item, $id) => [(int) $id => (float) ($item['approved_quantity'] ?? 0)])
            ->all();

        try {
            $this->requisitions->approve($stockRequisition, $approvedItems);
        } catch (Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Stock requisition approved.');
    }

    public function issue(StockRequisition $stockRequisition)
    {
        try {
            $this->requisitions->issue($stockRequisition);
        } catch (Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Stock issued from Main Store. Department stock will update after acknowledgement.');
    }

    public function acknowledge(StockRequisition $stockRequisition)
    {
        try {
            $this->requisitions->acknowledge($stockRequisition);
        } catch (Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Department stock acknowledged and updated.');
    }

    public function cancel(StockRequisition $stockRequisition)
    {
        try {
            $this->requisitions->cancel($stockRequisition);
        } catch (Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Stock requisition cancelled.');
    }
}
