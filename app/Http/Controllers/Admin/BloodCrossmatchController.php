<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BloodCrossmatch;
use App\Models\BloodRequest;
use App\Models\BloodUnit;
use App\Services\BloodCrossmatchService;
use Illuminate\Http\Request;

class BloodCrossmatchController extends Controller
{
    public function __construct(private BloodCrossmatchService $crossmatches) {}

    public function store(Request $request, BloodRequest $bloodRequest)
    {
        $data = $request->validate([
            'blood_unit_id' => ['required', 'exists:blood_units,id'],
            'method' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'emergency_override' => ['nullable', 'boolean'],
            'override_reason' => ['nullable', 'string'],
        ]);

        $emergencyOverride = $request->boolean('emergency_override');
        if ($emergencyOverride && ! $request->user()->can('blood_bank.compatibility.override')) {
            abort(403, 'You are not permitted to override blood compatibility.');
        }

        $crossmatch = $this->crossmatches->perform(
            $bloodRequest,
            BloodUnit::findOrFail($data['blood_unit_id']),
            $request->user(),
            $data['method'] ?? null,
            $data['notes'] ?? null,
            $emergencyOverride,
            $data['override_reason'] ?? null,
        );

        return back()->with(
            $crossmatch->result === BloodCrossmatch::RESULT_COMPATIBLE ? 'success' : 'error',
            'Crossmatch result: '.$crossmatch->result.' ('.$crossmatch->compatibility_status.')'
        );
    }

    public function verify(Request $request, BloodCrossmatch $crossmatch)
    {
        $this->crossmatches->verify($crossmatch, $request->user());

        return back()->with('success', __('messages.blood_bank.crossmatch_verified'));
    }
}
