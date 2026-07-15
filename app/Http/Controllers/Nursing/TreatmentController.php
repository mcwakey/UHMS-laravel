<?php

namespace App\Http\Controllers\Nursing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nursing\Concerns\ResolvesNursingDepartment;
use App\Models\Visit;
use App\Services\Nursing\NursingOpdService;
use Illuminate\Http\Request;

class TreatmentController extends Controller
{
    use ResolvesNursingDepartment;

    public function index(Request $request, NursingOpdService $opd)
    {
        $department = $this->nursingDepartment($request);
        $treatments = $opd->treatments($department)->with(['patient:id,patient_number,first_name,last_name', 'visit:id,visit_number,status', 'doctor:id,name'])
            ->latest()->paginate(20)->withQueryString();

        return view('nursing.treatments.index', compact('treatments'));
    }

    public function show(Request $request, Visit $visit, NursingOpdService $opd)
    {
        $department = $this->nursingDepartment($request);
        $opd->ensureAccessible($visit, $department);
        $visit->load('patient');
        $treatments = $opd->treatments($department)->where('visit_id', $visit->id)->with('doctor')->latest()->get();

        return view('nursing.treatments.show', compact('visit', 'treatments'));
    }
}
