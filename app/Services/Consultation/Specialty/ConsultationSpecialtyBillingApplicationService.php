<?php

namespace App\Services\Consultation\Specialty;

use App\Enums\LogModule;
use App\Models\ConsultationSpecialtyBillingApplication;
use App\Models\ConsultationSpecialtyServiceMapping;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Services\ActivityLogService;
use App\Services\BillingService;
use Illuminate\Support\Facades\DB;

class ConsultationSpecialtyBillingApplicationService
{
    public function __construct(
        private readonly ConsultationSpecialtyBillingMappingService $mappings,
        private readonly BillingService $billing,
        private readonly ActivityLogService $activity,
    ) {}

    public function previewBilling(
        $consultation,
        ConsultationSpecialtyServiceMapping $mapping,
        User $user,
        array $options = []
    ): array {
        $route = $this->route($consultation);
        $mapping->loadMissing(['service', 'profile']);
        $warnings = [];

        if (! $mapping->is_active) {
            $warnings[] = __('consultation_specialties.billing.inactive_mapping');
        }
        if (! $mapping->service?->is_active) {
            $warnings[] = __('consultation_specialties.billing.inactive_service');
        }
        if ($this->mappings->alreadyBilled($route, $mapping)) {
            $warnings[] = __('consultation_specialties.billing.already_billed');
        }
        if (! $user->can('invoices.create')) {
            $warnings[] = __('consultation_specialties.billing.not_authorized');
        }

        $payload = [
            'mapping_id' => $mapping->id,
            'service' => $mapping->service ? [
                'id' => $mapping->service->id,
                'name' => $mapping->service->name,
                'code' => $mapping->service->code,
                'price' => (float) $mapping->service->price,
            ] : null,
            'context' => $mapping->mapping_context,
            'can_bill' => $warnings === [],
            'requires_confirmation' => (bool) $mapping->requires_confirmation,
            'already_billed' => $this->mappings->alreadyBilled($route, $mapping),
            'warnings' => $warnings,
        ];

        if (! empty($options['record_preview']) && $route) {
            ConsultationSpecialtyBillingApplication::query()->create([
                'consultation_id' => $route->id,
                'consultation_specialty_profile_id' => $mapping->consultation_specialty_profile_id,
                'consultation_specialty_service_mapping_id' => $mapping->id,
                'service_id' => $mapping->service_id,
                'applied_by' => $user->id,
                'status' => ConsultationSpecialtyBillingApplication::STATUS_PREVIEWED,
                'trigger' => $mapping->billing_trigger,
                'preview_payload' => $payload,
                'warnings' => $warnings,
            ]);
        }

        return $payload;
    }

    public function applyBilling(
        $consultation,
        ConsultationSpecialtyServiceMapping $mapping,
        User $user,
        array $options = []
    ): array {
        $route = $this->route($consultation);
        $preview = $this->previewBilling($route, $mapping, $user);

        if (! $route || ! $route->visit || ! $mapping->service) {
            return $this->applicationResult($route, $mapping, $user, ConsultationSpecialtyBillingApplication::STATUS_UNSUPPORTED, $preview, null);
        }

        if ($mapping->requires_confirmation && empty($options['confirmed'])) {
            $preview['warnings'][] = __('consultation_specialties.billing.requires_confirmation');

            return $this->applicationResult($route, $mapping, $user, ConsultationSpecialtyBillingApplication::STATUS_UNSUPPORTED, $preview, null);
        }

        if (! $preview['can_bill']) {
            $status = $preview['already_billed']
                ? ConsultationSpecialtyBillingApplication::STATUS_SKIPPED_DUPLICATE
                : ConsultationSpecialtyBillingApplication::STATUS_UNSUPPORTED;

            return $this->applicationResult($route, $mapping, $user, $status, $preview, null);
        }

        try {
            return DB::transaction(function () use ($route, $mapping, $user, $preview) {
                if ($this->mappings->alreadyBilled($route, $mapping)) {
                    $preview['already_billed'] = true;
                    $preview['can_bill'] = false;
                    $preview['warnings'][] = __('consultation_specialties.billing.already_billed');

                    return $this->applicationResult($route, $mapping, $user, ConsultationSpecialtyBillingApplication::STATUS_SKIPPED_DUPLICATE, $preview, null);
                }

                $application = ConsultationSpecialtyBillingApplication::query()->create([
                    'consultation_id' => $route->id,
                    'consultation_specialty_profile_id' => $mapping->consultation_specialty_profile_id,
                    'consultation_specialty_service_mapping_id' => $mapping->id,
                    'service_id' => $mapping->service_id,
                    'applied_by' => $user->id,
                    'status' => ConsultationSpecialtyBillingApplication::STATUS_PREVIEWED,
                    'trigger' => $mapping->billing_trigger,
                    'preview_payload' => $preview,
                    'warnings' => $preview['warnings'] ?? [],
                ]);

                $item = $this->billing->addItemToVisitInvoice(
                    $route->visit,
                    $mapping->service,
                    InvoiceItem::SOURCE_SPECIALTY_SERVICE_MAPPING,
                    $application->id,
                    1,
                    $route->department_id,
                    $mapping->service->name,
                );

                $application->update([
                    'status' => ConsultationSpecialtyBillingApplication::STATUS_APPLIED,
                    'invoice_id' => $item->invoice_id,
                    'invoice_item_id' => $item->id,
                    'applied_payload' => [
                        'invoice_id' => $item->invoice_id,
                        'invoice_item_id' => $item->id,
                        'service_id' => $item->service_catalog_id,
                        'patient_payable' => (float) $item->patient_payable,
                    ],
                ]);

                $this->activity->log(LogModule::CONSULTATION, 'SPECIALTY_BILLING_APPLIED', [
                    'consultation_id' => $route->id,
                    'mapping_id' => $mapping->id,
                    'service_id' => $mapping->service_id,
                    'invoice_item_id' => $item->id,
                ], $application, 'Specialty billing service applied.');

                return [
                    'status' => ConsultationSpecialtyBillingApplication::STATUS_APPLIED,
                    'message' => __('consultation_specialties.billing.applied'),
                    'application' => $application->fresh(),
                    'preview' => $preview,
                ];
            });
        } catch (\Throwable $e) {
            $preview['warnings'][] = $e->getMessage();

            return $this->applicationResult($route, $mapping, $user, ConsultationSpecialtyBillingApplication::STATUS_FAILED, $preview, ['error' => $e->getMessage()]);
        }
    }

    private function applicationResult(?VisitConsultationRoute $route, ConsultationSpecialtyServiceMapping $mapping, User $user, string $status, array $preview, ?array $applied): array
    {
        $application = null;
        if ($route) {
            $application = ConsultationSpecialtyBillingApplication::query()->create([
                'consultation_id' => $route->id,
                'consultation_specialty_profile_id' => $mapping->consultation_specialty_profile_id,
                'consultation_specialty_service_mapping_id' => $mapping->id,
                'service_id' => $mapping->service_id,
                'applied_by' => $user->id,
                'status' => $status,
                'trigger' => $mapping->billing_trigger,
                'preview_payload' => $preview,
                'applied_payload' => $applied,
                'warnings' => $preview['warnings'] ?? [],
            ]);
        }

        $this->activity->log(LogModule::CONSULTATION, 'SPECIALTY_BILLING_'.$status, [
            'consultation_id' => $route?->id,
            'mapping_id' => $mapping->id,
            'service_id' => $mapping->service_id,
            'warnings' => $preview['warnings'] ?? [],
        ], $application, 'Specialty billing application did not apply.');

        return [
            'status' => $status,
            'message' => __('consultation_specialties.billing.'.$status),
            'application' => $application,
            'preview' => $preview,
        ];
    }

    private function route($consultation): ?VisitConsultationRoute
    {
        return $consultation instanceof VisitConsultationRoute ? $consultation->loadMissing('visit') : null;
    }
}
