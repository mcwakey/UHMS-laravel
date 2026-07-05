<?php

namespace App\Services\Maternity;

use App\Enums\DeliveryMode;
use App\Enums\DeliveryOutcome;
use App\Enums\LaborEpisodeStatus;
use App\Enums\LaborStage;
use App\Enums\MaternityRiskLevel;
use App\Enums\NewbornOutcome;
use App\Enums\NewbornRecordStatus;
use App\Enums\PostnatalCaseStatus;
use App\Enums\PregnancyProfileStatus;
use App\Models\AdmissionRequest;
use App\Models\AntenatalVisit;
use App\Models\DeliveryRecord;
use App\Models\LaborEpisode;
use App\Models\LaborObservation;
use App\Models\NewbornRecord;
use App\Models\PostnatalCase;
use App\Models\PostnatalMotherObservation;
use App\Models\PostnatalNewbornObservation;
use App\Models\PregnancyProfile;
use Illuminate\Database\Eloquent\Builder;

class MaternityReportService
{
    public function index(array $filters): array
    {
        return [
            'filters' => $this->normaliseFilters($filters),
            'cards' => [
                'pregnancies' => PregnancyProfile::count(),
                'anc_visits' => AntenatalVisit::count(),
                'labor_episodes' => LaborEpisode::count(),
                'deliveries' => DeliveryRecord::count(),
                'newborns' => NewbornRecord::count(),
                'postnatal_cases' => PostnatalCase::count(),
            ],
        ];
    }

    public function antenatal(array $filters, bool $export = false): array
    {
        $filters = $this->normaliseFilters($filters);
        $query = $this->dateRange(AntenatalVisit::with(['patient', 'department', 'recordedBy']), 'visit_date', $filters);
        $this->commonFilters($query, $filters, 'recorded_by');

        return [
            'title' => __('maternity.anc_report'),
            'metrics' => [
                'anc_visits' => (clone $query)->count(),
                'new_profiles' => $this->dateRange(PregnancyProfile::query(), 'created_at', $filters)->count(),
                'profiles_without_anc' => PregnancyProfile::doesntHave('antenatalVisits')->count(),
                'missed_anc_visits' => AntenatalVisit::whereNotNull('next_visit_date')->whereDate('next_visit_date', '<', today())->count(),
                'high_risk_pregnancies' => PregnancyProfile::where('profile_status', PregnancyProfileStatus::HIGH_RISK->value)->count(),
                'danger_signs_flagged' => $this->jsonNotEmptyCount((clone $query), 'danger_signs'),
                'referral_counts' => (clone $query)->whereNotNull('referral_type')->count(),
                'anc_admission_requests' => AdmissionRequest::where('source', 'antenatal')->count(),
                'expected_due_this_month' => PregnancyProfile::whereBetween('estimated_due_date', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            ],
            'columns' => ['Date', 'Patient', 'Visit #', 'GA', 'BP', 'Danger Signs', 'Referral', 'Status'],
            'rows' => $this->rows($query->latest('visit_date'), $export, fn ($visit) => [
                $visit->visit_date?->toDateString(),
                $visit->patient?->full_name,
                $visit->visit_number,
                trim(($visit->gestational_age_weeks ?? '').'w '.($visit->gestational_age_days ?? '').'d'),
                $visit->blood_pressure_systolic && $visit->blood_pressure_diastolic ? $visit->blood_pressure_systolic.'/'.$visit->blood_pressure_diastolic : '',
                count($visit->danger_signs ?? []),
                $visit->referral_type?->label() ?? '',
                $visit->status?->label() ?? '',
            ]),
        ];
    }

    public function labor(array $filters, bool $export = false): array
    {
        $filters = $this->normaliseFilters($filters);
        $query = $this->dateRange(LaborEpisode::with(['patient', 'department', 'latestObservation']), 'started_at', $filters);
        $this->commonFilters($query, $filters, 'started_by');

        return [
            'title' => __('maternity.labor_report'),
            'metrics' => [
                'active_labor_episodes' => LaborEpisode::open()->count(),
                'observations' => $this->dateRange(LaborObservation::query(), 'observed_at', $filters)->count(),
                'danger_signs_flagged' => $this->jsonNotEmptyCount($this->dateRange(LaborObservation::query(), 'observed_at', $filters), 'danger_signs'),
                'theatre_escalations' => (clone $query)->where('theatre_escalation_required', true)->count(),
                'emergency_escalations' => (clone $query)->where('emergency_escalation_required', true)->count(),
                'delivered' => (clone $query)->where('status', LaborEpisodeStatus::DELIVERED->value)->count(),
                'transferred_referred_cancelled' => (clone $query)->whereIn('status', [LaborEpisodeStatus::TRANSFERRED->value, LaborEpisodeStatus::REFERRED->value, LaborEpisodeStatus::CANCELLED->value])->count(),
            ],
            'columns' => ['Started', 'Patient', 'Stage', 'Status', 'Latest Observation', 'Theatre', 'Emergency'],
            'rows' => $this->rows($query->latest('started_at'), $export, fn ($episode) => [
                $episode->started_at?->toDateString(),
                $episode->patient?->full_name,
                $episode->labor_stage?->label() ?? '',
                $episode->status?->label() ?? '',
                $episode->latestObservation?->observed_at?->format('Y-m-d H:i'),
                $episode->theatre_escalation_required ? 'Yes' : 'No',
                $episode->emergency_escalation_required ? 'Yes' : 'No',
            ]),
        ];
    }

    public function deliveries(array $filters, bool $export = false): array
    {
        $filters = $this->normaliseFilters($filters);
        $query = $this->dateRange(DeliveryRecord::with(['patient', 'newbornRecords']), 'delivery_at', $filters);
        $this->commonFilters($query, $filters, 'recorded_by');
        $this->whenFilled($query, $filters, 'delivery_mode');
        $this->whenFilled($query, $filters, 'delivery_outcome');

        return [
            'title' => __('maternity.delivery_report'),
            'metrics' => [
                'deliveries' => (clone $query)->count(),
                'normal_delivery' => (clone $query)->where('delivery_mode', DeliveryMode::SPONTANEOUS_VAGINAL_DELIVERY->value)->count(),
                'caesarean_placeholder' => (clone $query)->whereNotNull('theatre_case_id')->count(),
                'newborn_records_pending' => (clone $query)->where('newborn_records_pending', true)->count(),
                'missing_newborn_records' => (clone $query)->doesntHave('newbornRecords')->count(),
                'blood_loss_warnings' => (clone $query)->where('estimated_blood_loss_ml', '>=', 500)->count(),
            ],
            'columns' => ['Date', 'Patient', 'Mode', 'Outcome', 'Blood Loss', 'Maternal Condition', 'Newborns', 'Pending'],
            'rows' => $this->rows($query->latest('delivery_at'), $export, fn ($delivery) => [
                $delivery->delivery_at?->toDateString(),
                $delivery->patient?->full_name,
                $delivery->delivery_mode?->label() ?? '',
                $delivery->delivery_outcome?->label() ?? '',
                $delivery->estimated_blood_loss_ml,
                $delivery->maternal_condition?->label() ?? '',
                $delivery->newbornRecords->count(),
                $delivery->newborn_records_pending ? 'Yes' : 'No',
            ]),
        ];
    }

    public function newborns(array $filters, bool $export = false): array
    {
        $filters = $this->normaliseFilters($filters);
        $query = $this->dateRange(NewbornRecord::with(['mother', 'newbornPatient', 'deliveryRecord']), 'birth_time', $filters);
        $this->commonFilters($query, $filters, 'recorded_by');
        $this->whenFilled($query, $filters, 'outcome');

        return [
            'title' => __('maternity.newborn_report'),
            'metrics' => [
                'newborns' => (clone $query)->count(),
                'live_births' => (clone $query)->where('outcome', NewbornOutcome::LIVE_BIRTH->value)->count(),
                'stillbirths' => (clone $query)->where('outcome', NewbornOutcome::STILLBIRTH->value)->count(),
                'neonatal_deaths' => (clone $query)->where('outcome', NewbornOutcome::NEONATAL_DEATH->value)->count(),
                'multiple_births' => DeliveryRecord::where('newborn_count', '>', 1)->whereHas('newbornRecords')->count(),
                'low_birth_weight' => (clone $query)->where('birth_weight_kg', '<', 2.5)->count(),
                'resuscitation_required' => (clone $query)->where('resuscitation_required', true)->count(),
                'poor_apgar' => (clone $query)->where('apgar_5_min', '<', 7)->count(),
                'under_observation' => (clone $query)->where('status', NewbornRecordStatus::UNDER_OBSERVATION->value)->count(),
                'without_linked_patient' => (clone $query)->whereNull('newborn_patient_id')->count(),
            ],
            'columns' => ['Birth Time', 'Mother', 'Order', 'Outcome', 'Weight', 'Apgar 5', 'Resuscitation', 'Linked Patient'],
            'rows' => $this->rows($query->latest('birth_time'), $export, fn ($newborn) => [
                $newborn->birth_time?->format('Y-m-d H:i'),
                $newborn->mother?->full_name,
                $newborn->birth_order,
                $newborn->outcome?->label() ?? '',
                $newborn->birth_weight_kg,
                $newborn->apgar_5_min,
                $newborn->resuscitation_required ? 'Yes' : 'No',
                $newborn->newbornPatient?->patient_number ?? '',
            ]),
        ];
    }

    public function postnatal(array $filters, bool $export = false): array
    {
        $filters = $this->normaliseFilters($filters);
        $query = $this->dateRange(PostnatalCase::with(['mother', 'latestMotherObservation', 'latestNewbornObservation']), 'opened_at', $filters);
        $this->commonFilters($query, $filters, 'opened_by');

        return [
            'title' => __('maternity.postnatal_report'),
            'metrics' => [
                'active_postnatal_cases' => PostnatalCase::active()->count(),
                'mother_observations' => $this->dateRange(PostnatalMotherObservation::query(), 'observed_at', $filters)->count(),
                'newborn_observations' => $this->dateRange(PostnatalNewbornObservation::query(), 'observed_at', $filters)->count(),
                'mother_ready' => (clone $query)->whereNotNull('mother_ready_at')->count(),
                'newborn_ready' => (clone $query)->whereNotNull('newborn_ready_at')->count(),
                'ready_for_discharge' => (clone $query)->whereNotNull('ready_for_discharge_at')->count(),
                'referrals_required' => (clone $query)->where('referral_required', true)->count(),
                'followups_due_this_week' => PostnatalCase::whereBetween('follow_up_date', [today(), today()->endOfWeek()])->count(),
                'danger_signs_flagged' => $this->jsonNotEmptyCount(PostnatalMotherObservation::query(), 'danger_signs') + $this->jsonNotEmptyCount(PostnatalNewbornObservation::query(), 'danger_signs'),
            ],
            'columns' => ['Opened', 'Mother', 'Status', 'Risk', 'Mother Ready', 'Newborn Ready', 'Referral', 'Follow Up'],
            'rows' => $this->rows($query->latest('opened_at'), $export, fn ($case) => [
                $case->opened_at?->toDateString(),
                $case->mother?->full_name,
                $case->status?->label() ?? '',
                $case->risk_level?->label() ?? '',
                $case->mother_ready_at ? 'Yes' : 'No',
                $case->newborn_ready_at ? 'Yes' : 'No',
                $case->referral_required ? 'Yes' : 'No',
                $case->follow_up_date?->toDateString(),
            ]),
        ];
    }

    public function risk(array $filters, bool $export = false): array
    {
        $rows = collect()
            ->merge(PregnancyProfile::with('patient')->where('profile_status', PregnancyProfileStatus::HIGH_RISK->value)->limit(100)->get()->map(fn ($profile) => ['Pregnancy', $profile->patient?->full_name, __('maternity.high_risk_pregnancies'), $profile->created_at?->toDateString()]))
            ->merge(AntenatalVisit::with('patient')->whereNotNull('danger_signs')->limit(100)->get()->filter(fn ($visit) => count($visit->danger_signs ?? []) > 0)->map(fn ($visit) => ['ANC', $visit->patient?->full_name, count($visit->danger_signs ?? []).' danger signs', $visit->visit_date?->toDateString()]))
            ->merge(LaborEpisode::with('patient')->where(fn ($q) => $q->where('theatre_escalation_required', true)->orWhere('emergency_escalation_required', true))->limit(100)->get()->map(fn ($episode) => ['Labor', $episode->patient?->full_name, __('maternity.escalation_required'), $episode->started_at?->toDateString()]))
            ->merge(DeliveryRecord::with('patient')->whereNotNull('complications')->limit(100)->get()->filter(fn ($delivery) => count($delivery->complications ?? []) > 0)->map(fn ($delivery) => ['Delivery', $delivery->patient?->full_name, __('maternity.complications'), $delivery->delivery_at?->toDateString()]))
            ->merge(NewbornRecord::with('mother')->whereNotNull('risk_flags')->limit(100)->get()->filter(fn ($newborn) => count($newborn->risk_flags ?? []) > 0)->map(fn ($newborn) => ['Newborn', $newborn->mother?->full_name, count($newborn->risk_flags ?? []).' risk flags', $newborn->birth_time?->toDateString()]))
            ->merge(PostnatalCase::with('mother')->where('referral_required', true)->limit(100)->get()->map(fn ($case) => ['Postnatal', $case->mother?->full_name, __('maternity.referral_required'), $case->opened_at?->toDateString()]));

        return [
            'title' => __('maternity.risk_report'),
            'metrics' => [
                'risk_items' => $rows->count(),
                'high_risk_pregnancies' => PregnancyProfile::where('profile_status', PregnancyProfileStatus::HIGH_RISK->value)->count(),
                'labor_escalations' => LaborEpisode::where(fn ($q) => $q->where('theatre_escalation_required', true)->orWhere('emergency_escalation_required', true))->count(),
                'postnatal_referrals' => PostnatalCase::where('referral_required', true)->count(),
            ],
            'columns' => ['Area', 'Patient', 'Signal', 'Date'],
            'rows' => $export ? $rows : $rows->take(50),
        ];
    }

    public function normaliseFilters(array $filters): array
    {
        return [
            'date_from' => $filters['date_from'] ?? now()->subMonth()->toDateString(),
            'date_to' => $filters['date_to'] ?? now()->toDateString(),
            'department_id' => $filters['department_id'] ?? null,
            'staff_id' => $filters['staff_id'] ?? null,
            'risk_level' => $filters['risk_level'] ?? null,
            'status' => $filters['status'] ?? null,
            'outcome' => $filters['outcome'] ?? null,
            'delivery_mode' => $filters['delivery_mode'] ?? null,
            'delivery_outcome' => $filters['delivery_outcome'] ?? null,
        ];
    }

    private function dateRange(Builder $query, string $column, array $filters): Builder
    {
        return $query
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate($column, '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate($column, '<=', $date));
    }

    private function commonFilters(Builder $query, array $filters, string $staffColumn): void
    {
        $this->whenFilled($query, $filters, 'department_id');
        $query->when($filters['staff_id'] ?? null, fn ($q, $id) => $q->where($staffColumn, $id));
        $this->whenFilled($query, $filters, 'status');
    }

    private function whenFilled(Builder $query, array $filters, string $column): void
    {
        $query->when(filled($filters[$column] ?? null), fn ($q) => $q->where($column, $filters[$column]));
    }

    private function rows(Builder $query, bool $export, callable $map)
    {
        return $export
            ? $query->limit(2000)->get()->map($map)
            : $query->paginate(20)->through($map);
    }

    private function jsonNotEmptyCount(Builder $query, string $column): int
    {
        return $query->get([$column])->filter(fn ($row) => count($row->{$column} ?? []) > 0)->count();
    }
}
