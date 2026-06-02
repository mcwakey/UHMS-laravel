<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BloodDonor;
use App\Models\Patient;
use Illuminate\Http\Request;

class BloodDonorController extends Controller
{
    public function index(Request $request)
    {
        $donors = BloodDonor::query()
            ->with('patient')
            ->when($request->search, function ($q, $search) {
                $q->where('donor_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->when($request->blood_group, fn ($q, $v) => $q->where('blood_group', $v))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('blood-bank.donors', [
            'donors' => $donors,
            'filters' => $request->only(['search', 'blood_group']),
            'patients' => Patient::orderByDesc('id')->limit(50)->get(['id', 'patient_number', 'first_name', 'last_name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['nullable', 'exists:patients,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'blood_group' => ['nullable', 'string', 'max:5'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ]);

        BloodDonor::create(array_merge($data, [
            'donor_number' => BloodDonor::generateDonorNumber(),
            'status' => BloodDonor::STATUS_ACTIVE,
            'registered_by' => $request->user()->id,
        ]));

        return back()->with('success', 'Blood donor registered.');
    }
}
