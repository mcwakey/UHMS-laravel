<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Enums\ResultType;
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
     * Enter result for a single investigation item (supports all result types).
     */
    public function store(Request $request, LabRequestItem $item)
    {
        $resultType = ResultType::tryFrom($request->input('result_type', 'parameters'))
            ?? ResultType::PARAMETERS;

        $rules = [
            'result_type' => ['required', 'string'],
            'is_abnormal' => ['nullable', 'boolean'],
            'remarks'     => ['nullable', 'string', 'max:2000'],
            // Attachment is always allowed regardless of result type so users
            // can pair an image / document with parameters or a written report.
            'result_file' => ['nullable', 'file', 'max:20480'], // 20MB
        ];

        if ($resultType === ResultType::RICHTEXT) {
            $rules['result_text'] = ['required', 'string'];
        } elseif ($resultType->isFileBased()) {
            $rules['result_file'] = ['required', 'file', 'max:20480']; // 20MB
        } else {
            $rules['result_value'] = ['required', 'string', 'max:5000'];
        }

        $validated = $request->validate($rules);
        // Pass the uploaded file separately because $request->validate strips
        // unrecognised non-validated keys.
        if ($request->hasFile('result_file')) {
            $validated['result_file'] = $request->file('result_file');
        }

        $this->labService->enterResult($item, $validated);

        return back()->with('success', 'Result saved successfully.');
    }

    /**
     * Batch enter results (parameters type only).
     */
    public function batchStore(Request $request, LabRequest $labRequest)
    {
        $validated = $request->validate([
            'results'                   => ['required', 'array'],
            'results.*.result_value'    => ['nullable', 'string', 'max:5000'],
            'results.*.is_abnormal'     => ['nullable', 'boolean'],
            'results.*.remarks'         => ['nullable', 'string', 'max:2000'],
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
