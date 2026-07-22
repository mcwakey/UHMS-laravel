# Side-effect isolation design

Migration context is explicit, non-production and command-scoped. It cannot be activated by a normal web request. The registry and factory refuse commit unless every required subsystem has a real control, and Phase 3 also unconditionally refuses commit.

The deny-list covers events, observers, Spatie activity, notifications, mail, SMS, queues/jobs, scheduled commands, external audit forwarding, payments, eligibility, billing, accounting, allocations, stock, dispensing, beds, queue/pathway, reminders, journey notifications, file/avatar writes, indexing, webhooks and other integrations.

Phase 3B binds exactly 25 observable application controls and boot-verifies the complete set together with independently observed queue-worker, scheduler and integration-sink barriers. Framework facade sinks, Eloquent event isolation, operational activity suppression, container-resolution circuit breakers and permanent invocation-time gates cover both normally resolved and pre-resolved/directly created operational services. Focused tests prove denial, counting, exhaustive restoration and normal-runtime restoration.

Migration runtime activation and restoration write keyed semantic events through the protected migration-audit repository. Operational activity remains denied while protected foundation audit/envelope writes remain available. `Model::withoutEvents()` alone remains insufficient, and no importer is authorized by these controls.
