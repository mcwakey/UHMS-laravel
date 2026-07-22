<?php

namespace Tests\Feature\LegacyMigration\Foundation;

use App\Listeners\Audit\ForwardCriticalActivityListener;
use App\Models\AdmissionRequest;
use App\Models\Bed;
use App\Models\Invoice;
use App\Models\JourneyHandoffAssignment;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\PaymentProviderCallback;
use App\Models\PrescriptionItem;
use App\Models\SmsNotificationEvent;
use App\Models\SmsProviderCallback;
use App\Models\Visit;
use App\Services\AccountingPostingService;
use App\Services\ActivityLogService;
use App\Services\Admissions\BedWorkflowService;
use App\Services\Billing\PatientPaymentAllocationService;
use App\Services\BillingService;
use App\Services\Insurance\Verification\InsuranceVerificationService;
use App\Services\Integrations\IntegrationProviderService;
use App\Services\Integrations\Payment\PaymentGatewayService;
use App\Services\Integrations\Payment\PaymentCallbackService;
use App\Services\Integrations\Sms\SmsCallbackService;
use App\Services\Integrations\Sms\SmsGatewayService;
use App\Services\Integrations\Sms\SmsNotificationEventService;
use App\Services\JournalEntryService;
use App\Services\Journey\JourneyHandoffNotificationService;
use App\Services\LegacyMigration\Foundation\Runtime\ApplicationEffectBindingMap;
use App\Services\LegacyMigration\Foundation\Runtime\ApplicationBoundSubsystemControl;
use App\Services\LegacyMigration\Foundation\Runtime\ApplicationIsolationAuthorityEvidence;
use App\Services\LegacyMigration\Foundation\Runtime\ApplicationIsolationAuthorityProbe;
use App\Services\LegacyMigration\Foundation\Runtime\ApplicationIsolationBarrierProvider;
use App\Services\LegacyMigration\Foundation\Runtime\ApplicationIsolationBootVerifier;
use App\Services\LegacyMigration\Foundation\Runtime\ApplicationIsolationBootCapability;
use App\Services\LegacyMigration\Foundation\Runtime\ApplicationIsolationControlFactory;
use App\Services\LegacyMigration\Foundation\Runtime\ApplicationIsolationState;
use App\Services\LegacyMigration\Foundation\Runtime\ApplicationMigrationRuntime;
use App\Services\LegacyMigration\Foundation\Runtime\DeploymentApplicationIsolationAuthorityProbe;
use App\Services\LegacyMigration\Foundation\Runtime\MigrationRunActivationAuthority;
use App\Services\LegacyMigration\Foundation\Runtime\MigrationRuntimeAudit;
use App\Services\LegacyMigration\Foundation\Runtime\MigrationRuntimeFactory;
use App\Services\LegacyMigration\Foundation\Runtime\MigrationRuntimeRequest;
use App\Services\LegacyMigration\Foundation\Runtime\OutboundChannel;
use App\Services\LegacyMigration\Foundation\Runtime\OperationalEffectGate;
use App\Services\LegacyMigration\Foundation\Runtime\OperationalGuardScopeManifest;
use App\Services\LegacyMigration\Foundation\Runtime\ProhibitedSubsystem;
use App\Services\LegacyMigration\Foundation\Runtime\RuntimeIsolationException;
use App\Services\LegacyMigration\Foundation\Runtime\RuntimeExecutionBoundary;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Services\PharmacyService;
use App\Services\ProductStockMovementService;
use App\Services\QueueService;
use App\Services\UserService;
use App\Services\VisitPathwayService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\ActivityLogger;
use Tests\Support\LegacyMigration\RecordingMigrationRuntimeAudit;
use Tests\TestCase;

final class ApplicationBoundSideEffectIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['legacy-migration' => $this->configuration()]);
        app()->forgetInstance(ApplicationMigrationRuntime::class);
        app()->instance(MigrationRuntimeAudit::class, new RecordingMigrationRuntimeAudit);

        foreach (ProhibitedSubsystem::cases() as $subsystem) {
            app()->bind('legacy-migration.effect.'.$subsystem->value, static fn () => new \stdClass);
        }
    }

    #[Test]
    public function provider_builds_and_boot_verifies_exactly_twenty_five_real_controls(): void
    {
        $controls = app(ApplicationIsolationControlFactory::class)->make();
        self::assertCount(25, $controls);
        (new ApplicationIsolationBootVerifier(new SyntheticIsolationAuthorityProbe))->verify($controls, $this->configuration());

        app()->instance(ApplicationIsolationBootVerifier::class, new ApplicationIsolationBootVerifier(new SyntheticIsolationAuthorityProbe));
        $runtime = app(ApplicationMigrationRuntime::class);
        self::assertCount(25, $runtime->registry->all());
    }

    #[Test]
    public function all_real_controls_restore_after_a_successful_scope(): void
    {
        app()->instance(ApplicationIsolationBootVerifier::class, new ApplicationIsolationBootVerifier(new SyntheticIsolationAuthorityProbe));
        $facades = [Notification::class, Mail::class, Queue::class, Bus::class, Storage::class, Http::class];
        $roots = [];
        foreach ($facades as $facade) {
            $roots[$facade] = $facade::getFacadeRoot();
        }
        $modelDispatcher = Model::getEventDispatcher();

        $result = app(ApplicationMigrationRuntime::class)->context->run($this->request(), static fn (): string => 'synthetic-success');

        self::assertSame('synthetic-success', $result);
        self::assertSame($modelDispatcher, Model::getEventDispatcher());
        foreach ($roots as $facade => $root) {
            self::assertSame($root, $facade::getFacadeRoot());
        }
    }

    #[Test]
    #[DataProvider('subsystems')]
    public function every_application_gateway_is_intercepted_measured_and_restored(ProhibitedSubsystem $subsystem): void
    {
        app()->instance(ApplicationIsolationBootVerifier::class, new ApplicationIsolationBootVerifier(new SyntheticIsolationAuthorityProbe));
        $runtime = app(ApplicationMigrationRuntime::class);
        try {
            $runtime->context->run($this->request(), fn () => app('legacy-migration.effect.'.$subsystem->value));
            self::fail('The application effect gateway was not denied.');
        } catch (RuntimeIsolationException $exception) {
            self::assertSame('FOUNDATION_APPLICATION_EFFECT_RESOLUTION_DENIED', $exception->faultCode);
        }

        // The same real container path works normally after restoration.
        self::assertInstanceOf(\stdClass::class, app('legacy-migration.effect.'.$subsystem->value));
    }

    #[Test]
    #[DataProvider('facadePaths')]
    public function framework_facade_roots_are_replaced_by_measured_deny_sinks_and_restored(string $facade, ProhibitedSubsystem $subsystem): void
    {
        app()->instance(ApplicationIsolationBootVerifier::class, new ApplicationIsolationBootVerifier(new SyntheticIsolationAuthorityProbe));
        $runtime = app(ApplicationMigrationRuntime::class);
        $before = $facade::getFacadeRoot();
        try {
            $runtime->context->run($this->request(), function () use ($facade): void {
                $facade::getFacadeRoot()->phase3bSyntheticEffect();
            });
            self::fail('The facade effect path was not denied.');
        } catch (RuntimeIsolationException $exception) {
            self::assertSame('FOUNDATION_APPLICATION_EFFECT_DENIED', $exception->faultCode);
        }
        self::assertSame($before, $facade::getFacadeRoot());
    }

    #[Test]
    public function eloquent_dispatcher_and_activity_logger_are_real_application_bindings(): void
    {
        app()->instance(ApplicationIsolationBootVerifier::class, new ApplicationIsolationBootVerifier(new SyntheticIsolationAuthorityProbe));
        $runtime = app(ApplicationMigrationRuntime::class);
        $before = Model::getEventDispatcher();
        try {
            $runtime->context->run($this->request(), static function (): void {
                Model::getEventDispatcher()?->dispatch('synthetic.foundation.event');
            });
            self::fail('The Eloquent event path was not denied.');
        } catch (RuntimeIsolationException $exception) {
            self::assertSame('FOUNDATION_APPLICATION_EFFECT_DENIED', $exception->faultCode);
        }
        self::assertSame($before, Model::getEventDispatcher());

        try {
            $runtime->context->run($this->request(), static fn () => app(ActivityLogger::class));
            self::fail('Operational activity logger resolution was not denied.');
        } catch (RuntimeIsolationException $exception) {
            self::assertSame('FOUNDATION_APPLICATION_EFFECT_RESOLUTION_DENIED', $exception->faultCode);
        }
    }

    #[Test]
    public function real_operational_service_classes_are_denied_at_container_resolution(): void
    {
        app()->instance(ApplicationIsolationBootVerifier::class, new ApplicationIsolationBootVerifier(new SyntheticIsolationAuthorityProbe));
        $runtime = app(ApplicationMigrationRuntime::class);
        $checked = 0;
        foreach (ApplicationEffectBindingMap::bindings() as $abstract => $subsystem) {
            if (! class_exists($abstract) || str_starts_with($abstract, 'App\\Services\\LegacyMigration\\')) {
                continue;
            }
            try {
                $runtime->context->run($this->request(), static fn () => app($abstract));
                self::fail("Operational binding {$abstract} was not denied.");
            } catch (RuntimeIsolationException $exception) {
                self::assertSame('FOUNDATION_APPLICATION_EFFECT_RESOLUTION_DENIED', $exception->faultCode);
            }
            $checked++;
        }
        self::assertGreaterThanOrEqual(15, $checked);
    }

    #[Test]
    public function pre_resolved_and_directly_created_operational_services_are_denied_at_invocation(): void
    {
        $preResolvedNotificationService = app(NotificationService::class);
        $cases = [
            'notification service resolved before isolation' => [
                ProhibitedSubsystem::Notifications,
                static fn () => $preResolvedNotificationService->notifyUser(null, []),
            ],
            'direct activity service' => [
                ProhibitedSubsystem::OperationalActivityLog,
                fn () => $this->unconstructed(ActivityLogService::class)->log('security', 'synthetic'),
            ],
            'direct SMS gateway' => [
                ProhibitedSubsystem::Sms,
                fn () => $this->unconstructed(SmsGatewayService::class)->send([]),
            ],
            'direct appointment reminder dispatch' => [
                ProhibitedSubsystem::AppointmentReminders,
                fn () => $this->unconstructed(SmsNotificationEventService::class)->dispatch([
                    'event_type' => SmsNotificationEvent::TYPE_APPOINTMENT_REMINDER,
                ]),
            ],
            'direct payment integration' => [
                ProhibitedSubsystem::PaymentIntegrations,
                fn () => $this->unconstructed(PaymentGatewayService::class)->initiate([]),
            ],
            'direct insurance eligibility integration' => [
                ProhibitedSubsystem::InsuranceEligibilityIntegrations,
                fn () => $this->unconstructed(InsuranceVerificationService::class)->verify(new PatientInsurance),
            ],
            'direct billing creation' => [
                ProhibitedSubsystem::BillingCreation,
                fn () => $this->unconstructed(BillingService::class)->createInvoice([], []),
            ],
            'direct payment recording' => [
                ProhibitedSubsystem::PaymentAllocation,
                fn () => $this->unconstructed(PaymentService::class)->recordPayment(new Invoice, []),
            ],
            'direct journal entry posting' => [
                ProhibitedSubsystem::AccountingPosting,
                fn () => $this->unconstructed(JournalEntryService::class)->createDraft([]),
            ],
            'direct accounting posting orchestration' => [
                ProhibitedSubsystem::AccountingPosting,
                fn () => $this->unconstructed(AccountingPostingService::class)->postFromSource('synthetic', new Invoice, []),
            ],
            'direct patient payment allocation' => [
                ProhibitedSubsystem::PaymentAllocation,
                fn () => $this->unconstructed(PatientPaymentAllocationService::class)->allocatePaymentOldestFirst(new Patient, []),
            ],
            'direct stock movement' => [
                ProhibitedSubsystem::StockMovement,
                fn () => $this->unconstructed(ProductStockMovementService::class)->createMovement([]),
            ],
            'direct pharmacy dispensing' => [
                ProhibitedSubsystem::PharmacyDispensing,
                fn () => $this->unconstructed(PharmacyService::class)->dispenseItem(new PrescriptionItem, 1),
            ],
            'direct bed occupancy' => [
                ProhibitedSubsystem::BedOccupancy,
                fn () => $this->unconstructed(BedWorkflowService::class)->reserveForRequest(new AdmissionRequest, new Bed),
            ],
            'direct queue mutation' => [
                ProhibitedSubsystem::QueuePathwayMutation,
                fn () => $this->unconstructed(QueueService::class)->addTriageEntry(new Visit),
            ],
            'direct pathway mutation' => [
                ProhibitedSubsystem::QueuePathwayMutation,
                fn () => $this->unconstructed(VisitPathwayService::class)->record(new Visit, 'synthetic'),
            ],
            'direct journey notification' => [
                ProhibitedSubsystem::JourneyNotifications,
                fn () => $this->unconstructed(JourneyHandoffNotificationService::class)->notifyAssigned(new JourneyHandoffAssignment, null),
            ],
            'direct external audit forwarding' => [
                ProhibitedSubsystem::ExternalAuditForwarding,
                fn () => (new ForwardCriticalActivityListener)->handle(),
            ],
            'direct integration provider mutation' => [
                ProhibitedSubsystem::ExternalIntegrations,
                fn () => $this->unconstructed(IntegrationProviderService::class)->create([], 'sms'),
            ],
            'direct file write' => [
                ProhibitedSubsystem::FileAvatarWrites,
                fn () => $this->unconstructed(UserService::class)->create(['avatar' => new class
                {
                    public function store(): never
                    {
                        throw new \LogicException('The file sink must not be reached.');
                    }
                }]),
            ],
        ];

        $state = app(ApplicationIsolationState::class);
        foreach ($cases as $name => [$subsystem, $operation]) {
            $state->activate($subsystem);
            try {
                $operation();
                self::fail("The {$name} invocation was not denied.");
            } catch (RuntimeIsolationException $exception) {
                self::assertSame('FOUNDATION_APPLICATION_EFFECT_DENIED', $exception->faultCode, $name);
            } finally {
                $state->deactivate($subsystem);
            }
        }
    }

    #[Test]
    public function every_payment_and_sms_callback_entry_is_denied_before_database_or_provider_work(): void
    {
        $payment = $this->unconstructed(PaymentCallbackService::class);
        $sms = $this->unconstructed(SmsCallbackService::class);
        $preResolvedPayment = app(PaymentCallbackService::class);
        $preResolvedSms = app(SmsCallbackService::class);
        $cases = [
            'payment store' => fn () => $payment->store('synthetic', []),
            'payment process' => fn () => $payment->process(new PaymentProviderCallback),
            'payment handle' => fn () => $payment->handle('synthetic', []),
            'sms store' => fn () => $sms->store('synthetic', []),
            'sms process' => fn () => $sms->process(new SmsProviderCallback),
            'sms handle' => fn () => $sms->handle('synthetic', []),
            'pre-resolved payment store' => fn () => $preResolvedPayment->store('synthetic', []),
            'pre-resolved sms store' => fn () => $preResolvedSms->store('synthetic', []),
        ];
        $state = app(ApplicationIsolationState::class);
        $state->activate(ProhibitedSubsystem::Webhooks);
        try {
            foreach ($cases as $name => $operation) {
                try {
                    $operation();
                    self::fail("The {$name} path was not denied.");
                } catch (RuntimeIsolationException $exception) {
                    self::assertSame('FOUNDATION_APPLICATION_EFFECT_DENIED', $exception->faultCode, $name);
                }
            }
        } finally {
            $state->deactivate(ProhibitedSubsystem::Webhooks);
        }

        self::assertContains(SmsCallbackService::class, array_keys(ApplicationEffectBindingMap::bindings()));
    }

    #[Test]
    public function a_non_application_control_that_claims_isolation_is_rejected_at_boot(): void
    {
        $controls = app(ApplicationIsolationControlFactory::class)->make();
        $controls[0] = new LyingIsolationControl($controls[0]->subsystem());

        try {
            (new ApplicationIsolationBootVerifier(new SyntheticIsolationAuthorityProbe))->verify($controls, $this->configuration());
            self::fail('A caller-supplied control was accepted as authoritative isolation proof.');
        } catch (RuntimeIsolationException $exception) {
            self::assertSame('FOUNDATION_ISOLATION_BOOT_CONTROL_UNPROVEN', $exception->faultCode);
        }
    }

    #[Test]
    public function external_barriers_are_reobserved_and_revocation_blocks_runtime_entry(): void
    {
        $controls = app(ApplicationIsolationControlFactory::class)->make();
        $probe = new RevocableIsolationAuthorityProbe(2);
        $capability = ApplicationIsolationBootCapability::fromVerifiedObservation(
            new ApplicationIsolationBootVerifier($probe),
            $controls,
            $this->configuration(),
        );
        $runtime = (new MigrationRuntimeFactory($capability))->create(
            $this->configuration(),
            $controls,
            new RecordingMigrationRuntimeAudit,
        );

        try {
            $runtime->context->run($this->request(), static fn () => null);
            self::fail('Runtime entry continued after an authoritative deployment barrier was revoked.');
        } catch (RuntimeIsolationException $exception) {
            self::assertSame('FOUNDATION_ISOLATION_BOOT_PREREQUISITE_MISSING', $exception->faultCode);
        }
        self::assertSame(3, $probe->observations);
    }

    #[Test]
    public function nested_application_runtime_activation_is_rejected(): void
    {
        app()->instance(ApplicationIsolationBootVerifier::class, new ApplicationIsolationBootVerifier(new SyntheticIsolationAuthorityProbe));
        $runtime = app(ApplicationMigrationRuntime::class);

        try {
            $runtime->context->run($this->request(), fn () => $runtime->context->run($this->request(), static fn () => null));
            self::fail('Nested migration runtime activation was accepted.');
        } catch (RuntimeIsolationException $exception) {
            self::assertSame('FOUNDATION_RUNTIME_REENTRANT', $exception->faultCode);
        }
    }

    #[Test]
    public function every_operational_gate_remains_open_outside_migration_runtime(): void
    {
        foreach (ProhibitedSubsystem::cases() as $subsystem) {
            OperationalEffectGate::assertAllowed($subsystem);
            self::assertFalse(app(ApplicationIsolationState::class)->isolated($subsystem), $subsystem->value);
        }
    }

    #[Test]
    public function web_request_boundary_cannot_activate_runtime(): void
    {
        $controls = app(ApplicationIsolationControlFactory::class)->make();
        $capability = ApplicationIsolationBootCapability::fromVerifiedObservation(
            new ApplicationIsolationBootVerifier(new SyntheticIsolationAuthorityProbe),
            $controls,
            $this->configuration(),
        );
        $runtime = (new MigrationRuntimeFactory($capability))->create(
            $this->configuration(), $controls, new RecordingMigrationRuntimeAudit, null, new SyntheticWebExecutionBoundary,
        );
        try {
            $runtime->context->run($this->request(), static fn () => null);
            self::fail('A web request boundary activated the migration runtime.');
        } catch (RuntimeIsolationException $exception) {
            self::assertSame('FOUNDATION_RUNTIME_CONSOLE_REQUIRED', $exception->faultCode);
        }
    }

    #[Test]
    public function a_real_control_that_fails_to_isolate_aborts_and_restores(): void
    {
        $controls = app(ApplicationIsolationControlFactory::class)->make();
        $broken = new NonIsolatingHook;
        $controls[0] = new ApplicationBoundSubsystemControl($controls[0]->subsystem(), $broken);
        $capability = ApplicationIsolationBootCapability::fromVerifiedObservation(
            new ApplicationIsolationBootVerifier(new SyntheticIsolationAuthorityProbe), $controls, $this->configuration(),
        );
        $runtime = (new MigrationRuntimeFactory($capability))->create(
            $this->configuration(), $controls, new RecordingMigrationRuntimeAudit,
        );
        try {
            $runtime->context->run($this->request(), static fn () => null);
            self::fail('A control that remained active was accepted.');
        } catch (RuntimeIsolationException $exception) {
            self::assertSame('FOUNDATION_ISOLATION_UNPROVEN', $exception->faultCode);
        }
        self::assertTrue($broken->restored);
    }

    #[Test]
    public function mapped_public_effect_entries_have_a_first_statement_invocation_guard(): void
    {
        $manifest = OperationalGuardScopeManifest::guardedEntries();
        self::assertSame(127, array_sum(array_map('count', $manifest)));
        /*
        $legacyInlineManifest = [
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
        ]; */

        foreach ($manifest as $class => $methods) {
            foreach ($methods as $method) {
                $reflection = new \ReflectionMethod($class, $method);
                $lines = file($reflection->getFileName(), FILE_IGNORE_NEW_LINES);
                $source = implode("\n", array_slice($lines, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));
                self::assertMatchesRegularExpression(
                    '/\{\s*(?:\$this->assertWebhookAllowed\(\);|OperationalEffectGate::assertAllowed\(ProhibitedSubsystem::[A-Za-z]+\);)/',
                    $source,
                    "{$class}::{$method} must invoke isolation at its first executable statement.",
                );
            }
        }
    }

    #[Test]
    public function missing_external_worker_or_scheduler_proof_prevents_runtime_boot(): void
    {
        $configuration = $this->configuration();
        $configuration['isolation']['application_bindings']['queue_workers_paused'] = false;

        $this->expectException(RuntimeIsolationException::class);
        $this->expectExceptionMessage('boot prerequisite');
        (new ApplicationIsolationBootVerifier(new SyntheticIsolationAuthorityProbe))->verify(app(ApplicationIsolationControlFactory::class)->make(), $configuration);
    }

    #[Test]
    public function caller_booleans_cannot_replace_the_authoritative_external_probe(): void
    {
        $this->expectException(RuntimeIsolationException::class);
        $this->expectExceptionMessage('probe is missing');
        (new ApplicationIsolationBootVerifier)->verify(app(ApplicationIsolationControlFactory::class)->make(), $this->configuration());
    }

    #[Test]
    public function deployment_probe_consumes_config_and_requires_a_bound_direct_observer(): void
    {
        self::assertInstanceOf(DeploymentApplicationIsolationAuthorityProbe::class, app(ApplicationIsolationAuthorityProbe::class));
        try {
            app(ApplicationIsolationAuthorityProbe::class)->observe();
            self::fail('The default missing deployment barrier provider was accepted.');
        } catch (RuntimeIsolationException $exception) {
            self::assertSame('FOUNDATION_ISOLATION_BARRIER_PROVIDER_MISSING', $exception->faultCode);
        }

        $evidence = (new DeploymentApplicationIsolationAuthorityProbe(
            new SyntheticIsolationBarrierProvider,
            $this->configuration(),
        ))->observe();
        self::assertSame('synthetic-direct-observation', $evidence->observationReference);
    }

    /** @return array<string,array{ProhibitedSubsystem}> */
    public static function subsystems(): array
    {
        $values = [];
        foreach (ProhibitedSubsystem::cases() as $subsystem) {
            $values[$subsystem->value] = [$subsystem];
        }

        return $values;
    }

    /** @return array<string,array{string,ProhibitedSubsystem}> */
    public static function facadePaths(): array
    {
        return [
            'notifications' => [Notification::class, ProhibitedSubsystem::Notifications],
            'mail' => [Mail::class, ProhibitedSubsystem::Mail],
            'queue' => [Queue::class, ProhibitedSubsystem::QueueDispatch],
            'bus' => [Bus::class, ProhibitedSubsystem::BusJobs],
            'file writes' => [Storage::class, ProhibitedSubsystem::FileAvatarWrites],
            'external HTTP' => [Http::class, ProhibitedSubsystem::ExternalIntegrations],
        ];
    }

    /** @return array<string,mixed> */
    private function configuration(): array
    {
        return [
            'isolation' => [
                'required_subsystems' => array_map(static fn (ProhibitedSubsystem $item) => $item->value, ProhibitedSubsystem::cases()),
                'outbound_deny_list' => array_map(static fn (OutboundChannel $item) => $item->value, OutboundChannel::cases()),
                'application_bindings' => [
                    'bindings_verified' => true,
                    'queue_workers_paused' => true,
                    'scheduler_paused' => true,
                    'external_integrations_sink_verified' => true,
                    'authority_reference' => 'synthetic-phase3b-authority',
                    'queue_worker_barrier_reference' => 'synthetic-queue-barrier',
                    'scheduler_barrier_reference' => 'synthetic-scheduler-barrier',
                    'null_sink_reference' => 'synthetic-null-sink',
                ],
            ],
        ];
    }

    private function request(): MigrationRuntimeRequest
    {
        return new MigrationRuntimeRequest(MigrationRunActivationAuthority::syntheticForTests(
            str_repeat('a', 64), str_repeat('b', 64),
        ));
    }

    /** @template T of object @param class-string<T> $class @return T */
    private function unconstructed(string $class): object
    {
        return (new \ReflectionClass($class))->newInstanceWithoutConstructor();
    }
}

final class SyntheticIsolationAuthorityProbe implements ApplicationIsolationAuthorityProbe
{
    public function observe(): ApplicationIsolationAuthorityEvidence
    {
        return new ApplicationIsolationAuthorityEvidence(
            true, true, true, true,
            'synthetic-phase3b-authority', 'synthetic-queue-barrier', 'synthetic-scheduler-barrier',
            'synthetic-null-sink', 'synthetic-direct-observation',
        );
    }
}

final class LyingIsolationControl implements \App\Services\LegacyMigration\Foundation\Runtime\SubsystemIsolationControl
{
    public function __construct(private readonly ProhibitedSubsystem $owned) {}
    public function subsystem(): ProhibitedSubsystem { return $this->owned; }
    public function isIsolated(): bool { return true; }
    public function isolate(): void {}
    public function restore(bool $previouslyIsolated): void {}
}

final class RevocableIsolationAuthorityProbe implements ApplicationIsolationAuthorityProbe
{
    public int $observations = 0;
    public function __construct(private readonly int $successfulObservations) {}
    public function observe(): ApplicationIsolationAuthorityEvidence
    {
        $this->observations++;
        $active = $this->observations <= $this->successfulObservations;
        return new ApplicationIsolationAuthorityEvidence(
            $active, $active, $active, $active,
            'synthetic-phase3b-authority', 'synthetic-queue-barrier', 'synthetic-scheduler-barrier',
            'synthetic-null-sink', 'synthetic-direct-observation-'.$this->observations,
        );
    }
}

final readonly class SyntheticWebExecutionBoundary implements RuntimeExecutionBoundary
{
    public function isConsole(): bool { return false; }
}

final class NonIsolatingHook implements \App\Services\LegacyMigration\Foundation\Runtime\SubsystemIsolationHook
{
    public bool $restored = false;
    public function proofReference(): string { return 'synthetic-non-isolating-hook'; }
    public function capture(): mixed { return false; }
    public function isolate(): void {}
    public function isolated(): bool { return false; }
    public function restore(mixed $state): void { $this->restored = true; }
}

final class SyntheticIsolationBarrierProvider implements ApplicationIsolationBarrierProvider
{
    public function observe(array $expectedReferences): ApplicationIsolationAuthorityEvidence
    {
        return new ApplicationIsolationAuthorityEvidence(
            true, true, true, true,
            $expectedReferences['authority_reference'],
            $expectedReferences['queue_worker_barrier_reference'],
            $expectedReferences['scheduler_barrier_reference'],
            $expectedReferences['null_sink_reference'],
            'synthetic-direct-observation',
        );
    }
}
