<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\LabResult;
use App\Services\LabService;
use Illuminate\Http\Request;

class LabResultController extends Controller
{
    public function __construct(
        protected LabService $labService,
    ) {}

    /**
     * List all lab results.
     */
    public function index(Request $request)
    {
        $results = $this->labService->getResults([
            'verified' => $request->verified,
            'search' => $request->search,
        ]);

        return view('lab.results', compact('results'));
    }

    /**
     * Enter result for a single test item.
     */
    public function store(Request $request, LabRequestItem $item)
    {
        $validated = $request->validate([
            'result_value' => 'required|string|max:5000',
            'is_abnormal' => 'nullable|boolean',
            'remarks' => 'nullable|string|max:2000',
        ]);

        $this->labService->enterResult($item, $validated);

        return back()->with('success', 'Result saved successfully.');
    }

    /**
     * Batch enter results for a request.
     */
    public function batchStore(Request $request, LabRequest $labRequest)
    {
        $validated = $request->validate([
            'results' => 'required|array',
            'results.*.result_value' => 'nullable|string|max:5000',
            'results.*.is_abnormal' => 'nullable|boolean',
            'results.*.remarks' => 'nullable|string|max:2000',
        ]);

        $this->labService->batchEnterResults($labRequest, $validated['results']);

        return back()->with('success', 'Results saved successfully.');
    }

    /**
     * Verify a lab result.
     */
    public function verify(LabResult $result)
    {
        try {
            $this->labService->verifyResult($result);
            return back()->with('success', 'Result verified successfully.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
