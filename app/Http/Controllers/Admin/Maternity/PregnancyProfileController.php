<?php

namespace App\Http\Controllers\Admin\Maternity;

use App\Enums\BloodGroup;
use App\Enums\DepartmentType;
use App\Enums\PregnancyProfileStatus;
use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Department;
use App\Models\Patient;
use App\Models\PregnancyProfile;
use App\Models\Visit;
use App\Services\Maternity\MaternityOverviewService;
use App\Services\Maternity\PregnancyProfileService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PregnancyProfileController extends Controller
{
    public function __construct(
        private PregnancyProfileService $profiles,
        private MaternityOverviewService $overview,
    ) {}

    public function index(Request $request)
    {
        $profiles = PregnancyProfile::with(['patient', 'visit', 'admission', 'department'])
            ->when($request->filled('status'), fn ($q) => $q->where('profile_status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->search;
                $q->whereHas('patient', fn ($p) => $p->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('patient_number', 'like', "%{$term}%"));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('maternity.pregnancies.index', [
            'profiles' => $profiles,
            'statuses' => PregnancyProfileStatus::cases(),
        ]);
    }

    public function create()
    {
        return view('maternity.pregnancies.create', $this->formData());
    }

    public function store(Request $request)
    {
        $profile = $this->profiles->create($request->validate($this->rules()), $request->user());

        return redirect()
            ->route('admin.maternity.pregnancies.show', $profile)
            ->with('success', __('maternity.profile_created'));
    }

    public function show(PregnancyProfile $pregnancyProfile)
    {
        $pregnancyProfile->load([
            'patient.activeAdmission',
            'visit.visitInsurance.insuranceProvider',
            'visit.visitInsurance.insuranceTier',
            'visit.latestInvoice.items.department',
            'visit.latestInvoice.items.serviceCatalog.department',
            'admission.visit.visitInsurance.insuranceProvider',
            'admission.visit.visitInsurance.insuranceTier',
            'admission.visit.latestInvoice.items.department',
            'admission.visit.latestInvoice.items.serviceCatalog.department',
            'admission.bed.ward',
            'department',
            'maternityCases.openedBy',
        ]);

        return view('maternity.pregnancies.show', [
            'profile' => $pregnancyProfile,
            'overview' => $this->overview->forProfile($pregnancyProfile),
            'maternityDepartments' => Department::active()->where('type', DepartmentType::MATERNITY->value)->orderBy('name')->get(),
        ]);
    }

    public function edit(PregnancyProfile $pregnancyProfile)
    {
        return view('maternity.pregnancies.edit', $this->formData($pregnancyProfile));
    }

    public function update(Request $request, PregnancyProfile $pregnancyProfile)
    {
        $this->profiles->update($pregnancyProfile, $request->validate($this->rules()), $request->user());

        return redirect()
            ->route('admin.maternity.pregnancies.show', $pregnancyProfile)
            ->with('success', __('maternity.profile_updated'));
    }

    public function status(Request $request, PregnancyProfile $pregnancyProfile)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_column(PregnancyProfileStatus::cases(), 'value'))],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $status = PregnancyProfileStatus::from($data['status']);
        if ($status === PregnancyProfileStatus::HIGH_RISK) {
            $this->profiles->markHighRisk($pregnancyProfile, $data['reason'] ?? null, $request->user());
        } else {
            $this->profiles->close($pregnancyProfile, $status, $data['reason'] ?? null, $request->user());
        }

        return redirect()
            ->route('admin.maternity.pregnancies.show', $pregnancyProfile)
            ->with('success', __('maternity.profile_status_updated'));
    }

    private function formData(?PregnancyProfile $profile = null): array
    {
        return [
            'profile' => $profile,
            'patients' => Patient::orderByDesc('created_at')->limit(100)->get(),
            'visits' => Visit::with('patient')->latest('visit_date')->limit(100)->get(),
            'admissions' => Admission::with('patient')->active()->latest('admission_date')->limit(100)->get(),
            'departments' => Department::active()->where('type', DepartmentType::MATERNITY->value)->orderBy('name')->get(),
            'bloodGroups' => BloodGroup::cases(),
            'statuses' => PregnancyProfileStatus::cases(),
        ];
    }

    private function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'visit_id' => ['nullable', 'exists:visits,id'],
            'admission_id' => ['nullable', 'exists:admissions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'gravida' => ['nullable', 'integer', 'min:0', 'max:30'],
            'para' => ['nullable', 'integer', 'min:0', 'max:30'],
            'abortions' => ['nullable', 'integer', 'min:0', 'max:30'],
            'living_children' => ['nullable', 'integer', 'min:0', 'max:30'],
            'last_menstrual_period' => ['nullable', 'date'],
            'estimated_due_date' => ['nullable', 'date'],
            'gestational_age_weeks' => ['nullable', 'integer', 'min:0', 'max:45'],
            'gestational_age_days' => ['nullable', 'integer', 'min:0', 'max:6'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'rhesus_status' => ['nullable', Rule::in(['positive', 'negative', 'unknown'])],
            'known_risks' => ['nullable', 'string', 'max:3000'],
            'allergies_snapshot' => ['nullable', 'string', 'max:2000'],
            'previous_caesarean' => ['nullable', 'boolean'],
            'previous_postpartum_haemorrhage' => ['nullable', 'boolean'],
            'hypertensive_disorder_risk' => ['nullable', 'boolean'],
            'diabetes_risk' => ['nullable', 'boolean'],
            'multiple_pregnancy' => ['nullable', 'boolean'],
            'profile_status' => ['nullable', Rule::in(array_column(PregnancyProfileStatus::cases(), 'value'))],
        ];
    }
}
