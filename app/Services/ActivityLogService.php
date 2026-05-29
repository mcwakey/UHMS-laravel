<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Central wrapper around Spatie\Activitylog. Writes structured properties
 * (module, severity, context, ip, user_agent, sanitised old/new values) into
 * the existing `activity_log` table so the standard log_name/event/properties
 * surface keeps working, while modules can read back consistent metadata.
 *
 * Do NOT introduce a parallel log table — extend this service instead.
 */
class ActivityLogService
{
    /**
     * Field names whose value must never appear in old/new value snapshots
     * or freeform metadata. Match is case-insensitive on the field name.
     */
    public const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'api_key',
        'api_secret',
        'secret',
        'remember_token',
        'access_token',
        'refresh_token',
        'card_number',
        'cvv',
        'pin',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function __construct(protected ?Request $request = null)
    {
        $this->request = $this->request ?: request();
    }

    /* ── Public API ─────────────────────────────────────────────── */

    public function log(
        string|LogModule $module,
        string $action,
        array $data = [],
        ?Model $subject = null,
        ?string $description = null
    ): void {
        $module = $this->normaliseModule($module);
        $severity = $this->normaliseSeverity($data['severity'] ?? LogSeverity::INFO);
        $description = $description ?? $data['description'] ?? $action;

        // Optional async dispatch: keep request hot-path light.
        if (
            config('audit_streaming.async_writes')
            && empty($data['_async_dispatched'])
            && $severity !== LogSeverity::SECURITY->value
        ) {
            $payload = $data;
            $payload['_async_dispatched'] = true;
            unset($payload['causer']); // serialise via causer_id instead
            $payload['_causer_id'] = ($data['causer'] ?? Auth::user())?->getKey();
            \App\Jobs\ProcessActivityLogJob::dispatch(
                $module,
                $action,
                $payload,
                $subject ? $subject::class : null,
                $subject?->getKey(),
                $payload['_causer_id'] ?? null,
                $description
            )->onQueue(config('audit_streaming.queue', 'default'));
            return;
        }

        $properties = $this->buildProperties($module, $action, $severity, $data);

        $activity = activity($module)
            ->event($action)
            ->withProperties($properties);

        $causer = $data['causer'] ?? (isset($data['_causer_id']) ? \App\Models\User::find($data['_causer_id']) : Auth::user());
        if ($causer) {
            $activity->causedBy($causer);
        }
        if ($subject) {
            $activity->performedOn($subject);
        }

        try {
            $activity->log($description);
        } catch (\Throwable $e) {
            // Logging must never break the request that triggered it.
            Log::channel(config('logging.activity_failures_channel', 'stack'))->warning('ActivityLogService.log failed', [
                'module' => $module,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function logCreated(Model $model, string|LogModule $module, ?string $description = null, array $extra = []): void
    {
        $this->log($module, 'CREATED', array_merge($extra, [
            'new_values' => $this->sanitise($model->getAttributes()),
            'description' => $description,
        ]), $model, $description);
    }

    public function logUpdated(
        Model $model,
        string|LogModule $module,
        array $oldValues,
        array $newValues,
        ?string $reason = null,
        array $extra = []
    ): void {
        $changedOld = $this->diffOld($oldValues, $newValues);
        $changedNew = $this->diffNew($oldValues, $newValues);
        if (empty($changedNew)) {
            return;
        }

        $this->log($module, 'UPDATED', array_merge($extra, [
            'old_values' => $this->sanitise($changedOld),
            'new_values' => $this->sanitise($changedNew),
            'reason' => $reason,
        ]), $model);
    }

    public function logDeleted(Model $model, string|LogModule $module, ?string $reason = null, array $extra = []): void
    {
        $this->log($module, 'DELETED', array_merge($extra, [
            'old_values' => $this->sanitise($model->getAttributes()),
            'reason' => $reason,
            'severity' => $extra['severity'] ?? LogSeverity::NOTICE,
        ]), $model);
    }

    public function logCorrection(
        Model $model,
        string|LogModule $module,
        array $oldValues,
        array $newValues,
        string $reason,
        array $extra = []
    ): void {
        $this->log($module, 'CORRECTED', array_merge($extra, [
            'old_values' => $this->sanitise($this->diffOld($oldValues, $newValues)),
            'new_values' => $this->sanitise($this->diffNew($oldValues, $newValues)),
            'reason' => $reason,
            'severity' => LogSeverity::WARNING,
        ]), $model);
    }

    public function logOverride(
        Model $model,
        string|LogModule $module,
        array $data,
        string $reason,
        string $action = 'OVERRIDE_UPDATED',
        array $extra = []
    ): void {
        $this->log($module, $action, array_merge($extra, $data, [
            'reason' => $reason,
            'severity' => LogSeverity::WARNING,
        ]), $model);
    }

    public function logSecurity(string $action, array $data = [], ?Model $subject = null): void
    {
        $this->log(LogModule::AUTH, $action, array_merge($data, [
            'severity' => $data['severity'] ?? LogSeverity::SECURITY,
        ]), $subject);
    }

    /* ── Internals ──────────────────────────────────────────────── */

    protected function buildProperties(string $module, string $action, string $severity, array $data): array
    {
        $properties = [
            'module' => $module,
            'action' => $action,
            'severity' => $severity,
        ];

        $contextKeys = [
            'patient_id', 'visit_id', 'admission_id', 'emergency_case_id',
            'department_id', 'invoice_id', 'claim_id', 'payment_id',
            'procedure_request_id', 'theatre_room_id',
        ];
        foreach ($contextKeys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null) {
                $properties[$key] = $data[$key];
            }
        }

        foreach (['reason', 'description'] as $key) {
            if (! empty($data[$key])) {
                $properties[$key] = $data[$key];
            }
        }

        if (! empty($data['old_values'])) {
            $properties['old'] = $this->sanitise((array) $data['old_values']);
        }
        if (! empty($data['new_values'])) {
            $properties['attributes'] = $this->sanitise((array) $data['new_values']);
        }

        if (! empty($data['metadata'])) {
            $properties['metadata'] = $this->sanitise((array) $data['metadata']);
        }

        $properties['ip'] = $this->request?->ip();
        $properties['user_agent'] = $this->request ? substr((string) $this->request->userAgent(), 0, 255) : null;

        return $properties;
    }

    protected function normaliseModule(string|LogModule $module): string
    {
        if ($module instanceof LogModule) {
            return $module->value;
        }
        return LogModule::tryFrom($module)?->value ?? LogModule::SYSTEM->value;
    }

    protected function normaliseSeverity(string|LogSeverity $severity): string
    {
        if ($severity instanceof LogSeverity) {
            return $severity->value;
        }
        return LogSeverity::tryFrom($severity)?->value ?? LogSeverity::INFO->value;
    }

    protected function diffOld(array $old, array $new): array
    {
        $out = [];
        foreach ($new as $key => $value) {
            if (array_key_exists($key, $old) && $old[$key] !== $value) {
                $out[$key] = $old[$key];
            } elseif (! array_key_exists($key, $old)) {
                $out[$key] = null;
            }
        }
        return $out;
    }

    protected function diffNew(array $old, array $new): array
    {
        $out = [];
        foreach ($new as $key => $value) {
            if (! array_key_exists($key, $old) || $old[$key] !== $value) {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    public function sanitise(array $data): array
    {
        $masked = array_map('strtolower', self::SENSITIVE_FIELDS);
        $out = [];
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), $masked, true)) {
                $out[$key] = '***MASKED***';
                continue;
            }
            if (is_array($value)) {
                $out[$key] = $this->sanitise($value);
                continue;
            }
            $out[$key] = $value;
        }
        return $out;
    }
}
