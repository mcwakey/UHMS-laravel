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
        ]);

        $this->issues->issue($bloodRequest, BloodUnit::findOrFail($data['blood_unit_id']), $request->user(), $data);

        return back()->with('success', 'Blood unit issued.');
    }

    public function transfuse(Request $request, BloodIssue $bloodIssue)
    {
        $data = $request->validate([
            'transfused_at' => ['nullable', 'date'],
            'reaction_notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->issues->recordTransfusion($bloodIssue, $request->user(), $data);

        return back()->with('success', 'Transfusion outcome recorded.');
    }
}
