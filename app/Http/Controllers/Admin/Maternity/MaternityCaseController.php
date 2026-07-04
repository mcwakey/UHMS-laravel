<?php

namespace App\Http\Controllers\Admin\Maternity;

use App\Enums\DepartmentType;
use App\Enums\MaternityCaseStatus;
use App\Enums\MaternityCaseType;
use App\Enums\MaternityRiskLevel;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\MaternityCase;
use App\Models\Ward;
use App\Services\Maternity\MaternityCaseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaternityCaseController extends Controller
{
    public function __construct(private MaternityCaseService $cases) {}

    public function store(Request $request)
    {
        $case = $this->cases->open($request->validate($this->rules()), $request->user());

        return redirect()
            ->route('admin.maternity.cases.show', $case)
            ->with('success', __('maternity.case_opened'));
    }

    public function show(MaternityCase $maternityCase)
    {
        $maternityCase->load(['patient', 'pregnancyProfile', 'visit', 'admission.bed.ward', 'department', 'openedBy', 'closedBy']);

        return view('maternity.cases.show', [
            'case' => $maternityCase,
            'departments' => Department::active()->where('type', DepartmentType::MATERNITY->value)->orderBy('name')->get(),
            'wards' => Ward::active()
                ->whereHas('department', fn ($query) => $query->where('type', DepartmentType::MATERNITY->value))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, MaternityCase $maternityCase)
    {
        $this->cases->update($maternityCase, $request->validate($this->rules(true)), $request->user());

        return redirect()
            ->route('admin.maternity.cases.show', $maternityCase)
            ->with('success', __('maternity.case_updated'));
    }

    public function close(Request $request, MaternityCase $maternityCase)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([MaternityCaseStatus::CLOSED->value, MaternityCaseStatus::CANCELLED->value])],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->cases->close($maternityCase, MaternityCaseStatus::from($data['status']), $data['reason'] ?? null, $request->user());

        return redirect()
            ->route('admin.maternity.cases.show', $maternityCase)
            ->with('success', __('maternity.case_closed'));
    }

    public function admissionRequest(Request $request, MaternityCase $maternityCase)
    {
        $data = $request->validate([
            'requested_ward_id' => ['nullable', 'exists:wards,id'],
            'priority' => ['nullable', 'string', 'max:50'],
            'provisional_diagnosis' => ['nullable', 'string', 'max:1000'],
            'clinical_summary' => ['nullable', 'string', 'max:3000'],
        ]);

        $admissionRequest = $this->cases->createAdmissionRequest($maternityCase, $data, $request->user());

        return redirect()
            ->route('admin.admissions.requests.show', $admissionRequest)
            ->with('success', __('maternity.admission_request_created'));
    }

    private function rules(bool $partial = false): array
    {
        return [
            'pregnancy_profile_id' => [$partial ? 'sometimes' : 'nullable', 'exists:pregnancy_profiles,id'],
            'patient_id' => [$partial ? 'sometimes' : 'required', 'exists:patients,id'],
            'visit_id' => ['nullable', 'exists:visits,id'],
            'admission_id' => ['nullable', 'exists:admissions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'source_type' => ['nullable', 'string', 'max:80'],
            'source_id' => ['nullable', 'integer'],
            'case_type' => ['nullable', Rule::in(array_column(MaternityCaseType::cases(), 'value'))],
            'status' => ['nullable', Rule::in(array_column(MaternityCaseStatus::cases(), 'value'))],
            'priority' => ['nullable', 'string', 'max:50'],
            'risk_level' => ['nullable', Rule::in(array_column(MaternityRiskLevel::cases(), 'value'))],
            'reason' => ['nullable', 'string', 'max:2000'],
            'clinical_summary' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
