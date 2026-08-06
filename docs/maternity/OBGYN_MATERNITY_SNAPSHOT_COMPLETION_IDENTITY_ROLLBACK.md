# O&G / Maternity — Snapshot Completion-Occurrence Identity (Rollback)

**Phase:** 14R.8 · **Closes:** risk **P2**

This document covers how to back Phase 14R.8 out, what is safely reversible, and what is
**deliberately not**.

---

## 1. Risk profile of a rollback

Phase 14R.8 is unusually safe to roll back, for one structural reason: **it changed no clinical
data and no hash.**

* Snapshot payloads: unchanged.
* Snapshot hashes: byte-identical, still verifying.
* Snapshot versions: unchanged.
* Existing completion timestamps: unchanged.
* Existing audit history: unchanged.
* Feature flags: unchanged (all still off).
* Billing: unchanged (still non-posting).

The phase adds one table, one nullable column, and rebinds an idempotency key. That is the entire
blast radius.

---

## 2. What rolling back costs you

Rolling back **re-opens risk P2**. A reopen-and-recompletion inside the same second will once again
collapse into one snapshot and the second completion's clinical state will be lost.

That is a medico-legal record-completeness regression. Do not roll back merely to reduce diff size;
roll back only if the ledger itself is causing a concrete failure.

---

## 3. Reversible: code

The service changes are ordinary and revert cleanly.

| File | Revert effect |
| --- | --- |
| `app/Services/ConsultationRouteService.php` | Stops minting occurrences; `captureMaternitySnapshot()` returns to taking its own row lock. |
| `app/Services/Consultation/Maternity/ConsultationMaternitySnapshotService.php` | `completionReference()` falls back to `legacyCompletionReference()` for **every** call — which is exactly the pre-14R.8 behaviour, byte for byte. |
| `app/Models/ConsultationMaternitySnapshot.php` | Drop `completion_occurrence_id` from `$fillable`, the relation and `usesLegacyCompletionReference()`. |
| `app/Console/Commands/ObgynMaternityPilotPreflightCommand.php` | Remove `checkCompletionIdentity()`; P2 returns to the open-risk list. |

**The fallback is the rollback.** Because `completionReference()` already degrades to the legacy
format when no occurrence is supplied, code that stops supplying occurrences immediately resumes
producing legacy references — no compatibility shim required.

### Partial rollback (recommended over full)

If the ledger is suspected but you do not want to lose the audit trail, stop **consuming** the
occurrence without dropping it:

```php
// ConsultationRouteService::completeRoute()
$occurrence = $this->recordCompletionOccurrence($route, $user, $from);
$this->captureMaternitySnapshot($route, $user, null); // ← identity falls back to legacy
```

Occurrences keep being recorded (useful for diagnosis); snapshot identity reverts. P2 returns, but
you retain the evidence needed to understand why you rolled back.

---

## 4. Reversible: schema

Both migrations declare `down()`:

| Migration | `down()` |
| --- | --- |
| `2026_07_28_000002_link_maternity_snapshots_to_completion_occurrences` | Drops the FK **by its explicit name** (`cms_occurrence_fk`) and then the column. |
| `2026_07_28_000001_create_consultation_completion_occurrences_table` | Drops the table. |

```bash
php artisan migrate:rollback --step=2   # MySQL / MariaDB only — see below
```

**Order matters** — the snapshot FK must go before the table it references. Running the two
rollbacks in the reverse order will fail on the foreign key.

### Driver limitation — SQLite cannot reverse migration 000002

**Corrected after independent review.** Two defects were found and fixed in the original `down()`:

1. It called `dropConstrainedForeignId()`, which emits Laravel's *conventional* FK name
   (`consultation_maternity_snapshots_completion_occurrence_id_foreign` — **65 characters**). That
   constraint was never created: `up()` names it `cms_occurrence_fk` precisely because the
   conventional name exceeds MariaDB's 64-character identifier limit. The rollback would have failed
   on MySQL/MariaDB with errno 1091.
2. SQLite cannot perform this rollback **at all**. It refuses to drop a column that still appears in
   a foreign-key definition, it cannot drop a constraint by name, and no `PRAGMA`
   (`foreign_keys`, `legacy_alter_table`) changes either fact — verified empirically on SQLite
   3.49.1.

`down()` now drops the FK by its real name on MySQL/MariaDB, and on SQLite **throws an explicit,
actionable exception** rather than failing with a confusing schema error or half-applying.

Rewriting `sqlite_master` to strip the constraint was considered and rejected: regex surgery on DDL
has no place in a migration that touches an append-only clinical table, where a partially-applied
rebuild would be far worse than an unreversed column.

**Practical impact: none.** Tests never roll back (`RefreshDatabase` migrates from scratch), and
production runs MySQL/MariaDB. On SQLite, use `php artisan migrate:fresh`.

### What a schema rollback destroys

Dropping `consultation_completion_occurrences` **permanently destroys the completion-occurrence
audit trail**. There is no backup copy of it elsewhere: it is the only record that a given
completion was the second (or third) genuine completion of that consultation.

Dropping `completion_occurrence_id` severs the lineage link on snapshots captured during 14R.8.
Those snapshots survive intact — payload, hash, version, `captured_at` all unchanged — but their
`completion_reference` values (`occ:{ULID}`) become **orphaned strings** that no longer resolve to
anything.

They remain readable, verifiable and correctly ordered. `isLegacyReference()` will classify them as
non-legacy while their occurrence no longer exists — cosmetically odd, clinically harmless. If that
matters operationally, treat it as a display concern, **not** a reason to rewrite the rows.

**Do not** rewrite `occ:` references back into `route:{id}@{timestamp}` form as part of a rollback.
That would mutate immutable clinical records and manufacture an identity retroactively — the exact
thing this phase refused to do to historical rows. It is also blocked at the model level and would
require raw SQL to attempt.

---

## 5. Not reversible, by design

**Append-only structures.** Neither table has `updated_at` or soft deletes, and both are guarded at
the model level. Individual occurrence or snapshot rows cannot be edited or removed through the
application under any circumstances, including rollback. Removal is all-or-nothing at the table
level, via migration.

This is intentional: a medico-legal record whose rows can be selectively deleted during an incident
is not a medico-legal record.

---

## 6. Data safety checklist before rolling back

1. Count what you are about to lose:

   ```sql
   SELECT COUNT(*) FROM consultation_completion_occurrences;
   SELECT COUNT(*) FROM consultation_maternity_snapshots WHERE completion_occurrence_id IS NOT NULL;
   ```

2. If either count is non-zero on a **production** database, export both tables before rolling
   back. The occurrence ledger cannot be reconstructed afterwards.

3. Confirm hashes still verify **before** and **after** the rollback (they should be identical —
   the hash never included the occurrence):

   ```bash
   php artisan maternity:obgyn-pilot-preflight
   ```

   Compare the `snapshots → hash sample` row across both runs.

4. After rollback, expect the preflight to report the completion-identity area as `BLOCKED`
   ("risk P2 is NOT closed"). That is correct reporting, not a new fault.

---

## 7. Rollback verification

```bash
php artisan migrate:rollback --step=2   # MySQL/MariaDB; on SQLite use migrate:fresh
php artisan test tests/Feature/ConsultationMaternitySummarySnapshotPhase14R6Test.php
php artisan test tests/Feature/ConsultationMaternitySnapshotHistoryUiPhase14R6_1Test.php
```

The 14R.6 / 14R.6.1 suites must still pass — they were written before the ledger existed and do not
depend on it.

The 14R.8 suite (`ConsultationSnapshotCompletionIdentityPhase14R8Test`) and the inverted 14R.7 guard
**will fail after a rollback**, correctly: they assert P2 is closed. Delete or skip them as part of
the revert; do not weaken their assertions to make them pass, which would leave a suite that claims
P2 is closed while it is open.

---

## 8. Forward-fix preference

For most failure modes, forward-fixing beats rolling back:

| Symptom | Forward fix |
| --- | --- |
| Occurrence insert failing | Investigate the unique index; the service already re-reads and verifies lineage on conflict. |
| Suspected duplicate versions | Check `cco_route_number_unique` is installed — the preflight reports this directly. |
| Legacy rows displaying oddly | A presentation change; never a data change. |
| Performance concern on completion | The ledger costs 1 read + 1 insert (measured). Look elsewhere first. |

Rolling back is the right call only if the ledger is causing completion failures that cannot be
diagnosed in place — and in that case, prefer the **partial rollback** in §3.
