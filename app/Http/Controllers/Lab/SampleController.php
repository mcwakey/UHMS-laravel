<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\LabRequest;
use App\Models\Sample;
use App\Services\LabService;
use App\Services\SampleService;
use Illuminate\Http\Request;

class SampleController extends Controller
{
    public function __construct(
        protected SampleService $samples,
        protected LabService $labService,
    ) {}

    /**
     * Specimen / sample tracking queue.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'specimen_type', 'search', 'date_from', 'date_to', 'department_id']);

        if (! empty($filters['department_id'])) {
            $filters['target_department_id'] = $filters['department_id'];
        }

        $samples     = $this->samples->getSampleQueue($filters);
        $stats       = $this->samples->getStats();
        $departments = $this->labService->getInvestigationDepartments();
        $specimenTypes = config('specimens.types', []);

        return view('lab.samples', compact('samples', 'stats', 'departments', 'specimenTypes'));
    }

    /**
     * Generate samples for a request (one per specimen type).
     */
    public function generate(LabRequest $labRequest)
    {
        $created = $this->samples->generateForRequest($labRequest);

        return back()->with('success', __('samples.generated_count', ['count' => $created->count()]));
    }

    public function collect(Request $request, Sample $sample)
    {
        $data = $request->validate([
            'barcode'   => ['nullable', 'string', 'max:100'],
            'container' => ['nullable', 'string', 'max:100'],
            'notes'     => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->samples->collect($sample, $data);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('samples.collected_success', ['number' => $sample->sample_number]));
    }

    public function receive(Sample $sample)
    {
        try {
            $this->samples->receive($sample);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('samples.received_success', ['number' => $sample->sample_number]));
    }

    public function reject(Request $request, Sample $sample)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->samples->reject($sample, $data['rejection_reason']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('samples.rejected_success', ['number' => $sample->sample_number]));
    }

    public function dispose(Sample $sample)
    {
        try {
            $this->samples->dispose($sample);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('samples.disposed_success', ['number' => $sample->sample_number]));
    }
}
