<?php

namespace App\Services\Maternity;

use App\Enums\DeliveryMode;
use App\Enums\NewbornCondition;
use App\Models\AntenatalVisit;
use App\Models\DeliveryRecord;
use App\Models\InvoiceItem;
use App\Models\LaborEpisode;
use App\Models\MaternityBillingEvent;
use App\Models\MaternityServiceMapping;
use App\Models\NewbornRecord;
use App\Models\PostnatalCase;
use App\Models\ServiceCatalog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class MaternityBillingPostingService
{
    public const STATUS_MISSING_MAPPING = 'missing_mapping';
    public const STATUS_MAPPING_DISABLED = 'mapping_disabled';
    public const STATUS_SERVICE_INACTIVE = 'service_inactive';
    public const STATUS_ALREADY_POSTED = 'already_posted';
    public const STATUS_BILLING_DISABLED = 'billing_disabled';
    public const STATUS_NEWBORN_BILLING_DISABLED = 'newborn_billing_disabled';
    public const STATUS_READY_TO_POST = 'ready_to_post';
    public const STATUS_POSTING_NOT_IMPLEMENTED = 'posting_not_implemented';

    private const NEWBORN_POLICIES = ['mother', 'newborn_if_linked', 'disabled'];

    public function previewManyForSource(Model $sourceModel): Collection
    {
        return collect($this->mappingKeysForSource($sourceModel))
            ->map(fn (string $mappingKey) => $this->previewForSource($sourceModel, $mappingKey))
            ->values();
    }

    public function previewForSource(Model $sourceModel, string $mappingKey): array
    {
        $mapping = $this->resolveMapping($mappingKey);
        $service = $mapping?->service;
        $context = $this->resolveBillingContext($sourceModel, $mappingKey);
        $alreadyPosted = $this->alreadyPosted($sourceModel, $mappingKey);
        $invoicePayload = $this->buildInvoiceItemPayload($sourceModel, $mappingKey, $service, $context);
        $status = $this->previewStatus($mapping, $service, $alreadyPosted, $context);

        return [
            'mapping_key' => $mappingKey,
            'label' => __('maternity.billing_mapping_keys.'.$mappingKey),
            'status' => $status,
            'status_label' => __('maternity.billing_preview_statuses.'.$status),
            'reason' => __('maternity.billing_preview_reasons.'.$status),
            'mapping' => $mapping,
            'service' => $service,
            'amount' => $service ? (float) $service->price : null,
            'source_type' => $this->sourceTypeFor($sourceModel),
            'source_id' => $sourceModel->getKey(),
            'invoice_source_type' => $this->invoiceSourceType($mappingKey),
            'patient_id' => $context['patient_id'],
            'visit_id' => $context['visit_id'],
            'admission_id' => $context['admission_id'],
            'responsible_patient_type' => $context['responsible_patient_type'],
            'newborn_billing_policy' => $context['newborn_billing_policy'],
            'billing_enabled' => $this->billingEnabled(),
            'auto_post_enabled' => $this->autoPostEnabled(),
            'can_post' => false,
            'already_posted' => $alreadyPosted,
            'invoice_payload' => $invoicePayload,
            'warnings' => array_values(array_filter([$context['warning']])),
        ];
    }

    public function postForSource(Model $sourceModel, string $mappingKey, User $actor): array
    {
        $preview = $this->previewForSource($sourceModel, $mappingKey);

        return array_merge($preview, [
            'posted' => false,
            'status' => self::STATUS_POSTING_NOT_IMPLEMENTED,
            'status_label' => __('maternity.billing_preview_statuses.'.self::STATUS_POSTING_NOT_IMPLEMENTED),
            'reason' => __('maternity.billing_preview_reasons.'.self::STATUS_POSTING_NOT_IMPLEMENTED),
            'actor_id' => $actor->id,
        ]);
    }

    public function alreadyPosted(Model $sourceModel, string $mappingKey): bool
    {
        $sourceType = $this->sourceTypeFor($sourceModel);
        $sourceId = $sourceModel->getKey();

        if (! $sourceId) {
            return false;
        }

        $eventPosted = MaternityBillingEvent::query()
            ->where('mapping_key', $mappingKey)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', MaternityBillingEvent::STATUS_POSTED)
            ->exists();

        if ($eventPosted) {
            return true;
        }

        return InvoiceItem::query()
            ->where('source_type', $this->invoiceSourceType($mappingKey))
            ->where('source_id', $sourceId)
            ->exists();
    }

    public function resolveMapping(string $mappingKey): ?MaternityServiceMapping
    {
        return MaternityServiceMapping::with('service')
            ->where('mapping_key', $mappingKey)
            ->first();
    }

    public function resolveBillingContext(Model $sourceModel, string $mappingKey): array
    {
        $policy = $this->newbornBillingPolicy();
        $isNewbornCharge = $this->isNewbornMapping($mappingKey);
        $patientId = $sourceModel->patient_id ?? $sourceModel->mother_patient_id ?? null;
        $visitId = $sourceModel->visit_id ?? null;
        $admissionId = $sourceModel->admission_id ?? null;
        $responsibleType = 'mother';
        $warning = null;

        if ($sourceModel instanceof NewbornRecord && $isNewbornCharge) {
            [$patientId, $visitId, $admissionId, $responsibleType, $warning] = $this->resolveNewbornContext(
                $sourceModel,
                $policy
            );
        }

        if ($sourceModel instanceof PostnatalCase && $isNewbornCharge) {
            [$patientId, $visitId, $admissionId, $responsibleType, $warning] = $this->resolvePostnatalNewbornContext(
                $sourceModel,
                $policy
            );
        }

        return [
            'patient_id' => $patientId,
            'visit_id' => $visitId,
            'admission_id' => $admissionId,
            'responsible_patient_type' => $responsibleType,
            'newborn_billing_policy' => $policy,
            'warning' => $warning,
        ];
    }

    public function buildInvoiceItemPayload(
        Model $sourceModel,
        string $mappingKey,
        ?ServiceCatalog $service,
        array $context
    ): array {
        return array_filter([
            'service_id' => $service?->id,
            'description' => $service?->name,
            'quantity' => 1,
            'unit_price' => $service ? (float) $service->price : null,
            'total' => $service ? (float) $service->price : null,
            'patient_id' => $context['patient_id'],
            'visit_id' => $context['visit_id'],
            'admission_id' => $context['admission_id'],
            'source_type' => $this->invoiceSourceType($mappingKey),
            'source_id' => $sourceModel->getKey(),
        ], fn ($value) => $value !== null);
    }

    public function mappingKeysForSource(Model $sourceModel): array
    {
        return match (true) {
            $sourceModel instanceof AntenatalVisit => [
                ((int) ($sourceModel->visit_number ?? 0)) <= 1
                    ? 'anc_registration_package'
                    : 'anc_follow_up',
            ],
            $sourceModel instanceof LaborEpisode => array_values(array_filter([
                'labor_observation',
                $sourceModel->admission_id ? 'maternity_admission' : null,
            ])),
            $sourceModel instanceof DeliveryRecord => array_values(array_filter([
                $this->deliveryMappingKey($sourceModel),
                'delivery_consumables',
            ])),
            $sourceModel instanceof NewbornRecord => array_values(array_filter([
                'newborn_care',
                $this->requiresNeonatalObservation($sourceModel) ? 'neonatal_observation' : null,
                $sourceModel->resuscitation_required ? 'newborn_resuscitation' : null,
            ])),
            $sourceModel instanceof PostnatalCase => array_values(array_filter([
                'postnatal_mother_care',
                $this->hasLiveNewborns($sourceModel) ? 'postnatal_newborn_care' : null,
            ])),
            default => throw new InvalidArgumentException('Unsupported maternity billing source: '.$sourceModel::class),
        };
    }

    public function invoiceSourceType(string $mappingKey): string
    {
        return 'maternity_'.$mappingKey;
    }

    private function previewStatus(
        ?MaternityServiceMapping $mapping,
        ?ServiceCatalog $service,
        bool $alreadyPosted,
        array $context
    ): string {
        if (! $mapping || ! $mapping->service_id) {
            return self::STATUS_MISSING_MAPPING;
        }

        if (! $mapping->is_active) {
            return self::STATUS_MAPPING_DISABLED;
        }

        if ($service && ! $service->is_active) {
            return self::STATUS_SERVICE_INACTIVE;
        }

        if ($alreadyPosted) {
            return self::STATUS_ALREADY_POSTED;
        }

        if ($context['newborn_billing_policy'] === 'disabled' && $context['responsible_patient_type'] === 'newborn_disabled') {
            return self::STATUS_NEWBORN_BILLING_DISABLED;
        }

        if (! $this->billingEnabled()) {
            return self::STATUS_BILLING_DISABLED;
        }

        return self::STATUS_READY_TO_POST;
    }

    private function sourceTypeFor(Model $sourceModel): string
    {
        return match (true) {
            $sourceModel instanceof AntenatalVisit => 'antenatal_visit',
            $sourceModel instanceof LaborEpisode => 'labor_episode',
            $sourceModel instanceof DeliveryRecord => 'delivery_record',
            $sourceModel instanceof NewbornRecord => 'newborn_record',
            $sourceModel instanceof PostnatalCase => 'postnatal_case',
            default => $sourceModel->getMorphClass(),
        };
    }

    private function deliveryMappingKey(DeliveryRecord $record): string
    {
        return match ($record->delivery_mode) {
            DeliveryMode::CAESAREAN_SECTION => 'caesarean_theatre_handoff',
            DeliveryMode::ASSISTED_DELIVERY => 'assisted_delivery',
            default => 'normal_delivery',
        };
    }

    private function requiresNeonatalObservation(NewbornRecord $record): bool
    {
        return in_array($record->neonatal_condition, [
            NewbornCondition::OBSERVE,
            NewbornCondition::AT_RISK,
            NewbornCondition::CRITICAL,
        ], true);
    }

    private function hasLiveNewborns(PostnatalCase $case): bool
    {
        if ($case->relationLoaded('deliveryRecord') && $case->deliveryRecord?->relationLoaded('newbornRecords')) {
            return $case->deliveryRecord->newbornRecords
                ->contains(fn (NewbornRecord $record) => $record->outcome?->value === 'live_birth');
        }

        return $case->deliveryRecord?->newbornRecords()
            ->where('outcome', 'live_birth')
            ->exists() ?? false;
    }

    private function isNewbornMapping(string $mappingKey): bool
    {
        return in_array($mappingKey, [
            'newborn_care',
            'neonatal_observation',
            'newborn_resuscitation',
            'postnatal_newborn_care',
            'immunisation_placeholder',
        ], true);
    }

    private function resolveNewbornContext(NewbornRecord $record, string $policy): array
    {
        if ($policy === 'disabled') {
            return [
                $record->mother_patient_id,
                $record->visit_id,
                $record->admission_id,
                'newborn_disabled',
                __('maternity.newborn_billing_disabled'),
            ];
        }

        if ($policy === 'newborn_if_linked' && $record->newborn_patient_id) {
            return [
                $record->newborn_patient_id,
                $record->visit_id,
                $record->admission_id,
                'newborn',
                null,
            ];
        }

        return [
            $record->mother_patient_id,
            $record->visit_id,
            $record->admission_id,
            'mother',
            $policy === 'newborn_if_linked' ? __('maternity.newborn_billing_to_mother') : null,
        ];
    }

    private function resolvePostnatalNewbornContext(PostnatalCase $case, string $policy): array
    {
        if ($policy === 'disabled') {
            return [
                $case->mother_patient_id,
                $case->visit_id,
                $case->admission_id,
                'newborn_disabled',
                __('maternity.newborn_billing_disabled'),
            ];
        }

        if ($policy === 'newborn_if_linked') {
            $newborn = $case->deliveryRecord?->newbornRecords()
                ->whereNotNull('newborn_patient_id')
                ->orderBy('birth_order')
                ->first();

            if ($newborn) {
                return [
                    $newborn->newborn_patient_id,
                    $newborn->visit_id ?? $case->visit_id,
                    $newborn->admission_id ?? $case->admission_id,
                    'newborn',
                    null,
                ];
            }

            return [
                $case->mother_patient_id,
                $case->visit_id,
                $case->admission_id,
                'mother',
                __('maternity.newborn_billing_to_mother'),
            ];
        }

        return [
            $case->mother_patient_id,
            $case->visit_id,
            $case->admission_id,
            'mother',
            null,
        ];
    }

    private function newbornBillingPolicy(): string
    {
        $policy = (string) config('billing.maternity_billing.newborn_billing_policy', 'mother');

        return in_array($policy, self::NEWBORN_POLICIES, true) ? $policy : 'mother';
    }

    private function billingEnabled(): bool
    {
        return (bool) config('billing.maternity_billing.enabled', false);
    }

    private function autoPostEnabled(): bool
    {
        return (bool) config('billing.maternity_billing.auto_post', false);
    }
}
