<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\Visit;
use App\Services\BloodBankCompatibilityService;
use App\Services\BloodRequestService;
use Illuminate\Http\Request;

class BloodRequestController extends Controller
{
    public function __construct(
        private BloodRequestService $requests,
        private BloodBankCompatibilityService $compatibility,
    ) {}

    public function index(Request $request)
    {
        $requests = BloodRequest::with([
            'patient', 'visit', 'admission', 'emergencyCase', 'requestedBy', 'approvedBy',
            'recipient', 'crossmatches.unit', 'crossmatches.performedBy', 'issues.unit',
        ])
            ->when($request->status, fn ($q, $v) => $q->where('status', strtoupper($v)))
            ->when($request->blood_group, fn ($q, $v) => $q->where('blood_group', $v))
            ->latest('requested_at')
            ->paginate(25)
            ->withQueryString();

        // Compatible-unit suggestions for the active (non-completed) requests on the page.
        $suggestions = [];
        foreach ($requests as $req) {
            if (in_array($req->status, [BloodRequest::STATUS_APPROVED, BloodRequest::STATUS_PARTIALLY_ISSUED, BloodRequest::STATUS_PENDING], true)) {
                $component = $req->recipient?->requested_component_type ?: $req->component_type;
                $suggestions[$req->id] = $this->compatibility->suggestUnits($req->recipientGroup(), $component, 10);
            }
        }

        return view('blood-bank.requests', [
            'requests' => $requests,
            'suggestions' => $suggestions,
            'visits' => Visit::with('patient')->latest('visit_date')->limit(75)->get(),
            'clinicalIndications' => config('blood_bank.clinical_indications'),
            'emergencyReleaseTypes' => config('blood_bank.emergency_release_types'),
            'filters' => $request->only(['status', 'blood_group']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'visit_id' => ['required', 'exists:visits,id'],
            'blood_group' => ['required', 'string', 'max:5'],
            'component_type' => ['nullable', 'string', 'max:50'],
            'units_requested' => ['required', 'integer', 'min:1'],
            'priority' => ['nullable', 'in:ROUTINE,URGENT,EMERGENCY,MASSIVE_TRANSFUSION'],
            'needed_at' => ['nullable', 'date'],
            'hb_level' => ['nullable', 'string', 'max:50'],
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'indication' => ['nullable', 'string'],
            'clinical_indication' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            // recipient clinical details
            'patient_blood_group' => ['nullable', 'string', 'max:5'],
            'pregnancy_status' => ['nullable', 'string', 'max:50'],
            'previous_transfusion_reaction' => ['nullable', 'boolean'],
            'previous_transfusion_reaction_notes' => ['nullable', 'string'],
            'transfusion_history' => ['nullable', 'string'],
            'special_requirements' => ['nullable', 'string'],
        ]);

        $this->requests->createForVisit(Visit::findOrFail($data['visit_id']), $data, $request->user());

        return back()->with('success', 'Blood request and recipient details created.');
    }

    public function updateRecipient(Request $request, BloodRequest $bloodRequest)
    {
        if (! $request->user()->can('blood_bank.recipient_details.manage') && ! $request->user()->can('blood_bank.requests.create')) {
            abort(403);
        }

        $data = $request->validate([
            'patient_blood_group' => ['nullable', 'string', 'max:5'],
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'clinical_indication' => ['nullable', 'string'],
            'hemoglobin_level' => ['nullable', 'string', 'max:50'],
            'pregnancy_status' => ['nullable', 'string', 'max:50'],
            'previous_transfusion_reaction' => ['nullable', 'boolean'],
            'previous_transfusion_reaction_notes' => ['nullable', 'string'],
            'transfusion_history' => ['nullable', 'string'],
            'special_requirements' => ['nullable', 'string'],
            'component_type' => ['nullable', 'string', 'max:50'],
            'units_required' => ['nullable', 'integer', 'min:1'],
            'urgency' => ['nullable', 'in:ROUTINE,URGENT,EMERGENCY,MASSIVE_TRANSFUSION'],
        ]);

        $this->requests->upsertRecipient($bloodRequest, $bloodRequest->visit, $data, $request->user());

        return back()->with('success', 'Recipient details updated.');
    }

    public function approve(Request $request, BloodRequest $bloodRequest)
    {
        $this->requests->approve($bloodRequest, $request->user());

        return back()->with('success', 'Blood request approved.');
    }
}
