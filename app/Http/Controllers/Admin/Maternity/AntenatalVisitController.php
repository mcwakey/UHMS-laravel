<?php

namespace App\Http\Controllers\Admin\Maternity;

use App\Enums\AntenatalDangerSign;
use App\Enums\AntenatalReferralType;
use App\Enums\AntenatalRiskFlag;
use App\Enums\AntenatalVisitStatus;
use App\Enums\DepartmentType;
use App\Enums\FetalPresentation;
use App\Enums\UrineGlucoseResult;
use App\Enums\UrineProteinResult;
use App\Http\Controllers\Controller;
use App\Models\AntenatalVisit;
use App\Models\Department;
use App\Models\MaternityCase;
use App\Models\PregnancyProfile;
use App\Models\Ward;
use App\Services\Maternity\AntenatalOverviewService;
use App\Services\Maternity\AntenatalRiskAssessmentService;
use App\Services\Maternity\AntenatalVisitService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AntenatalVisitController extends Controller
{
    public function __construct(
        private AntenatalVisitService $visits,
        private AntenatalOverviewService $overview,
        private AntenatalRiskAssessmentService $riskAssessment,
    ) {}

    public function index(PregnancyProfile $pregnancyProfile)
    {
        return view('maternity.antenatal.index', [
            'profile' => $pregnancyProfile->load(['patient', 'visit', 'admission', 'department']),
            'ancOverview' => $this->overview->forProfile($pregnancyProfile),
            'visits' => $pregnancyProfile->antenatalVisits()->with(['recordedBy', 'maternityCase'])->paginate(20),
        ]);
    }

    public function create(PregnancyProfile $pregnancyProfile)
    {
        return view('maternity.antenatal.create', $this->formData($pregnancyProfile));
    }

    public function store(Request $request, PregnancyProfile $pregnancyProfile)
    {
        $visit = $this->visits->create($pregnancyProfile, $request->validate($this->rules($pregnancyProfile)), $request->user());

        return redirect()
            ->route('admin.maternity.antenatal.show', $visit)
            ->with('success', __('maternity.anc_visit_recorded'));
    }

    public function show(AntenatalVisit $antenatalVisit)
    {
        $antenatalVisit->load($this->visits->relations());

        return view('maternity.antenatal.show', [
            'ancVisit' => $antenatalVisit,
            'riskAssessment' => $this->riskAssessment->assess($antenatalVisit),
            'wards' => Ward::active()
                ->whereHas('department', fn ($query) => $query->where('type', DepartmentType::MATERNITY->value))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function edit(AntenatalVisit $antenatalVisit)
    {
        return view('maternity.antenatal.edit', $this->formData($antenatalVisit->pregnancyProfile, $antenatalVisit));
    }

    public function update(Request $request, AntenatalVisit $antenatalVisit)
    {
        $this->visits->update($antenatalVisit, $request->validate($this->rules($antenatalVisit->pregnancyProfile, $antenatalVisit)), $request->user());

        return redirect()
            ->route('admin.maternity.antenatal.show', $antenatalVisit)
            ->with('success', __('maternity.anc_visit_updated'));
    }

    public function cancel(Request $request, AntenatalVisit $antenatalVisit)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);
        $this->visits->cancel($antenatalVisit, $data['reason'] ?? null, $request->user());

        return redirect()
            ->route('admin.maternity.antenatal.show', $antenatalVisit)
            ->with('success', __('maternity.anc_visit_cancelled'));
    }

    public function referral(Request $request, AntenatalVisit $antenatalVisit)
    {
        $this->visits->recordReferral($antenatalVisit, $request->validate([
            'referral_type' => ['required', Rule::in(array_column(AntenatalReferralType::cases(), 'value'))],
            'referral_reason' => ['nullable', 'string', 'max:3000'],
        ]), $request->user());

        return redirect()
            ->route('admin.maternity.antenatal.show', $antenatalVisit)
            ->with('success', __('maternity.anc_referral_recorded'));
    }

    public function admissionRequest(Request $request, AntenatalVisit $antenatalVisit)
    {
        $admissionRequest = $this->visits->createAdmissionRequest($antenatalVisit, $request->validate([
            'requested_ward_id' => ['nullable', 'exists:wards,id'],
            'priority' => ['nullable', 'string', 'max:50'],
            'provisional_diagnosis' => ['nullable', 'string', 'max:1000'],
            'clinical_summary' => ['nullable', 'string', 'max:3000'],
        ]), $request->user());

        return redirect()
            ->route('admin.admissions.requests.show', $admissionRequest)
            ->with('success', __('maternity.anc_admission_request_created'));
    }

    private function formData(PregnancyProfile $profile, ?AntenatalVisit $ancVisit = null): array
    {
        $profile->load(['patient', 'maternityCases']);

        return [
            'profile' => $profile,
            'ancVisit' => $ancVisit,
            'maternityCases' => MaternityCase::where('pregnancy_profile_id', $profile->id)->open()->latest('opened_at')->get(),
            'departments' => Department::active()->where('type', DepartmentType::MATERNITY->value)->orderBy('name')->get(),
            'statuses' => AntenatalVisitStatus::cases(),
            'referralTypes' => AntenatalReferralType::cases(),
            'dangerSigns' => AntenatalDangerSign::cases(),
            'riskFlags' => AntenatalRiskFlag::cases(),
            'presentations' => FetalPresentation::cases(),
            'urineProteinResults' => UrineProteinResult::cases(),
            'urineGlucoseResults' => UrineGlucoseResult::cases(),
            'supplementOptions' => ['iron_folate', 'calcium', 'multivitamin', 'other'],
            'immunisationOptions' => ['tetanus_diphtheria_1', 'tetanus_diphtheria_2', 'malaria_prevention', 'other'],
        ];
    }

    private function rules(PregnancyProfile $profile, ?AntenatalVisit $ancVisit = null): array
    {
        return [
            'maternity_case_id' => ['nullable', Rule::exists('maternity_cases', 'id')->where('pregnancy_profile_id', $profile->id)],
            'visit_id' => ['nullable', 'exists:visits,id'],
            'admission_id' => ['nullable', 'exists:admissions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'visit_number' => ['nullable', 'integer', 'min:1', 'max:100'],
            'visit_date' => ['required', 'date'],
            'gestational_age_weeks' => ['nullable', 'integer', 'min:0', 'max:45'],
            'gestational_age_days' => ['nullable', 'integer', 'min:0', 'max:6'],
            'weight_kg' => ['nullable', 'numeric', 'min:20', 'max:250'],
            'blood_pressure_systolic' => ['nullable', 'integer', 'min:40', 'max:260'],
            'blood_pressure_diastolic' => ['nullable', 'integer', 'min:20', 'max:180'],
            'pulse' => ['nullable', 'integer', 'min:20', 'max:240'],
            'temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'respiratory_rate' => ['nullable', 'integer', 'min:5', 'max:80'],
            'fundal_height_cm' => ['nullable', 'numeric', 'min:0', 'max:60'],
            'fetal_heart_rate' => ['nullable', 'integer', 'min:40', 'max:240'],
            'fetal_movement' => ['nullable', 'string', 'max:100'],
            'presentation' => ['nullable', Rule::in(array_column(FetalPresentation::cases(), 'value'))],
            'urine_protein' => ['nullable', Rule::in(array_column(UrineProteinResult::cases(), 'value'))],
            'urine_glucose' => ['nullable', Rule::in(array_column(UrineGlucoseResult::cases(), 'value'))],
            'oedema' => ['nullable', 'string', 'max:100'],
            'haemoglobin' => ['nullable', 'numeric', 'min:2', 'max:25'],
            'danger_signs' => ['nullable', 'array'],
            'danger_signs.*' => [Rule::in(array_column(AntenatalDangerSign::cases(), 'value'))],
            'risk_flags' => ['nullable', 'array'],
            'risk_flags.*' => [Rule::in(array_column(AntenatalRiskFlag::cases(), 'value'))],
            'assessment' => ['nullable', 'string', 'max:5000'],
            'plan' => ['nullable', 'string', 'max:5000'],
            'counselling' => ['nullable', 'string', 'max:5000'],
            'supplements' => ['nullable', 'array'],
            'supplements.*' => ['string', 'max:100'],
            'immunisations' => ['nullable', 'array'],
            'immunisations.*' => ['string', 'max:100'],
            'next_visit_date' => ['nullable', 'date'],
            'referral_type' => ['nullable', Rule::in(array_column(AntenatalReferralType::cases(), 'value'))],
            'referral_reason' => ['nullable', 'string', 'max:3000'],
            'status' => ['nullable', Rule::in(array_column(AntenatalVisitStatus::cases(), 'value'))],
        ];
    }
}
