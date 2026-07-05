<?php

namespace App\Http\Controllers\Admin\Maternity;

use App\Enums\DeliveryMode;
use App\Enums\DepartmentType;
use App\Enums\LaborDangerSign;
use App\Enums\LaborEpisodeStatus;
use App\Enums\LaborObservationStatus;
use App\Enums\LaborRiskFlag;
use App\Enums\LaborStage;
use App\Enums\LiquorColour;
use App\Enums\MaternityRiskLevel;
use App\Enums\MembranesStatus;
use App\Enums\UrineGlucoseResult;
use App\Enums\UrineProteinResult;
use App\Http\Controllers\Controller;
use App\Models\AntenatalVisit;
use App\Models\Department;
use App\Models\LaborEpisode;
use App\Models\LaborObservation;
use App\Models\MaternityCase;
use App\Models\PregnancyProfile;
use App\Models\Ward;
use App\Services\Maternity\LaborEpisodeService;
use App\Services\Maternity\LaborObservationService;
use App\Services\Maternity\LaborOverviewService;
use App\Services\Maternity\LaborRiskAssessmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LaborEpisodeController extends Controller
{
    public function __construct(
        private LaborEpisodeService $episodes,
        private LaborObservationService $observations,
        private LaborOverviewService $overview,
        private LaborRiskAssessmentService $riskAssessment,
    ) {}

    public function index()
    {
        return view('maternity.labor.index', [
            'episodes' => LaborEpisode::with(['patient', 'pregnancyProfile', 'admission.bed.ward', 'latestObservation', 'latestDeliveryRecord'])
                ->latest('started_at')
                ->latest('id')
                ->paginate(20),
            'overview' => $this->overview->dashboard(),
        ]);
    }

    public function create(PregnancyProfile $pregnancyProfile)
    {
        return view('maternity.labor.create', $this->episodeFormData($pregnancyProfile));
    }

    public function createFromAntenatal(PregnancyProfile $pregnancyProfile, AntenatalVisit $antenatalVisit)
    {
        abort_unless($antenatalVisit->pregnancy_profile_id === $pregnancyProfile->id, 404);

        return view('maternity.labor.create', $this->episodeFormData($pregnancyProfile, null, $antenatalVisit));
    }

    public function store(Request $request, PregnancyProfile $pregnancyProfile)
    {
        $antenatalVisit = null;
        if ($request->filled('antenatal_visit_id')) {
            $antenatalVisit = AntenatalVisit::where('pregnancy_profile_id', $pregnancyProfile->id)->findOrFail($request->integer('antenatal_visit_id'));
        }

        $episode = $this->episodes->start($pregnancyProfile, $request->validate($this->episodeRules($pregnancyProfile)), $request->user(), $antenatalVisit);

        return redirect()
            ->route('admin.maternity.labor.show', $episode)
            ->with('success', __('maternity.labor_episode_started'));
    }

    public function show(LaborEpisode $laborEpisode)
    {
        $laborEpisode->load($this->episodes->relations());

        return view('maternity.labor.show', [
            'episode' => $laborEpisode,
            'laborOverview' => $this->overview->forEpisode($laborEpisode),
            'riskAssessment' => $this->riskAssessment->assess($laborEpisode->latestObservation ?: $laborEpisode),
            'observations' => $laborEpisode->observations()->with('recordedBy')->paginate(15),
            'wards' => Ward::active()
                ->whereHas('department', fn ($query) => $query->where('type', DepartmentType::MATERNITY->value))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function edit(LaborEpisode $laborEpisode)
    {
        return view('maternity.labor.edit', $this->episodeFormData($laborEpisode->pregnancyProfile, $laborEpisode, $laborEpisode->antenatalVisit));
    }

    public function update(Request $request, LaborEpisode $laborEpisode)
    {
        $this->episodes->update($laborEpisode, $request->validate($this->episodeRules($laborEpisode->pregnancyProfile, $laborEpisode)), $request->user());

        return redirect()
            ->route('admin.maternity.labor.show', $laborEpisode)
            ->with('success', __('maternity.labor_episode_updated'));
    }

    public function stage(Request $request, LaborEpisode $laborEpisode)
    {
        $data = $request->validate([
            'labor_stage' => ['required', Rule::in(array_column(LaborStage::cases(), 'value'))],
            'status' => ['nullable', Rule::in(array_column(LaborEpisodeStatus::cases(), 'value'))],
        ]);

        $this->episodes->changeStage(
            $laborEpisode,
            LaborStage::from($data['labor_stage']),
            filled($data['status'] ?? null) ? LaborEpisodeStatus::from($data['status']) : null,
            $request->user()
        );

        return redirect()
            ->route('admin.maternity.labor.show', $laborEpisode)
            ->with('success', __('maternity.labor_stage_updated'));
    }

    public function close(Request $request, LaborEpisode $laborEpisode)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);
        $this->episodes->close($laborEpisode, $data['reason'] ?? null, $request->user());

        return redirect()
            ->route('admin.maternity.labor.show', $laborEpisode)
            ->with('success', __('maternity.labor_episode_closed'));
    }

    public function cancel(Request $request, LaborEpisode $laborEpisode)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);
        $this->episodes->cancel($laborEpisode, $data['reason'] ?? null, $request->user());

        return redirect()
            ->route('admin.maternity.labor.show', $laborEpisode)
            ->with('success', __('maternity.labor_episode_cancelled'));
    }

    public function createObservation(LaborEpisode $laborEpisode)
    {
        return view('maternity.labor.observations.create', $this->observationFormData($laborEpisode));
    }

    public function storeObservation(Request $request, LaborEpisode $laborEpisode)
    {
        $observation = $this->observations->create($laborEpisode, $request->validate($this->observationRules()), $request->user());

        return redirect()
            ->route('admin.maternity.labor.show', $laborEpisode)
            ->with('success', __('maternity.labor_observation_recorded'));
    }

    public function showObservation(LaborObservation $laborObservation)
    {
        return view('maternity.labor.observations.show', [
            'observation' => $laborObservation->load($this->observations->relations()),
            'riskAssessment' => $this->riskAssessment->assess($laborObservation),
        ]);
    }

    public function editObservation(LaborObservation $laborObservation)
    {
        return view('maternity.labor.observations.edit', $this->observationFormData($laborObservation->laborEpisode, $laborObservation));
    }

    public function updateObservation(Request $request, LaborObservation $laborObservation)
    {
        $this->observations->update($laborObservation, $request->validate($this->observationRules()), $request->user());

        return redirect()
            ->route('admin.maternity.labor.show', $laborObservation->laborEpisode)
            ->with('success', __('maternity.labor_observation_updated'));
    }

    public function cancelObservation(Request $request, LaborObservation $laborObservation)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);
        $this->observations->cancel($laborObservation, $data['reason'] ?? null, $request->user());

        return redirect()
            ->route('admin.maternity.labor.show', $laborObservation->laborEpisode)
            ->with('success', __('maternity.labor_observation_cancelled'));
    }

    public function theatreEscalation(LaborEpisode $laborEpisode, Request $request)
    {
        $this->episodes->markEscalation($laborEpisode, 'theatre', $request->user());

        return redirect()
            ->route('admin.maternity.labor.show', $laborEpisode)
            ->with('success', __('maternity.theatre_escalation_marked'));
    }

    public function emergencyEscalation(LaborEpisode $laborEpisode, Request $request)
    {
        $this->episodes->markEscalation($laborEpisode, 'emergency', $request->user());

        return redirect()
            ->route('admin.maternity.labor.show', $laborEpisode)
            ->with('success', __('maternity.emergency_escalation_marked'));
    }

    public function admissionRequest(Request $request, LaborEpisode $laborEpisode)
    {
        $admissionRequest = $this->episodes->createAdmissionRequest($laborEpisode, $request->validate([
            'requested_ward_id' => ['nullable', 'exists:wards,id'],
            'priority' => ['nullable', 'string', 'max:50'],
            'provisional_diagnosis' => ['nullable', 'string', 'max:1000'],
            'clinical_summary' => ['nullable', 'string', 'max:3000'],
        ]), $request->user());

        return redirect()
            ->route('admin.admissions.requests.show', $admissionRequest)
            ->with('success', __('maternity.labor_admission_request_created'));
    }

    private function episodeFormData(PregnancyProfile $profile, ?LaborEpisode $episode = null, ?AntenatalVisit $antenatalVisit = null): array
    {
        $profile->load(['patient.activeAdmission', 'maternityCases', 'antenatalVisits']);

        return [
            'profile' => $profile,
            'episode' => $episode,
            'antenatalVisit' => $antenatalVisit,
            'maternityCases' => MaternityCase::where('pregnancy_profile_id', $profile->id)->open()->latest('opened_at')->get(),
            'antenatalVisits' => $profile->antenatalVisits()->limit(10)->get(),
            'departments' => Department::active()->where('type', DepartmentType::MATERNITY->value)->orderBy('name')->get(),
            'statuses' => LaborEpisodeStatus::cases(),
            'stages' => LaborStage::cases(),
            'membranesStatuses' => MembranesStatus::cases(),
            'liquorColours' => LiquorColour::cases(),
            'deliveryModes' => DeliveryMode::cases(),
        ];
    }

    private function observationFormData(LaborEpisode $episode, ?LaborObservation $observation = null): array
    {
        return [
            'episode' => $episode->load(['patient', 'pregnancyProfile', 'admission.bed.ward']),
            'observation' => $observation,
            'statuses' => LaborObservationStatus::cases(),
            'stages' => LaborStage::cases(),
            'membranesStatuses' => MembranesStatus::cases(),
            'liquorColours' => LiquorColour::cases(),
            'dangerSigns' => LaborDangerSign::cases(),
            'riskFlags' => LaborRiskFlag::cases(),
            'urineProteinResults' => UrineProteinResult::cases(),
            'urineGlucoseResults' => UrineGlucoseResult::cases(),
        ];
    }

    private function episodeRules(PregnancyProfile $profile, ?LaborEpisode $episode = null): array
    {
        return [
            'maternity_case_id' => ['nullable', Rule::exists('maternity_cases', 'id')->where('pregnancy_profile_id', $profile->id)],
            'antenatal_visit_id' => ['nullable', Rule::exists('antenatal_visits', 'id')->where('pregnancy_profile_id', $profile->id)],
            'visit_id' => ['nullable', 'exists:visits,id'],
            'admission_id' => ['nullable', 'exists:admissions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'started_at' => ['nullable', 'date'],
            'labor_onset_at' => ['nullable', 'date'],
            'membranes_status' => ['nullable', Rule::in(array_column(MembranesStatus::cases(), 'value'))],
            'rupture_of_membranes_at' => ['nullable', 'date'],
            'liquor_colour' => ['nullable', Rule::in(array_column(LiquorColour::cases(), 'value'))],
            'presentation' => ['nullable', 'string', 'max:100'],
            'fetal_position' => ['nullable', 'string', 'max:100'],
            'contractions_started_at' => ['nullable', 'date'],
            'labor_stage' => ['nullable', Rule::in(array_column(LaborStage::cases(), 'value'))],
            'status' => ['nullable', Rule::in(array_column(LaborEpisodeStatus::cases(), 'value'))],
            'risk_level' => ['nullable', Rule::in(array_column(MaternityRiskLevel::cases(), 'value'))],
            'referral_source' => ['nullable', 'string', 'max:255'],
            'delivery_mode_planned' => ['nullable', Rule::in(array_column(DeliveryMode::cases(), 'value'))],
            'clinical_summary' => ['nullable', 'string', 'max:5000'],
            'initial_assessment' => ['nullable', 'string', 'max:5000'],
            'complications' => ['nullable', 'array'],
            'complications.*' => ['string', 'max:255'],
        ];
    }

    private function observationRules(): array
    {
        return [
            'observed_at' => ['required', 'date'],
            'labor_stage' => ['nullable', Rule::in(array_column(LaborStage::cases(), 'value'))],
            'cervical_dilation_cm' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'fetal_heart_rate' => ['nullable', 'integer', 'min:40', 'max:240'],
            'contractions_per_10_min' => ['nullable', 'integer', 'min:0', 'max:10'],
            'contraction_duration_seconds' => ['nullable', 'integer', 'min:0', 'max:300'],
            'descent' => ['nullable', 'string', 'max:100'],
            'moulding' => ['nullable', 'string', 'max:100'],
            'caput' => ['nullable', 'string', 'max:100'],
            'membranes_status' => ['nullable', Rule::in(array_column(MembranesStatus::cases(), 'value'))],
            'liquor_colour' => ['nullable', Rule::in(array_column(LiquorColour::cases(), 'value'))],
            'maternal_pulse' => ['nullable', 'integer', 'min:20', 'max:240'],
            'blood_pressure_systolic' => ['nullable', 'integer', 'min:40', 'max:260'],
            'blood_pressure_diastolic' => ['nullable', 'integer', 'min:20', 'max:180'],
            'temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'respiratory_rate' => ['nullable', 'integer', 'min:5', 'max:80'],
            'urine_protein' => ['nullable', Rule::in(array_column(UrineProteinResult::cases(), 'value'))],
            'urine_glucose' => ['nullable', Rule::in(array_column(UrineGlucoseResult::cases(), 'value'))],
            'urine_volume_ml' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'pain_score' => ['nullable', 'integer', 'min:0', 'max:10'],
            'oxytocin' => ['nullable', 'string', 'max:1000'],
            'fluids' => ['nullable', 'string', 'max:1000'],
            'medication' => ['nullable', 'string', 'max:1000'],
            'danger_signs' => ['nullable', 'array'],
            'danger_signs.*' => [Rule::in(array_column(LaborDangerSign::cases(), 'value'))],
            'risk_flags' => ['nullable', 'array'],
            'risk_flags.*' => [Rule::in(array_column(LaborRiskFlag::cases(), 'value'))],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', Rule::in(array_column(LaborObservationStatus::cases(), 'value'))],
        ];
    }
}
