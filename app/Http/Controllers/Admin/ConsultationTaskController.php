<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultationTask;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Http\Request;

class ConsultationTaskController extends Controller
{
    public function store(Request $request, MedicalRecord $medicalRecord)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $data['created_by'] = auth()->id();
        $data['status'] = 'pending';

        $task = $medicalRecord->tasks()->create($data);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'task' => $task->load('assignedUser')]);
        }

        return back()->with('success', 'Task created.');
    }

    public function update(Request $request, ConsultationTask $task)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
            'status' => ['nullable', 'string', 'in:pending,in_progress,completed,cancelled'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        if (($data['status'] ?? null) === 'completed' && $task->status !== 'completed') {
            $data['completed_at'] = now();
        }

        $task->update($data);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'task' => $task->fresh('assignedUser')]);
        }

        return back()->with('success', 'Task updated.');
    }

    public function toggleComplete(ConsultationTask $task)
    {
        if ($task->status === 'completed') {
            $task->update(['status' => 'pending', 'completed_at' => null]);
        } else {
            $task->markCompleted();
        }

        if (request()->ajax()) {
            return response()->json(['success' => true, 'task' => $task->fresh()]);
        }

        return back()->with('success', 'Task status toggled.');
    }

    public function destroy(ConsultationTask $task)
    {
        $task->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Task deleted.');
    }
}
