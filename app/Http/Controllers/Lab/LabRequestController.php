<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\LabRequest;
use App\Services\LabService;
use Illuminate\Http\Request;

class LabRequestController extends Controller
{
    public function __construct(
        protected LabService $labService,
    ) {}

    /**
     * Lab request queue / list.
     */
    public function index(Request $request)
    {
        $requests = $this->labService->getRequests([
            'status' => $request->status,
            'urgency' => $request->urgency,
            'search' => $request->search,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ]);

        $stats = $this->labService->getLabStats();

        return view('lab.requests', compact('requests', 'stats'));
    }

    /**
     * Show / process a lab request (result entry form).
     */
    public function show(LabRequest $labRequest)
    {
        $request = $this->labService->getRequestDetails($labRequest);

        return view('lab.process', compact('request'));
    }

    /**
     * Accept a pending lab request.
     */
    public function accept(LabRequest $labRequest)
    {
        try {
            $this->labService->acceptRequest($labRequest);
            return back()->with('success', 'Lab request accepted and is now being processed.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel a lab request.
     */
    public function cancel(LabRequest $labRequest)
    {
        try {
            $this->labService->cancelRequest($labRequest);
            return back()->with('success', 'Lab request cancelled.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
