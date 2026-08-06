# O&G / Maternity — Snapshot Completion-Occurrence Identity (Test Matrix)

**Phase:** 14R.8 · **Closes:** risk **P2**
**Primary suite:** `tests/Feature/ConsultationSnapshotCompletionIdentityPhase14R8Test.php`
**Regression guard:** `tests/Feature/ConsultationSnapshotSameSecondRiskPhase14R7Test.php`

Result: **29 tests, 130 assertions, all passing** — covering the 41 checks required by the phase
specification, plus one added after independent review (double-submit, §C). Checks are grouped into tests where they share a setup; the mapping below is exact.

Test method names carry the spec check numbers in their docblocks so this document and the suite
cannot drift apart.

---

## A. Same-second completion identity (checks 1–6)

All three completions run under a **frozen clock** (`2026-07-26 10:00:00`). Every occurrence has an
identical `completed_at`, which is what makes this a real proof rather than a timing coincidence.

| # | Check | Test | Result |
| --- | --- | --- | --- |
| 1 | Complete once → v1 | `test_three_same_second_completion_cycles_create_three_distinct_versions` | PASS |
| 2 | Reopen + recomplete, same second → v2 | ″ | PASS |
| 3 | Reopen + recomplete again, same second → v3 | ″ | PASS |
| 4 | All versions have distinct occurrence identities | ″ (+ `test_occurrence_lineage_is_chained_and_uses_generated_identifiers`) | PASS |
| 5 | v1 byte-identical after v2 and v3 | ″ | PASS |
| 6 | Every hash verifies | ″ | PASS |

Additional assertions in the same test: `completed_at` is a single distinct value across all three
occurrences; occurrence numbers are `[1,2,3]`; each version holds its own clinical state
(`gravida` `[2, 6, 7]`).

The lineage test additionally asserts the identity is a **ULID** (regex-matched), that the chain
links `previous_occurrence_id` correctly, and that the recorded transition (`ACTIVE → COMPLETED`)
is truthful.

---

## B. Intended idempotency (checks 7–11)

Distinguishing *intended* idempotency from the P2 collapse was the point of this group: the fix
must not turn a retry into a new version.

| # | Check | Test | Result |
| --- | --- | --- | --- |
| 7 | Repeat completion, no reopen → 1 occurrence, 1 snapshot | `test_repeat_completion_without_reopen_creates_one_occurrence_and_one_snapshot` | PASS |
| 8 | Retry same completion request → no duplicate (5 replays) | `test_retrying_the_same_completion_request_creates_no_duplicate_snapshot` | PASS |
| 9 | Capture twice for one occurrence → 1 snapshot | `test_capturing_twice_for_the_same_occurrence_returns_the_same_snapshot` | PASS |
| 10 | Duplicate listener/event delivery (10×) → 1 snapshot | `test_duplicate_capture_delivery_for_one_occurrence_creates_one_snapshot` | PASS |
| 11 | One-second-separated reopen/recompletion → v2 | `test_a_one_second_separated_reopen_and_recompletion_still_creates_v2` | PASS |

---

## C. Concurrency (checks 12–15)

| # | Check | Test | Result |
| --- | --- | --- | --- |
| 12 | Racing insert for the same occurrence cannot create a second snapshot | `test_a_racing_insert_for_the_same_occurrence_cannot_create_a_second_snapshot` | PASS |
| 12a | **Double-submitted completion creates only one occurrence** (added after review) | `test_a_double_submitted_completion_creates_only_one_occurrence` | PASS |
| 13 | Concurrent capture calls resolve to one snapshot | `test_concurrent_capture_calls_resolve_to_a_single_snapshot` | PASS |
| 14 | Version numbers unique and monotonic (5 cycles, one second) | `test_version_numbers_remain_unique_and_monotonic` | PASS |
| 15 | Unique conflicts resolve only through verified lineage | `test_a_unique_conflict_resolves_only_when_the_lineage_belongs_to_this_route` | PASS |

**Stated limitation.** True OS-thread concurrency is not available in this suite, and no browser or
process-level harness was introduced (out of scope). Check 12 therefore tests the guarantee **where
it actually lives** — the database unique index — by forcing the exact insert a racing request
would attempt and asserting the database rejects it and the row count stays at 1. This is the last
line of defence, which is precisely the one that must hold when the application-level row lock is
bypassed. Checks 13–15 exercise the service-level path with independently resolved service
instances.

This is honest coverage of the mechanism, not a simulation of parallelism. Multi-process
verification under load remains a pilot-time observation, not a closed test.

**Check 12a was added after independent review, and it found a real regression.** Two
request-scoped bindings of the same route, both loaded while `ACTIVE`, produced **2 occurrences and
2 snapshot versions with byte-identical clinical content** — a ledger row asserting a completion
that never happened. The old timestamp identity had accidentally masked this by collapsing both
writes. The status guard now re-checks under the row lock *inside* the transaction. The test was
written first, observed failing (`2 !== 1`), then fixed.

---

## D. Failure and retry (checks 16–20)

| # | Check | Test | Result |
| --- | --- | --- | --- |
| 16 | Failure inside the transaction → no false completion success | `test_a_failure_inside_the_completion_transaction_rolls_back_everything` | PASS |
| 17 | Occurrence without snapshot can resume, creating it exactly once | `test_a_completion_whose_snapshot_is_missing_can_resume_against_the_same_occurrence` | PASS |
| 18 | Retry after committed snapshot resolves the existing row | `test_retry_after_a_committed_snapshot_resolves_the_existing_row` | PASS |
| 19 | Hash mismatch is detected, never falsely accepted | `test_a_hash_mismatch_is_detected_rather_than_falsely_accepted` | PASS |
| 20 | A later reopen creates a new occurrence after recovery | `test_a_reopen_after_a_recovered_completion_creates_a_new_occurrence` | PASS |

Check 16 asserts the route is **not** left `COMPLETED`, and that the occurrence and snapshot tables
are both empty — the rollback is total.

Check 17 resumes against **the same** occurrence (asserted by id and by reference), proving recovery
does not fabricate a new one.

Check 19 tampers with the stored hash through a raw query, bypassing the model guards entirely, and
asserts verification fails closed.

---

## E. Legacy compatibility (checks 21–25)

| # | Check | Test | Result |
| --- | --- | --- | --- |
| 21 | Legacy-reference snapshot remains readable | `test_a_legacy_reference_snapshot_remains_readable_verifiable_and_ordered` | PASS |
| 22 | Legacy hash unchanged and still verifies | ″ | PASS |
| 23 | History still orders correctly across mixed references | ″ | PASS |
| 24 | No backfilled identity presented as historical fact | `test_legacy_rows_are_not_backfilled_with_a_fabricated_occurrence` | PASS |
| 25 | Append-only guards still reject update/delete | `test_snapshot_and_occurrence_mutation_guards_still_reject_update_and_delete` | PASS |

Checks 21–23 construct a genuine pre-14R.8 row (legacy reference, `NULL` occurrence), then complete
a *new* occurrence on top of it and assert the mixed history orders `[2, 1]`, that both rows verify,
and that each is correctly classified as legacy / occurrence-based.

Check 24 asserts the historical row still has `completion_occurrence_id = NULL` and its original
reference **after** a subsequent completion — nothing reaches back and invents history.

Check 25 covers **both** tables (snapshot and ledger) for `update()` and `delete()`, and asserts
neither declares `UPDATED_AT`.

---

## F. Workflow protection (checks 26–38)

| # | Check | Test | Result |
| --- | --- | --- | --- |
| 26 | Preview shows the correct completed version | `test_preview_summary_and_print_all_select_the_latest_version` | PASS |
| 27 | Preview contains no scripts / forms / raw JSON | ″ | PASS |
| 28 | Summary and print select the same version | ″ | PASS |
| 29 | Current live record stays lazy and separate | `test_the_current_live_record_remains_separate_from_the_snapshot` | PASS |
| 30 | Permission denial omits snapshot context | `test_permission_denial_omits_the_maternity_snapshot_context` | PASS |
| 31 | Flag-off behaviour unchanged | `test_flag_off_behaviour_remains_unchanged` | PASS |
| 32 | Reopen permissions unchanged | `test_reopen_and_completion_readiness_behaviour_are_unchanged` | PASS |
| 33 | Completion readiness unchanged (still advisory) | ″ | PASS |
| 34 | No invoice item created | `test_completion_creates_no_billing_orderset_or_flag_side_effects` | PASS |
| 35 | No maternity billing event created | ″ | PASS |
| 36 | No order-set write | ″ | PASS |
| 37 | No environment reconciliation write | ″ | PASS |
| 38 | No feature flag changed | ″ | PASS |

Check 28 asserts screen and print view models carry an **identical** `snapshotPayload` — one
renderer, not two.

Check 29 mutates live data after the snapshot is sealed and asserts the snapshot still reads the
captured value while the separately-requested current record reads the live one.

Check 31 records the important nuance: with the maternity snapshot flag **off**, no snapshot is
captured, but the occurrence ledger still records the completion. The ledger is consultation
lifecycle infrastructure, not a maternity feature.

Check 33 also asserts a `CANCELLED` route produces **no** occurrence — the early return is intact.

---

## G. Performance (checks 39–41)

| # | Check | Test | Result |
| --- | --- | --- | --- |
| 39 | Snapshot history adds no N+1 | `test_snapshot_history_does_not_add_an_n_plus_one_query` | PASS — ≤2 queries for 4 versions |
| 40 | Preview builds no second projection | `test_preview_does_not_build_a_second_projection` | PASS — second build adds 0 queries |
| 41 | Feature-off query counts hold | `test_feature_off_completion_adds_no_maternity_queries` | PASS — 0 maternity queries |

Check 41 additionally bounds the new ledger cost at **≤2 queries** (one read, one insert) on the
completion path.

---

## H. Regression guard (inverted 14R.7 suite)

`ConsultationSnapshotSameSecondRiskPhase14R7Test` was written in 14R.7 to **prove the defect
existed**. With P2 closed, its assertions were **inverted** — from "documents the collapse" to
"proves the collapse cannot happen".

This is recorded explicitly because inverting an existing test can look like weakening one. It is
not: the file now asserts strictly more than before (5 tests / 17 assertions, up from 5 / 11), and
the original defect is documented in its class docblock so the history is not lost.

| Test | Was | Now |
| --- | --- | --- |
| `test_same_second_reopen_and_recompletion_creates_a_second_version` | asserted **1** snapshot (collapse) | asserts **2**, with both clinical states preserved |
| `test_the_completion_reference_is_no_longer_derived_from_the_clock` | asserted the `route:{id}@{ts}` shape | asserts `occ:` prefix, non-null occurrence, **and** that the legacy format is still produced by `legacyCompletionReference()` |
| `test_recompletion_never_corrupts_or_rewrites_the_first_snapshot` | v1 intact | unchanged — still passing |
| `test_repeated_completion_without_a_reopen_is_still_correctly_idempotent` | 1 snapshot | unchanged, **plus** 1 occurrence |
| `test_one_second_apart_produces_two_distinct_versions` | 2 versions | unchanged — still passing |

---

## I. Focused regression run

| Suite group | Tests | Result |
| --- | --- | --- |
| P2 closure + snapshot/summary/history/print/readiness/billing-dedup/reconciliation | 141 | PASS |
| Gynaecology, obstetrics, handoffs (14R.3–14R.5.1), admission & emergency integration | 171 (1 skipped) | PASS |
| Consultation route/session, follow-up/next-patient, workspace, preview parity, bridge, order sets, routing | 319 passed / **31 pre-existing failures** | see report §11 — both failure sets A/B-proven pre-existing |

---

## J. Not covered — stated plainly

* **Multi-process / real-thread concurrency** — see §C limitation.
* **The unique-violation catch branch** in `ConsultationCompletionOccurrenceService::record()` is
  not directly exercised: provoking a genuine race inside one process is not possible here. Its
  logic was hardened after review (the winner must now be **strictly newer**, not merely
  route-scoped, which was tautological) but that hardening is reviewed, not tested. Stated rather
  than counted as covered.
* **Browser E2E** — no browser harness exists; out of scope by spec.
* **Wide suite** — still OOMs at the project's intentional 512 MB limit around 60%, a pre-existing
  infrastructure issue reproduced with these changes stashed. Not claimed as green.
* **Clinician acceptance** — `NOT_ASSESSED`. No clinician has evaluated this.
