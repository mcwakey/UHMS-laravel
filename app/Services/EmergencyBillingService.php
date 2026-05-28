<?php

namespace App\Services;

use App\Models\EmergencyCase;
use App\Models\InvoiceItem;
use App\Models\ServiceCatalog;
use App\Models\User;

class EmergencyBillingService
{
    public function __construct(
        private BillingService $billing,
        private EmergencyTimelineService $timeline,
        private EmergencySessionService $sessions,
    ) {}

    public function addService(EmergencyCase $case, ServiceCatalog $service, int $quantity, User $user, ?string $notes = null): InvoiceItem
    {
        $item = $this->billing->addItemToVisitInvoice(
            visit: $case->visit,
            service: $service,
            sourceType: 'emergency_service',
            sourceId: $service->id,
            quantity: max(1, $quantity),
            departmentId: $service->department_id,
            description: $notes ?: $service->name,
        );

        $this->sessions->recordContribution($case, $user, 'Billing');
        $this->timeline->record($case, 'BILLING_ITEM_ADDED', 'Emergency billable service added', $service->name, $item, $user);

        return $item;
    }
}
