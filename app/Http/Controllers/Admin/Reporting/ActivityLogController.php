<?php

namespace App\Http\Controllers\Admin\Reporting;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    public function __construct(protected ActivityLogService $logger)
    {
    }

    public function index(Request $request)
    {
        $activities = $this->buildQuery($request)
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('settings.activity-log', [
            'activities' => $activities,
            'modules' => LogModule::cases(),
            'severities' => LogSeverity::cases(),
            'filters' => $this->extractFilters($request),
            'totalCount' => $activities->total(),
        ]);
    }

    public function show(Activity $activityLog)
    {
        return view('settings.activity-log-show', [
            'activity' => $activityLog->load('causer', 'subject'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        if (! Auth::user()?->can('logs.export')) {
            abort(403, 'You do not have permission to export logs.');
        }

        $activities = $this->buildQuery($request)->latest()->limit(10000)->cursor();

        $filename = 'activity-logs-' . now()->format('Ymd-His') . '.csv';

        $callback = function () use ($activities) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Timestamp', 'Module', 'Action', 'Severity', 'Description',
                'User', 'Subject', 'Subject ID', 'Patient ID', 'Visit ID',
                'Reason', 'IP',
            ]);
            foreach ($activities as $a) {
                $p = is_array($a->properties) ? $a->properties : ($a->properties?->toArray() ?? []);
                fputcsv($handle, [
                    $a->created_at?->toIso8601String(),
                    $p['module'] ?? $a->log_name,
                    $p['action'] ?? $a->event,
                    $p['severity'] ?? 'INFO',
                    $a->description,
                    optional($a->causer)->email ?? optional($a->causer)->name ?? '',
                    class_basename((string) $a->subject_type),
                    $a->subject_id,
                    $p['patient_id'] ?? '',
                    $p['visit_id'] ?? '',
                    $p['reason'] ?? '',
                    $p['ip'] ?? '',
                ]);
            }
            fclose($handle);
        };

        // Log the export itself.
        $this->logger->log(LogModule::SYSTEM, 'EXPORTED', [
            'description' => __('messages.activity_logs.exported_to_csv'),
            'metadata' => ['filters' => $this->extractFilters($request)],
        ]);

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /* ── Internals ──────────────────────────────────────────────── */

    protected function buildQuery(Request $request)
    {
        $user = Auth::user();
        $query = Activity::with('causer', 'subject');

        // Restrict by viewing-scope permissions. Super Admin / logs.view sees all.
        if (! $user?->hasRole('Super Admin') && ! $user?->can('logs.view')) {
            $allowedModules = [];
            if ($user?->can('logs.view_clinical')) {
                $allowedModules = array_merge($allowedModules, [
                    LogModule::CONSULTATION->value, LogModule::MAR->value,
                    LogModule::CLINICAL_TASKS->value, LogModule::INVESTIGATION->value,
                    LogModule::PROCEDURE->value, LogModule::THEATRE->value,
                    LogModule::EMERGENCY->value, LogModule::ADMISSION->value,
                ]);
            }
            if ($user?->can('logs.view_financial')) {
                $allowedModules = array_merge($allowedModules, [
                    LogModule::BILLING->value, LogModule::PAYMENTS->value,
                    LogModule::CLAIMS->value, LogModule::INSURANCE->value,
                ]);
            }
            if ($user?->can('logs.view_stock')) {
                $allowedModules = array_merge($allowedModules, [
                    LogModule::STOCK->value, LogModule::PHARMACY->value,
                    LogModule::PURCHASE_ORDERS->value, LogModule::SUPPLIER_LEDGER->value,
                ]);
            }
            if ($user?->can('logs.view_security')) {
                $allowedModules[] = LogModule::AUTH->value;
                $allowedModules[] = LogModule::USERS->value;
                $allowedModules[] = LogModule::ROLES->value;
                $allowedModules[] = LogModule::PERMISSIONS->value;
            }

            if (empty($allowedModules)) {
                $query->whereRaw('1=0');
            } else {
                $query->whereIn('log_name', array_unique($allowedModules));
            }
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%")
                    ->orWhere('log_name', 'like', "%{$search}%");
            });
        }

        if ($module = $request->query('module')) {
            if (LogModule::tryFrom($module)) {
                $query->where('log_name', $module);
            }
        }

        if ($severity = $request->query('severity')) {
            if (LogSeverity::tryFrom($severity)) {
                $query->where('properties', 'like', '%"severity":"' . $severity . '"%');
            }
        }

        if ($action = $request->query('action')) {
            $query->where('event', $action);
        }

        if ($userId = $request->query('user_id')) {
            $query->where('causer_id', (int) $userId)->where('causer_type', \App\Models\User::class);
        }

        if ($patientId = $request->query('patient_id')) {
            $query->where('properties', 'like', '%"patient_id":' . (int) $patientId . '%');
        }

        if ($visitId = $request->query('visit_id')) {
            $query->where('properties', 'like', '%"visit_id":' . (int) $visitId . '%');
        }

        if ($subjectType = $request->query('subject_type')) {
            $query->where('subject_type', 'like', "%{$subjectType}%");
        }

        if ($from = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }

    protected function extractFilters(Request $request): array
    {
        return [
            'search' => $request->query('search'),
            'module' => $request->query('module'),
            'severity' => $request->query('severity'),
            'action' => $request->query('action'),
            'user_id' => $request->query('user_id'),
            'patient_id' => $request->query('patient_id'),
            'visit_id' => $request->query('visit_id'),
            'subject_type' => $request->query('subject_type'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];
    }
}

