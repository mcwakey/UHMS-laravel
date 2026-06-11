<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Drug;
use App\Models\ServiceCatalog;
use App\Services\CounterSaleService;
use Illuminate\Http\Request;

class CounterSaleController extends Controller
{
    public function __construct(private CounterSaleService $sales) {}

    public function create()
    {
        return view('billing.counter-sale.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'external_party_name' => ['required', 'string', 'max:255'],
            'external_party_contact' => ['nullable', 'string', 'max:100'],
            'external_party_sex' => ['nullable', 'string', 'max:20'],
            'external_party_age' => ['nullable', 'integer', 'min:0', 'max:150'],
            'drug_id' => ['array'],
            'drug_id.*' => ['nullable', 'integer'],
            'drug_qty' => ['array'],
            'service_id' => ['array'],
            'service_id.*' => ['nullable', 'integer'],
            'service_qty' => ['array'],
            'procedure_id' => ['array'],
            'procedure_id.*' => ['nullable', 'integer'],
            'procedure_qty' => ['array'],
        ]);

        $drugs = [];
        foreach ((array) $request->input('drug_id', []) as $i => $id) {
            if ($id) {
                $drugs[] = ['id' => (int) $id, 'quantity' => max(1, (int) $request->input("drug_qty.{$i}", 1))];
            }
        }

        $services = [];
        foreach ((array) $request->input('service_id', []) as $i => $id) {
            if ($id) {
                $services[] = ['id' => (int) $id, 'quantity' => max(1, (int) $request->input("service_qty.{$i}", 1))];
            }
        }

        $procedures = [];
        foreach ((array) $request->input('procedure_id', []) as $i => $id) {
            if ($id) {
                $procedures[] = ['id' => (int) $id, 'quantity' => max(1, (int) $request->input("procedure_qty.{$i}", 1))];
            }
        }

        $invoice = $this->sales->create([
            'external_party_name' => $data['external_party_name'],
            'external_party_contact' => $data['external_party_contact'] ?? null,
            'external_party_sex' => $data['external_party_sex'] ?? null,
            'external_party_age' => $data['external_party_age'] ?? null,
            'drugs' => $drugs,
            'services' => $services,
            'procedures' => $procedures,
        ], $request->user());

        return redirect()
            ->route('admin.billing.invoices.show', $invoice)
            ->with('success', __('messages.counter_sales.created'));
    }

    /** select2 JSON: billable, stock-linked drugs with cash price. */
    public function drugSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $drugs = Drug::with('product')
            ->where('is_active', true)
            ->whereNotNull('product_id')
            ->when($q !== '', fn ($x) => $x->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json($drugs->map(fn ($d) => [
            'id' => $d->id,
            'text' => $d->display_name,
            'price' => (float) ($d->product->base_price ?? 0),
        ]));
    }

    /** select2 JSON: active investigation services with cash price. */
    public function serviceSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $services = ServiceCatalog::where('category', 'investigation')
            ->where('is_active', true)
            ->when($q !== '', fn ($x) => $x->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json($services->map(fn ($s) => [
            'id' => $s->id,
            'text' => $s->name,
            'price' => (float) ($s->price ?? 0),
        ]));
    }

    /** select2 JSON: active procedure services with cash price. */
    public function procedureSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $services = ServiceCatalog::where('category', 'procedure')
            ->where('is_active', true)
            ->when($q !== '', fn ($x) => $x->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json($services->map(fn ($s) => [
            'id' => $s->id,
            'text' => $s->name,
            'price' => (float) ($s->price ?? 0),
        ]));
    }
}
