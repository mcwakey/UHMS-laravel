<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BloodDonor;
use App\Models\Patient;
use App\Services\BloodDonorScreeningService;
use Illuminate\Http\Request;

class BloodDonorController extends Controller
{
    public function __construct(private BloodDonorScreeningService $screening) {}

    public function index(Request $request)
    {
        $donors = BloodDonor::query()
            ->with(['patient', 'latestScreening'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('donor_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->blood_group, fn ($q, $v) => $q->where('blood_group', $v))
            ->when($request->screening_status, fn ($q, $v) => $q->where('screening_status', strtoupper($v)))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('blood-bank.donors', [
            'donors' => $donors,
            'filters' => $request->only(['search', 'blood_group', 'screening_status']),
            'patients' => Patient::orderByDesc('id')->limit(50)->get(['id', 'patient_number', 'first_name', 'last_name']),
            'questionnaireRisk' => config('blood_bank.questionnaire_risk'),
        ]);
    }

    /** Donor profile + WHO screening workspace (questionnaire / assessment / eligibility). */
    public function show(BloodDonor $donor)
    {
        $donor->load([
            'latestScreening.assessedBy', 'latestScreening.reviewedBy', 'latestScreening.questionnaireBy',
            'registeredBy', 'donations' => fn ($q) => $q->with('unit')->latest('donation_date'),
        ]);

        $screening = $donor->latestScreening;
        $suggestion = $screening
            ? $this->screening->suggestEligibility($screening)
            : ['decision' => null, 'flags' => []];

        return view('blood-bank.donor-profile', [
            'donor' => $donor,
            'screening' => $screening,
            'suggestion' => $suggestion,
            'questionnaireRisk' => config('blood_bank.questionnaire_risk'),
            'donorConfig' => config('blood_bank.donor'),
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
            'screening_status' => BloodDonor::SCREENING_REGISTERED,
            'registered_by' => $request->user()->id,
        ]));

        return back()->with('success', __('messages.blood_bank.donor_registered'));
    }

    public function questionnaire(Request $request, BloodDonor $donor)
    {
        $data = $request->validate([
            // Nullable, not required: unticking every box (a donor with no risk
            // factors) is valid and must be allowed to save.
            'questionnaire' => ['nullable', 'array'],
            'consent_donate' => ['nullable', 'boolean'],
            'consent_testing' => ['nullable', 'boolean'],
            'consent_contact' => ['nullable', 'boolean'],
        ]);

        // Normalise checkbox values to booleans (empty when nothing is ticked).
        $answers = collect($data['questionnaire'] ?? [])->map(fn ($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN))->all();

        $screening = $this->screening->startScreening($donor, $request->user());
        $this->screening->recordQuestionnaire($screening, $answers, $request->user(), [
            'consent_donate' => $request->boolean('consent_donate'),
            'consent_testing' => $request->boolean('consent_testing'),
            'consent_contact' => $request->boolean('consent_contact'),
        ]);

        return back()->with('success', __('messages.blood_bank.donor_questionnaire_saved'));
    }

    public function assessment(Request $request, BloodDonor $donor)
    {
        $data = $request->validate([
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'temperature_c' => ['nullable', 'numeric'],
            'pulse' => ['nullable', 'integer', 'min:0'],
            'bp_systolic' => ['nullable', 'integer', 'min:0'],
            'bp_diastolic' => ['nullable', 'integer', 'min:0'],
            'hemoglobin' => ['nullable', 'numeric', 'min:0'],
            'general_appearance' => ['nullable', 'string', 'max:255'],
            'venous_access' => ['nullable', 'string', 'max:255'],
            'fitness_notes' => ['nullable', 'string'],
        ]);

        $screening = $this->screening->startScreening($donor, $request->user());
        $this->screening->recordPhysicalAssessment($screening, $data, $request->user());

        return back()->with('success', __('messages.blood_bank.physical_assessment_saved'));
    }

    public function eligibility(Request $request, BloodDonor $donor)
    {
        $data = $request->validate([
            'decision' => ['nullable', 'in:ELIGIBLE,TEMPORARILY_DEFERRED,PERMANENTLY_DEFERRED,NEEDS_REVIEW'],
            'deferral_reason' => ['nullable', 'string'],
            'deferral_until' => ['nullable', 'date'],
            'override_reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'override' => ['nullable', 'boolean'],
        ]);

        $override = $request->boolean('override');
        if ($override && ! $request->user()->can('blood_bank.donor.override_eligibility')) {
            abort(403, 'You are not permitted to override donor eligibility.');
        }

        $screening = $this->screening->startScreening($donor, $request->user());
        $this->screening->decideEligibility($screening, $request->user(), $data['decision'] ?? null, $data, $override);

        return back()->with('success', __('messages.blood_bank.donor_eligibility_saved'));
    }
}
