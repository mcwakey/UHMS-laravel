<?php

namespace App\Http\Controllers\Lab;

use App\Enums\ResultType;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\LabRequest;
use App\Models\LabTest;
use App\Models\Visit;
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
     * Search visits (AJAX) so a new investigation request can be started
     * for a patient/visit — used by the "New Request" modal on the
     * investigation requests index page.
     */
    public function visitSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $visits = Visit::query()
            ->with('patient:id,patient_number,first_name,last_name')
            ->when($q !== '', function ($query) use ($q) {
                $query->where('visit_number', 'like', "%{$q}%")
                    ->orWhereHas('patient', function ($p) use ($q) {
                        $p->where('first_name', 'like', "%{$q}%")
                            ->orWhere('last_name', 'like', "%{$q}%")
                            ->orWhere('patient_number', 'like', "%{$q}%");
                    });
            })
            ->latest('visit_date')
            ->limit(20)
            ->get();

        return response()->json($visits->map(fn ($v) => [
            'id' => $v->id,
            'visit_number' => $v->visit_number,
            'patient_name' => $v->patient?->full_name,
            'patient_number' => $v->patient?->patient_number,
            'visit_date' => $v->visit_date?->format('d M Y'),
        ]));
    }

    /**
     * Tests available for a target department (AJAX) — catalogued lab
     * tests when the department uses a structured result type, otherwise
     * the modal falls back to free-text test names.
     */
    public function departmentTests(Department $department)
    {
        $resultType = $department->result_type ?? ResultType::NONE;
        $usesCatalog = $resultType->usesTestCatalog();

        return response()->json([
            'result_type' => $resultType->value,
            'uses_catalog' => $usesCatalog,
            'lab_tests' => $usesCatalog
                ? LabTest::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'unit', 'normal_range'])
                : [],
        ]);
    }

    /**
     * Start a brand-new investigation request for a chosen visit, from the
     * requests index page (no active consultation session required).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'visit_id' => ['required', 'exists:visits,id'],
            'target_department_id' => ['required', 'exists:departments,id'],
            'items' => ['required', 'array', 'min:1'],
            'urgency' => ['nullable', 'in:routine,urgent,emergency'],
            'clinical_info' => ['nullable', 'string', 'max:2000'],
        ]);

        $visit = Visit::findOrFail($data['visit_id']);

        $labRequest = $this->labService->createRequest($visit, $data['items'], $data);
        $labRequest->load('targetDepartment');

        return redirect()
            ->route('admin.lab.requests.show', $labRequest)
            ->with('success', __('messages.consultations.lab_request_sent', [
                'number' => $labRequest->request_number,
                'department' => $labRequest->targetDepartment?->name,
            ]));
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
