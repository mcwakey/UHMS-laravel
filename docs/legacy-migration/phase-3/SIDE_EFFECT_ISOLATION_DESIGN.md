# Side-effect isolation design

Migration context is explicit, non-production and command-scoped. It cannot be activated by a normal web request. The registry and factory refuse commit unless every required subsystem has a real control, and Phase 3 also unconditionally refuses commit.

The deny-list covers events, observers, Spatie activity, notifications, mail, SMS, queues/jobs, scheduled commands, external audit forwarding, payments, eligibility, billing, accounting, allocations, stock, dispensing, beds, queue/pathway, reminders, journey notifications, file/avatar writes, indexing, webhooks and other integrations.

The current concrete test controls prove denial, exhaustive restoration attempts and fail-closed factory behavior. No real application `SubsystemIsolationControl` implementations are yet bound to the 25 subsystems or their call sites. Therefore operational isolation is **not complete**, and no commit-capable context or Phase 4A exercise is authorized. `Model::withoutEvents()` alone is insufficient.
