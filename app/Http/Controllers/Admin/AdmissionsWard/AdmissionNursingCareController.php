<?php

namespace App\Http\Controllers\Admin\AdmissionsWard;

use App\Enums\AdmissionCareFlag;
use App\Enums\NursingNoteType;
use App\Enums\NursingTaskStatus;
use App\Enums\NursingTaskType;
use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\NursingNote;
use App\Models\NursingTask;
use App\Models\User;
use App\Services\Admissions\AdmissionNursingCareService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdmissionNursingCareController extends Controller
{
    public function __construct(private AdmissionNursingCareService $care) {}

    public function storeNote(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'note_type' => ['nullable', Rule::in(array_column(NursingNoteType::cases(), 'value'))],
            'note' => ['required', 'string', 'max:5000'],
            'observed_at' => ['nullable', 'date'],
        ]);

        $this->care->createNote($admission, $data, $request->user());

        return redirect()
            ->route('admin.admissions.show', $admission)
            ->withFragment('tab-nursing')
            ->with('success', __('admissions.nursing_note_saved'));
    }

    public function updateNote(Request $request, Admission $admission, NursingNote $nursingNote)
    {
        abort_unless((int) $nursingNote->admission_id === (int) $admission->id, 404);

        $data = $request->validate([
            'note_type' => ['nullable', Rule::in(array_column(NursingNoteType::cases(), 'value'))],
            'note' => ['required', 'string', 'max:5000'],
            'observed_at' => ['nullable', 'date'],
        ]);

        $this->care->updateNote($nursingNote, $data, $request->user());

        return redirect()
            ->route('admin.admissions.show', $admission)
            ->withFragment('tab-nursing')
            ->with('success', __('admissions.nursing_note_updated'));
    }

    public function storeTask(Request $request, Admission $admission)
    {
        $data = $request->validate($this->taskRules());

        $this->care->createTask($admission, $data, $request->user());

        return redirect()
            ->route('admin.admissions.show', $admission)
            ->withFragment('tab-nursing')
            ->with('success', __('admissions.nursing_task_saved'));
    }

    public function updateTask(Request $request, Admission $admission, NursingTask $nursingTask)
    {
        abort_unless((int) $nursingTask->admission_id === (int) $admission->id, 404);

        $data = $request->validate($this->taskRules(true));

        $this->care->updateTask($nursingTask, $data, $request->user());

        return redirect()
            ->route('admin.admissions.show', $admission)
            ->withFragment('tab-nursing')
            ->with('success', __('admissions.nursing_task_updated'));
    }

    public function completeTask(Request $request, Admission $admission, NursingTask $nursingTask)
    {
        abort_unless((int) $nursingTask->admission_id === (int) $admission->id, 404);

        $this->care->completeTask($nursingTask, $request->user());

        return redirect()
            ->route('admin.admissions.show', $admission)
            ->withFragment('tab-nursing')
            ->with('success', __('admissions.nursing_task_completed'));
    }

    public function updateCareFlags(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'care_flags' => ['nullable', 'array'],
            'care_flags.*' => ['string', Rule::in(array_column(AdmissionCareFlag::cases(), 'value'))],
        ]);

        $this->care->updateCareFlags($admission, $data['care_flags'] ?? [], $request->user());

        return redirect()
            ->route('admin.admissions.show', $admission)
            ->withFragment('tab-nursing')
            ->with('success', __('admissions.care_flags_updated'));
    }

    private function taskRules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'task_type' => ['nullable', Rule::in(array_column(NursingTaskType::cases(), 'value'))],
            'title' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'medium', 'high', 'urgent'])],
            'status' => ['nullable', Rule::in(array_column(NursingTaskStatus::cases(), 'value'))],
            'assigned_to' => ['nullable', Rule::exists(User::class, 'id')],
            'due_at' => ['nullable', 'date'],
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
