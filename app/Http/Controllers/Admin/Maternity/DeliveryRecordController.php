<?php

namespace App\Http\Controllers\Admin\Maternity;

use App\Enums\DeliveryMode;
use App\Enums\DeliveryOutcome;
use App\Enums\DeliveryRecordStatus;
use App\Enums\MaternalCondition;
use App\Enums\PlacentaStatus;
use App\Http\Controllers\Controller;
use App\Models\DeliveryRecord;
use App\Models\LaborEpisode;
use App\Models\User;
use App\Services\Maternity\DeliveryRecordService;
use App\Services\Maternity\MaternityBillingPostingService;
use App\Services\Maternity\NewbornOverviewService;
use App\Services\Maternity\PostnatalOverviewService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliveryRecordController extends Controller
{
    public function __construct(
        private DeliveryRecordService $deliveries,
        private NewbornOverviewService $newbornOverview,
        private PostnatalOverviewService $postnatalOverview,
        private MaternityBillingPostingService $billingPosting,
    ) {}

    public function create(LaborEpisode $laborEpisode)
    {
        return view('maternity.labor.deliveries.create', $this->formData($laborEpisode));
    }

    public function store(Request $request, LaborEpisode $laborEpisode)
    {
        $record = $this->deliveries->create($laborEpisode, $request->validate($this->rules()), $request->user());

        return redirect()
            ->route('admin.maternity.deliveries.show', $record)
            ->with('success', __('maternity.delivery_record_created'));
    }

    public function show(DeliveryRecord $deliveryRecord)
    {
        return view('maternity.labor.deliveries.show', [
            'record' => $deliveryRecord->load($this->deliveries->relations()),
            'newbornOverview' => $this->newbornOverview->forDelivery($deliveryRecord),
            'postnatalOverview' => $this->postnatalOverview->forDelivery($deliveryRecord),
            'billingPreviews' => $this->billingPosting->previewManyForSource($deliveryRecord),
        ]);
    }

    public function edit(DeliveryRecord $deliveryRecord)
    {
        return view('maternity.labor.deliveries.edit', $this->formData($deliveryRecord->laborEpisode, $deliveryRecord));
    }

    public function update(Request $request, DeliveryRecord $deliveryRecord)
    {
        $this->deliveries->update($deliveryRecord, $request->validate($this->rules()), $request->user());

        return redirect()
            ->route('admin.maternity.deliveries.show', $deliveryRecord)
            ->with('success', __('maternity.delivery_record_updated'));
    }

    public function complete(Request $request, DeliveryRecord $deliveryRecord)
    {
        $this->deliveries->complete($deliveryRecord, $request->validate($this->rules()), $request->user());

        return redirect()
            ->route('admin.maternity.deliveries.show', $deliveryRecord)
            ->with('success', __('maternity.delivery_record_completed'));
    }

    private function formData(LaborEpisode $episode, ?DeliveryRecord $record = null): array
    {
        return [
            'episode' => $episode->load(['patient', 'pregnancyProfile', 'admission.bed.ward']),
            'record' => $record,
            'deliveryModes' => DeliveryMode::cases(),
            'deliveryOutcomes' => DeliveryOutcome::cases(),
            'placentaStatuses' => PlacentaStatus::cases(),
            'maternalConditions' => MaternalCondition::cases(),
            'statuses' => DeliveryRecordStatus::cases(),
            'staff' => User::query()->orderBy('name')->limit(200)->get(),
        ];
    }

    private function rules(): array
    {
        return [
            'delivery_at' => ['nullable', 'date'],
            'delivery_mode' => ['nullable', Rule::in(array_column(DeliveryMode::cases(), 'value'))],
            'delivery_outcome' => ['nullable', Rule::in(array_column(DeliveryOutcome::cases(), 'value'))],
            'placenta_status' => ['nullable', Rule::in(array_column(PlacentaStatus::cases(), 'value'))],
            'estimated_blood_loss_ml' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'maternal_condition' => ['nullable', Rule::in(array_column(MaternalCondition::cases(), 'value'))],
            'complications' => ['nullable', 'array'],
            'complications.*' => ['string', 'max:255'],
            'attending_staff_id' => ['nullable', 'exists:users,id'],
            'theatre_case_id' => ['nullable', 'integer'],
            'emergency_case_id' => ['nullable', 'integer'],
            'newborn_count' => ['nullable', 'integer', 'min:0', 'max:10'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', Rule::in(array_column(DeliveryRecordStatus::cases(), 'value'))],
        ];
    }
}
