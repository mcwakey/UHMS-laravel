# Phase 2E — patient insurance and eligibility

Status: **PASS; independently reviewed; cleared for Phase 2F specification work only; no persistence is authorized.**

Phase 2E classifies the 31,307 rows and all nine columns in Classic `uuhms.insurance`, plus `patients.Company` and `patients.BillStatus`. It consumes Phase 2A provider crosswalks, Phase 2C patient/quarantine contracts and Phase 2D sequencing/privacy contracts unchanged.

Evidence labels: **Confirmed** is repository, installed non-production target or sanitized Classic aggregate evidence; **Inference** is non-authoritative interpretation; **Approved policy** is D-205/D-206/D-207/D-212 and Q-004/Q-005/Q-009/Q-301; **Phase 2 technical specification** is deterministic design for later review.

The 19 JSON files under `specifications/` are normative machine-readable contracts. Four hashed Phase 2E evidence manifests under `../evidence/` capture the Classic and installed non-production target inspections. Drafts are specialist handoffs; consolidated documents and JSON prevail.

No importer, migration-state table, UI, synchronization runtime, seeder, schema change or database write is created. Claims, billing, payments, accounting, eligibility APIs and operational verification remain out of scope.
