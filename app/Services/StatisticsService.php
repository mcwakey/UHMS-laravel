<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Hospital-wide statistical analytics. Produces aggregate KPIs, chart series,
 * and ranked top-lists from the EXISTING UHMS tables — it never mutates data
 * and never invents a parallel source of truth.
 *
 * Every page returns a normalised structure consumed by one generic Blade view:
 *   [
 *     'key','title','description','filters',
 *     'kpis'   => [ ['label','value','format','color','route'(opt),'params'(opt)] ],
 *     'charts' => [ ['id','type','title','labels','datasets','colors'(opt)] ],
 *     'lists'  => [ ['title','columns','rows','drilldown'(opt)] ],
 *   ]
 *
 * Data-source rules (see docs/STATISTICAL_REPORTS_FORMULAS.md):
 *   diagnoses → diagnoses, complaints → complaints, prescribed → prescription_items,
 *   dispensed → dispensing_records, sold → invoice_items/payments,
 *   administered → medication_administrations, stock → stock_movements/balances,
 *   blood available → blood_units.status = AVAILABLE.
 */
class StatisticsService
{
    public function catalogue(): array
    {
        return [
            'activity' => ['title' => 'Hospital Activity', 'icon' => 'ti-activity', 'permission' => 'statistics.activity.view'],
            'diagnoses' => ['title' => 'Diagnosis Statistics', 'icon' => 'ti-clipboard-text', 'permission' => 'statistics.diagnosis.view'],
            'complaints' => ['title' => 'Complaint Statistics', 'icon' => 'ti-message-report', 'permission' => 'statistics.complaints.view'],
            'consultations' => ['title' => 'Consultation Statistics', 'icon' => 'ti-stethoscope', 'permission' => 'statistics.consultation.view'],
            'pharmacy' => ['title' => 'Pharmacy Statistics', 'icon' => 'ti-pill', 'permission' => 'statistics.pharmacy.view'],
            'investigations' => ['title' => 'Investigation Statistics', 'icon' => 'ti-microscope', 'permission' => 'statistics.investigations.view'],
            'procedures' => ['title' => 'Procedure / Theatre Statistics', 'icon' => 'ti-scalpel', 'permission' => 'statistics.procedures.view'],
            'emergency' => ['title' => 'Emergency Statistics', 'icon' => 'ti-ambulance', 'permission' => 'statistics.emergency.view'],
            'admission' => ['title' => 'Admission Statistics', 'icon' => 'ti-bed', 'permission' => 'statistics.admission.view'],
            'mar' => ['title' => 'Medication Administration Statistics', 'icon' => 'ti-checkup-list', 'permission' => 'statistics.mar.view'],
            'billing' => ['title' => 'Billing & Financial Statistics', 'icon' => 'ti-receipt', 'permission' => 'statistics.billing.view'],
            'claims' => ['title' => 'Claims Statistics', 'icon' => 'ti-file-dollar', 'permission' => 'statistics.claims.view'],
            'stock' => ['title' => 'Stock / Inventory Statistics', 'icon' => 'ti-packages', 'permission' => 'statistics.stock.view'],
            'blood-bank' => ['title' => 'Blood Bank Statistics', 'icon' => 'ti-droplet', 'permission' => 'statistics.blood_bank.view'],
            'staff-performance' => ['title' => 'Staff Performance', 'icon' => 'ti-users', 'permission' => 'statistics.staff_performance.view'],
        ];
    }

    public function build(string $key, array $filters = []): array
    {
        $range = $this->range($filters);
        $base = [
            'key' => $key,
            'title' => $this->catalogue()[$key]['title'] ?? ucfirst($key),
            'filters' => $range,
            'kpis' => [],
            'charts' => [],
            'lists' => [],
        ];

        $data = match ($key) {
            'activity' => $this->activity($range),
            'diagnoses' => $this->diagnoses($range),
            'complaints' => $this->complaints($range),
            'consultations' => $this->consultations($range),
            'pharmacy' => $this->pharmacy($range),
            'investigations' => $this->investigations($range),
            'procedures' => $this->procedures($range),
            'emergency' => $this->emergency($range),
            'admission' => $this->admission($range),
            'mar' => $this->mar($range),
            'billing' => $this->billing($range),
            'claims' => $this->claims($range),
            'stock' => $this->stock($range),
            'blood-bank' => $this->bloodBank($range),
            'staff-performance' => $this->staffPerformance($range),
            default => [],
        };

        return array_merge($base, $data);
    }

    /* ─────────────────────────── Dashboard ─────────────────────────── */

    public function dashboard(array $filters = []): array
    {
        $r = $this->range($filters);

        $newPatients = $this->count('patients', 'created_at', $r);
        $totalVisits = $this->count('visits', 'created_at', $r);
        $returningPatients = (int) $this->table('visits')
            ->whereDate('created_at', '>=', $r['from'])->whereDate('created_at', '<=', $r['to'])
            ->whereExists(fn ($q) => $q->from('visits as v2')
                ->whereColumn('v2.patient_id', 'visits.patient_id')
                ->whereColumn('v2.created_at', '<', 'visits.created_at'))
            ->distinct()->count(DB::raw('patient_id'));

        $billed = (float) $this->table('invoices')->whereDate('created_at', '>=', $r['from'])->whereDate('created_at', '<=', $r['to'])->sum('total_amount');
        $collected = (float) $this->table('payments')->where('is_reversal', false)->whereDate('paid_at', '>=', $r['from'])->whereDate('paid_at', '<=', $r['to'])->sum('amount');
        $outstanding = (float) $this->table('invoices')->whereIn('status', ['PENDING', 'PARTIALLY_PAID'])->sum('balance');

        $kpis = [
            $this->kpi('New Patients', $newPatients, 'number', 'primary', 'admin.statistics.activity'),
            $this->kpi('Total Visits', $totalVisits, 'number', 'primary', 'admin.statistics.activity'),
            $this->kpi('Returning Patients', $returningPatients, 'number', 'secondary'),
            $this->kpi('Emergency Cases', $this->count('emergency_cases', 'arrival_time', $r), 'number', 'danger', 'admin.statistics.emergency'),
            $this->kpi('Admissions', $this->count('admissions', 'admission_date', $r), 'number', 'info', 'admin.statistics.admission'),
            $this->kpi('Active Admissions', (int) $this->table('admissions')->where('status', 'ADMITTED')->count(), 'number', 'info', 'admin.statistics.admission'),
            $this->kpi('Consultations', $this->count('visit_consultation_routes', 'created_at', $r), 'number', 'success', 'admin.statistics.consultations'),
            $this->kpi('Diagnoses', $this->count('diagnoses', 'created_at', $r), 'number', 'success', 'admin.statistics.diagnoses'),
            $this->kpi('Prescriptions', $this->count('prescriptions', 'created_at', $r), 'number', 'warning', 'admin.statistics.pharmacy'),
            $this->kpi('Drugs Dispensed', (int) $this->table('dispensing_records')->whereDate('dispensed_at', '>=', $r['from'])->whereDate('dispensed_at', '<=', $r['to'])->count(), 'number', 'warning', 'admin.statistics.pharmacy'),
            $this->kpi('Investigations', $this->count('lab_requests', 'created_at', $r), 'number', 'info', 'admin.statistics.investigations'),
            $this->kpi('Procedures', (int) $this->table('procedure_requests')->whereDate('requested_at', '>=', $r['from'])->whereDate('requested_at', '<=', $r['to'])->count(), 'number', 'primary', 'admin.statistics.procedures'),
            $this->kpi('Total Billed', $billed, 'currency', 'success', 'admin.statistics.billing'),
            $this->kpi('Total Paid', $collected, 'currency', 'success', 'admin.statistics.billing'),
            $this->kpi('Outstanding', $outstanding, 'currency', 'danger', 'admin.statistics.billing'),
            $this->kpi('Blood Units Available', (int) $this->table('blood_units')->where('status', 'AVAILABLE')->count(), 'number', 'danger', 'admin.statistics.blood-bank'),
        ];

        return [
            'filters' => $r,
            'kpis' => $kpis,
            'charts' => [
                $this->dailyTrend('visits_trend', 'Visits Over Time', 'visits', 'created_at', $r),
                $this->revenueTrend($r),
            ],
            'catalogue' => $this->catalogue(),
            'collectionRate' => $billed > 0 ? round($collected / $billed * 100, 1) : 0,
        ];
    }

    /* ─────────────────────────── Domains ─────────────────────────── */

    protected function activity(array $r): array
    {
        $opd = (int) $this->table('visits')->where('visit_type', 'OPD')->whereDate('created_at', '>=', $r['from'])->whereDate('created_at', '<=', $r['to'])->count();
        $emergency = $this->count('emergency_cases', 'arrival_time', $r);
        $admissions = $this->count('admissions', 'admission_date', $r);
        $discharges = (int) $this->table('admissions')->whereNotNull('actual_discharge_date')->whereDate('actual_discharge_date', '>=', $r['from'])->whereDate('actual_discharge_date', '<=', $r['to'])->count();
        $deaths = (int) $this->table('patients')->where('is_deceased', true)->whereDate('deceased_at', '>=', $r['from'])->whereDate('deceased_at', '<=', $r['to'])->count();

        $genderRows = $this->table('visits')
            ->join('patients', 'patients.id', '=', 'visits.patient_id')
            ->whereDate('visits.created_at', '>=', $r['from'])->whereDate('visits.created_at', '<=', $r['to'])
            ->select('patients.gender', DB::raw('COUNT(DISTINCT visits.patient_id) as total'))
            ->groupBy('patients.gender')->get();

        return [
            'kpis' => [
                $this->kpi('Total Visits', $this->count('visits', 'created_at', $r), 'number', 'primary'),
                $this->kpi('OPD Visits', $opd, 'number', 'primary'),
                $this->kpi('Emergency Cases', $emergency, 'number', 'danger', 'admin.statistics.emergency'),
                $this->kpi('Admissions', $admissions, 'number', 'info', 'admin.statistics.admission'),
                $this->kpi('Discharges', $discharges, 'number', 'secondary'),
                $this->kpi('Deaths', $deaths, 'number', 'dark'),
                $this->kpi('Consultations', $this->count('visit_consultation_routes', 'created_at', $r), 'number', 'success'),
                $this->kpi('Investigations', $this->count('lab_requests', 'created_at', $r), 'number', 'info'),
            ],
            'charts' => [
                $this->dailyTrend('activity_visits', 'Visits Trend', 'visits', 'created_at', $r),
                $this->donut('visit_mix', 'Visit Mix', ['OPD', 'Emergency', 'Admission'], [$opd, $emergency, $admissions]),
                $this->donut('gender_mix', 'Patient Gender Distribution', $genderRows->pluck('gender')->map(fn ($g) => $g ?: 'Unknown')->all(), $genderRows->pluck('total')->all()),
            ],
            'lists' => [
                $this->dailyBreakdownList($r),
            ],
        ];
    }

    protected function diagnoses(array $r): array
    {
        $top = $this->table('diagnoses')
            ->whereDate('created_at', '>=', $r['from'])->whereDate('created_at', '<=', $r['to'])
            ->select('description', DB::raw('COUNT(*) as total'))
            ->groupBy('description')->orderByDesc('total')->limit(15)->get();

        $byType = $this->groupCount('diagnoses', 'type', 'created_at', $r);
        $primary = (int) $this->table('diagnoses')->where('is_primary', true)->whereDate('created_at', '>=', $r['from'])->whereDate('created_at', '<=', $r['to'])->count();

        return [
            'kpis' => [
                $this->kpi('Total Diagnoses', $this->count('diagnoses', 'created_at', $r), 'number', 'primary', 'admin.reports.diagnoses'),
                $this->kpi('Primary Diagnoses', $primary, 'number', 'danger'),
                $this->kpi('Distinct Conditions', (int) $this->table('diagnoses')->whereDate('created_at', '>=', $r['from'])->whereDate('created_at', '<=', $r['to'])->distinct()->count(DB::raw('description')), 'number', 'info'),
            ],
            'charts' => [
                $this->bar('top_dx', 'Top Diagnoses', $top->pluck('description')->all(), $top->pluck('total')->all()),
                $this->donut('dx_type', 'Diagnoses by Type', $byType->pluck('label')->all(), $byType->pluck('total')->all()),
            ],
            'lists' => [
                $this->rankedList('Top Diagnoses', ['Diagnosis', 'Cases'], $top, 'description', 'total', 'admin.reports.diagnoses', $r),
            ],
        ];
    }

    protected function complaints(array $r): array
    {
        $top = $this->table('complaints')
            ->whereDate('created_at', '>=', $r['from'])->whereDate('created_at', '<=', $r['to'])
            ->select('description', DB::raw('COUNT(*) as total'))
            ->groupBy('description')->orderByDesc('total')->limit(15)->get();
        $bySeverity = $this->groupCount('complaints', 'severity', 'created_at', $r);

        return [
            'kpis' => [
                $this->kpi('Total Complaints', $this->count('complaints', 'created_at', $r), 'number', 'primary', 'admin.reports.complaints'),
                $this->kpi('Distinct Complaints', (int) $this->table('complaints')->whereDate('created_at', '>=', $r['from'])->whereDate('created_at', '<=', $r['to'])->distinct()->count(DB::raw('description')), 'number', 'info'),
                $this->kpi('Linked to Emergency', (int) $this->table('complaints')->whereNotNull('emergency_case_id')->whereDate('created_at', '>=', $r['from'])->whereDate('created_at', '<=', $r['to'])->count(), 'number', 'danger'),
            ],
            'charts' => [
                $this->bar('top_complaints', 'Top Complaints', $top->pluck('description')->all(), $top->pluck('total')->all()),
                $this->donut('complaint_severity', 'Complaints by Severity', $bySeverity->pluck('label')->all(), $bySeverity->pluck('total')->all()),
            ],
            'lists' => [
                $this->rankedList('Top Complaints', ['Complaint', 'Count'], $top, 'description', 'total', 'admin.reports.complaints', $r),
            ],
        ];
    }

    protected function consultations(array $r): array
    {
        $byStatus = $this->groupCount('visit_consultation_routes', 'status', 'created_at', $r);
        $byDoctor = $this->staffAgg('visit_consultation_routes', 'doctor_id', 'created_at', $r);

        $completed = (int) $this->table('visit_consultation_routes')->where('status', 'COMPLETED')->whereDate('created_at', '>=', $r['from'])->whereDate('created_at', '<=', $r['to'])->count();
        $total = $this->count('visit_consultation_routes', 'created_at', $r);

        return [
            'kpis' => [
                $this->kpi('Consultation Sessions', $total, 'number', 'primary', 'admin.reports.consultations'),
                $this->kpi('Completed', $completed, 'number', 'success'),
                $this->kpi('Completion Rate', $total > 0 ? round($completed / $total * 100, 1) : 0, 'percent', 'info'),
            ],
            'charts' => [
                $this->bar('consult_doctor', 'Sessions per Doctor', $byDoctor->pluck('label')->all(), $byDoctor->pluck('total')->all()),
                $this->donut('consult_status', 'Sessions by Status', $byStatus->pluck('label')->all(), $byStatus->pluck('total')->all()),
            ],
            'lists' => [
                $this->rankedList('Most Active Doctors', ['Doctor', 'Sessions'], $byDoctor, 'label', 'total', 'admin.reports.consultations', $r),
            ],
        ];
    }

    protected function pharmacy(array $r): array
    {
        $prescribed = $this->table('prescription_items')
            ->join('prescriptions', 'prescriptions.id', '=', 'prescription_items.prescription_id')
            ->whereDate('prescriptions.created_at', '>=', $r['from'])->whereDate('prescriptions.created_at', '<=', $r['to'])
            ->select('prescription_items.drug_name as label', DB::raw('COUNT(*) as total'), DB::raw('SUM(prescription_items.quantity) as qty'))
            ->groupBy('prescription_items.drug_name')->orderByDesc('total')->limit(15)->get();

        $dispensed = $this->table('dispensing_records')
            ->join('prescription_items', 'prescription_items.id', '=', 'dispensing_records.prescription_item_id')
            ->whereDate('dispensing_records.dispensed_at', '>=', $r['from'])->whereDate('dispensing_records.dispensed_at', '<=', $r['to'])
            ->select('prescription_items.drug_name as label', DB::raw('SUM(dispensing_records.quantity_dispensed) as qty'), DB::raw('COUNT(*) as total'))
            ->groupBy('prescription_items.drug_name')->orderByDesc('qty')->limit(15)->get();

        $rxByStatus = $this->groupCount('prescriptions', 'status', 'created_at', $r);
        $drugRevenue = (float) $this->table('invoice_items')->whereNotNull('product_id')->whereDate('created_at', '>=', $r['from'])->whereDate('created_at', '<=', $r['to'])->sum('paid_amount');

        return [
            'kpis' => [
                $this->kpi('Prescriptions', $this->count('prescriptions', 'created_at', $r), 'number', 'primary'),
                $this->kpi('Dispense Events', (int) $this->table('dispensing_records')->whereDate('dispensed_at', '>=', $r['from'])->whereDate('dispensed_at', '<=', $r['to'])->count(), 'number', 'warning', 'admin.reports.pharmacy'),
                $this->kpi('Units Dispensed', (int) $this->table('dispensing_records')->whereDate('dispensed_at', '>=', $r['from'])->whereDate('dispensed_at', '<=', $r['to'])->sum('quantity_dispensed'), 'number', 'warning'),
                $this->kpi('Drug Sales Revenue', $drugRevenue, 'currency', 'success'),
            ],
            'charts' => [
                $this->bar('top_prescribed', 'Most Prescribed Drugs', $prescribed->pluck('label')->all(), $prescribed->pluck('total')->all()),
                $this->bar('top_dispensed', 'Most Dispensed Drugs (qty)', $dispensed->pluck('label')->all(), $dispensed->pluck('qty')->all()),
                $this->donut('rx_status', 'Prescriptions by Status', $rxByStatus->pluck('label')->all(), $rxByStatus->pluck('total')->all()),
            ],
            'lists' => [
                $this->rankedList('Most Prescribed (prescription items)', ['Drug', 'Prescriptions'], $prescribed, 'label', 'total', 'admin.reports.pharmacy', $r),
                $this->rankedList('Most Dispensed (dispensing records)', ['Drug', 'Qty Dispensed'], $dispensed, 'label', 'qty', 'admin.reports.pharmacy', $r),
            ],
        ];
    }

    protected function investigations(array $r): array
    {
        $top = $this->table('lab_request_items')
            ->join('lab_requests', 'lab_requests.id', '=', 'lab_request_items.lab_request_id')
            ->whereDate('lab_requests.created_at', '>=', $r['from'])->whereDate('lab_requests.created_at', '<=', $r['to'])
            ->select('lab_request_items.name as label', DB::raw('COUNT(*) as total'))
            ->groupBy('lab_request_items.name')->orderByDesc('total')->limit(15)->get();
        $byStatus = $this->groupCount('lab_requests', 'status', 'created_at', $r);
        $emergency = (int) $this->table('lab_requests')->where('is_emergency', true)->whereDate('created_at', '>=', $r['from'])->whereDate('created_at', '<=', $r['to'])->count();

        return [
            'kpis' => [
                $this->kpi('Requests', $this->count('lab_requests', 'created_at', $r), 'number', 'primary', 'admin.reports.investigations'),
                $this->kpi('Emergency Requests', $emergency, 'number', 'danger'),
                $this->kpi('Distinct Tests', (int) $this->table('lab_request_items')->join('lab_requests', 'lab_requests.id', '=', 'lab_request_items.lab_request_id')->whereDate('lab_requests.created_at', '>=', $r['from'])->whereDate('lab_requests.created_at', '<=', $r['to'])->distinct()->count(DB::raw('lab_request_items.name')), 'number', 'info'),
            ],
            'charts' => [
                $this->bar('top_tests', 'Most Requested Investigations', $top->pluck('label')->all(), $top->pluck('total')->all()),
                $this->donut('lab_status', 'Requests by Status', $byStatus->pluck('label')->all(), $byStatus->pluck('total')->all()),
            ],
            'lists' => [
                $this->rankedList('Most Requested Investigations', ['Investigation', 'Requests'], $top, 'label', 'total', 'admin.reports.investigations', $r),
            ],
        ];
    }

    protected function procedures(array $r): array
    {
        $byStatus = $this->groupCount('procedure_requests', 'status', 'requested_at', $r);
        $total = (int) $this->table('procedure_requests')->whereDate('requested_at', '>=', $r['from'])->whereDate('requested_at', '<=', $r['to'])->count();
        $completed = (int) $this->table('procedure_requests')->where('status', 'COMPLETED')->whereDate('requested_at', '>=', $r['from'])->whereDate('requested_at', '<=', $r['to'])->count();
        $cancelled = (int) $this->table('procedure_requests')->whereIn('status', ['CANCELLED', 'POSTPONED', 'REJECTED'])->whereDate('requested_at', '>=', $r['from'])->whereDate('requested_at', '<=', $r['to'])->count();

        $byDept = $this->table('procedure_requests')
            ->leftJoin('departments', 'departments.id', '=', 'procedure_requests.department_id')
            ->whereDate('procedure_requests.requested_at', '>=', $r['from'])->whereDate('procedure_requests.requested_at', '<=', $r['to'])
            ->select(DB::raw("COALESCE(departments.name, 'Unassigned') as label"), DB::raw('COUNT(*) as total'))
            ->groupBy('departments.name')->orderByDesc('total')->limit(15)->get();

        return [
            'kpis' => [
                $this->kpi('Procedures Requested', $total, 'number', 'primary', 'admin.reports.procedures'),
                $this->kpi('Completed', $completed, 'number', 'success'),
                $this->kpi('Cancelled / Postponed', $cancelled, 'number', 'danger'),
                $this->kpi('Completion Rate', $total > 0 ? round($completed / $total * 100, 1) : 0, 'percent', 'info'),
            ],
            'charts' => [
                $this->donut('proc_status', 'Procedures by Status', $byStatus->pluck('label')->all(), $byStatus->pluck('total')->all()),
                $this->bar('proc_dept', 'Procedures by Department', $byDept->pluck('label')->all(), $byDept->pluck('total')->all()),
            ],
            'lists' => [
                $this->rankedList('Procedures by Department', ['Department', 'Count'], $byDept, 'label', 'total', 'admin.reports.procedures', $r),
            ],
        ];
    }

    protected function emergency(array $r): array
    {
        $byTriage = $this->groupCount('emergency_cases', 'final_triage_category', 'arrival_time', $r);
        $byDisposition = $this->groupCount('emergency_cases', 'disposition', 'arrival_time', $r);
        $byArrival = $this->groupCount('emergency_cases', 'arrival_mode', 'arrival_time', $r);

        $avgWait = $this->avgMinutesBetween('emergency_cases', 'arrival_time', 'triaged_at', 'arrival_time', $r);

        return [
            'kpis' => [
                $this->kpi('Emergency Cases', $this->count('emergency_cases', 'arrival_time', $r), 'number', 'danger', 'admin.reports.emergency'),
                $this->kpi('Avg Triage Wait (min)', round($avgWait ?? 0, 1), 'number', 'warning'),
                $this->kpi('Admitted from ER', (int) $this->table('emergency_cases')->where('disposition', 'ADMITTED')->whereDate('arrival_time', '>=', $r['from'])->whereDate('arrival_time', '<=', $r['to'])->count(), 'number', 'info'),
            ],
            'charts' => [
                $this->donut('er_triage', 'Triage Categories', $byTriage->pluck('label')->all(), $byTriage->pluck('total')->all(), ['#dc3545', '#fd7e14', '#ffc107', '#198754', '#212529']),
                $this->donut('er_disposition', 'Dispositions', $byDisposition->pluck('label')->all(), $byDisposition->pluck('total')->all()),
                $this->bar('er_arrival', 'Arrival Mode', $byArrival->pluck('label')->all(), $byArrival->pluck('total')->all()),
            ],
            'lists' => [
                $this->groupList('Triage Breakdown', ['Triage Category', 'Cases'], $byTriage, 'admin.reports.emergency', $r),
            ],
        ];
    }

    protected function admission(array $r): array
    {
        $active = (int) $this->table('admissions')->where('status', 'ADMITTED')->count();
        $discharged = (int) $this->table('admissions')->whereNotNull('actual_discharge_date')->whereDate('actual_discharge_date', '>=', $r['from'])->whereDate('actual_discharge_date', '<=', $r['to'])->count();
        $avgLos = $this->avgDaysBetween('admissions', 'admission_date', 'actual_discharge_date', 'actual_discharge_date', $r);

        $totalBeds = (int) $this->table('beds')->count();
        $occupiedBeds = (int) $this->table('beds')->where('status', 'OCCUPIED')->count();

        $byWard = $this->table('beds')
            ->join('wards', 'wards.id', '=', 'beds.ward_id')
            ->select('wards.name as label', DB::raw('COUNT(*) as total'), DB::raw("SUM(CASE WHEN beds.status = 'OCCUPIED' THEN 1 ELSE 0 END) as occupied"))
            ->groupBy('wards.id', 'wards.name')->orderByDesc('total')->get();

        return [
            'kpis' => [
                $this->kpi('Admissions', $this->count('admissions', 'admission_date', $r), 'number', 'primary', 'admin.reports.admission'),
                $this->kpi('Active Admissions', $active, 'number', 'info'),
                $this->kpi('Discharges', $discharged, 'number', 'secondary'),
                $this->kpi('Avg Length of Stay (days)', round($avgLos ?? 0, 1), 'number', 'warning'),
                $this->kpi('Bed Occupancy', $totalBeds > 0 ? round($occupiedBeds / $totalBeds * 100, 1) : 0, 'percent', 'danger'),
            ],
            'charts' => [
                $this->bar('ward_beds', 'Beds by Ward', $byWard->pluck('label')->all(), $byWard->pluck('total')->all()),
                $this->bar('ward_occupied', 'Occupied Beds by Ward', $byWard->pluck('label')->all(), $byWard->pluck('occupied')->all()),
            ],
            'lists' => [
                [
                    'title' => 'Ward Bed Occupancy',
                    'columns' => ['Ward', 'Total Beds', 'Occupied', 'Occupancy %'],
                    'rows' => $byWard->map(fn ($w) => ['cells' => [
                        $w->label, $w->total, $w->occupied,
                        ($w->total > 0 ? round($w->occupied / $w->total * 100, 1) : 0).'%',
                    ]])->all(),
                ],
            ],
        ];
    }

    protected function mar(array $r): array
    {
        $byStatus = $this->groupCount('medication_administrations', 'status', 'administered_at', $r);
        $given = (int) $this->table('medication_administrations')->where('status', 'GIVEN')->whereDate('administered_at', '>=', $r['from'])->whereDate('administered_at', '<=', $r['to'])->count();
        $total = $this->count('medication_administrations', 'administered_at', $r);
        $reactions = (int) $this->table('medication_administrations')->whereNotNull('reaction')->where('reaction', '!=', '')->whereDate('administered_at', '>=', $r['from'])->whereDate('administered_at', '<=', $r['to'])->count();

        $byNurse = $this->staffAgg('medication_administrations', 'administered_by', 'administered_at', $r);

        return [
            'kpis' => [
                $this->kpi('Administrations', $total, 'number', 'primary', 'admin.reports.mar'),
                $this->kpi('Given', $given, 'number', 'success'),
                $this->kpi('Compliance', $total > 0 ? round($given / $total * 100, 1) : 0, 'percent', 'info'),
                $this->kpi('Adverse Reactions', $reactions, 'number', 'danger'),
            ],
            'charts' => [
                $this->donut('mar_status', 'Administration Status', $byStatus->pluck('label')->all(), $byStatus->pluck('total')->all()),
                $this->bar('mar_nurse', 'Administrations per Nurse', $byNurse->pluck('label')->all(), $byNurse->pluck('total')->all()),
            ],
            'lists' => [
                $this->rankedList('Most Active Nurses', ['Nurse', 'Administrations'], $byNurse, 'label', 'total', 'admin.reports.mar', $r),
            ],
        ];
    }

    protected function billing(array $r): array
    {
        $billed = (float) $this->table('invoices')->whereDate('created_at', '>=', $r['from'])->whereDate('created_at', '<=', $r['to'])->sum('total_amount');
        $paid = (float) $this->table('payments')->where('is_reversal', false)->whereDate('paid_at', '>=', $r['from'])->whereDate('paid_at', '<=', $r['to'])->sum('amount');
        $outstanding = (float) $this->table('invoices')->whereIn('status', ['PENDING', 'PARTIALLY_PAID'])->sum('balance');
        $discounts = (float) $this->table('invoices')->whereDate('created_at', '>=', $r['from'])->whereDate('created_at', '<=', $r['to'])->sum('discount_amount');

        $byMethod = $this->table('payments')
            ->where('is_reversal', false)->whereDate('paid_at', '>=', $r['from'])->whereDate('paid_at', '<=', $r['to'])
            ->select('payment_method as label', DB::raw('SUM(amount) as total'))->groupBy('payment_method')->orderByDesc('total')->get();

        $byDept = $this->table('invoice_items')
            ->leftJoin('departments', 'departments.id', '=', 'invoice_items.department_id')
            ->whereDate('invoice_items.created_at', '>=', $r['from'])->whereDate('invoice_items.created_at', '<=', $r['to'])
            ->select(DB::raw("COALESCE(departments.name, 'Unassigned') as label"), DB::raw('SUM(invoice_items.paid_amount) as total'))
            ->groupBy('departments.name')->orderByDesc('total')->limit(15)->get();

        return [
            'kpis' => [
                $this->kpi('Total Billed', $billed, 'currency', 'primary'),
                $this->kpi('Total Paid', $paid, 'currency', 'success', 'admin.reports.billing'),
                $this->kpi('Outstanding', $outstanding, 'currency', 'danger'),
                $this->kpi('Discounts', $discounts, 'currency', 'warning'),
                $this->kpi('Collection Rate', $billed > 0 ? round($paid / $billed * 100, 1) : 0, 'percent', 'info'),
            ],
            'charts' => [
                $this->revenueTrend($r),
                $this->donut('pay_method', __('reports.billing.revenue_by_payment_method'), $byMethod->pluck('label')->all(), $byMethod->pluck('total')->map(fn ($v) => round((float) $v, 2))->all()),
                $this->bar('rev_dept', __('accounting.revenue_by_department'), $byDept->pluck('label')->all(), $byDept->pluck('total')->map(fn ($v) => round((float) $v, 2))->all()),
            ],
            'lists' => [
                [
                    'title' => __('accounting.revenue_by_department'),
                    'columns' => [__('reports.col_department'), __('reports.billing.collected_ghs')],
                    'rows' => $byDept->map(fn ($d) => ['cells' => [$d->label, number_format((float) $d->total, 2)]])->all(),
                ],
            ],
        ];
    }

    protected function claims(array $r): array
    {
        $byStatus = $this->groupCount('claims', 'status', 'claim_date', $r);
        $submitted = (int) $this->table('claims')->whereNotNull('submitted_at')->whereDate('claim_date', '>=', $r['from'])->whereDate('claim_date', '<=', $r['to'])->count();
        $approved = (int) $this->table('claims')->where('status', 'APPROVED')->whereDate('claim_date', '>=', $r['from'])->whereDate('claim_date', '<=', $r['to'])->count();
        $claimAmount = (float) $this->table('claims')->whereDate('claim_date', '>=', $r['from'])->whereDate('claim_date', '<=', $r['to'])->sum('total_claim_amount');
        $paidAmount = (float) $this->table('claims')->whereDate('claim_date', '>=', $r['from'])->whereDate('claim_date', '<=', $r['to'])->sum('paid_amount');

        return [
            'kpis' => [
                $this->kpi('Total Claims', $this->count('claims', 'claim_date', $r), 'number', 'primary', 'admin.reports.claims'),
                $this->kpi('Submitted', $submitted, 'number', 'info'),
                $this->kpi('Approved', $approved, 'number', 'success'),
                $this->kpi('Approval Rate', $submitted > 0 ? round($approved / $submitted * 100, 1) : 0, 'percent', 'info'),
                $this->kpi('Claim Amount', $claimAmount, 'currency', 'warning'),
                $this->kpi('Paid Amount', $paidAmount, 'currency', 'success'),
            ],
            'charts' => [
                $this->donut('claim_status', 'Claims by Status', $byStatus->pluck('label')->all(), $byStatus->pluck('total')->all()),
            ],
            'lists' => [
                $this->groupList('Claims by Status', ['Status', 'Count'], $byStatus, 'admin.reports.claims', $r),
            ],
        ];
    }

    protected function stock(array $r): array
    {
        $fastMoving = $this->table('stock_movements')
            ->leftJoin('products', 'products.id', '=', 'stock_movements.product_id')
            ->where('stock_movements.direction', 'OUT')
            ->whereDate('stock_movements.movement_date', '>=', $r['from'])->whereDate('stock_movements.movement_date', '<=', $r['to'])
            ->select('stock_movements.product_id', 'products.name as pname', DB::raw('SUM(stock_movements.quantity) as qty'))
            ->groupBy('stock_movements.product_id', 'products.name')->orderByDesc('qty')->limit(15)->get()
            ->map(fn ($row) => (object) ['label' => $row->pname ?: ('#'.$row->product_id), 'qty' => (float) $row->qty]);

        $byLocation = $this->table('stock_balances')
            ->join('stock_locations', 'stock_locations.id', '=', 'stock_balances.stock_location_id')
            ->select('stock_locations.name as label', DB::raw('SUM(stock_balances.quantity_on_hand) as qty'))
            ->groupBy('stock_locations.id', 'stock_locations.name')->orderByDesc('qty')->get();

        $lowStock = (int) $this->table('stock_balances')->where('quantity_on_hand', '>', 0)->where('quantity_on_hand', '<=', 10)->count();
        $outOfStock = (int) $this->table('stock_balances')->where('quantity_on_hand', '<=', 0)->count();

        return [
            'kpis' => [
                $this->kpi('Stock Movements', (int) $this->table('stock_movements')->whereDate('movement_date', '>=', $r['from'])->whereDate('movement_date', '<=', $r['to'])->count(), 'number', 'primary', 'admin.reports.stock'),
                $this->kpi('Low Stock Items (≤10)', $lowStock, 'number', 'warning'),
                $this->kpi('Out of Stock Items', $outOfStock, 'number', 'danger'),
            ],
            'charts' => [
                $this->bar('fast_moving', 'Fast-Moving Products (OUT qty)', $fastMoving->pluck('label')->all(), $fastMoving->pluck('qty')->all()),
                $this->bar('stock_location', 'Stock On Hand by Location', $byLocation->pluck('label')->all(), $byLocation->pluck('qty')->all()),
            ],
            'lists' => [
                $this->rankedList('Fast-Moving Products', ['Product', 'Qty Out'], $fastMoving, 'label', 'qty', 'admin.reports.stock', $r),
            ],
        ];
    }

    protected function bloodBank(array $r): array
    {
        $byGroup = $this->table('blood_units')->where('status', 'AVAILABLE')
            ->select('blood_group as label', DB::raw('COUNT(*) as total'))->groupBy('blood_group')->orderBy('blood_group')->get();
        $byComponent = $this->table('blood_units')->where('status', 'AVAILABLE')
            ->select('component_type as label', DB::raw('COUNT(*) as total'))->groupBy('component_type')->get();

        $xmTotal = (int) $this->table('blood_crossmatches')->whereDate('performed_at', '>=', $r['from'])->whereDate('performed_at', '<=', $r['to'])->count();
        $xmCompatible = (int) $this->table('blood_crossmatches')->where('result', 'COMPATIBLE')->whereDate('performed_at', '>=', $r['from'])->whereDate('performed_at', '<=', $r['to'])->count();
        $deferred = (int) $this->table('blood_donors')->whereIn('screening_status', ['TEMPORARILY_DEFERRED', 'PERMANENTLY_DEFERRED'])->count();
        $reactions = (int) $this->table('blood_issues')->where('reaction_occurred', true)->whereDate('issued_at', '>=', $r['from'])->whereDate('issued_at', '<=', $r['to'])->count();

        return [
            'kpis' => [
                $this->kpi('Units Available', (int) $this->table('blood_units')->where('status', 'AVAILABLE')->count(), 'number', 'danger', 'admin.reports.blood-bank'),
                $this->kpi('Units Issued', (int) $this->table('blood_issues')->whereDate('issued_at', '>=', $r['from'])->whereDate('issued_at', '<=', $r['to'])->count(), 'number', 'primary'),
                $this->kpi('Crossmatch Compatibility', $xmTotal > 0 ? round($xmCompatible / $xmTotal * 100, 1) : 0, 'percent', 'info'),
                $this->kpi('Deferred Donors', $deferred, 'number', 'warning'),
                $this->kpi('Transfusion Reactions', $reactions, 'number', 'dark'),
                $this->kpi('Expired / Discarded', (int) $this->table('blood_units')->whereIn('status', ['EXPIRED', 'DISCARDED', 'REJECTED'])->count(), 'number', 'secondary'),
            ],
            'charts' => [
                $this->bar('blood_group', 'Available Units by Blood Group', $byGroup->pluck('label')->all(), $byGroup->pluck('total')->all(), ['#dc3545']),
                $this->donut('blood_component', 'Available Units by Component', $byComponent->pluck('label')->all(), $byComponent->pluck('total')->all()),
            ],
            'lists' => [
                [
                    'title' => 'Blood Inventory (Available)',
                    'columns' => ['Blood Group', 'Available Units'],
                    'rows' => $byGroup->map(fn ($g) => ['cells' => [$g->label, $g->total]])->all(),
                    'drilldown' => ['route' => 'admin.reports.blood-bank', 'label' => 'Open Blood Bank Report', 'params' => $r],
                ],
            ],
        ];
    }

    protected function staffPerformance(array $r): array
    {
        $diagnosesByDoctor = $this->staffAgg('diagnoses', 'doctor_id', 'created_at', $r);
        $dispensingByPharmacist = $this->staffAgg('dispensing_records', 'dispensed_by', 'dispensed_at', $r);
        $marByNurse = $this->staffAgg('medication_administrations', 'administered_by', 'administered_at', $r);
        $paymentsByCashier = $this->staffAgg('payments', 'received_by', 'paid_at', $r);

        return [
            'kpis' => [
                $this->kpi('Active Clinicians', $diagnosesByDoctor->count(), 'number', 'primary'),
                $this->kpi('Active Nurses (MAR)', $marByNurse->count(), 'number', 'info'),
                $this->kpi('Active Pharmacists', $dispensingByPharmacist->count(), 'number', 'warning'),
                $this->kpi('Active Cashiers', $paymentsByCashier->count(), 'number', 'success'),
            ],
            'charts' => [
                $this->bar('perf_dx', 'Diagnoses per Doctor', $diagnosesByDoctor->pluck('label')->all(), $diagnosesByDoctor->pluck('total')->all()),
                $this->bar('perf_mar', 'Administrations per Nurse', $marByNurse->pluck('label')->all(), $marByNurse->pluck('total')->all()),
            ],
            'lists' => [
                $this->rankedList('Diagnoses per Doctor', ['Doctor', 'Diagnoses'], $diagnosesByDoctor, 'label', 'total'),
                $this->rankedList('Dispenses per Pharmacist', ['Pharmacist', 'Dispenses'], $dispensingByPharmacist, 'label', 'total'),
                $this->rankedList('Administrations per Nurse', ['Nurse', 'Administrations'], $marByNurse, 'label', 'total'),
                $this->rankedList('Payments per Cashier', ['Cashier', 'Payments'], $paymentsByCashier, 'label', 'total'),
            ],
        ];
    }

    /* ─────────────────────────── Helpers ─────────────────────────── */

    protected function range(array $filters): array
    {
        $from = ! empty($filters['date_from']) ? Carbon::parse($filters['date_from'])->toDateString() : now()->startOfMonth()->toDateString();
        $to = ! empty($filters['date_to']) ? Carbon::parse($filters['date_to'])->toDateString() : now()->toDateString();

        return ['from' => $from, 'to' => $to, 'date_from' => $from, 'date_to' => $to];
    }

    protected function table(string $name): Builder
    {
        return DB::table($name);
    }

    protected function count(string $table, string $column, array $r): int
    {
        return (int) $this->table($table)->whereDate($column, '>=', $r['from'])->whereDate($column, '<=', $r['to'])->count();
    }

    protected function groupCount(string $table, string $column, string $dateColumn, array $r)
    {
        return $this->table($table)
            ->whereDate($dateColumn, '>=', $r['from'])->whereDate($dateColumn, '<=', $r['to'])
            ->whereNotNull($column)
            ->select($column.' as label', DB::raw('COUNT(*) as total'))
            ->groupBy($column)->orderByDesc('total')->limit(20)->get()
            ->map(fn ($row) => (object) ['label' => (string) ($row->label ?: '—'), 'total' => (int) $row->total]);
    }

    /**
     * Count rows per acting user. Names are concatenated in PHP so the query
     * stays portable (sqlite tests have no CONCAT; MySQL/MariaDB in production).
     */
    protected function staffAgg(string $table, string $userColumn, string $dateColumn, array $r)
    {
        return $this->table($table)
            ->join('users', 'users.id', '=', $table.'.'.$userColumn)
            ->whereDate($table.'.'.$dateColumn, '>=', $r['from'])->whereDate($table.'.'.$dateColumn, '<=', $r['to'])
            ->select('users.id', 'users.first_name', 'users.last_name', DB::raw('COUNT(*) as total'))
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->orderByDesc('total')->limit(20)->get()
            ->map(fn ($row) => (object) [
                'label' => trim($row->first_name.' '.$row->last_name) ?: ('User #'.$row->id),
                'total' => (int) $row->total,
            ]);
    }

    /** DB-portable average minutes between two timestamps over a period. */
    protected function avgMinutesBetween(string $table, string $startCol, string $endCol, string $dateCol, array $r): float
    {
        $rows = $this->table($table)
            ->whereNotNull($startCol)->whereNotNull($endCol)
            ->whereDate($dateCol, '>=', $r['from'])->whereDate($dateCol, '<=', $r['to'])
            ->select($startCol.' as s', $endCol.' as e')->limit(10000)->get();

        $sum = 0;
        $n = 0;
        foreach ($rows as $row) {
            if ($row->s && $row->e) {
                $sum += Carbon::parse($row->s)->diffInMinutes(Carbon::parse($row->e));
                $n++;
            }
        }

        return $n > 0 ? $sum / $n : 0.0;
    }

    /** DB-portable average days between two timestamps over a period. */
    protected function avgDaysBetween(string $table, string $startCol, string $endCol, string $dateCol, array $r): float
    {
        return $this->avgMinutesBetween($table, $startCol, $endCol, $dateCol, $r) / 1440;
    }

    protected function kpi(string $label, $value, string $format = 'number', string $color = 'primary', ?string $route = null): array
    {
        return array_filter([
            'label' => $label,
            'value' => $value,
            'format' => $format,
            'color' => $color,
            'route' => $route,
        ], fn ($v) => $v !== null);
    }

    protected function bar(string $id, string $title, array $labels, array $data, ?array $colors = null): array
    {
        return [
            'id' => $id, 'type' => 'bar', 'title' => $title,
            'labels' => array_values($labels),
            'datasets' => [['label' => $title, 'data' => array_map(fn ($v) => (float) $v, array_values($data))]],
            'colors' => $colors,
        ];
    }

    protected function donut(string $id, string $title, array $labels, array $data, ?array $colors = null): array
    {
        return [
            'id' => $id, 'type' => 'doughnut', 'title' => $title,
            'labels' => array_values($labels),
            'datasets' => [['label' => $title, 'data' => array_map(fn ($v) => (float) $v, array_values($data))]],
            'colors' => $colors,
        ];
    }

    protected function dailyTrend(string $id, string $title, string $table, string $column, array $r): array
    {
        $rows = $this->table($table)
            ->whereDate($column, '>=', $r['from'])->whereDate($column, '<=', $r['to'])
            ->select(DB::raw("DATE($column) as d"), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw("DATE($column)"))->orderBy('d')->get();

        return [
            'id' => $id, 'type' => 'line', 'title' => $title,
            'labels' => $rows->pluck('d')->map(fn ($d) => Carbon::parse($d)->format('d M'))->all(),
            'datasets' => [['label' => $title, 'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all()]],
        ];
    }

    protected function revenueTrend(array $r): array
    {
        $rows = $this->table('payments')
            ->where('is_reversal', false)
            ->whereDate('paid_at', '>=', $r['from'])->whereDate('paid_at', '<=', $r['to'])
            ->select(DB::raw('DATE(paid_at) as d'), DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('DATE(paid_at)'))->orderBy('d')->get();

        return [
            'id' => 'revenue_trend', 'type' => 'line', 'title' => __('reports.billing.revenue_over_time'),
            'labels' => $rows->pluck('d')->map(fn ($d) => Carbon::parse($d)->format('d M'))->all(),
            'datasets' => [['label' => __('reports.billing.collected_ghs'), 'data' => $rows->pluck('total')->map(fn ($v) => round((float) $v, 2))->all()]],
        ];
    }

    protected function rankedList(string $title, array $columns, $rows, string $labelKey, string $valueKey, ?string $route = null, array $params = []): array
    {
        return [
            'title' => $title,
            'columns' => $columns,
            'rows' => collect($rows)->map(fn ($row) => [
                'cells' => [is_object($row) ? ($row->{$labelKey} ?? '—') : ($row[$labelKey] ?? '—'), is_object($row) ? ($row->{$valueKey} ?? 0) : ($row[$valueKey] ?? 0)],
            ])->all(),
            'drilldown' => $route ? ['route' => $route, 'label' => 'Open detailed report', 'params' => $params] : null,
        ];
    }

    protected function groupList(string $title, array $columns, $rows, ?string $route = null, array $params = []): array
    {
        return [
            'title' => $title,
            'columns' => $columns,
            'rows' => collect($rows)->map(fn ($row) => ['cells' => [$row->label, $row->total]])->all(),
            'drilldown' => $route ? ['route' => $route, 'label' => 'Open detailed report', 'params' => $params] : null,
        ];
    }

    protected function dailyBreakdownList(array $r): array
    {
        $visits = $this->table('visits')
            ->whereDate('created_at', '>=', $r['from'])->whereDate('created_at', '<=', $r['to'])
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN visit_type = 'OPD' THEN 1 ELSE 0 END) as opd"),
                DB::raw("SUM(CASE WHEN visit_type = 'EMERGENCY' THEN 1 ELSE 0 END) as emergency"))
            ->groupBy(DB::raw('DATE(created_at)'))->orderByDesc('d')->limit(31)->get();

        return [
            'title' => 'Daily Activity Breakdown',
            'columns' => ['Date', 'Total Visits', 'OPD', 'Emergency'],
            'rows' => $visits->map(fn ($v) => ['cells' => [Carbon::parse($v->d)->format('d M Y'), $v->total, $v->opd, $v->emergency]])->all(),
        ];
    }
}
