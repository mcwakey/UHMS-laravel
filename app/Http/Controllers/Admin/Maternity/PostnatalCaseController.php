<?php

namespace App\Http\Controllers\Admin\Maternity;

use App\Enums\BleedingStatus;
use App\Services\Maternity\Handoffs\MaternityEmergencyHandoffPresenter;
use App\Enums\BreastfeedingStatus;
use App\Enums\JaundiceStatus;
use App\Enums\MaternityRiskLevel;
use App\Enums\NewbornBreathingStatus;
use App\Enums\NewbornCordStatus;
use App\Enums\NewbornFeedingStatus;
use App\Enums\PostnatalCaseStatus;
use App\Enums\PostnatalMotherDangerSign;
use App\Enums\PostnatalMotherRiskFlag;
use App\Enums\PostnatalNewbornDangerSign;
use App\Enums\PostnatalNewbornRiskFlag;
use App\Enums\UterusCondition;
use App\Enums\WoundCondition;
use App\Http\Controllers\Controller;
use App\Models\DeliveryRecord;
use App\Models\NewbornRecord;
use App\Models\PostnatalCase;
use App\Models\PostnatalMotherObservation;
use App\Models\PostnatalNewbornObservation;
use App\Services\Maternity\MaternityBillingPostingService;
use App\Services\Maternity\PostnatalCaseService;
use App\Services\Maternity\PostnatalMotherObservationService;
use App\Services\Maternity\PostnatalNewbornObservationService;
use App\Services\Maternity\PostnatalOverviewService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostnatalCaseController extends Controller
{
    public function __construct(
        private PostnatalCaseService $cases,
        private PostnatalMotherObservationService $motherObservations,
        private PostnatalNewbornObservationService $newbornObservations,
        private PostnatalOverviewService $overview,
        private MaternityBillingPostingService $billingPosting,
    ) {}

    public function index()
    {
        return view('maternity.postnatal.index', [
            'cases' => PostnatalCase::with(['mother', 'deliveryRecord', 'latestMotherObservation', 'latestNewbornObservation'])
                ->latest('opened_at')
                ->latest('id')
                ->paginate(20),
        ]);
    }

    public function store(Request $request, DeliveryRecord $deliveryRecord)
    {
        $case = $this->cases->openFromDelivery($deliveryRecord, $request->validate($this->caseRules()), $request->user());

        return redirect()
            ->route('admin.maternity.postnatal.show', $case)
            ->with('success', __('maternity.postnatal_case_opened'));
    }

    public function show(PostnatalCase $postnatalCase)
    {
        return view('maternity.postnatal.show', [
            'case' => $postnatalCase->load($this->cases->relations()),
            'emergencyHandoffActions' => app(MaternityEmergencyHandoffPresenter::class)
                ->build($postnatalCase, request()->user()),
            'overview' => $this->overview->forCase($postnatalCase),
            'billingPreviews' => $this->billingPosting->previewManyForSource($postnatalCase),
            'statuses' => PostnatalCaseStatus::cases(),
            'riskLevels' => MaternityRiskLevel::cases(),
        ]);
    }

    public function update(Request $request, PostnatalCase $postnatalCase)
    {
        $this->cases->update($postnatalCase, $request->validate($this->caseRules()), $request->user());

        return redirect()
            ->route('admin.maternity.postnatal.show', $postnatalCase)
            ->with('success', __('maternity.postnatal_case_updated'));
    }

    public function status(Request $request, PostnatalCase $postnatalCase)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['mother_ready', 'newborn_ready', 'ready_for_discharge', 'referral_required'])],
            'referral_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        match ($data['action']) {
            'mother_ready' => $this->cases->markMotherReady($postnatalCase, $request->user()),
            'newborn_ready' => $this->cases->markNewbornReady($postnatalCase, $request->user()),
            'ready_for_discharge' => $this->cases->markReadyForDischarge($postnatalCase, $request->user()),
            'referral_required' => $this->cases->markReferralRequired($postnatalCase, $data['referral_reason'] ?? null, $request->user()),
        };

        return redirect()
            ->route('admin.maternity.postnatal.show', $postnatalCase)
            ->with('success', __('maternity.postnatal_status_updated'));
    }

    public function close(Request $request, PostnatalCase $postnatalCase)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);
        $this->cases->close($postnatalCase, PostnatalCaseStatus::CLOSED, $data['reason'] ?? null, $request->user());

        return redirect()
            ->route('admin.maternity.postnatal.show', $postnatalCase)
            ->with('success', __('maternity.postnatal_case_closed'));
    }

    public function cancel(Request $request, PostnatalCase $postnatalCase)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);
        $this->cases->close($postnatalCase, PostnatalCaseStatus::CANCELLED, $data['reason'] ?? null, $request->user());

        return redirect()
            ->route('admin.maternity.postnatal.show', $postnatalCase)
            ->with('success', __('maternity.postnatal_case_cancelled'));
    }

    public function createMotherObservation(PostnatalCase $postnatalCase)
    {
        return view('maternity.postnatal.mother-observations.create', $this->motherFormData($postnatalCase));
    }

    public function storeMotherObservation(Request $request, PostnatalCase $postnatalCase)
    {
        $observation = $this->motherObservations->create($postnatalCase, $request->validate($this->motherObservationRules()), $request->user());

        return redirect()
            ->route('admin.maternity.postnatal.mother-observations.show', $observation)
            ->with('success', __('maternity.mother_observation_recorded'));
    }

    public function showMotherObservation(PostnatalMotherObservation $observation)
    {
        return view('maternity.postnatal.mother-observations.show', [
            'observation' => $observation->load($this->motherObservations->relations()),
        ]);
    }

    public function editMotherObservation(PostnatalMotherObservation $observation)
    {
        return view('maternity.postnatal.mother-observations.edit', $this->motherFormData($observation->postnatalCase, $observation));
    }

    public function updateMotherObservation(Request $request, PostnatalMotherObservation $observation)
    {
        $this->motherObservations->update($observation, $request->validate($this->motherObservationRules()), $request->user());

        return redirect()
            ->route('admin.maternity.postnatal.mother-observations.show', $observation)
            ->with('success', __('maternity.mother_observation_updated'));
    }

    public function cancelMotherObservation(Request $request, PostnatalMotherObservation $observation)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);
        $this->motherObservations->cancel($observation, $data['reason'] ?? null, $request->user());

        return redirect()
            ->route('admin.maternity.postnatal.show', $observation->postnatalCase)
            ->with('success', __('maternity.mother_observation_cancelled'));
    }

    public function createNewbornObservation(PostnatalCase $postnatalCase, NewbornRecord $newbornRecord)
    {
        return view('maternity.postnatal.newborn-observations.create', $this->newbornFormData($postnatalCase, $newbornRecord));
    }

    public function storeNewbornObservation(Request $request, PostnatalCase $postnatalCase, NewbornRecord $newbornRecord)
    {
        $observation = $this->newbornObservations->create($postnatalCase, $newbornRecord, $request->validate($this->newbornObservationRules()), $request->user());

        return redirect()
            ->route('admin.maternity.postnatal.newborn-observations.show', $observation)
            ->with('success', __('maternity.newborn_observation_recorded'));
    }

    public function showNewbornObservation(PostnatalNewbornObservation $observation)
    {
        return view('maternity.postnatal.newborn-observations.show', [
            'observation' => $observation->load($this->newbornObservations->relations()),
        ]);
    }

    public function editNewbornObservation(PostnatalNewbornObservation $observation)
    {
        return view('maternity.postnatal.newborn-observations.edit', $this->newbornFormData($observation->postnatalCase, $observation->newbornRecord, $observation));
    }

    public function updateNewbornObservation(Request $request, PostnatalNewbornObservation $observation)
    {
        $this->newbornObservations->update($observation, $request->validate($this->newbornObservationRules()), $request->user());

        return redirect()
            ->route('admin.maternity.postnatal.newborn-observations.show', $observation)
            ->with('success', __('maternity.newborn_observation_updated'));
    }

    public function cancelNewbornObservation(Request $request, PostnatalNewbornObservation $observation)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);
        $this->newbornObservations->cancel($observation, $data['reason'] ?? null, $request->user());

        return redirect()
            ->route('admin.maternity.postnatal.show', $observation->postnatalCase)
            ->with('success', __('maternity.newborn_observation_cancelled'));
    }

    private function motherFormData(PostnatalCase $case, ?PostnatalMotherObservation $observation = null): array
    {
        return [
            'case' => $case->load(['mother', 'deliveryRecord']),
            'observation' => $observation,
            'bleedingStatuses' => BleedingStatus::cases(),
            'uterusConditions' => UterusCondition::cases(),
            'woundConditions' => WoundCondition::cases(),
            'breastfeedingStatuses' => BreastfeedingStatus::cases(),
            'dangerSigns' => PostnatalMotherDangerSign::cases(),
            'riskFlags' => PostnatalMotherRiskFlag::cases(),
        ];
    }

    private function newbornFormData(PostnatalCase $case, NewbornRecord $newborn, ?PostnatalNewbornObservation $observation = null): array
    {
        return [
            'case' => $case->load(['mother', 'deliveryRecord']),
            'newborn' => $newborn,
            'observation' => $observation,
            'feedingStatuses' => NewbornFeedingStatus::cases(),
            'breathingStatuses' => NewbornBreathingStatus::cases(),
            'cordStatuses' => NewbornCordStatus::cases(),
            'jaundiceStatuses' => JaundiceStatus::cases(),
            'dangerSigns' => PostnatalNewbornDangerSign::cases(),
            'riskFlags' => PostnatalNewbornRiskFlag::cases(),
        ];
    }

    private function caseRules(): array
    {
        return [
            'risk_level' => ['nullable', Rule::in(array_column(MaternityRiskLevel::cases(), 'value'))],
            'follow_up_date' => ['nullable', 'date'],
            'follow_up_instructions' => ['nullable', 'string', 'max:3000'],
            'referral_required' => ['nullable', 'boolean'],
            'referral_reason' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function motherObservationRules(): array
    {
        return [
            'observed_at' => ['nullable', 'date'],
            'blood_pressure_systolic' => ['nullable', 'integer', 'min:40', 'max:260'],
            'blood_pressure_diastolic' => ['nullable', 'integer', 'min:20', 'max:180'],
            'pulse' => ['nullable', 'integer', 'min:20', 'max:240'],
            'temperature' => ['nullable', 'numeric', 'min:25', 'max:45'],
            'respiratory_rate' => ['nullable', 'integer', 'min:5', 'max:80'],
            'bleeding_status' => ['nullable', Rule::in(array_column(BleedingStatus::cases(), 'value'))],
            'uterus_condition' => ['nullable', Rule::in(array_column(UterusCondition::cases(), 'value'))],
            'pain_score' => ['nullable', 'integer', 'min:0', 'max:10'],
            'wound_condition' => ['nullable', Rule::in(array_column(WoundCondition::cases(), 'value'))],
            'breastfeeding_status' => ['nullable', Rule::in(array_column(BreastfeedingStatus::cases(), 'value'))],
            'mobility' => ['nullable', 'string', 'max:255'],
            'urination' => ['nullable', 'string', 'max:255'],
            'mental_wellbeing_note' => ['nullable', 'string', 'max:3000'],
            'danger_signs' => ['nullable', 'array'],
            'danger_signs.*' => [Rule::in(array_column(PostnatalMotherDangerSign::cases(), 'value'))],
            'risk_flags' => ['nullable', 'array'],
            'risk_flags.*' => [Rule::in(array_column(PostnatalMotherRiskFlag::cases(), 'value'))],
            'assessment' => ['nullable', 'string', 'max:5000'],
            'plan' => ['nullable', 'string', 'max:5000'],
            'counselling' => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function newbornObservationRules(): array
    {
        return [
            'observed_at' => ['nullable', 'date'],
            'temperature' => ['nullable', 'numeric', 'min:25', 'max:45'],
            'weight_kg' => ['nullable', 'numeric', 'min:0.2', 'max:8'],
            'feeding_status' => ['nullable', Rule::in(array_column(NewbornFeedingStatus::cases(), 'value'))],
            'breathing_status' => ['nullable', Rule::in(array_column(NewbornBreathingStatus::cases(), 'value'))],
            'cord_status' => ['nullable', Rule::in(array_column(NewbornCordStatus::cases(), 'value'))],
            'jaundice_status' => ['nullable', Rule::in(array_column(JaundiceStatus::cases(), 'value'))],
            'stooling' => ['nullable', 'string', 'max:255'],
            'urination' => ['nullable', 'string', 'max:255'],
            'activity' => ['nullable', 'string', 'max:255'],
            'danger_signs' => ['nullable', 'array'],
            'danger_signs.*' => [Rule::in(array_column(PostnatalNewbornDangerSign::cases(), 'value'))],
            'risk_flags' => ['nullable', 'array'],
            'risk_flags.*' => [Rule::in(array_column(PostnatalNewbornRiskFlag::cases(), 'value'))],
            'immunisation_note' => ['nullable', 'string', 'max:3000'],
            'assessment' => ['nullable', 'string', 'max:5000'],
            'plan' => ['nullable', 'string', 'max:5000'],
            'counselling' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
