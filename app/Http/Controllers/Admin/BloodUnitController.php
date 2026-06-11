<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BloodStorageLocation;
use App\Models\BloodUnit;
use Illuminate\Http\Request;

class BloodUnitController extends Controller
{
    public function index(Request $request)
    {
        $units = BloodUnit::with(['donor', 'donation', 'storageLocation', 'reservedForRequest.patient'])
            ->when($request->blood_group, fn ($q, $v) => $q->where('blood_group', $v))
            ->when($request->component_type, fn ($q, $v) => $q->where('component_type', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', strtoupper($v)))
            ->orderBy('expiry_date')
            ->paginate(30)
            ->withQueryString();

        return view('blood-bank.units', [
            'units' => $units,
            'locations' => BloodStorageLocation::active()->orderBy('name')->get(),
            'filters' => $request->only(['blood_group', 'component_type', 'status']),
        ]);
    }

    public function discard(Request $request, BloodUnit $unit)
    {
        $data = $request->validate([
            'discard_reason' => ['required', 'string'],
        ]);

        if (in_array($unit->status, [BloodUnit::STATUS_ISSUED, BloodUnit::STATUS_TRANSFUSED], true)) {
            return back()->with('error', __('messages.blood_bank.cannot_discard_issued'));
        }

        $unit->update([
            'status' => BloodUnit::STATUS_DISCARDED,
            'discarded_at' => now(),
            'discarded_by' => $request->user()->id,
            'discard_reason' => $data['discard_reason'],
        ]);

        return back()->with('success', __('messages.blood_bank.unit_discarded'));
    }
}
