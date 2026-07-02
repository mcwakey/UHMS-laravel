<?php

namespace App\Http\Controllers\Admin\Patients;

use App\Enums\LogSeverity;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Patient;
use App\Models\PatientPrivacyDirective;
use App\Models\PatientPrivacyOverride;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class PatientPrivacyController extends Controller
{
    public function startBreakGlass(Request $request, Patient $patient)
    {
        abort_unless($request->user()?->can('patients.privacy.break_glass'), 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'visit_id' => ['nullable', 'integer', 'exists:visits,id'],
        ]);

        $override = PatientPrivacyOverride::create([
            'user_id' => $request->user()->id,
            'patient_id' => $patient->id,
            'visit_id' => $validated['visit_id'] ?? null,
            'reason' => $validated['reason'],
            'starts_at' => now(),
            'expires_at' => now()->addMinutes((int) config('patient_privacy.break_glass.duration_minutes', 30)),
        ]);

        app(ActivityLogService::class)->logPatientAction($patient, 'PATIENT_PRIVACY_BREAK_GLASS_STARTED', [
            'severity' => LogSeverity::SECURITY,
            'metadata' => [
                'override_id' => $override->id,
                'visit_id' => $override->visit_id,
                'expires_at' => $override->expires_at?->toIso8601String(),
                'reason_length' => strlen($validated['reason']),
            ],
                'description' => __('patients.privacy.break_glass_started_description'),
        ]);

        return back()->with('success', __('patients.privacy.break_glass_active'));
    }

    public function revokeBreakGlass(Request $request, PatientPrivacyOverride $override)
    {
        abort_unless($request->user()?->can('patients.privacy.break_glass'), 403);

        if ($override->revoked_at === null) {
            $override->update([
                'revoked_at' => now(),
                'revoked_by' => $request->user()->id,
            ]);

            if ($override->patient) {
                app(ActivityLogService::class)->logPatientAction($override->patient, 'PATIENT_PRIVACY_BREAK_GLASS_REVOKED', [
                    'severity' => LogSeverity::SECURITY,
                    'metadata' => [
                        'override_id' => $override->id,
                        'revoked_by' => $request->user()->id,
                    ],
                'description' => __('patients.privacy.break_glass_revoked_description'),
                ]);
            }
        }

        return back()->with('success', __('patients.privacy.break_glass_revoked'));
    }

    public function storeDirective(Request $request, Patient $patient)
    {
        abort_unless($request->user()?->can('patients.privacy_directives.manage'), 403);

        $validated = $request->validate([
            'directive_type' => ['required', 'string', 'max:80'],
            'summary' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $directive = $patient->privacyDirectives()->create([
            'directive_type' => $validated['directive_type'],
            'status' => PatientPrivacyDirective::STATUS_ACTIVE,
            'summary' => $validated['summary'],
            'details' => $validated['details'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        app(ActivityLogService::class)->logPatientAction($patient, 'PATIENT_PRIVACY_DIRECTIVE_CREATED', [
            'severity' => LogSeverity::WARNING,
            'metadata' => [
                'directive_id' => $directive->id,
                'directive_type' => $directive->directive_type,
                'status' => $directive->status,
            ],
                'description' => __('patients.privacy.directive_created_description'),
        ]);

        return back()->with('success', __('patients.privacy.privacy_directive_created'));
    }

    public function audit(Request $request)
    {
        abort_unless($request->user()?->can('patients.privacy_audit.view'), 403);

        $filters = $request->only(['patient_id', 'user_id', 'action', 'date_from', 'date_to']);
        $actions = [
            'PATIENT_SENSITIVE_PROFILE_VIEWED',
            'PATIENT_PII_VIEWED',
            'PATIENT_IDENTITY_VIEWED',
            'PATIENT_ADDRESS_VIEWED',
            'PATIENT_CLINICAL_SENSITIVE_VIEWED',
            'PATIENT_PRIVACY_BREAK_GLASS_STARTED',
            'PATIENT_PRIVACY_BREAK_GLASS_USED',
            'PATIENT_PRIVACY_BREAK_GLASS_REVOKED',
            'PATIENT_PRIVACY_DIRECTIVE_CREATED',
            'PATIENT_PRIVACY_DIRECTIVE_UPDATED',
        ];

        $logs = ActivityLog::query()
            ->with('causer')
            ->whereIn('event', $actions)
            ->when($filters['patient_id'] ?? null, fn ($query, $patientId) => $query->where('patient_id', $patientId))
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('causer_id', $userId))
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('event', $action))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('patients.privacy-audit', compact('logs', 'actions', 'filters'));
    }

    public function historicalLogDryRun(Request $request)
    {
        abort_unless($request->user()?->can('patients.privacy_audit.view'), 403);

        $sensitive = ActivityLogService::SENSITIVE_FIELDS;
        $matches = ActivityLog::query()
            ->where(function ($query) use ($sensitive) {
                foreach ($sensitive as $field) {
                    $query->orWhere('properties', 'like', '%'.$field.'%');
                }
            })
            ->count();

        return view('patients.privacy-historical-dry-run', [
            'matches' => $matches,
            'fields' => $sensitive,
        ]);
    }
}
