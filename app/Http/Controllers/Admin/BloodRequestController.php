<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\Visit;
use App\Services\BloodRequestService;
use Illuminate\Http\Request;

class BloodRequestController extends Controller
{
    public function __construct(private BloodRequestService $requests) {}

    public function index(Request $request)
    {
        return view('blood-bank.requests', [
            'requests' => BloodRequest::with(['patient', 'visit', 'admission', 'emergencyCase', 'requestedBy', 'approvedBy', 'crossmatches.unit', 'issues.unit'])
                ->when($request->status, fn ($q, $v) => $q->where('status', strtoupper($v)))
                ->when($request->blood_group, fn ($q, $v) => $q->where('blood_group', $v))
                ->latest('requested_at')
                ->paginate(25)
                ->withQueryString(),
            'visits' => Visit::with('patient')->latest('visit_date')->limit(75)->get(),
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
            'priority' => ['nullable', 'in:ROUTINE,URGENT,EMERGENCY'],
            'needed_at' => ['nullable', 'date'],
            'hb_level' => ['nullable', 'string', 'max:50'],
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'indication' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->requests->createForVisit(Visit::findOrFail($data['visit_id']), $data, $request->user());

        return back()->with('success', 'Blood request created.');
    }

    public function approve(Request $request, BloodRequest $bloodRequest)
    {
        $this->requests->approve($bloodRequest, $request->user());

        return back()->with('success', 'Blood request approved.');
    }
}
