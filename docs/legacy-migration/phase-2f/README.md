# Phase 2F — patient pilot mapping contract

Status: **PASSED INDEPENDENT REVIEW — specification only. No pilot execution or persistence is authorized.**

Phase 2F composes the approved Phase 2A–2E contracts into one bounded patient-pilot design. It does not reopen upstream mappings or business policy. The normative JSON under `specifications/` owns the executable-shape contract; the Markdown documents explain it; specialist drafts are evidence handoffs only.

The pilot has three inputs: obviously fictitious synthetic contract scenarios, a deterministic protected `uuhms` dry-run cohort, and a read-only existing-target collision cohort. Repository outputs are aggregate-only and contain no patient, OPD, contact, insurance, source-key, target-ID, remediation, row-date or row-token values.

Phase 2F creates no importer, persistence layer, migration table, schema change, allocator, UI, queue job, synchronisation runtime, seeder, production test or database write. Commit-mode patient creation remains blocked by the Phase 3 foundation and all mandatory state/remediation prerequisites.

The final independent review reports **PASS** with Critical 0, High 0, Medium 0 and Low 0. Focused validation passes 15 Phase 2F tests with 18,449 assertions; the retained Phase 1/2 evidence and Phase 2D–2F focused batch passes 45 tests with 19,922 assertions. Phase 3 foundation work may begin under the exit report restrictions, but Cohort B selection, any pilot run and all importer work remain unauthorized.

Evidence labels remain distinct:

- **Confirmed evidence:** installed schema, repository behavior, or sanitized aggregate evidence from Phases 1–2.
- **Approved policy:** `APPROVED_DECISION_SPECIFICATIONS.md` and accepted `DECISIONS.md` entries.
- **Phase 2F technical specification:** deterministic composition for later implementation.
- **Recommendation/blocker:** not source evidence and not implementation authority.
