<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BloodIssue;
use App\Models\BloodRequest;
use App\Models\BloodUnit;
use App\Services\BloodIssueService;
use Illuminate\Http\Request;

class BloodIssueController extends Controller
{
    public function __construct(private BloodIssueService $issues) {}

    public function store(Request $request, BloodRequest $bloodRequest)
    {
        $data = $request->validate([
            'blood_unit_id' => ['required', 'exists:blood_units,id'],
            'received_by' => ['nullable', 'exists:users,id'],
            'received_by_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'emergency' => ['nullable', 'boolean'],
            'emergency_release_type' => ['nullable', 'string', 'max:60'],
            'emergency_release_reason' => ['nullable', 'string'],
            'authorized_by' => ['nullable', 'exists:users,id'],
        ]);

        if ($request->boolean('emergency') && ! $request->user()->can('blood_bank.emergency_release')) {
            abort(403, 'You are not permitted to perform an emergency blood release.');
        }

        $this->issues->issue(
            $bloodRequest,
            BloodUnit::findOrFail($data['blood_unit_id']),
            $request->user(),
            array_merge($data, ['emergency' => $request->boolean('emergency')])
        );

        return back()->with('success', 'Blood unit issued.');
    }

    public function transfuse(Request $request, BloodIssue $bloodIssue)
    {
        $data = $request->validate([
            'transfusion_started_at' => ['nullable', 'date'],
            'transfusion_completed_at' => ['nullable', 'date'],
            'transfused_at' => ['nullable', 'date'],
            'witnessed_by' => ['nullable', 'exists:users,id'],
            'reaction_occurred' => ['nullable', 'boolean'],
            'reaction_type' => ['nullable', 'in:FEVER,CHILLS,RASH,BREATHING_DIFFICULTY,HYPOTENSION,HEMOLYTIC_REACTION_SUSPECTED,ANAPHYLAXIS,OTHER'],
            'reaction_notes' => ['nullable', 'string'],
            'outcome' => ['nullable', 'in:COMPLETED,STOPPED_DUE_TO_REACTION,PARTIALLY_TRANSFUSED,CANCELLED'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['reaction_occurred'] = $request->boolean('reaction_occurred');
        $this->issues->recordTransfusion($bloodIssue, $request->user(), $data);

        return back()->with('success', 'Transfusion outcome recorded.');
    }

    public function reaction(Request $request, BloodIssue $bloodIssue)
    {
        $data = $request->validate([
            'reaction_type' => ['required', 'in:FEVER,CHILLS,RASH,BREATHING_DIFFICULTY,HYPOTENSION,HEMOLYTIC_REACTION_SUSPECTED,ANAPHYLAXIS,OTHER'],
            'reaction_notes' => ['nullable', 'string'],
            'outcome' => ['nullable', 'in:COMPLETED,STOPPED_DUE_TO_REACTION,PARTIALLY_TRANSFUSED,CANCELLED'],
        ]);

        $this->issues->recordReaction($bloodIssue, $request->user(), $data);

        return back()->with('success', 'Transfusion reaction recorded.');
    }
}
