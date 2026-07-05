<?php

namespace App\Services\Maternity;

use App\Enums\LogModule;
use App\Models\MaternityServiceMapping;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Collection;

class MaternityBillingReadinessService
{
    public const KEYS = [
        'anc_registration_package',
        'anc_follow_up',
        'maternity_admission',
        'labor_observation',
        'normal_delivery',
        'assisted_delivery',
        'caesarean_theatre_handoff',
        'delivery_consumables',
        'newborn_care',
        'neonatal_observation',
        'newborn_resuscitation',
        'postnatal_mother_care',
        'postnatal_newborn_care',
        'immunisation_placeholder',
        'ultrasound_placeholder',
        'maternity_consumables',
    ];

    public function __construct(private ActivityLogService $logger) {}

    public function rows(): Collection
    {
        $mappings = MaternityServiceMapping::with(['service', 'configuredBy'])
            ->whereIn('mapping_key', self::KEYS)
            ->get()
            ->keyBy('mapping_key');
        $duplicateServiceIds = $mappings
            ->filter(fn (MaternityServiceMapping $mapping) => $mapping->service_id !== null)
            ->groupBy('service_id')
            ->filter(fn (Collection $serviceMappings) => $serviceMappings->count() > 1)
            ->keys()
            ->map(fn ($serviceId) => (int) $serviceId)
            ->all();

        return collect(self::KEYS)->map(function (string $key) use ($mappings, $duplicateServiceIds) {
            $mapping = $mappings->get($key);
            $service = $mapping?->service;
            $warnings = [];

            if (! $mapping || ! $mapping->service_id) {
                $warnings[] = __('maternity.mapping_missing');
            }
            if ($mapping && ! $mapping->is_active) {
                $warnings[] = __('maternity.mapping_disabled');
            }
            if ($service && ! $service->is_active) {
                $warnings[] = __('maternity.service_inactive');
            }
            if ($mapping?->service_id && in_array((int) $mapping->service_id, $duplicateServiceIds, true)) {
                $warnings[] = __('maternity.duplicate_mapping_warning');
            }

            return [
                'key' => $key,
                'label' => __('maternity.billing_mapping_keys.'.$key),
                'mapping' => $mapping,
                'service' => $service,
                'configured' => $mapping && $mapping->service_id && $mapping->is_active && (! $service || $service->is_active),
                'warnings' => $warnings,
            ];
        });
    }

    public function save(array $data, User $user): void
    {
        foreach (self::KEYS as $key) {
            $payload = $data['mappings'][$key] ?? [];
            MaternityServiceMapping::updateOrCreate(
                ['mapping_key' => $key],
                [
                    'service_id' => filled($payload['service_id'] ?? null) ? (int) $payload['service_id'] : null,
                    'is_active' => (bool) ($payload['is_active'] ?? false),
                    'description' => $payload['description'] ?? null,
                    'configured_by' => $user->id,
                    'configured_at' => now(),
                ],
            );
        }

        $this->logger->log(LogModule::MATERNITY, 'MATERNITY_BILLING_READINESS_UPDATED', [
            'metadata' => ['mapping_count' => count(self::KEYS)],
            'causer' => $user,
        ], null, 'Maternity billing readiness mappings updated');
    }
}
