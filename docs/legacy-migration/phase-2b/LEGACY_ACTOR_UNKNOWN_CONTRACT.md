# Legacy Actor Unknown contract

## Approved purpose

Exactly one stable identity labelled `Legacy Actor Unknown` may represent attribution only when no valid actor can be determined and the specific target field permits it. It is never a normal application fallback.

## Mandatory state

- disabled and non-login;
- no usable password, reset, invitation or external identity;
- no roles, direct/effective permissions, department access, specialty assignment or clinical privilege;
- no sessions, email/SMS/push routing or notifications;
- migration-only attribution eligibility;
- every use carries a relationship ID, reason code and protected original Classic actor reference;
- all uses are counted and manually reviewable.

Its future target representation shares the Phase 3 prerequisite in [TARGET_STAFF_IDENTITY_CONTRACT.md](TARGET_STAFF_IDENTITY_CONTRACT.md).

## Permitted/prohibited use

Permission is field-specific in [downstream_actor_dependencies.json](specifications/downstream_actor_dependencies.json). Of the currently enumerated target fields, only historical `visits.created_by` is `PERMITTED`, and only under D-202 with `LEGACY-STAFF-ACTOR-032`, protected original evidence and manual review. Every other enumerated field is `PROHIBITED`; nullable fields use null/provenance-only when no actor is verified.

It remains prohibited for evidence-required appointment creators; consultation/medical-record clinicians; clinical performers/requesters; admission creators; financial/payment/claim creators; operational stock/procurement actors; migration execution/audit actors; or any field whose domain contract says verified actor mandatory.

Normal services must never select it automatically. Current user, first user, administrator, importer, system operator, guessed user and department supervisor are also prohibited fallbacks.
