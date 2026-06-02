<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BloodDonation;
use App\Models\BloodDonationTest;
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
            'donations' => BloodDonation::with(['donor', 'unit.storageLocation', 'collectedBy', 'screenedBy', 'tests.performedBy', 'tests.verifiedBy'])
                ->when($request->screening_status, fn ($q, $v) => $q->where('screening_status', strtoupper($v)))
                ->latest('donation_date')
                ->paginate(30)
                ->withQueryString(),
            'donors' => BloodDonor::where('screening_status', BloodDonor::SCREENING_ELIGIBLE)
                ->orderBy('first_name')->limit(100)->get(),
            'locations' => BloodStorageLocation::active()->orderBy('name')->get(),
            'screeningTests' => config('blood_bank.screening_tests'),
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
            'override' => ['nullable', 'boolean'],
            'override_reason' => ['nullable', 'string'],
        ]);

        $override = $request->boolean('override');
        if ($override && ! $request->user()->can('blood_bank.donor.override_eligibility')) {
            abort(403, 'You are not permitted to override donor eligibility.');
        }

        $this->donations->recordDonation(BloodDonor::findOrFail($data['donor_id']), $data, $request->user(), $override);

        return back()->with('success', 'Donation recorded and unit quarantined pending screening.');
    }

    public function recordTest(Request $request, BloodDonation $donation)
    {
        $data = $request->validate([
            'test_code' => ['required', 'string', 'max:50'],
            'result' => ['required', 'in:NEGATIVE,POSITIVE,REACTIVE,NON_REACTIVE,INCONCLUSIVE,NOT_DONE'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->donations->recordTest($donation, $data['test_code'], $data['result'], $request->user(), $data['notes'] ?? null);

        return back()->with('success', 'Screening test result saved.');
    }

    public function verifyTest(Request $request, BloodDonationTest $test)
    {
        $this->donations->verifyTest($test, $request->user());

        return back()->with('success', 'Screening test verified.');
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
