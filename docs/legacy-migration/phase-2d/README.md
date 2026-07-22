# Phase 2D — patient aliases, contacts and demographic children

Status: **PASS after independent review; no persistence is authorized**.

This package maps exactly seven Classic `uuhms.patients` columns: `Work`, `Address`, `NOK`, `NOKPhoneNo`, `NOKRel`, `Religion`, and `MaritalStatus`. It consumes Phase 2C patient mapping, OPD alias, existing-target, quarantine-chain, extraction and privacy contracts without changing them.

Evidence labels used throughout:

- **Confirmed** — captured repository, installed non-production target, or sanitized aggregate Classic evidence.
- **Inference** — a source meaning suggested by naming/shape, never identity or persistence authority.
- **Approved policy** — `APPROVED_DECISION_SPECIFICATIONS.md` and `DECISIONS.md`.
- **Phase 2 technical specification** — deterministic mapping design for later implementation review; not importer authority.

## Package map

The source and target evidence are in `CLASSIC_PATIENT_CHILD_SOURCE_INVENTORY.md` and `TARGET_PATIENT_CHILD_CONTRACT.md`. Domain mappings cover alias integration, the NOK tuple/emergency contact, occupation, address, religion and marital status. Relationship, sentinel, exception, reconciliation, extraction, privacy and readiness documents define the common execution contract. Sixteen parseable specifications under `specifications/` are normative machine inputs.

Drafts under `drafts/` are specialist evidence handoffs. Consolidated documents and JSON take precedence where they differ.

## Non-authorization

Phase 2D creates no importer, patient/alias/contact row, migration table, schema change, state ledger, UI, queue job, synchronization runtime, seeder, cutover logic or database write. Future persistence remains blocked on Phase 3 migration foundation and reviewed patient-pilot contracts.
