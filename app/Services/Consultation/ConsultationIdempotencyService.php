<?php

namespace App\Services\Consultation;

use App\Enums\LogModule;
use App\Models\ConsultationActionIdempotencyKey;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\ActivityLogService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConsultationIdempotencyService
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function run(
        Request $request,
        string $action,
        Visit $visit,
        ?VisitConsultationRoute $route,
        array $payload,
        Closure $callback,
    ): mixed {
        $key = $this->keyFromRequest($request);
        if (! $key) {
            return $callback();
        }

        $user = $request->user();
        $hash = $this->payloadHash($payload);

        return DB::transaction(function () use ($key, $user, $visit, $route, $action, $hash, $callback) {
            $existing = ConsultationActionIdempotencyKey::query()
                ->where('key', $key)
                ->where('user_id', $user?->id)
                ->where('action', $action)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if (! hash_equals($existing->payload_hash, $hash)) {
                    throw ValidationException::withMessages([
                        'idempotency_key' => __('messages.consultations.duplicate_action_payload_mismatch'),
                    ]);
                }

                $this->logReplay($existing, $visit, $route);

                return $this->resolveReference($existing);
            }

            $entry = ConsultationActionIdempotencyKey::create([
                'key' => $key,
                'user_id' => $user?->id,
                'visit_id' => $visit->id,
                'consultation_route_id' => $route?->id,
                'action' => $action,
                'payload_hash' => $hash,
                'expires_at' => now()->addDay(),
            ]);

            $result = $callback();
            if ($result instanceof Model) {
                $entry->update([
                    'response_reference_type' => $result::class,
                    'response_reference_id' => $result->getKey(),
                ]);
            }

            return $result;
        });
    }

    public function keyFromRequest(Request $request): ?string
    {
        $key = $request->header('Idempotency-Key')
            ?: $request->input('_idempotency_key')
            ?: $request->input('idempotency_key');

        $key = is_string($key) ? trim($key) : '';

        return $key !== '' ? mb_substr($key, 0, 191) : null;
    }

    public function payloadHash(array $payload): string
    {
        $payload = $this->normalisePayload($payload);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function normalisePayload(array $payload): array
    {
        unset($payload['_token'], $payload['_method'], $payload['_idempotency_key'], $payload['idempotency_key']);

        ksort($payload);
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->normalisePayload($value);
            }
        }

        return $payload;
    }

    private function resolveReference(ConsultationActionIdempotencyKey $entry): ?Model
    {
        $type = $entry->response_reference_type;
        if (! $type || ! class_exists($type) || ! is_subclass_of($type, Model::class)) {
            return null;
        }

        return $type::query()->find($entry->response_reference_id);
    }

    private function logReplay(ConsultationActionIdempotencyKey $entry, Visit $visit, ?VisitConsultationRoute $route): void
    {
        $this->activityLog->log(
            LogModule::CONSULTATION,
            'CONSULTATION_IDEMPOTENCY_REPLAYED',
            [
                'patient_id' => $visit->patient_id,
                'visit_id' => $visit->id,
                'consultation_route_id' => $route?->id ?? $entry->consultation_route_id,
                'metadata' => [
                    'action' => $entry->action,
                    'response_reference_type' => $entry->response_reference_type,
                    'response_reference_id' => $entry->response_reference_id,
                ],
            ],
            $route,
            'Consultation action idempotency replayed.',
        );
    }
}

