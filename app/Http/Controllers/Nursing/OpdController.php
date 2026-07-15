<?php

namespace App\Http\Controllers\Nursing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nursing\Concerns\ResolvesNursingDepartment;
use App\Models\Visit;
use App\Services\Nursing\NursingOpdService;
use Illuminate\Http\Request;

class OpdController extends Controller
{
    use ResolvesNursingDepartment;

    public function index(Request $request, NursingOpdService $opd, ?string $category = null)
    {
        $department = $this->nursingDepartment($request);
        $category = $category ?: (string) $request->query('category', 'all');
        $allowed = ['all', 'active', 'waiting_for_triage', 'triage_in_progress', 'vitals_incomplete', 'waiting_for_consultation', 'consultation_in_progress', 'nursing_action_required', 'treatment_pending', 'service_follow_up', 'ready_for_discharge', 'completed_today'];
        $category = in_array($category, $allowed, true) ? $category : 'all';

        $query = $opd->category($department, $category)
            ->with(['patient:id,patient_number,first_name,last_name', 'triage', 'latestVitals', 'currentDepartment'])
            ->withCount(['clinicalTasks as pending_tasks_count' => fn ($tasks) => $tasks->whereNotIn('status', ['COMPLETED', 'CANCELLED'])]);

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }
        if ($request->filled('search')) {
            $search = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $request->string('search')->trim()).'%';
            $query->where(fn ($q) => $q->where('visit_number', 'like', $search)
                ->orWhereHas('patient', fn ($patient) => $patient->where('patient_number', 'like', $search)));
        }

        $visits = $query->latest('arrived_at')->latest('id')->paginate(20)->withQueryString();
        $metrics = $opd->metrics($department);

        return view('nursing.opd.index', compact('department', 'visits', 'metrics', 'category'));
    }

    public function show(Request $request, Visit $visit, NursingOpdService $opd)
    {
        $department = $this->nursingDepartment($request);
        $opd->ensureAccessible($visit, $department);
        $visit->load([
            'patient', 'currentDepartment', 'triage.triagedBy', 'vitals.recordedBy',
            'clinicalTasks.assignedUser', 'medicalRecord', 'serviceRenderings',
            'consultationRoutes.department', 'consultationRoutes.doctor',
        ]);
        $treatments = $opd->treatments($department)->where('visit_id', $visit->id)->with('doctor')->latest()->get();

        return view('nursing.opd.show', compact('department', 'visit', 'treatments'));
    }
}
