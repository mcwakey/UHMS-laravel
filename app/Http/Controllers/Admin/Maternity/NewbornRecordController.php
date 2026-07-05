<?php

namespace App\Http\Controllers\Admin\Maternity;

use App\Enums\Gender;
use App\Enums\NewbornBreathingStatus;
use App\Enums\NewbornCondition;
use App\Enums\NewbornCordStatus;
use App\Enums\NewbornDangerSign;
use App\Enums\NewbornFeedingStatus;
use App\Enums\NewbornOutcome;
use App\Enums\NewbornRecordStatus;
use App\Enums\NewbornRiskFlag;
use App\Enums\NewbornSex;
use App\Http\Controllers\Controller;
use App\Models\DeliveryRecord;
use App\Models\NewbornRecord;
use App\Models\Patient;
use App\Services\Maternity\NewbornOverviewService;
use App\Services\Maternity\NewbornRecordService;
use App\Services\Maternity\NewbornRiskAssessmentService;
use App\Services\Maternity\PostnatalOverviewService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NewbornRecordController extends Controller
{
    public function __construct(
        private NewbornRecordService $newborns,
        private NewbornOverviewService $overview,
        private NewbornRiskAssessmentService $riskAssessment,
        private PostnatalOverviewService $postnatalOverview,
    ) {}

    public function index(DeliveryRecord $deliveryRecord)
    {
        return view('maternity.newborns.index', [
            'delivery' => $deliveryRecord->load(['patient', 'laborEpisode', 'pregnancyProfile']),
            'newbornOverview' => $this->overview->forDelivery($deliveryRecord),
        ]);
    }

    public function create(DeliveryRecord $deliveryRecord)
    {
        return view('maternity.newborns.create', $this->formData($deliveryRecord));
    }

    public function store(Request $request, DeliveryRecord $deliveryRecord)
    {
        $record = $this->newborns->create($deliveryRecord, $request->validate($this->rules($deliveryRecord)), $request->user());

        return redirect()
            ->route('admin.maternity.newborns.show', $record)
            ->with('success', __('maternity.newborn_record_created'));
    }

    public function bulkCreate(Request $request, DeliveryRecord $deliveryRecord)
    {
        $created = $this->newborns->bulkCreate($deliveryRecord, $request->user());

        return redirect()
            ->route('admin.maternity.deliveries.show', $deliveryRecord)
            ->with('success', __('maternity.newborn_records_bulk_created', ['count' => count($created)]));
    }

    public function show(NewbornRecord $newbornRecord)
    {
        return view('maternity.newborns.show', [
            'record' => $newbornRecord->load($this->newborns->relations()),
            'riskAssessment' => $this->riskAssessment->assess($newbornRecord),
            'postnatalOverview' => $this->postnatalOverview->forNewborn($newbornRecord),
            'patientCandidates' => Patient::query()
                ->whereDate('date_of_birth', $newbornRecord->birth_time?->toDateString() ?? today())
                ->orderBy('first_name')
                ->limit(50)
                ->get(),
        ]);
    }

    public function edit(NewbornRecord $newbornRecord)
    {
        return view('maternity.newborns.edit', $this->formData($newbornRecord->deliveryRecord, $newbornRecord));
    }

    public function update(Request $request, NewbornRecord $newbornRecord)
    {
        $this->newborns->update($newbornRecord, $request->validate($this->rules($newbornRecord->deliveryRecord, $newbornRecord)), $request->user());

        return redirect()
            ->route('admin.maternity.newborns.show', $newbornRecord)
            ->with('success', __('maternity.newborn_record_updated'));
    }

    public function status(Request $request, NewbornRecord $newbornRecord)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_column(NewbornRecordStatus::cases(), 'value'))],
            'outcome' => ['nullable', Rule::in(array_column(NewbornOutcome::cases(), 'value'))],
        ]);

        $this->newborns->changeStatus(
            $newbornRecord,
            NewbornRecordStatus::from($data['status']),
            filled($data['outcome'] ?? null) ? NewbornOutcome::from($data['outcome']) : null,
            $request->user()
        );

        return redirect()
            ->route('admin.maternity.newborns.show', $newbornRecord)
            ->with('success', __('maternity.newborn_status_updated'));
    }

    public function close(Request $request, NewbornRecord $newbornRecord)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([NewbornRecordStatus::CLOSED->value, NewbornRecordStatus::CANCELLED->value, NewbornRecordStatus::DISCHARGED->value, NewbornRecordStatus::DECEASED->value, NewbornRecordStatus::TRANSFERRED->value])],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->newborns->close($newbornRecord, NewbornRecordStatus::from($data['status']), $data['reason'] ?? null, $request->user());

        return redirect()
            ->route('admin.maternity.newborns.show', $newbornRecord)
            ->with('success', __('maternity.newborn_record_closed'));
    }

    public function linkPatient(Request $request, NewbornRecord $newbornRecord)
    {
        $data = $request->validate(['newborn_patient_id' => ['required', 'exists:patients,id']]);
        $patient = Patient::findOrFail($data['newborn_patient_id']);
        $this->newborns->linkPatient($newbornRecord, $patient, $request->user());

        return redirect()
            ->route('admin.maternity.newborns.show', $newbornRecord)
            ->with('success', __('maternity.newborn_patient_linked'));
    }

    public function createPatient(Request $request, NewbornRecord $newbornRecord)
    {
        $patient = $this->newborns->createPatient($newbornRecord, $request->user());

        return redirect()
            ->route('admin.patients.show', $patient)
            ->with('success', __('maternity.newborn_patient_created'));
    }

    private function formData(DeliveryRecord $delivery, ?NewbornRecord $record = null): array
    {
        return [
            'delivery' => $delivery->load(['patient', 'laborEpisode', 'pregnancyProfile']),
            'record' => $record,
            'sexes' => NewbornSex::cases(),
            'feedingStatuses' => NewbornFeedingStatus::cases(),
            'breathingStatuses' => NewbornBreathingStatus::cases(),
            'cordStatuses' => NewbornCordStatus::cases(),
            'conditions' => NewbornCondition::cases(),
            'outcomes' => NewbornOutcome::cases(),
            'statuses' => NewbornRecordStatus::cases(),
            'riskFlags' => NewbornRiskFlag::cases(),
            'dangerSigns' => NewbornDangerSign::cases(),
            'patientGenders' => Gender::cases(),
        ];
    }

    private function rules(DeliveryRecord $delivery, ?NewbornRecord $record = null): array
    {
        return [
            'newborn_patient_id' => ['nullable', 'exists:patients,id'],
            'baby_number' => ['nullable', 'integer', 'min:1', 'max:20'],
            'birth_order' => [
                'nullable',
                'integer',
                'min:1',
                'max:20',
                Rule::unique('newborn_records', 'birth_order')
                    ->where('delivery_record_id', $delivery->id)
                    ->ignore($record?->id),
            ],
            'sex' => ['nullable', Rule::in(array_column(NewbornSex::cases(), 'value'))],
            'birth_time' => ['nullable', 'date'],
            'birth_weight_kg' => ['nullable', 'numeric', 'min:0.2', 'max:8'],
            'length_cm' => ['nullable', 'numeric', 'min:10', 'max:80'],
            'head_circumference_cm' => ['nullable', 'numeric', 'min:10', 'max:60'],
            'apgar_1_min' => ['nullable', 'integer', 'min:0', 'max:10'],
            'apgar_5_min' => ['nullable', 'integer', 'min:0', 'max:10'],
            'apgar_10_min' => ['nullable', 'integer', 'min:0', 'max:10'],
            'cried_at_birth' => ['nullable', 'boolean'],
            'resuscitation_required' => ['nullable', 'boolean'],
            'resuscitation_details' => ['nullable', 'string', 'max:3000'],
            'congenital_concerns' => ['nullable', 'string', 'max:3000'],
            'feeding_status' => ['nullable', Rule::in(array_column(NewbornFeedingStatus::cases(), 'value'))],
            'temperature' => ['nullable', 'numeric', 'min:25', 'max:45'],
            'breathing_status' => ['nullable', Rule::in(array_column(NewbornBreathingStatus::cases(), 'value'))],
            'cord_status' => ['nullable', Rule::in(array_column(NewbornCordStatus::cases(), 'value'))],
            'colour' => ['nullable', 'string', 'max:100'],
            'risk_flags' => ['nullable', 'array'],
            'risk_flags.*' => [Rule::in(array_column(NewbornRiskFlag::cases(), 'value'))],
            'danger_signs' => ['nullable', 'array'],
            'danger_signs.*' => [Rule::in(array_column(NewbornDangerSign::cases(), 'value'))],
            'neonatal_condition' => ['nullable', Rule::in(array_column(NewbornCondition::cases(), 'value'))],
            'outcome' => ['nullable', Rule::in(array_column(NewbornOutcome::cases(), 'value'))],
            'status' => ['nullable', Rule::in(array_column(NewbornRecordStatus::cases(), 'value'))],
            'transferred_to' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
