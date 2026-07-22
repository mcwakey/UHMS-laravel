<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

use App\Listeners\Audit\ForwardCriticalActivityListener;
use App\Services\AccountingPostingService;
use App\Services\Admissions\BedWorkflowService;
use App\Services\AppointmentService;
use App\Services\Billing\PatientPaymentAllocationService;
use App\Services\BillingService;
use App\Services\Insurance\Verification\InsuranceVerificationService;
use App\Services\Insurance\Verification\VerificationManager;
use App\Services\Integrations\IntegrationProviderService;
use App\Services\Integrations\Payment\PaymentCallbackService;
use App\Services\Integrations\Payment\PaymentGatewayService;
use App\Services\Integrations\Sms\SmsGatewayService;
use App\Services\Integrations\Sms\SmsCallbackService;
use App\Services\Journey\JourneyHandoffNotificationService;
use App\Services\NotificationService;
use App\Services\PharmacyService;
use App\Services\ProductStockMovementService;
use App\Services\QueueService;
use App\Services\VisitPathwayService;
use Illuminate\Console\Scheduling\Schedule;
use Spatie\Activitylog\ActivityLogger;

final class ApplicationEffectBindingMap
{
    /** @return array<string,ProhibitedSubsystem> */
    public static function bindings(): array
    {
        $bindings = [
            Schedule::class => ProhibitedSubsystem::ScheduledCommands,
            ForwardCriticalActivityListener::class => ProhibitedSubsystem::ExternalAuditForwarding,
            NotificationService::class => ProhibitedSubsystem::Notifications,
            ActivityLogger::class => ProhibitedSubsystem::OperationalActivityLog,
            VerificationManager::class => ProhibitedSubsystem::InsuranceEligibilityIntegrations,
            InsuranceVerificationService::class => ProhibitedSubsystem::InsuranceEligibilityIntegrations,
            SmsGatewayService::class => ProhibitedSubsystem::Sms,
            PaymentGatewayService::class => ProhibitedSubsystem::PaymentIntegrations,
            BillingService::class => ProhibitedSubsystem::BillingCreation,
            AccountingPostingService::class => ProhibitedSubsystem::AccountingPosting,
            PatientPaymentAllocationService::class => ProhibitedSubsystem::PaymentAllocation,
            ProductStockMovementService::class => ProhibitedSubsystem::StockMovement,
            PharmacyService::class => ProhibitedSubsystem::PharmacyDispensing,
            BedWorkflowService::class => ProhibitedSubsystem::BedOccupancy,
            QueueService::class => ProhibitedSubsystem::QueuePathwayMutation,
            VisitPathwayService::class => ProhibitedSubsystem::QueuePathwayMutation,
            AppointmentService::class => ProhibitedSubsystem::AppointmentReminders,
            JourneyHandoffNotificationService::class => ProhibitedSubsystem::JourneyNotifications,
            PaymentCallbackService::class => ProhibitedSubsystem::Webhooks,
            SmsCallbackService::class => ProhibitedSubsystem::Webhooks,
            IntegrationProviderService::class => ProhibitedSubsystem::ExternalIntegrations,
            'legacy-migration.effect.sms' => ProhibitedSubsystem::Sms,
            'legacy-migration.effect.payment' => ProhibitedSubsystem::PaymentIntegrations,
            'legacy-migration.effect.billing' => ProhibitedSubsystem::BillingCreation,
            'legacy-migration.effect.accounting' => ProhibitedSubsystem::AccountingPosting,
            'legacy-migration.effect.payment_allocation' => ProhibitedSubsystem::PaymentAllocation,
            'legacy-migration.effect.stock' => ProhibitedSubsystem::StockMovement,
            'legacy-migration.effect.pharmacy' => ProhibitedSubsystem::PharmacyDispensing,
            'legacy-migration.effect.bed' => ProhibitedSubsystem::BedOccupancy,
            'legacy-migration.effect.queue_pathway' => ProhibitedSubsystem::QueuePathwayMutation,
            'legacy-migration.effect.appointment_reminder' => ProhibitedSubsystem::AppointmentReminders,
            'legacy-migration.effect.journey_notification' => ProhibitedSubsystem::JourneyNotifications,
            'legacy-migration.effect.search' => ProhibitedSubsystem::SearchIndexing,
            'legacy-migration.effect.webhook' => ProhibitedSubsystem::Webhooks,
        ];

        // Every application subsystem also has a mandatory migration gateway
        // alias. Provider-level before-resolving interception makes bypass by
        // ordinary container resolution fail closed and measurable.
        foreach (ProhibitedSubsystem::cases() as $subsystem) {
            $bindings['legacy-migration.effect.'.$subsystem->value] = $subsystem;
        }

        return $bindings;
    }
}
