# Target patient-child contract

## Installed non-production target

**Confirmed:** a fresh rolled-back read-only inspection of local `uhms_clean` found 100 aggregate patient rows, zero `emergency_contacts`, and zero nonblank dormant inline emergency-contact fields. Relevant migrations are installed. No trigger governs these tables.

| Target | Installed contract | Operational contract |
|---|---|---|
| `patients.occupation` | nullable varchar(191) | nullable string, max 100; configured labels are not enforced catalogue rows |
| `patients.address` | nullable text | nullable string, max 500 |
| `patients.city`, `town`, `region` | nullable varchar(191) | max 100; no Phase 2D source |
| `patients.digital_address` | nullable varchar(191) | max 30 plus Ghana regex; no Phase 2D source |
| `patients.postal_code` | **not installed** | no destination |
| `patients.religion` | nullable varchar(191) | nullable string, max 100; no enum/check |
| `patients.marital_status` | nullable varchar(191) | model/request enum: `single`, `married`, `divorced`, `widowed`; no DB check |
| `emergency_contacts.patient_id` | unsigned bigint, NOT NULL, FK cascade, indexed | committed patient required |
| contact `name`, `phone` | varchar(191), NOT NULL | registration max 100/max 20 and configured phone regex |
| contact `phone_secondary`, `relationship` | nullable varchar(191) | registration max 30/max 50; standalone controller uses secondary max 20 |
| contact `is_primary` | NOT NULL default false | service-only behavior; multiple primary rows possible |

The target has no contact type, uniqueness/idempotency key, source identity, audit actor, soft delete, relationship enum, or database one-primary rule. Dormant `patients.emergency_contact_*` fields are not fillable or displayed and remain unused.

## Runtime hazards

Normal patient/contact services are not migration entry points. They can generate current attribution/timestamps, activity, partial patient-without-child creation, bulk primary-flag mutation and hard deletes. Search uses contact name/phone; merge can move contacts and fabricate phone aliases; archive can duplicate patient payload. Route binding in the contact controller does not independently verify that the contact belongs to the route patient. Schedulers, activity, archive, queues and integrations require isolation.

## Technical contract

The future boundary must enforce the stricter versioned migration validation, protected idempotency, FK, enum and length rules, null timestamp absence provenance, atomic one-primary validation and zero side effects. It may create one complete valid contact only for a newly created patient after parent release. Explicitly linked existing targets receive no demographic or contact enrichment.

