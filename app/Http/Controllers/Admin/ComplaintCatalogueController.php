<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComplaintCatalogue;
use App\Services\ComplaintCatalogueService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComplaintCatalogueController extends Controller
{
    public function index(Request $request, ComplaintCatalogueService $complaints)
    {
        $items = $complaints->query($request->only(['search', 'category', 'active']))
            ->paginate(25)
            ->withQueryString();

        $categories = ComplaintCatalogue::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('complaints.catalogue.index', [
            'complaints' => $items,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'active']),
        ]);
    }

    public function store(Request $request, ComplaintCatalogueService $complaints)
    {
        $data = $this->validated($request);
        $complaints->create($data);

        return back()->with('success', 'Complaint added to the catalogue.');
    }

    public function update(Request $request, ComplaintCatalogue $complaint, ComplaintCatalogueService $complaints)
    {
        $data = $this->validated($request, $complaint);
        $complaints->update($complaint, $data);

        return back()->with('success', 'Complaint catalogue entry updated.');
    }

    public function toggle(ComplaintCatalogue $complaint, ComplaintCatalogueService $complaints)
    {
        $complaints->toggle($complaint);

        return back()->with('success', 'Complaint catalogue status updated.');
    }

    private function validated(Request $request, ?ComplaintCatalogue $complaint = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('complaint_catalogues', 'name')
                    ->where(fn ($query) => $query->where('category', $request->input('category')))
                    ->ignore($complaint?->id),
            ],
            'category' => ['nullable', 'string', 'max:191'],
            'body_system' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:2000'],
            'keywords' => ['nullable'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}