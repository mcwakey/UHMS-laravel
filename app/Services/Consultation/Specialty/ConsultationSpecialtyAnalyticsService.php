<?php

namespace App\Services\Consultation\Specialty;

use App\Models\ConsultationSpecialtyProfile;
use App\Models\InvoiceItem;
use App\Models\VisitConsultationRoute;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ConsultationSpecialtyAnalyticsService
{
    public function __construct(private readonly ConsultationSpecialtyReadinessService $readiness) {}

    public function dashboardPayload(array $filters = []): array
    {
        $filters = $this->normaliseFilters($filters);

        return [
            'filters' => $filters,
            'summary' => $this->summary($filters),
            'volume' => $this->consultationVolume($filters),
            'workload' => $this->doctorWorkload($filters),
            'sections' => $this->sectionCompletion($filters),
            'readiness' => $this->readinessBreakdown($filters),
            'order_sets' => $this->orderSetUsage($filters),
            'billing' => $this->billingMappingHealth($filters),
            'revenue' => $this->revenueBySpecialty($filters),
            'summary_builder' => $this->summaryBuilderUsage($filters),
        ];
    }

    public function summary(array $filters = []): array
    {
        $filters = $this->normaliseFilters($filters);
        $revenue = $this->revenueBySpecialty($filters);

        return [
            'total_specialist_consultations' => $this->entriesBase($filters)
                ->distinct('consultation_specialty_entries.consultation_id')
                ->count('consultation_specialty_entries.consultation_id'),
            'specialties_used' => $this->entriesBase($filters)
                ->distinct('consultation_specialty_entries.consultation_specialty_profile_id')
                ->count('consultation_specialty_entries.consultation_specialty_profile_id'),
            'structured_entries_count' => $this->entriesBase($filters)->count(),
            'configured_specialty_profiles' => ConsultationSpecialtyProfile::query()
                ->where('code', '!=', ConsultationSpecialtyProfile::GENERAL_MEDICINE)
                ->count(),
            'active_specialty_profiles' => ConsultationSpecialtyProfile::query()
                ->where('code', '!=', ConsultationSpecialtyProfile::GENERAL_MEDICINE)
                ->where('is_active', true)
                ->count(),
            'doctors_with_specialist_cases' => $this->entriesBase($filters)
                ->whereNotNull('visit_consultation_routes.doctor_id')
                ->distinct('visit_consultation_routes.doctor_id')
                ->count('visit_consultation_routes.doctor_id'),
            'order_sets_applied' => $this->orderSetApplicationsBase($filters)->count(),
            'billing_mappings_count' => $this->serviceMappingsBase($filters)->count(),
            'billing_applications_count' => $this->billingApplicationsBase($filters)->count(),
            'specialty_revenue' => $revenue['total_patient_payable'],
        ];
    }

    public function consultationVolume(array $filters = []): array
    {
        $filters = $this->normaliseFilters($filters);

        return [
            'by_specialty' => $this->entriesBase($filters)
                ->select([
                    'consultation_specialty_profiles.id as profile_id',
                    'consultation_specialty_profiles.code',
                    'consultation_specialty_profiles.name',
                    DB::raw('COUNT(DISTINCT consultation_specialty_entries.consultation_id) as consultations_count'),
                    DB::raw('COUNT(consultation_specialty_entries.id) as entries_count'),
                ])
                ->groupBy('consultation_specialty_profiles.id', 'consultation_specialty_profiles.code', 'consultation_specialty_profiles.name')
                ->orderByDesc('consultations_count')
                ->get(),
            'by_department' => $this->entriesBase($filters)
                ->leftJoin('departments', 'departments.id', '=', 'visit_consultation_routes.department_id')
                ->select([
                    'departments.id as department_id',
                    DB::raw("COALESCE(departments.name, 'Unassigned') as department_name"),
                    DB::raw('COUNT(DISTINCT consultation_specialty_entries.consultation_id) as consultations_count'),
                ])
                ->groupBy('departments.id', 'departments.name')
                ->orderByDesc('consultations_count')
                ->limit(20)
                ->get(),
            'daily' => $this->entriesBase($filters)
                ->select([
                    DB::raw('DATE(consultation_specialty_entries.created_at) as report_date'),
                    DB::raw('COUNT(DISTINCT consultation_specialty_entries.consultation_id) as consultations_count'),
                ])
                ->groupBy(DB::raw('DATE(consultation_specialty_entries.created_at)'))
                ->orderBy('report_date')
                ->get(),
        ];
    }

    public function doctorWorkload(array $filters = []): Collection
    {
        $filters = $this->normaliseFilters($filters);

        return $this->entriesBase($filters)
            ->leftJoin('users', 'users.id', '=', 'visit_consultation_routes.doctor_id')
            ->select([
                'users.id as doctor_id',
                'users.first_name',
                'users.last_name',
                DB::raw('COUNT(DISTINCT consultation_specialty_entries.consultation_id) as consultations_count'),
                DB::raw('COUNT(consultation_specialty_entries.id) as entries_count'),
                DB::raw('COUNT(DISTINCT consultation_specialty_entries.consultation_specialty_profile_id) as specialty_count'),
            ])
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->orderByDesc('consultations_count')
            ->limit(25)
            ->get()
            ->map(fn ($row) => $this->withDoctorName($row));
    }

    public function sectionCompletion(array $filters = []): Collection
    {
        $filters = $this->normaliseFilters($filters);
        $profileConsultations = $this->entriesBase($filters)
            ->select([
                'consultation_specialty_entries.consultation_specialty_profile_id',
                DB::raw('COUNT(DISTINCT consultation_specialty_entries.consultation_id) as consultations_count'),
            ])
            ->groupBy('consultation_specialty_entries.consultation_specialty_profile_id')
            ->pluck('consultations_count', 'consultation_specialty_profile_id');

        return $this->entriesBase($filters)
            ->leftJoin('consultation_specialty_sections', function ($join) {
                $join->on('consultation_specialty_sections.consultation_specialty_profile_id', '=', 'consultation_specialty_entries.consultation_specialty_profile_id')
                    ->on('consultation_specialty_sections.section_key', '=', 'consultation_specialty_entries.section_key');
            })
            ->select([
                'consultation_specialty_profiles.id as profile_id',
                'consultation_specialty_profiles.code',
                'consultation_specialty_profiles.name',
                'consultation_specialty_entries.section_key',
                DB::raw('COALESCE(consultation_specialty_sections.label, consultation_specialty_entries.section_key) as section_label'),
                DB::raw('COUNT(consultation_specialty_entries.id) as entry_count'),
                DB::raw('COUNT(DISTINCT consultation_specialty_entries.consultation_id) as consultations_with_section'),
            ])
            ->groupBy(
                'consultation_specialty_profiles.id',
                'consultation_specialty_profiles.code',
                'consultation_specialty_profiles.name',
                'consultation_specialty_entries.section_key',
                'consultation_specialty_sections.label'
            )
            ->orderBy('consultation_specialty_profiles.name')
            ->orderByDesc('entry_count')
            ->get()
            ->map(function ($row) use ($profileConsultations) {
                $denominator = max(1, (int) ($profileConsultations[$row->profile_id] ?? 0));
                $row->completion_rate = round(((int) $row->consultations_with_section / $denominator) * 100, 1);

                return $row;
            });
    }

    public function readinessBreakdown(array $filters = []): array
    {
        $filters = $this->normaliseFilters($filters);
        $rows = $this->entriesBase($filters)
            ->select([
                'consultation_specialty_entries.consultation_id',
                'consultation_specialty_entries.consultation_specialty_profile_id',
                'consultation_specialty_profiles.code',
                'consultation_specialty_profiles.name',
            ])
            ->groupBy(
                'consultation_specialty_entries.consultation_id',
                'consultation_specialty_entries.consultation_specialty_profile_id',
                'consultation_specialty_profiles.code',
                'consultation_specialty_profiles.name'
            )
            ->orderByDesc(DB::raw('MAX(consultation_specialty_entries.created_at)'))
            ->limit(150)
            ->get();

        $byStatus = collect();
        $bySpecialty = collect();
        $scores = [];

        foreach ($rows as $row) {
            $route = VisitConsultationRoute::query()->find($row->consultation_id);
            if (! $route) {
                continue;
            }

            $result = $this->readiness->evaluate($route, [
                'profile' => ['id' => $row->consultation_specialty_profile_id],
            ]);

            $byStatus[$result->status] = (int) ($byStatus[$result->status] ?? 0) + 1;
            $key = $row->code.'|'.$result->status;
            $bySpecialty[$key] = [
                'profile_id' => $row->consultation_specialty_profile_id,
                'code' => $row->code,
                'name' => $row->name,
                'status' => $result->status,
                'count' => (int) (($bySpecialty[$key]['count'] ?? 0) + 1),
            ];
            $scores[] = $result->score;
        }

        return [
            'sample_size' => count($scores),
            'sample_limited' => true,
            'by_status' => $byStatus->map(fn ($count, $status) => ['status' => $status, 'count' => $count])->values(),
            'by_specialty' => $bySpecialty->values(),
            'average_score' => count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0,
        ];
    }

    public function orderSetUsage(array $filters = []): array
    {
        $filters = $this->normaliseFilters($filters);

        return [
            'applications_by_order_set' => $this->orderSetApplicationsBase($filters)
                ->leftJoin('consultation_specialty_order_sets', 'consultation_specialty_order_sets.id', '=', 'consultation_specialty_order_set_applications.consultation_specialty_order_set_id')
                ->select([
                    'consultation_specialty_profiles.id as profile_id',
                    'consultation_specialty_profiles.code',
                    'consultation_specialty_profiles.name as profile_name',
                    'consultation_specialty_order_sets.code as order_set_code',
                    DB::raw("COALESCE(consultation_specialty_order_sets.name, 'Unknown order set') as order_set_name"),
                    DB::raw('COUNT(consultation_specialty_order_set_applications.id) as applications_count'),
                ])
                ->groupBy(
                    'consultation_specialty_profiles.id',
                    'consultation_specialty_profiles.code',
                    'consultation_specialty_profiles.name',
                    'consultation_specialty_order_sets.code',
                    'consultation_specialty_order_sets.name'
                )
                ->orderByDesc('applications_count')
                ->limit(25)
                ->get(),
            'item_statuses' => DB::table('consultation_specialty_order_set_application_items')
                ->join('consultation_specialty_order_set_applications', 'consultation_specialty_order_set_applications.id', '=', 'consultation_specialty_order_set_application_items.consultation_specialty_order_set_application_id')
                ->join('consultation_specialty_profiles', 'consultation_specialty_profiles.id', '=', 'consultation_specialty_order_set_applications.consultation_specialty_profile_id')
                ->tap(fn (Builder $query) => $this->applyDateFilter($query, 'consultation_specialty_order_set_applications.created_at', $filters))
                ->tap(fn (Builder $query) => $this->applyProfileFilter($query, 'consultation_specialty_profiles', $filters))
                ->where('consultation_specialty_profiles.code', '!=', ConsultationSpecialtyProfile::GENERAL_MEDICINE)
                ->select([
                    'consultation_specialty_order_set_application_items.status',
                    DB::raw('COUNT(*) as count'),
                ])
                ->groupBy('consultation_specialty_order_set_application_items.status')
                ->orderByDesc('count')
                ->get(),
        ];
    }

    public function summaryBuilderUsage(array $filters = []): array
    {
        $filters = $this->normaliseFilters($filters);

        return [
            'usage_tracked' => false,
            'note' => __('reports.consultation_specialties.summary_builder_note'),
            'available_by_specialty' => ConsultationSpecialtyProfile::query()
                ->where('code', '!=', ConsultationSpecialtyProfile::GENERAL_MEDICINE)
                ->where('is_active', true)
                ->when($filters['specialty_profile_id'] ?? null, fn ($query, $id) => $query->whereKey($id))
                ->ordered()
                ->get(['id', 'code', 'name'])
                ->map(fn (ConsultationSpecialtyProfile $profile) => [
                    'profile_id' => $profile->id,
                    'code' => $profile->code,
                    'name' => $profile->translatedName(),
                    'available' => true,
                ]),
        ];
    }

    public function billingMappingHealth(array $filters = []): array
    {
        $filters = $this->normaliseFilters($filters);

        return [
            'mappings_by_context' => $this->serviceMappingsBase($filters)
                ->select(['mapping_context', DB::raw('COUNT(*) as count')])
                ->groupBy('mapping_context')
                ->orderByDesc('count')
                ->get(),
            'mappings_by_trigger' => $this->serviceMappingsBase($filters)
                ->select(['billing_trigger', DB::raw('COUNT(*) as count')])
                ->groupBy('billing_trigger')
                ->orderByDesc('count')
                ->get(),
            'applications_by_status' => $this->billingApplicationsBase($filters)
                ->select(['consultation_specialty_billing_applications.status', DB::raw('COUNT(*) as count')])
                ->groupBy('consultation_specialty_billing_applications.status')
                ->orderByDesc('count')
                ->get(),
            'auto_bill_mappings' => $this->serviceMappingsBase($filters)->where('auto_bill', true)->count(),
            'manual_mappings' => $this->serviceMappingsBase($filters)->where('billing_trigger', 'manual')->count(),
        ];
    }

    public function revenueBySpecialty(array $filters = []): array
    {
        $filters = $this->normaliseFilters($filters);

        $rows = $this->billingApplicationsBase($filters)
            ->leftJoin('invoice_items', 'invoice_items.id', '=', 'consultation_specialty_billing_applications.invoice_item_id')
            ->select([
                'consultation_specialty_profiles.id as profile_id',
                'consultation_specialty_profiles.code',
                'consultation_specialty_profiles.name',
                DB::raw('COUNT(DISTINCT consultation_specialty_billing_applications.id) as billing_applications_count'),
                DB::raw('COALESCE(SUM(invoice_items.patient_payable), 0) as patient_payable'),
                DB::raw('COALESCE(SUM(invoice_items.paid_amount), 0) as paid_amount'),
                DB::raw('COALESCE(SUM(invoice_items.balance), 0) as balance'),
            ])
            ->groupBy('consultation_specialty_profiles.id', 'consultation_specialty_profiles.code', 'consultation_specialty_profiles.name')
            ->orderByDesc('patient_payable')
            ->get();

        return [
            'by_specialty' => $rows,
            'total_patient_payable' => (float) $rows->sum('patient_payable'),
            'total_paid' => (float) $rows->sum('paid_amount'),
            'total_balance' => (float) $rows->sum('balance'),
            'source_type' => InvoiceItem::SOURCE_SPECIALTY_SERVICE_MAPPING,
        ];
    }

    public function normaliseFilters(array $filters): array
    {
        $from = filled($filters['date_from'] ?? null)
            ? CarbonImmutable::parse($filters['date_from'])->startOfDay()
            : now()->subDays(30)->startOfDay()->toImmutable();
        $to = filled($filters['date_to'] ?? null)
            ? CarbonImmutable::parse($filters['date_to'])->endOfDay()
            : now()->endOfDay()->toImmutable();

        return [
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'from' => $from,
            'to' => $to,
            'specialty_profile_id' => $this->nullableInt($filters['specialty_profile_id'] ?? null),
            'department_id' => $this->nullableInt($filters['department_id'] ?? null),
            'doctor_id' => $this->nullableInt($filters['doctor_id'] ?? null),
            'billing_status' => filled($filters['billing_status'] ?? null) ? (string) $filters['billing_status'] : null,
        ];
    }

    private function entriesBase(array $filters): Builder
    {
        $query = DB::table('consultation_specialty_entries')
            ->join('consultation_specialty_profiles', 'consultation_specialty_profiles.id', '=', 'consultation_specialty_entries.consultation_specialty_profile_id')
            ->leftJoin('visit_consultation_routes', 'visit_consultation_routes.id', '=', 'consultation_specialty_entries.consultation_id')
            ->where('consultation_specialty_profiles.code', '!=', ConsultationSpecialtyProfile::GENERAL_MEDICINE)
            ->whereNotNull('consultation_specialty_entries.consultation_id');

        $this->applyDateFilter($query, 'consultation_specialty_entries.created_at', $filters);
        $this->applyProfileFilter($query, 'consultation_specialty_profiles', $filters);
        $this->applyRouteFilters($query, $filters);

        return $query;
    }

    private function orderSetApplicationsBase(array $filters): Builder
    {
        $query = DB::table('consultation_specialty_order_set_applications')
            ->join('consultation_specialty_profiles', 'consultation_specialty_profiles.id', '=', 'consultation_specialty_order_set_applications.consultation_specialty_profile_id')
            ->leftJoin('visit_consultation_routes', 'visit_consultation_routes.id', '=', 'consultation_specialty_order_set_applications.consultation_id')
            ->where('consultation_specialty_profiles.code', '!=', ConsultationSpecialtyProfile::GENERAL_MEDICINE);

        $this->applyDateFilter($query, 'consultation_specialty_order_set_applications.created_at', $filters);
        $this->applyProfileFilter($query, 'consultation_specialty_profiles', $filters);
        $this->applyRouteFilters($query, $filters);

        return $query;
    }

    private function billingApplicationsBase(array $filters): Builder
    {
        $query = DB::table('consultation_specialty_billing_applications')
            ->join('consultation_specialty_profiles', 'consultation_specialty_profiles.id', '=', 'consultation_specialty_billing_applications.consultation_specialty_profile_id')
            ->leftJoin('visit_consultation_routes', 'visit_consultation_routes.id', '=', 'consultation_specialty_billing_applications.consultation_id')
            ->where('consultation_specialty_profiles.code', '!=', ConsultationSpecialtyProfile::GENERAL_MEDICINE);

        $this->applyDateFilter($query, 'consultation_specialty_billing_applications.created_at', $filters);
        $this->applyProfileFilter($query, 'consultation_specialty_profiles', $filters);
        $this->applyRouteFilters($query, $filters);

        if ($filters['billing_status'] ?? null) {
            $query->where('consultation_specialty_billing_applications.status', $filters['billing_status']);
        }

        return $query;
    }

    private function serviceMappingsBase(array $filters): Builder
    {
        $query = DB::table('consultation_specialty_service_mappings')
            ->join('consultation_specialty_profiles', 'consultation_specialty_profiles.id', '=', 'consultation_specialty_service_mappings.consultation_specialty_profile_id')
            ->where('consultation_specialty_profiles.code', '!=', ConsultationSpecialtyProfile::GENERAL_MEDICINE);

        $this->applyProfileFilter($query, 'consultation_specialty_profiles', $filters);

        if ($filters['department_id'] ?? null) {
            $query->where('consultation_specialty_service_mappings.department_id', $filters['department_id']);
        }

        return $query;
    }

    private function applyDateFilter(Builder $query, string $column, array $filters): void
    {
        $query->whereBetween($column, [$filters['from'], $filters['to']]);
    }

    private function applyProfileFilter(Builder $query, string $profileTable, array $filters): void
    {
        if ($filters['specialty_profile_id'] ?? null) {
            $query->where($profileTable.'.id', $filters['specialty_profile_id']);
        }
    }

    private function applyRouteFilters(Builder $query, array $filters): void
    {
        if ($filters['department_id'] ?? null) {
            $query->where('visit_consultation_routes.department_id', $filters['department_id']);
        }

        if ($filters['doctor_id'] ?? null) {
            $query->where('visit_consultation_routes.doctor_id', $filters['doctor_id']);
        }
    }

    private function nullableInt(mixed $value): ?int
    {
        return filled($value) ? (int) $value : null;
    }

    private function withDoctorName(object $row): object
    {
        $row->doctor_name = trim(implode(' ', array_filter([$row->first_name ?? null, $row->last_name ?? null]))) ?: __('reports.consultation_specialties.unassigned_doctor');

        return $row;
    }
}
