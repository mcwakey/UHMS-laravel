# Application side-effect bindings

## Status

The 25-subsystem registry is now connected to application-level controls and Laravel provider wiring. Runtime resolution remains fail-closed unless external queue-worker, scheduler and null-sink evidence is configured and boot-verified. Normal web/application behavior is unchanged while migration context is inactive.

## Concrete control layers

`AppServiceProvider` registers a lazy `ApplicationMigrationRuntime`, one shared attempt counter and the global container-resolution gate. Runtime construction requires a typed boot capability issued only after `ApplicationIsolationBootVerifier`; the capability re-observes the external barriers at construction and every runtime entry.

The control set contains exactly one `ApplicationBoundSubsystemControl` per `ProhibitedSubsystem`:

- Laravel Event and Eloquent model dispatchers are replaced with a denying dispatcher and restored exactly.
- Notification, Mail, Queue, Bus, Storage and HTTP facade roots are replaced with measured denying sinks and restored exactly.
- Spatie operational activity logging is disabled through `ActivityLogStatus` and restored.
- scheduler, SMS, external audit, payments, eligibility, billing, accounting, allocation, stock, dispensing, bed state, queue/pathway, reminders, journey notifications, webhooks and other integrations are protected by the active circuit breaker and provider-level before-resolving interception. Search indexing has no present mutating application path and retains a mandatory prospective gateway.
- representative real operational services and commands are registered in `ApplicationEffectBindingMap`; all 25 subsystems also have a mandatory migration effect gateway.
- prohibited attempts increment `SideEffectCounter` and abort the migration scope.

The separate migration audit is not a prohibited subsystem and is not redirected to operational activity logging.

## Boot evidence

Runtime construction requires these configuration fields under `isolation.application_bindings`:

- `bindings_verified`
- `queue_workers_paused`
- `scheduler_paused`
- `external_integrations_sink_verified`
- `authority_reference`
- `queue_worker_barrier_reference`
- `scheduler_barrier_reference`
- `null_sink_reference`

Every flag must be true and every reference nonblank. `DeploymentApplicationIsolationAuthorityProbe` consumes those references and requires a bound `ApplicationIsolationBarrierProvider` to observe the external queue-worker barrier, scheduler barrier and null sink directly. The default missing provider fails closed. Defaults remain false/blank, and a process-local callback cannot assert external isolation.

`MigrationRuntimeRequest` no longer accepts caller booleans. It accepts one typed `MigrationRunActivationAuthority`, minted from sealed authoritative source/target manifests and `VerifiedRunPrerequisites`. Its synthetic constructor is restricted to PHPUnit and cannot activate an application runtime outside tests.

## Focused tests

`ApplicationBoundSideEffectIsolationTest` passes 47 cases with 294 assertions:

- exactly 25 concrete controls boot-verify;
- every subsystem gateway is intercepted, measured and restored;
- six framework facade roots are intercepted and restored;
- Eloquent event dispatch and Spatie activity logger resolution are intercepted;
- at least 15 real operational service/command bindings are denied at container resolution;
- normal resolution after scope restoration is unchanged;
- missing external queue/scheduler evidence blocks boot.
- caller booleans without an authoritative external probe block boot.
- the deployment probe consumes pinned config and blocks when no direct barrier provider is bound.
- all concrete controls restore after a successful scope as well as after denied-effect exceptions.
- payment and SMS callback `store`, `process` and `handle` paths deny both direct and pre-resolved invocation before writes;
- all 25 gates remain open outside migration runtime, while nested and web-boundary activation fail closed;
- external barrier revocation is detected by fresh observation at runtime entry;
- lying and non-isolating controls are rejected, and failed isolation restores the prior state;
- a method-level manifest verifies first-statement invocation guards across 127 concrete public operational effect entries, including direct billing, receivables, accounting posting, stock, pharmacy, appointment, notification, callback, queue and bed initiators.
- the same deterministic source-path manifest is mandatory privacy-scan scope, so configuration cannot omit an operational guard, callback, listener or command file.

Test-file SHA-256: `1e5803267a76a40cbae6dbe6a4893947ece26ea6ce28e390e198cbb970977379`.

Operational-guard manifest SHA-256: `5829121e0a5f1a3030724d630afeddc1e1b4a9c39662a53d058c3e75a6b2283f`.

## Safety boundary

No operational service was called as a migration API, and no notification, email, SMS, job, HTTP request, payment, accounting entry, stock movement, dispense action, bed mutation, file write, webhook or operational audit was emitted by the tests.

The application flags default fail-closed. External worker/scheduler pause references remain deployment evidence, not a caller boolean: absent evidence prevents activation. Commit, importers, Cohort B and the patient pilot remain disabled independently of these controls.

## Evidence classification

- **Confirmed:** provider binding, facade/Eloquent/activity controls, service-resolution gates, 25-control completeness and focused restoration tests.
- **External prerequisite:** authoritative queue-worker/scheduler pause and verified null-sink references for the specific non-production exercise environment.
- **Not authorized:** a commit-capable runtime, importer execution or business-domain write.
