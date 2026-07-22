<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

/** Deterministic repository scope for every application-level migration isolation guard. */
final class OperationalGuardScopeManifest
{
    /** @return list<string> */
    public static function sourcePaths(): array
    {
        return [
            'app/Console/Commands/IntegrationsSmsSendAppointmentRemindersCommand.php',
            'app/Listeners/Audit/ForwardCriticalActivityListener.php',
            'app/Providers/AppServiceProvider.php',
            'app/Services/AccountingPostingRetryService.php',
            'app/Services/AccountingPostingService.php',
            'app/Services/AccountingService.php',
            'app/Services/ActivityLogService.php',
            'app/Services/Admissions/BedWorkflowService.php',
            'app/Services/AppointmentService.php',
            'app/Services/BankReconciliationAdjustmentPostingService.php',
            'app/Services/BasicAccountingPostingService.php',
            'app/Services/Billing/PatientPaymentAllocationService.php',
            'app/Services/BillingAccountingPostingService.php',
            'app/Services/BillingService.php',
            'app/Services/Insurance/Verification/InsuranceVerificationService.php',
            'app/Services/Integrations/IntegrationProviderService.php',
            'app/Services/Integrations/Payment/PaymentCallbackService.php',
            'app/Services/Integrations/Payment/PaymentGatewayService.php',
            'app/Services/Integrations/Sms/SmsCallbackService.php',
            'app/Services/Integrations/Sms/SmsGatewayService.php',
            'app/Services/Integrations/Sms/SmsNotificationEventService.php',
            'app/Services/InventoryAccountingPostingService.php',
            'app/Services/InvoiceReceivableService.php',
            'app/Services/InvoiceService.php',
            'app/Services/JournalEntryService.php',
            'app/Services/Journey/JourneyHandoffNotificationService.php',
            'app/Services/LabService.php',
            'app/Services/NotificationService.php',
            'app/Services/PatientService.php',
            'app/Services/PaymentAccountingPostingService.php',
            'app/Services/PaymentService.php',
            'app/Services/PayrollAccountingService.php',
            'app/Services/PharmacyService.php',
            'app/Services/ProductStockMovementService.php',
            'app/Services/QueueService.php',
            'app/Services/ReceivableAccountingPostingService.php',
            'app/Services/SupplierAccountingPostingService.php',
            'app/Services/UserService.php',
            'app/Services/VisitPathwayService.php',
        ];
    }

    /** @return array<class-string,list<string>> */
    public static function guardedEntries(): array
    {
        return [
            \App\Services\Integrations\Sms\SmsGatewayService::class => ['send', 'queueOrSend', 'deliverNow', 'retry', 'resend', 'deliverRecipient', 'handleDeliveryCallback'],
            \App\Services\Integrations\Payment\PaymentCallbackService::class => ['store', 'process', 'handle'],
            \App\Services\Integrations\Sms\SmsCallbackService::class => ['store', 'process', 'handle'],
            \App\Services\QueueService::class => ['addToQueue', 'addTriageEntry', 'ensureTriageEntry', 'addForDepartment', 'ensureForDepartment', 'callNext', 'markServing', 'markCompleted', 'skip', 'requeue', 'completeCurrentEntry'],
            \App\Services\Admissions\BedWorkflowService::class => ['reserveForRequest', 'releaseActiveReservationsForRequest', 'releaseReservation', 'cancelReservation', 'expireReservation', 'fulfillReservationForAdmission', 'recordAdmissionStart', 'transferAdmission', 'releaseBedForDischarge', 'updateBedStatus'],
            \App\Services\ProductStockMovementService::class => ['createMovement', 'reverseMovement', 'rebuildAllBalances', 'rebuildBalance'],
            \App\Services\JournalEntryService::class => ['createDraft', 'updateDraft', 'post', 'reverse', 'cancelDraft'],
            \App\Services\BillingService::class => ['addItemToVisitInvoice', 'applyDiscount', 'addProductToVisitInvoice', 'addItemIfNotBilled', 'recalculateInvoiceForItem', 'createInvoice', 'recordPayment', 'cancelInvoice', 'generateItemsFromVisit'],
            \App\Services\InvoiceService::class => ['getOrCreateVisitInvoice', 'recalculateTotals', 'updateStatus'],
            \App\Services\BillingAccountingPostingService::class => ['postInvoice', 'postDiscount', 'postCreditNote', 'reverseInvoice', 'reverseCreditNote'],
            \App\Services\InvoiceReceivableService::class => ['syncFromInvoice', 'resolvePaymentReceivable', 'applyPayment', 'refreshReceivableAmounts', 'logAllocationChange'],
            \App\Services\NotificationService::class => ['notifyUser', 'notifyUsers', 'notifyRole', 'notifyPermission', 'notifyDepartment', 'markAsRead', 'markAllAsRead'],
            \App\Services\ActivityLogService::class => ['log', 'logCreated', 'logUpdated', 'logDeleted', 'logCorrection', 'logOverride', 'logSecurity', 'logPatientAction', 'logVisitAction', 'logClinicalAction', 'logFinancialAction', 'logStockAction'],
            \App\Services\PharmacyService::class => ['storeCategory', 'updateCategory', 'deleteCategory', 'storeDrug', 'updateDrug', 'toggleDrug', 'addStock', 'dispenseItem', 'batchDispense'],
            \App\Services\AppointmentService::class => ['create', 'update', 'transition', 'cancel', 'checkIn', 'markNoShow'],
            \App\Services\PaymentAccountingPostingService::class => ['postPayment'],
            \App\Services\InventoryAccountingPostingService::class => ['postForMovement', 'reverseForMovement'],
            \App\Services\ReceivableAccountingPostingService::class => ['postReallocation'],
            \App\Services\SupplierAccountingPostingService::class => ['postGoodsReceipt', 'postSupplierPayment', 'postPurchaseReturn', 'reverseSupplierPayment'],
            \App\Services\BasicAccountingPostingService::class => ['preview', 'post', 'reverse'],
            \App\Services\AccountingPostingRetryService::class => ['retry'],
            \App\Services\PayrollAccountingService::class => ['postPayroll', 'reversePayroll', 'settlePayroll', 'reverseSettlement', 'settleStatutoryLiability', 'reverseStatutorySettlement'],
            \App\Services\BankReconciliationAdjustmentPostingService::class => ['propose', 'approve', 'reject', 'post'],
            \App\Services\AccountingService::class => ['createEntry', 'approveEntry', 'deleteEntry', 'openShift', 'closeShift', 'verifyShift'],
        ];
    }
}
