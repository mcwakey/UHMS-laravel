<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\PatientProcedure;
use App\Models\Procedure;
use App\Services\ClinicalService;
use Illuminate\Http\Request;

class ProcedureController extends Controller
{
    public function __construct(protected ClinicalService $clinicalService)
    {
    }

    /*
    |--------------------------------------------------------------------------
    | Procedure Catalog CRUD
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        // Legacy `procedures` catalogue is deprecated — the canonical source is now
        // service_catalogs filtered by procedure/theatre departments (Section 16 of UHMS spec).
        // Redirect to the service-based Procedure Catalogue so users no longer
        // see two parallel pickers.
        if (\Illuminate\Support\Facades\Route::has('admin.procedure-catalogue.index')) {
            return redirect()->route('admin.procedure-catalogue.index');
        }

        $procedures = $this->clinicalService->listProcedures($request->only('search', 'category', 'department_id', 'is_active'));
        $departments = Department::orderBy('name')->pluck('name', 'id');
        $categories = ['surgical', 'diagnostic', 'therapeutic', 'other'];
        $stats = $this->clinicalService->getProcedureStats();

        return view('admin.procedures.index', compact('procedures', 'departments', 'categories', 'stats'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'category' => ['required', 'in:surgical,diagnostic,therapeutic,other'],
            'description' => ['nullable', 'string', 'max:2000'],
            'default_price' => ['required', 'numeric', 'min:0'],
            'nhis_price' => ['nullable', 'numeric', 'min:0'],
            'requires_consent' => ['nullable', 'boolean'],
        ]);

        $data['requires_consent'] = $request->boolean('requires_consent');
        $this->clinicalService->createProcedure($data);

        return back()->with('success', 'Procedure added successfully.');
    }

    public function update(Request $request, Procedure $procedure)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'category' => ['required', 'in:surgical,diagnostic,therapeutic,other'],
            'description' => ['nullable', 'string', 'max:2000'],
            'default_price' => ['required', 'numeric', 'min:0'],
            'nhis_price' => ['nullable', 'numeric', 'min:0'],
            'requires_consent' => ['nullable', 'boolean'],
        ]);

        $data['requires_consent'] = $request->boolean('requires_consent');
        $this->clinicalService->updateProcedure($procedure, $data);

        return back()->with('success', 'Procedure updated successfully.');
    }

    public function toggle(Procedure $procedure)
    {
        $this->clinicalService->toggleProcedure($procedure);
        $status = $procedure->fresh()->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Procedure {$procedure->name} {$status}.");
    }

    /*
    |--------------------------------------------------------------------------
    | Patient Procedure Scheduling & Tracking
    |--------------------------------------------------------------------------
    */

    public function schedule(Request $request)
    {
        $patientProcedures = $this->clinicalService->listPatientProcedures($request->only('status', 'search', 'date_from', 'date_to'));

        return view('admin.procedures.schedule', compact('patientProcedures'));
    }

    public function storeSchedule(Request $request)
    {
        $data = $request->validate([
            'visit_id' => ['required', 'exists:visits,id'],
            'patient_id' => ['required', 'exists:patients,id'],
            'procedure_id' => ['required', 'exists:procedures,id'],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'performed_by' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'consent_signed' => ['nullable', 'boolean'],
        ]);

        $data['consent_signed'] = $request->boolean('consent_signed');
        $procedure = Procedure::findOrFail($data['procedure_id']);

        if ($procedure->requires_consent && !$data['consent_signed']) {
            return back()->with('error', 'This procedure requires signed consent before scheduling.');
        }

        $this->clinicalService->scheduleProcedure($data);

        return back()->with('success', 'Procedure scheduled.');
    }

    public function startProcedure(PatientProcedure $patientProcedure)
    {
        $this->clinicalService->startProcedure($patientProcedure);

        return back()->with('success', 'Procedure started.');
    }

    public function completeProcedure(Request $request, PatientProcedure $patientProcedure)
    {
        $data = $request->validate([
            'outcome' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->clinicalService->completeProcedure($patientProcedure, $data);

        return back()->with('success', 'Procedure completed.');
    }

    public function cancelProcedure(Request $request, PatientProcedure $patientProcedure)
    {
        $reason = $request->input('reason');
        $this->clinicalService->cancelProcedure($patientProcedure, $reason);

        return back()->with('success', 'Procedure cancelled.');
    }
}
