<?php

namespace App\Services;

use App\Models\BloodCrossmatch;
use App\Models\BloodIssue;
use App\Models\BloodRequest;
use App\Models\BloodUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BloodIssueService
{
    public function issue(BloodRequest $request, BloodUnit $unit, User $user, array $data = []): BloodIssue
    {
        if (! in_array($request->status, [BloodRequest::STATUS_APPROVED, BloodRequest::STATUS_PARTIALLY_ISSUED], true)) {
            throw ValidationException::withMessages(['blood_request_id' => 'Only approved blood requests can be issued.']);
        }

        if (! $unit->isIssueable()) {
            throw ValidationException::withMessages(['blood_unit_id' => 'This blood unit is not safe or available for issue.']);
        }

        $compatible = BloodCrossmatch::where('blood_request_id', $request->id)
            ->where('blood_unit_id', $unit->id)
            ->where('result', BloodCrossmatch::RESULT_COMPATIBLE)
            ->exists();

        if (! $compatible) {
            throw ValidationException::withMessages(['blood_unit_id' => 'A compatible crossmatch is required before issue.']);
        }

        return DB::transaction(function () use ($request, $unit, $user, $data) {
            $issue = BloodIssue::create([
                'issue_number' => BloodIssue::generateIssueNumber(),
                'blood_request_id' => $request->id,
                'blood_unit_id' => $unit->id,
                'visit_id' => $request->visit_id,
                'patient_id' => $request->patient_id,
                'admission_id' => $request->admission_id,
                'emergency_case_id' => $request->emergency_case_id,
                'issued_by' => $user->id,
                'received_by' => $data['received_by'] ?? null,
                'received_by_name' => $data['received_by_name'] ?? null,
                'issued_at' => $data['issued_at'] ?? now(),
                'transfusion_status' => BloodIssue::STATUS_ISSUED,
                'notes' => $data['notes'] ?? null,
            ]);

            $unit->update([
                'status' => BloodUnit::STATUS_ISSUED,
                'issued_at' => $issue->issued_at,
            ]);

            $issued = $request->issues()->count();
            $request->update([
                'units_issued' => $issued,
                'status' => $issued >= $request->units_requested
                    ? BloodRequest::STATUS_ISSUED
                    : BloodRequest::STATUS_PARTIALLY_ISSUED,
            ]);

            return $issue->load('unit', 'request');
        });
    }

    public function recordTransfusion(BloodIssue $issue, User $user, array $data = []): BloodIssue
    {
        return DB::transaction(function () use ($issue, $user, $data) {
            $issue->update([
                'transfusion_status' => ! empty($data['reaction_notes'])
                    ? BloodIssue::STATUS_REACTION_RECORDED
                    : BloodIssue::STATUS_TRANSFUSED,
                'transfused_by' => $user->id,
                'transfused_at' => $data['transfused_at'] ?? now(),
                'reaction_notes' => $data['reaction_notes'] ?? null,
                'notes' => $data['notes'] ?? $issue->notes,
            ]);

            $issue->unit?->update(['status' => BloodUnit::STATUS_TRANSFUSED]);

            if ($issue->request && $issue->request->units_issued >= $issue->request->units_requested) {
                $issue->request->update(['status' => BloodRequest::STATUS_COMPLETED]);
            }

            return $issue->refresh()->load('unit', 'request');
        });
    }
}
