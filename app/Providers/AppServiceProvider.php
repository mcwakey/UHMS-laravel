<?php

namespace App\Providers;

use App\Events\LabRequestCreated;
use App\Events\LabResultsCompleted;
use App\Events\PatientAdmitted;
use App\Events\PatientDischarged;
use App\Events\PaymentRecorded;
use App\Events\PrescriptionCreated;
use App\Events\StockLow;
use App\Listeners\Audit\ForwardCriticalActivityListener;
use App\Listeners\Auth\LogAuthEvents;
use App\Listeners\Integrations\SendPaymentReceiptSms;
use App\Listeners\NotifyAccountants;
use App\Listeners\NotifyAccountantsDischarge;
use App\Listeners\NotifyDoctorLabResults;
use App\Listeners\NotifyLabTechnicians;
use App\Listeners\NotifyPharmacists;
use App\Listeners\NotifyStockManagers;
use App\Listeners\NotifyWardStaffAdmission;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceReceivable;
use App\Models\Payment;
use App\Models\User;
use App\Models\Visit;
use App\Observers\InvoiceItemObserver;
use App\Observers\InvoiceObserver;
use App\Observers\InvoiceReceivableObserver;
use App\Observers\PaymentObserver;
use App\Observers\UserObserver;
use App\Observers\VisitObserver;
use App\Services\Billing\OperationalVisitPaymentTimingResolver;
use App\Services\Billing\PaymentTimingCutoverConfigurationService;
use App\Services\Billing\PaymentTimingCutoverDiagnostics;
use App\Services\Consultation\ConsultationUserRelationLoader;
use App\Services\Department\DepartmentContextSwitcherService;
use App\Services\Insurance\Verification\InsuranceVerificationService;
use App\Services\Insurance\Verification\VerificationManager;
use App\Services\LegacyMigration\Foundation\Recovery\AtomicRecoveryJournal;
use App\Services\LegacyMigration\Foundation\Allocation\AuthorityBoundReservationFactory;
use App\Services\LegacyMigration\Foundation\Allocation\LaravelNumberReservationStore;
use App\Services\LegacyMigration\Foundation\Allocation\NumberReservationStore;
use App\Services\LegacyMigration\Foundation\Allocation\ReservationAttributeFactory;
use App\Services\LegacyMigration\Foundation\Allocation\RecoveryReservationCoordinateContextFactory;
use App\Services\LegacyMigration\Foundation\Allocation\ReservationCoordinateContextFactory;
use App\Services\LegacyMigration\Foundation\Allocation\ReservationProtectionFactory;
use App\Services\LegacyMigration\Foundation\Recovery\AuthorityBoundRecoveryJournalAttributeFactory;
use App\Services\LegacyMigration\Foundation\Recovery\CompareAndSetStateStore;
use App\Services\LegacyMigration\Foundation\Recovery\ConfiguredRecoveryJournalAuthority;
use App\Services\LegacyMigration\Foundation\Recovery\LaravelAtomicRecoveryJournal;
use App\Services\LegacyMigration\Foundation\Recovery\LaravelCompareAndSetStateStore;
use App\Services\LegacyMigration\Foundation\Recovery\ProtectedRecoveryStore;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryException;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryJournalAttributeFactory;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryJournalAuthority;
use App\Services\LegacyMigration\Foundation\Runtime\ApplicationEffectBindingMap;
use App\Services\LegacyMigration\Foundation\Runtime\ApplicationIsolationAuthorityProbe;
use App\Services\LegacyMigration\Foundation\Runtime\ApplicationIsolationBarrierProvider;
use App\Services\LegacyMigration\Foundation\Runtime\ApplicationIsolationBootVerifier;
use App\Services\LegacyMigration\Foundation\Runtime\ApplicationIsolationBootCapability;
use App\Services\LegacyMigration\Foundation\Runtime\ApplicationIsolationControlFactory;
use App\Services\LegacyMigration\Foundation\Runtime\ApplicationIsolationState;
use App\Services\LegacyMigration\Foundation\Runtime\ApplicationMigrationRuntime;
use App\Services\LegacyMigration\Foundation\Runtime\DeploymentApplicationIsolationAuthorityProbe;
use App\Services\LegacyMigration\Foundation\Runtime\MigrationRuntimeAudit;
use App\Services\LegacyMigration\Foundation\Runtime\MigrationRuntimeFactory;
use App\Services\LegacyMigration\Foundation\Runtime\MissingApplicationIsolationBarrierProvider;
use App\Services\LegacyMigration\Foundation\Runtime\ProtectedMigrationRuntimeAudit;
use App\Services\LegacyMigration\Foundation\Runtime\SideEffectCounter;
use App\Services\LegacyMigration\Foundation\Runtime\SideEffectGuard;
use App\Services\LegacyMigration\Foundation\Security\CanonicalizationVersionRegistry;
use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\HmacTokenService;
use App\Services\LegacyMigration\Foundation\Security\KeyReferenceManifestLoader;
use App\Services\LegacyMigration\Foundation\Security\LegacyMigrationSecurityFactory;
use App\Services\LegacyMigration\Foundation\Security\PinnedProtectedRecordRotationAuthority;
use App\Services\LegacyMigration\Foundation\Security\PinnedProtectedStoreAccessAuthority;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordEnvelopeFactory;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordEnvelopeVerifier;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordRotationAuthority;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessAuthority;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessGuard;
use App\Services\LegacyMigration\Foundation\Security\SecurityConfigurationException;
use App\Services\LegacyMigration\Foundation\Security\UnavailableProtectedRecordRotationAuthority;
use App\Services\LegacyMigration\Foundation\Storage\CompareAndSet;
use App\Services\LegacyMigration\Foundation\Storage\MigrationAuditRepository;
use App\Services\LegacyMigration\Foundation\Storage\NumberReservationRepository;
use App\Services\LegacyMigration\Foundation\Storage\ProtectedRecordSecurityRepository;
use App\Services\LegacyMigration\Foundation\Storage\RecoveryJournalRepository;
use App\Services\LegacyMigration\Foundation\Storage\RecoveryRepository;
use App\Services\ModuleService;
use App\Services\NotificationService;
use App\Services\SidebarMenuBuilder;
use App\Services\WorkspaceRouteResolver;
use App\Support\DatabaseQueryProfiler;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SideEffectCounter::class);
        $this->app->singleton(ApplicationIsolationBarrierProvider::class, MissingApplicationIsolationBarrierProvider::class);
        $this->app->singleton(ApplicationIsolationAuthorityProbe::class, fn ($app) => new DeploymentApplicationIsolationAuthorityProbe(
            $app->make(ApplicationIsolationBarrierProvider::class),
            (array) config('legacy-migration', []),
        ));
        $this->app->singleton(ApplicationIsolationBootVerifier::class, fn ($app) => new ApplicationIsolationBootVerifier(
            $app->make(ApplicationIsolationAuthorityProbe::class),
        ));
        $this->app->singleton(RecoveryJournalAuthority::class, fn () => ConfiguredRecoveryJournalAuthority::fromConfiguration(
            (array) config('legacy-migration', []),
            (string) app()->environment(),
        ));
        $this->app->singleton(HmacTokenService::class, fn () => LegacyMigrationSecurityFactory::make(
            (array) config('legacy-migration', []),
            (string) app()->environment(),
        ));
        $this->app->singleton(CanonicalTypedMessageEncoder::class, function () {
            $version = (string) config('legacy-migration.versions.canonicalization');

            return new CanonicalTypedMessageEncoder(new CanonicalizationVersionRegistry([$version], $version));
        });
        $this->app->singleton(ProtectedStoreAccessAuthority::class, function ($app) {
            /** @var RecoveryJournalAuthority $recovery */
            $recovery = $app->make(RecoveryJournalAuthority::class);
            $captureEnabled = config('legacy-migration.snapshots.authoritative_capture_enabled') === true;
            $requiredDomains = ['idempotency', 'migration_run', 'source_snapshot', 'target_snapshot', 'recovery', 'migration_audit'];
            if ($captureEnabled) {
                $requiredDomains[] = 'target_collision';
            }
            if (config('legacy-migration.allocation.protected_reservations_enabled') === true) {
                $requiredDomains[] = 'number_reservation';
            }
            $references = (array) config('legacy-migration.key_provider.references', []);
            if ($references === []) {
                $manifest = config('legacy-migration.key_provider.reference_manifest');
                $manifestHash = config('legacy-migration.key_provider.reference_manifest_hash');
                if (! is_string($manifest) || ! is_string($manifestHash)) {
                    throw SecurityConfigurationException::forCode('LM-SEC-KEY-REFERENCE-LIST-001');
                }
                $references = (new KeyReferenceManifestLoader)->load($manifest, $manifestHash);
            }
            $matched = array_values(array_filter($references, static function (mixed $reference) use ($requiredDomains, $recovery): bool {
                return is_array($reference)
                    && ($reference['active_for_signing'] ?? false) === true
                    && ($reference['revoked'] ?? true) === false
                    && hash_equals($recovery->environment(), (string) ($reference['environment'] ?? ''))
                    && array_diff($requiredDomains, (array) ($reference['domains'] ?? [])) === [];
            }));
            if (count($matched) !== 1) {
                throw RecoveryException::failClosed('RECOVERY-PROTECTED-KEY-AUTHORITY-MISSING');
            }
            $reference = $matched[0];

            $operationPolicies = ['foundation_recovery' => [
                'operations' => ['read', 'write', 'transition'],
                'authority_references' => [$recovery->authorityReference()],
                'access_classifications' => [$recovery->accessClassification()],
                'retention_classifications' => [$recovery->retentionClassification()],
            ]];
            $rotationAuthority = trim((string) config('legacy-migration.key_provider.rotation_authority_reference'));
            $rotationReasons = (array) config('legacy-migration.key_provider.approved_rotation_reasons', []);
            if ($rotationAuthority !== '' && $rotationReasons !== []) {
                $rotationAccess = trim((string) config('legacy-migration.protected_store.access_classification'));
                $rotationRetention = trim((string) config('legacy-migration.retention.protected'));
                if ($rotationAccess === '' || $rotationRetention === '' || $rotationRetention === 'pending_approved_retention_schedule') {
                    throw SecurityConfigurationException::forCode('LM-SEC-STORE-ROTATION-AUTHORITY-001');
                }
                $operationPolicies['protected_key_rotation'] = [
                    'operations' => ['rotate'],
                    'authority_references' => [$rotationAuthority],
                    'access_classifications' => [$rotationAccess],
                    'retention_classifications' => [$rotationRetention],
                ];
            }
            if ($captureEnabled) {
                $captureAuthority = strtolower(trim((string) config('legacy-migration.snapshots.persistence_authority_reference')));
                $captureAccess = trim((string) config('legacy-migration.protected_store.access_classification'));
                $captureRetention = trim((string) config('legacy-migration.retention.protected'));
                if (preg_match('/\A[a-f0-9]{64}\z/', $captureAuthority) !== 1
                    || $captureAccess === ''
                    || $captureRetention === ''
                    || $captureRetention === 'pending_approved_retention_schedule') {
                    throw SecurityConfigurationException::forCode('LM-SEC-SNAPSHOT-PERSISTENCE-AUTHORITY-001');
                }
                $operationPolicies['authoritative_run_capture'] = [
                    'operations' => ['read', 'write'],
                    'authority_references' => [$captureAuthority],
                    'access_classifications' => [$captureAccess],
                    'retention_classifications' => [$captureRetention],
                ];
            }
            if (config('legacy-migration.runtime_audit.enabled') === true) {
                $runtimeAuditAuthority = trim((string) config('legacy-migration.runtime_audit.authority_reference'));
                $runtimeAuditAccess = trim((string) config('legacy-migration.runtime_audit.access_classification'));
                $runtimeAuditRetention = trim((string) config('legacy-migration.runtime_audit.retention_classification'));
                if ($runtimeAuditAuthority === '' || $runtimeAuditAccess === '' || $runtimeAuditRetention === '') {
                    throw RecoveryException::failClosed('RUNTIME-AUDIT-AUTHORITY-MISSING');
                }
                $operationPolicies['foundation_runtime_audit'] = [
                    'operations' => ['read', 'write'],
                    'authority_references' => [$runtimeAuditAuthority],
                    'access_classifications' => [$runtimeAuditAccess],
                    'retention_classifications' => [$runtimeAuditRetention],
                ];
            }
            if (config('legacy-migration.allocation.protected_reservations_enabled') === true) {
                $allocationAuthority = trim((string) config('legacy-migration.allocation.authority_reference'));
                $allocationAccess = trim((string) config('legacy-migration.allocation.access_classification'));
                $allocationRetention = trim((string) config('legacy-migration.allocation.retention_classification'));
                if ($allocationAuthority === '' || $allocationAccess === '' || $allocationRetention === '') {
                    throw RecoveryException::failClosed('PATIENT-NUM-PROTECTED-AUTHORITY-INCOMPLETE');
                }
                $operationPolicies['foundation_number_reservation'] = [
                    'operations' => ['read', 'write'],
                    'authority_references' => [$allocationAuthority],
                    'access_classifications' => [$allocationAccess],
                    'retention_classifications' => [$allocationRetention],
                ];
            }

            return new PinnedProtectedStoreAccessAuthority(
                $recovery->environment(),
                $requiredDomains,
                (string) ($reference['key_id'] ?? ''),
                (string) ($reference['version'] ?? ''),
                $recovery->canonicalizationVersion(),
                'artifact_integrity',
                $operationPolicies,
                array_values(array_map(static fn (array $candidate): array => [
                    'key_id' => (string) ($candidate['key_id'] ?? ''),
                    'version' => (string) ($candidate['version'] ?? ''),
                ], array_filter($references, static fn (mixed $candidate): bool => is_array($candidate)
                    && ($candidate['revoked'] ?? true) === false
                    && hash_equals($recovery->environment(), (string) ($candidate['environment'] ?? ''))
                    && array_diff($requiredDomains, (array) ($candidate['domains'] ?? [])) === []))),
            );
        });
        $this->app->singleton(ProtectedStoreAccessGuard::class, fn ($app) => new ProtectedStoreAccessGuard(
            $app->make(ProtectedStoreAccessAuthority::class),
            $app->make(HmacTokenService::class),
            $app->make(CanonicalTypedMessageEncoder::class),
        ));
        $this->app->singleton(ProtectedRecordEnvelopeFactory::class, fn ($app) => new ProtectedRecordEnvelopeFactory($app->make(ProtectedStoreAccessGuard::class)));
        $this->app->singleton(ProtectedRecordEnvelopeVerifier::class, fn ($app) => new ProtectedRecordEnvelopeVerifier($app->make(ProtectedStoreAccessGuard::class)));
        $this->app->singleton(ProtectedRecordRotationAuthority::class, function ($app) {
            $authority = trim((string) config('legacy-migration.key_provider.rotation_authority_reference'));
            $reasons = array_values(array_filter((array) config('legacy-migration.key_provider.approved_rotation_reasons', []), static fn (mixed $reason): bool => is_string($reason) && $reason !== ''));
            if ($authority === '' || $reasons === []) {
                return new UnavailableProtectedRecordRotationAuthority;
            }
            $references = (array) config('legacy-migration.key_provider.references', []);
            if ($references === []) {
                $manifest = config('legacy-migration.key_provider.reference_manifest');
                $manifestHash = config('legacy-migration.key_provider.reference_manifest_hash');
                if (! is_string($manifest) || ! is_string($manifestHash)) {
                    return new UnavailableProtectedRecordRotationAuthority;
                }
                $references = (new KeyReferenceManifestLoader)->load($manifest, $manifestHash);
            }
            $active = array_values(array_filter($references, static fn (mixed $reference): bool => is_array($reference)
                && ($reference['active_for_signing'] ?? false) === true
                && ($reference['revoked'] ?? true) === false
                && hash_equals($authority, (string) ($reference['rotation_authority'] ?? ''))));
            if (count($active) !== 1) {
                return new UnavailableProtectedRecordRotationAuthority;
            }

            return new PinnedProtectedRecordRotationAuthority(
                $app->make(HmacTokenService::class),
                $app->make(ProtectedRecordEnvelopeFactory::class),
                $app->make(ProtectedRecordEnvelopeVerifier::class),
                $authority,
                $reasons,
            );
        });
        $this->app->singleton(ProtectedRecordSecurityRepository::class, fn ($app) => new ProtectedRecordSecurityRepository(
            $app->make(ProtectedRecordEnvelopeFactory::class),
            $app->make(ProtectedRecordEnvelopeVerifier::class),
            $app->make(ProtectedRecordRotationAuthority::class),
            trim((string) config('legacy-migration.recovery.connection')) !== ''
                ? (string) config('legacy-migration.recovery.connection')
                : (string) config('database.default'),
        ));
        $this->app->singleton(MigrationAuditRepository::class, fn ($app) => new MigrationAuditRepository($app->make(ProtectedRecordSecurityRepository::class)));
        $this->app->singleton(ReservationCoordinateContextFactory::class, fn ($app) => new RecoveryReservationCoordinateContextFactory($app->make(RecoveryJournalAttributeFactory::class)));
        $this->app->singleton(NumberReservationRepository::class, fn ($app) => new NumberReservationRepository(
            $app->make(ProtectedRecordSecurityRepository::class),
            $app->make(ReservationCoordinateContextFactory::class),
            trim((string) config('legacy-migration.allocation.connection')) !== ''
                ? (string) config('legacy-migration.allocation.connection')
                : (string) config('database.default'),
        ));
        $this->app->singleton(AuthorityBoundReservationFactory::class, fn ($app) => new AuthorityBoundReservationFactory(
            $app->make(HmacTokenService::class),
            $app->make(CanonicalTypedMessageEncoder::class),
            $app->make(ProtectedRecoveryStore::class),
            (array) config('legacy-migration', []),
            (string) app()->environment(),
        ));
        $this->app->singleton(ReservationAttributeFactory::class, fn ($app) => $app->make(AuthorityBoundReservationFactory::class));
        $this->app->singleton(ReservationProtectionFactory::class, fn ($app) => $app->make(AuthorityBoundReservationFactory::class));
        $this->app->bind(NumberReservationStore::class, fn ($app) => new LaravelNumberReservationStore(
            $app->make(ReservationAttributeFactory::class),
            (string) config('legacy-migration.allocation.connection'),
            protectedRepository: $app->make(NumberReservationRepository::class),
            protection: $app->make(ReservationProtectionFactory::class),
        ));
        $this->app->singleton(MigrationRuntimeAudit::class, fn ($app) => new ProtectedMigrationRuntimeAudit(
            $app->make(MigrationAuditRepository::class),
            $app->make(ProtectedRecordSecurityRepository::class),
            $app->make(HmacTokenService::class),
            $app->make(CanonicalTypedMessageEncoder::class),
            (array) config('legacy-migration', []),
            (string) app()->environment(),
            (string) config('legacy-migration.recovery.connection'),
        ));
        $this->app->singleton(RecoveryJournalAttributeFactory::class, fn ($app) => new AuthorityBoundRecoveryJournalAttributeFactory(
            $app->make(RecoveryJournalAuthority::class),
            $app->make(HmacTokenService::class),
            $app->make(CanonicalTypedMessageEncoder::class),
        ));
        $this->app->singleton(RecoveryRepository::class, fn ($app) => new RecoveryRepository(
            new CompareAndSet($app->make(ProtectedRecordSecurityRepository::class)),
            $app->make(ProtectedRecordSecurityRepository::class),
        ));
        $this->app->singleton(RecoveryJournalRepository::class, fn ($app) => new RecoveryJournalRepository($app->make(ProtectedRecordSecurityRepository::class)));
        $this->app->singleton(ProtectedRecoveryStore::class, fn ($app) => new ProtectedRecoveryStore(
            $app->make(RecoveryJournalAttributeFactory::class),
            $app->make(RecoveryRepository::class),
            $app->make(RecoveryJournalRepository::class),
            $app->make(ProtectedRecordSecurityRepository::class),
            (string) config('legacy-migration.recovery.connection'),
        ));
        $this->app->bind(AtomicRecoveryJournal::class, fn ($app) => new LaravelAtomicRecoveryJournal($app->make(ProtectedRecoveryStore::class)));
        $this->app->bind(CompareAndSetStateStore::class, fn ($app) => new LaravelCompareAndSetStateStore($app->make(ProtectedRecoveryStore::class)));
        $this->app->singleton(ApplicationIsolationState::class);
        $this->app->singleton(ApplicationIsolationControlFactory::class);
        $this->app->singleton(ApplicationMigrationRuntime::class, function ($app) {
            $controls = $app->make(ApplicationIsolationControlFactory::class)->make();
            $configuration = (array) config('legacy-migration', []);
            $capability = ApplicationIsolationBootCapability::fromVerifiedObservation(
                $app->make(ApplicationIsolationBootVerifier::class),
                $controls,
                $configuration,
            );

            return (new MigrationRuntimeFactory($capability))->create(
                $configuration,
                $controls,
                $app->make(MigrationRuntimeAudit::class),
                $app->make(SideEffectCounter::class),
            );
        });
        $this->app->singleton(SideEffectGuard::class, fn ($app) => $app->make(ApplicationMigrationRuntime::class)->sideEffects
        );
        $this->app->scoped(DatabaseQueryProfiler::class);
        $this->app->scoped(ConsultationUserRelationLoader::class);
        $this->app->scoped(ModuleService::class);
        $this->app->scoped(NotificationService::class);
        $this->app->scoped(SidebarMenuBuilder::class);
        $this->app->scoped(DepartmentContextSwitcherService::class);
        $this->app->singleton(VerificationManager::class);
        $this->app->singleton(InsuranceVerificationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        $isolationState = $this->app->make(ApplicationIsolationState::class);
        foreach (ApplicationEffectBindingMap::bindings() as $abstract => $subsystem) {
            $isolationState->registerResolutionBinding($abstract, $subsystem);
        }
        $this->app->beforeResolving(static function (string $abstract) use ($isolationState): void {
            $isolationState->assertResolutionAllowed($abstract);
        });

        if ($this->app->environment(['local', 'testing'])) {
            DB::listen(
                fn (QueryExecuted $query) => app(DatabaseQueryProfiler::class)->record($query),
            );
        }

        if (config('performance.lazy_loading.detect') && $this->app->environment(['local', 'testing'])) {
            Model::preventLazyLoading();
            Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
                if (! config('performance.lazy_loading.log')) {
                    return;
                }

                Log::warning('Eloquent lazy loading detected.', [
                    'model' => $model::class,
                    'relation' => $relation,
                    'route' => request()->route()?->getName(),
                    'method' => request()->method(),
                    'path' => request()->path(),
                ]);
            });
        }

        // Rate limiters. `api` backs the default API middleware group; the
        // dedicated `integration-callbacks` limiter throttles inbound provider
        // webhooks (keyed by source IP) so a noisy/abusive caller can't flood us.
        RateLimiter::for('api', fn ($request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('integration-callbacks', fn ($request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('public-payments', fn ($request) => Limit::perMinute(30)->by($request->ip()));

        Paginator::defaultView('vendor.pagination.uhms');
        Paginator::defaultSimpleView('vendor.pagination.uhms-simple');

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Super Admin bypasses all permission checks
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('Super Admin')) {
                return true;
            }

            if (str_starts_with((string) $ability, 'departments.')
                && $ability !== 'departments.manage'
                && method_exists($user, 'hasPermissionTo')
                && $user->getAllPermissions()->contains('name', 'departments.manage')) {
                return true;
            }

            if (str_starts_with((string) $ability, 'sponsors.')
                && $ability !== 'sponsors.manage'
                && method_exists($user, 'hasPermissionTo')
                && $user->getAllPermissions()->contains('name', 'sponsors.manage')) {
                return true;
            }

            return null;
        });

        // Register event listeners for notifications
        Event::listen(LabRequestCreated::class, NotifyLabTechnicians::class);
        Event::listen(LabResultsCompleted::class, NotifyDoctorLabResults::class);
        Event::listen(PrescriptionCreated::class, NotifyPharmacists::class);
        Event::listen(PaymentRecorded::class, NotifyAccountants::class);
        Event::listen(PaymentRecorded::class, SendPaymentReceiptSms::class);
        Event::listen(PatientAdmitted::class, NotifyWardStaffAdmission::class);
        Event::listen(PatientDischarged::class, NotifyAccountantsDischarge::class);
        Event::listen(StockLow::class, NotifyStockManagers::class);

        // Security / auth audit logging
        Event::listen(Login::class, [LogAuthEvents::class, 'handleLogin']);
        Event::listen(Logout::class, [LogAuthEvents::class, 'handleLogout']);
        Event::listen(Failed::class, [LogAuthEvents::class, 'handleFailed']);
        Event::listen(PasswordReset::class, [LogAuthEvents::class, 'handlePasswordReset']);

        // Forward CRITICAL / SECURITY activity rows to optional external sinks.
        Event::listen('eloquent.saved: '.Activity::class, ForwardCriticalActivityListener::class);

        // Module-tagged log observers (extend Spatie LogsActivity with severity / context).
        Invoice::observe(InvoiceObserver::class);
        Payment::observe(PaymentObserver::class);
        InvoiceItem::observe(InvoiceItemObserver::class);
        InvoiceReceivable::observe(InvoiceReceivableObserver::class);
        User::observe(UserObserver::class);
        // Payment Timing Policy Phase 6 — observational visit-payment-policy materialisation.
        Visit::observe(VisitObserver::class);

        // Payment Timing Policy Phase 8 — request-scoped memoisation/de-dup for the
        // operational resolver and cutover diagnostics (bounded gate performance).
        $this->app->scoped(OperationalVisitPaymentTimingResolver::class);
        $this->app->scoped(PaymentTimingCutoverDiagnostics::class);
        $this->app->scoped(PaymentTimingCutoverConfigurationService::class);

        // ---- Module feature-flag Blade directives ----
        // @module('pharmacy') ... @endmodule  → renders only when module enabled
        Blade::if('module', function (string $slug) {
            return app(ModuleService::class)->enabled($slug);
        });

        View::composer('layouts.partials.sidebar', function ($view) {
            /** @var User|null $user */
            $user = Auth::user();
            $moduleService = app(ModuleService::class);

            $unreadNotifications = $user instanceof User
                && $moduleService->enabled('notifications')
                && $user->can('notifications.view')
                    ? app(NotificationService::class)->unreadCount($user)
                    : 0;

            $view->with('sidebarSections', app(SidebarMenuBuilder::class)->build(
                $user,
                request()->route()?->getName() ?? '',
                $unreadNotifications,
            ));
        });

        View::composer('*', function ($view) {
            $routes = app(WorkspaceRouteResolver::class);

            $view->with('workspaceRoutes', $routes);
            $view->with('workspaceContext', $routes->viewContext());
        });
    }
}
