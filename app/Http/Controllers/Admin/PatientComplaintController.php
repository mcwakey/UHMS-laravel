<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\MedicalRecord;
use App\Services\MedicalRecordEntryPermissionService;
use App\Services\PatientComplaintService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientComplaintController extends Controller
{
    public function store(Request $request, MedicalRecord $medicalRecord, PatientComplaintService $complaints)
    {
        $data = $this->validated($request);
        $complaint = $complaints->createForRecord($medicalRecord, $data, $request->user());

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'complaint' => $complaint]);
        }

        return back()->with('success', 'Complaint recorded.');
    }

    public function update(Request $request, Complaint $complaint, PatientComplaintService $complaints, MedicalRecordEntryPermissionService $permissions)
    {
        abort_unless($request->user() && $permissions->canEdit($request->user(), $complaint), 403);

        $complaint = $complaints->update($complaint, $this->validated($request), $request->user());

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'complaint' => $complaint]);
        }

        return back()->with('success', 'Complaint updated.');
    }

    public function destroy(Request $request, Complaint $complaint, PatientComplaintService $complaints, MedicalRecordEntryPermissionService $permissions)
    {
        abort_unless($request->user() && $permissions->canDelete($request->user(), $complaint), 403);

        $complaints->delete($complaint, $request->user());

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Complaint removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'complaint_catalogue_id' => ['nullable', Rule::exists('complaint_catalogues', 'id')->where('is_active', true)],
            'description' => ['required_without:complaint_catalogue_id', 'nullable', 'string', 'max:2000'],
            'duration' => ['nullable', 'string', 'max:191'],
            'duration_unit' => ['nullable', 'in:minutes,hours,days,weeks,months,years'],
            'severity' => ['nullable', 'in:mild,moderate,severe,critical'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'source_pattern_id' => ['nullable', 'exists:medical_patterns,id'],
            'emergency_case_id' => ['nullable', 'exists:emergency_cases,id'],
            'emergency_session_id' => ['nullable', 'exists:emergency_sessions,id'],
            'admission_id' => ['nullable', 'exists:admissions,id'],
        ]);
    }
}