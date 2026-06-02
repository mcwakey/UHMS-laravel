<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BloodDonation;
use App\Models\BloodDonor;
use App\Models\BloodStorageLocation;
use App\Services\BloodDonationService;
use Illuminate\Http\Request;

class BloodDonationController extends Controller
{
    public function __construct(private BloodDonationService $donations) {}

    public function index(Request $request)
    {
        return view('blood-bank.donations', [
            'donations' => BloodDonation::with(['donor', 'unit.storageLocation', 'collectedBy', 'screenedBy'])
                ->when($request->screening_status, fn ($q, $v) => $q->where('screening_status', strtoupper($v)))
                ->latest('donation_date')
                ->paginate(30)
                ->withQueryString(),
            'donors' => BloodDonor::orderBy('first_name')->limit(100)->get(),
            'locations' => BloodStorageLocation::active()->orderBy('name')->get(),
            'filters' => $request->only(['screening_status']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'donor_id' => ['required', 'exists:blood_donors,id'],
            'donation_date' => ['nullable', 'date'],
            'donation_type' => ['nullable', 'string', 'max:50'],
            'component_type' => ['nullable', 'string', 'max:50'],
            'volume_ml' => ['nullable', 'integer', 'min:1'],
            'blood_group' => ['required', 'string', 'max:5'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
            'storage_location_id' => ['nullable', 'exists:blood_storage_locations,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->donations->recordDonation(BloodDonor::findOrFail($data['donor_id']), $data, $request->user());

        return back()->with('success', 'Donation recorded and unit quarantined pending screening.');
    }

    public function updateScreening(Request $request, BloodDonation $donation)
    {
        $data = $request->validate([
            'screening_status' => ['required', 'in:PASSED,FAILED,INCONCLUSIVE,PENDING'],
            'screening_notes' => ['nullable', 'string'],
        ]);

        $this->donations->updateScreening($donation, $data['screening_status'], $request->user(), $data['screening_notes'] ?? null);

        return back()->with('success', 'Donation screening updated.');
    }
}
