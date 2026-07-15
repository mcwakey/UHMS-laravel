<?php

namespace App\Http\Controllers\Nursing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nursing\Concerns\ResolvesNursingDepartment;
use App\Models\Visit;
use App\Services\Nursing\NursingOpdService;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    use ResolvesNursingDepartment;

    public function index(Request $request, NursingOpdService $opd)
    {
        $department = $this->nursingDepartment($request);
        $query = $opd->query($department)
            ->whereHas('consultationRoutes')
            ->with(['patient:id,patient_number,first_name,last_name', 'currentDepartment', 'consultationRoutes.department', 'consultationRoutes.doctor'])
            ->withCount('consultationRoutes');

        if ($request->filled('status')) {
            $query->whereHas('consultationRoutes', fn ($routes) => $routes->where('status', $request->string('status')));
        }
        if ($request->filled('search')) {
            $search = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $request->string('search')->trim()).'%';
            $query->where(fn ($visits) => $visits->where('visit_number', 'like', $search)
                ->orWhereHas('patient', fn ($patients) => $patients->where('patient_number', 'like', $search)));
        }

        $visits = $query->latest('visit_date')->latest('id')->paginate(20)->withQueryString();

        return view('nursing.consultations.index', compact('department', 'visits'));
    }

    public function show(Request $request, Visit $visit, NursingOpdService $opd)
    {
        $department = $this->nursingDepartment($request);
        $opd->ensureAccessible($visit, $department);
        $visit->load([
            'patient', 'currentDepartment', 'triage', 'vitals.recordedBy',
            'consultationRoutes.department', 'consultationRoutes.doctor', 'consultationRoutes.primaryNurse',
            'medicalRecords.doctor', 'medicalRecords.department', 'medicalRecords.complaints',
            'medicalRecords.diagnoses', 'medicalRecords.treatments', 'medicalRecords.tasks.assignedUser',
        ]);

        return view('nursing.consultations.show', compact('department', 'visit'));
    }

    public function history(Request $request, Visit $visit, NursingOpdService $opd)
    {
        return $this->show($request, $visit, $opd);
    }
}
