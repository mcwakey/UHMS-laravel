<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\MedicalPattern;
use App\Models\MedicalRecord;
use App\Models\Visit;
use App\Services\ConsultationService;
use App\Services\MedicalPatternService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class MedicalPatternController extends Controller
{
    public function __construct(
        protected MedicalPatternService $patternService,
        protected ConsultationService $consultationService,
    ) {}

    /**
     * List all patterns (doctor's own + system-wide).
     */
    public function index(Request $request)
    {
        $patterns = $this->patternService->list([
            'search' => $request->search,
            'scope' => $request->scope,
            'is_active' => $request->has('inactive') ? false : true,
        ]);

        return view('patterns.index', compact('patterns'));
    }

    /**
     * Show pattern creation form.
     */
    public function create()
    {
        return view('patterns.create');
    }

    /**
     * Store a new pattern.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'scope' => ['required', 'in:personal,system'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'in:complaint,diagnosis,treatment,prescription_item'],
            'items.*.data' => ['required', 'array'],
        ]);

        $pattern = $this->patternService->create([
            'name' => $request->name,
            'doctor_id' => $request->scope === 'personal' ? Auth::id() : null,
            'items' => $request->items,
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'pattern' => $pattern]);
        }

        return redirect()->route('admin.patterns.index')->with('success', "Pattern \"{$pattern->name}\" created.");
    }

    /**
     * Save pattern from current consultation record.
     */
    public function storeFromRecord(Request $request, Visit $visit)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'scope' => ['required', 'in:personal,system'],
        ]);

        $record = $this->consultationService->getOrCreateRecord($visit);

        $pattern = $this->patternService->createFromRecord(
            $record,
            $request->name,
            $request->scope === 'personal' ? Auth::id() : null,
        );

        if ($request->ajax()) {
            return response()->json(['success' => true, 'pattern' => $pattern->load('items')]);
        }

        return back()->with('success', "Pattern \"{$pattern->name}\" saved from consultation.");
    }

    /**
     * Show a pattern's details.
     */
    public function show(MedicalPattern $pattern)
    {
        $pattern->load(['items', 'doctor']);

        if (request()->ajax()) {
            return response()->json(['pattern' => $pattern]);
        }

        return view('patterns.show', compact('pattern'));
    }

    /**
     * Update a pattern.
     */
    public function update(Request $request, MedicalPattern $pattern)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'items' => ['nullable', 'array'],
            'items.*.type' => ['required_with:items', 'in:complaint,diagnosis,treatment,prescription_item'],
            'items.*.data' => ['required_with:items', 'array'],
        ]);

        $pattern = $this->patternService->update($pattern, $request->only('name', 'items'));

        if ($request->ajax()) {
            return response()->json(['success' => true, 'pattern' => $pattern]);
        }

        return redirect()->route('admin.patterns.index')->with('success', "Pattern \"{$pattern->name}\" updated.");
    }

    /**
     * Toggle active/inactive.
     */
    public function toggleActive(MedicalPattern $pattern)
    {
        $pattern = $this->patternService->toggleActive($pattern);

        if (request()->ajax()) {
            return response()->json(['success' => true, 'is_active' => $pattern->is_active]);
        }

        return back()->with('success', "Pattern \"" . $pattern->name . "\" " . ($pattern->is_active ? 'activated' : 'deactivated') . ".");
    }

    /**
     * Delete a pattern.
     */
    public function destroy(MedicalPattern $pattern)
    {
        $name = $pattern->name;
        $this->patternService->delete($pattern);

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.patterns.index')->with('success', "Pattern \"{$name}\" deleted.");
    }

    /**
     * AJAX: suggest patterns based on complaint text.
     */
    public function suggest(Request $request)
    {
        $request->validate([
            'query' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $suggestions = $this->patternService->suggest(
            $request->query('query'),
            Auth::id(),
            5
        );

        return response()->json(['patterns' => $suggestions]);
    }

    /**
     * AJAX: apply a pattern to a visit's medical record.
     */
    public function apply(Request $request, MedicalPattern $pattern)
    {
        $request->validate([
            'visit_id' => ['required', 'exists:visits,id'],
        ]);

        $visit = Visit::findOrFail($request->visit_id);
        $record = $this->consultationService->getOrCreateRecord($visit);

        $applied = $this->patternService->applyPattern($pattern, $record);

        return response()->json([
            'success' => true,
            'applied' => $applied,
            'message' => "Pattern \"{$pattern->name}\" applied successfully.",
        ]);
    }
}
