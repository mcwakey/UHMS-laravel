<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\LabRequest;
use App\Services\InvestigationRequestService;
use App\Services\LabService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LabRequestController extends Controller
{
    public function __construct(
        protected LabService $labService,
        protected InvestigationRequestService $investigationRequestService,
    ) {}

    /**
     * Lab/Investigation request queue.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'urgency', 'search', 'date_from', 'date_to', 'department_id']);

        if (!empty($filters['department_id'])) {
            $filters['target_department_id'] = $filters['department_id'];
        }

        $requests = $this->labService->getRequests($filters);
        $stats = $this->labService->getLabStats();
        $departments = $this->labService->getInvestigationDepartments();

        $requestsPayload = $requests->through(function (LabRequest $req) {
            $resultType = $req->result_type;
            return [
                'id'             => $req->id,
                'request_number' => $req->request_number,
                'patient' => $req->patient ? [
                    'full_name'      => $req->patient->full_name,
                    'patient_number' => $req->patient->patient_number,
                ] : null,
                'target_department_name' => optional($req->targetDepartment)->name,
                'result_type' => ($resultType && $resultType->value !== 'none') ? [
                    'value' => $resultType->value,
                    'label' => $resultType->label(),
                    'color' => $resultType->color(),
                    'icon'  => $resultType->icon(),
                ] : null,
                'urgency'       => $req->urgency,
                'urgency_label' => ucfirst((string) $req->urgency),
                'urgency_color' => $req->urgency_color,
                'status'        => $req->status,
                'status_label'  => $req->status_label,
                'status_color'  => $req->status_color,
                'completion_percentage' => $req->completion_percentage,
                'created_at_date' => optional($req->created_at)->format('d M Y'),
                'created_at_time' => optional($req->created_at)->format('H:i'),
                'urls' => [
                    'show' => route('admin.lab.requests.show', $req),
                ],
            ];
        });

        return Inertia::render('Lab/Requests/Index', [
            'requests'    => $requestsPayload,
            'stats'       => $stats,
            'departments' => $departments->map(fn ($d) => ['id' => $d->id, 'name' => $d->name])->values(),
            'filters'     => $request->only(['status', 'urgency', 'search', 'date_from', 'date_to', 'department_id']),
            'routes' => [
                'index' => route('admin.lab.requests.index'),
            ],
        ]);
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
     * Accept selected items only (with auto-invoice for them).
     */
    public function acceptSelected(Request $request, LabRequest $labRequest)
    {
        $data = $request->validate([
            'item_ids'   => ['required', 'array', 'min:1'],
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
        $msg = "Accepted {$result['accepted_count']} item(s)" .
               ($invoice ? " — invoice {$invoice->invoice_number} generated." : ' (no billable services).');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => $msg,
                'invoice' => $invoice?->only(['id', 'invoice_number', 'total_amount', 'status']),
                'redirect' => route('admin.lab.requests.show', $labRequest),
            ]);
        }

        return redirect()->route('admin.lab.requests.show', $labRequest)->with('success', $msg);
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
