<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\LabRequest;
use App\Services\InpatientWorkspaceScope;
use App\Services\InvestigationRequestService;
use App\Services\InvestigationWorkspaceScope;
use App\Services\LabService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LabRequestController extends Controller
{
    public function __construct(
        protected LabService $labService,
        protected InvestigationRequestService $investigationRequestService,
        protected InpatientWorkspaceScope $inpatientScope,
        protected InvestigationWorkspaceScope $investigationScope,
    ) {}

    /**
     * Lab/Investigation request queue.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'urgency', 'search', 'date_from', 'date_to', 'department_id']);

        if (! empty($filters['department_id'])) {
            $filters['target_department_id'] = $filters['department_id'];
        }

        if ($this->shouldScopeToLoggedInDoctor($request)) {
            $filters['requested_by'] = $request->user()->id;
        }

        if ($request->routeIs('inpatient.*')) {
            $filters['admission_department_id'] = $this->inpatientScope->departmentId();
        }

        // Investigations workspace: requests are scoped to the active performing
        // (target) department, regardless of any submitted department filter.
        if ($request->routeIs('investigations.*')) {
            $filters['target_department_id'] = $this->investigationScope->departmentId();
        }

        $requests = $this->labService->getRequests($filters);
        $stats = $this->labService->getLabStats($filters);
        $departments = $this->labService->getInvestigationDepartments();

        return view('lab.requests', compact('requests', 'stats', 'departments'));
    }

    /**
     * Show / process a lab request (result entry form).
     */
    public function show(LabRequest $labRequest)
    {
        $this->authorizeDoctorWorkspaceRequest($labRequest);
        abort_if(request()->routeIs('inpatient.*') && ! $this->inpatientScope->contains($labRequest), 404);
        abort_if(request()->routeIs('investigations.*') && ! $this->investigationScope->contains($labRequest), 404);

        $request = $this->labService->getRequestDetails($labRequest);
        $billingPrices = $this->investigationRequestService->billingPreview($request);

        return view('lab.process', compact('request', 'billingPrices'));
    }

    /**
     * Accept a pending lab request.
     */
    public function accept(LabRequest $labRequest)
    {
        $this->guardInvestigationWorkspaceRequest($labRequest);
        try {
            $this->labService->acceptRequest($labRequest);

            return redirect()
                ->route('admin.lab.results.index', ['search' => $labRequest->request_number])
                ->with('success', __('messages.lab.request_accepted'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Accept selected items only (with auto-invoice for them).
     */
    public function acceptSelected(Request $request, LabRequest $labRequest)
    {
        $this->guardInvestigationWorkspaceRequest($labRequest);
        $data = $request->validate([
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer'],
        ]);

        try {
            $result = $this->investigationRequestService->acceptSelectedItems(
                $labRequest,
                $data['item_ids'],
                Auth::user()
            );
        } catch (\RuntimeException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        $invoice = $result['invoice'];
        $msg = $invoice
            ? __('messages.lab.items_accepted_with_invoice', ['count' => $result['accepted_count'], 'number' => $invoice->invoice_number])
            : __('messages.lab.items_accepted', ['count' => $result['accepted_count']]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => $msg,
                'invoice' => $invoice?->only(['id', 'invoice_number', 'total_amount', 'status']),
                'redirect' => route('admin.lab.results.index', ['search' => $labRequest->request_number]),
            ]);
        }

        return redirect()
            ->route('admin.lab.results.index', ['search' => $labRequest->request_number])
            ->with('success', $msg);
    }

    /**
     * Cancel a lab request.
     */
    public function cancel(LabRequest $labRequest)
    {
        $this->guardInvestigationWorkspaceRequest($labRequest);
        try {
            $this->labService->cancelRequest($labRequest);

            return back()->with('success', __('messages.lab.request_cancelled'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /** In the Investigations workspace, requests outside the active department are invisible. */
    private function guardInvestigationWorkspaceRequest(LabRequest $labRequest): void
    {
        abort_if(request()->routeIs('investigations.*') && ! $this->investigationScope->contains($labRequest), 404);
    }

    private function shouldScopeToLoggedInDoctor(Request $request): bool
    {
        return $request->routeIs('doctor.*') && ! ($request->user()?->hasRole('Super Admin') ?? false);
    }

    private function authorizeDoctorWorkspaceRequest(LabRequest $labRequest): void
    {
        $request = request();

        abort_if(
            $this->shouldScopeToLoggedInDoctor($request)
                && (int) $labRequest->requested_by !== (int) $request->user()?->id,
            404
        );
    }
}
