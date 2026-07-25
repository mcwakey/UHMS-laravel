# Phase 14R.2 — Consultation ↔ Maternity Bridge (Implementation Report)

**Status:** ✅ Implemented. Additive only — **no workspace, UI, readiness, summary or billing behaviour was changed.**
**Companions:** `OBGYN_MATERNITY_RECONCILIATION_GAP_ANALYSIS.md`, `OBGYN_MATERNITY_SOURCE_OF_TRUTH_MATRIX.md`, `OBGYN_MATERNITY_INTEGRATION_PLAN.md`

---

## 1. What was implemented

The foundation that lets a consultation encounter point at a longitudinal maternity record **without duplicating a single clinical field**:

- Two closed enums (context type, link role).
- The `consultation_maternity_links` table with an active-link uniqueness strategy that works on MySQL/MariaDB.
- An Eloquent model with scopes/helpers, plus `maternityLinks` / `activeMaternityLinks` on the encounter model.
- A typed context DTO.
- A resolver implementing the approved precedence chain.
- A link service that is the **only** supported write path (link / relink / unlink).
- Activity logging, additive permissions, EN/FR localisation.
- A 22-test feature suite.

## 2. Pre-migration audit (Step 1) — confirmed

| Question | Finding |
|---|---|
| Consultation encounter key | **`visit_consultation_routes.id`** |
| `ConsultationSpecialtyEntry.consultation_id` FK | → `visit_consultation_routes` (`css_entries_consultation_fk`), confirmed in migration `2026_07_05_000002` |
| Encounter model | **`App\Models\VisitConsultationRoute`** |
| Patient context | `VisitConsultationRoute::patient()` / `patient_id` |
| Visit context | `VisitConsultationRoute::visit()` / `visit_id` |
| Admission context | via `visit->admission` (`Visit::admission()` — `hasOne`); the route has no direct admission FK |

**No second consultation-session identity was created.**

Patient-key quirk found and handled: `NewbornRecord` and `PostnatalCase` use **`mother_patient_id`**; `MaternityCase`, `AntenatalVisit`, `LaborEpisode`, `DeliveryRecord` use `patient_id`.

## 3. Files changed

**New**
```
app/Enums/ConsultationMaternityContextType.php
app/Enums/ConsultationMaternityLinkRole.php
app/Models/ConsultationMaternityLink.php
app/Data/Consultation/Maternity/ConsultationMaternityContext.php
app/Services/Consultation/Maternity/ConsultationMaternityContextResolver.php
app/Services/Consultation/Maternity/ConsultationMaternityLinkService.php
app/Services/Consultation/Maternity/ConsultationMaternityLinkException.php
database/migrations/2026_07_25_000001_create_consultation_maternity_links_table.php
database/migrations/2026_07_25_000002_seed_consultation_maternity_context_permissions.php
lang/en/consultation_maternity.php
lang/fr/consultation_maternity.php
tests/Feature/ConsultationMaternityBridgePhase14R2Test.php
docs/maternity/OBGYN_MATERNITY_BRIDGE_PHASE_14R_2_REPORT.md
```

**Modified (additive only)**
```
app/Models/VisitConsultationRoute.php   — added maternityLinks() + activeMaternityLinks()
database/seeders/RoleSeeder.php         — added 3 bridge permissions to the master list
docs/maternity/OBGYN_MATERNITY_RECONCILIATION_GAP_ANALYSIS.md   — recorded approved decisions
docs/maternity/OBGYN_MATERNITY_SOURCE_OF_TRUTH_MATRIX.md        — recorded approved decisions
docs/maternity/OBGYN_MATERNITY_INTEGRATION_PLAN.md              — marked 14R.2 delivered
```

## 4. Table design

`consultation_maternity_links`

| Column | Type | Notes |
|---|---|---|
| `consultation_route_id` | FK → `visit_consultation_routes`, cascade delete | encounter key |
| `pregnancy_profile_id` | FK nullable, null-on-delete | longitudinal root, **always derived** |
| `maternity_case_id`, `antenatal_visit_id`, `labor_episode_id`, `delivery_record_id`, `newborn_record_id`, `postnatal_case_id` | FK nullable, null-on-delete | explicit targets |
| `context_type` | string(32) | cast to `ConsultationMaternityContextType` |
| `link_role` | string(32) | cast to `ConsultationMaternityLinkRole` |
| `linked_by` / `linked_at` | FK users / timestamp | |
| `unlinked_by` / `unlinked_at` / `reason` | | soft-unlink audit |
| `metadata` | json nullable | |
| `active_slot` | unsignedTinyInteger **nullable** | `1` = active, `NULL` = historical |

Indexes: unique `cml_active_context_unique (consultation_route_id, context_type, active_slot)`; plus `(consultation_route_id, context_type)`, `linked_at`, `unlinked_at`. Every FK carries its own index via `constrained()`.

Explicit FKs were chosen over a polymorphic `context_id` because the target set is closed at seven, every other maternity relationship uses constrained FKs, and reporting needs indexable joins.

## 5. MySQL active-link uniqueness strategy

MySQL/MariaDB does not support partial unique indexes (`WHERE unlinked_at IS NULL`), but a unique index **does permit unlimited NULLs**. So:

- active link → `active_slot = 1`
- retired link → `active_slot = NULL`

The unique index on `(consultation_route_id, context_type, active_slot)` therefore enforces **exactly one active link per consultation/context type** while allowing **unlimited historical rows** for the same pair. A plain unique index on a boolean `is_active` would have blocked the second property.

The link service always sets `unlinked_at`, `unlinked_by` and `active_slot = null` **inside the same transaction**, and the unique index acts as the final race guard: a duplicate-key collision is converted into either an idempotent result (same target won the race) or a clear `RELINK_REQUIRED` domain error.

## 6. Context types and roles

**Types:** `pregnancy_profile`, `maternity_case`, `anc_visit`, `labor`, `delivery`, `newborn`, `postnatal`.
Each knows its own `foreignKey()` and `modelClass()`, and `forModel()` maps an instance back to its type — so context type and FK column are **derived**, never caller-supplied.

**Roles:** `primary`, `reviewed`, `created`, `handoff`, `historical`.

## 7. Link validation (fail closed)

`validateTarget()` enforces, in order:

1. **Supported target** — unknown model → `UNSUPPORTED_TARGET`.
2. **Persisted target** — unsaved/keyless → `INVALID_TARGET`.
3. **Patient ownership** — the maternity record must belong to the consultation's patient → otherwise `PATIENT_MISMATCH`.
   - Newborn/postnatal targets validate against **`mother_patient_id`** (a newborn↔paediatrics bridge is explicitly out of scope).
   - **Visit mismatch is allowed on purpose** — maternity records are longitudinal and legitimately span visits. Only patient mismatch blocks.

`deriveContextPayload()` builds every FK from the target model and derives `pregnancy_profile_id` from the target chain. A target with **no derivable pregnancy-profile root** is rejected as `INCONSISTENT_CONTEXT`.

## 8. Resolver behaviour

Order: **explicit link → same visit → same admission → single active profile → none.**

- **Explicit:** loads active links, validates every target chain, aggregates them into one bundle. An inconsistent explicit link is **never silently ignored** — it returns `invalid` with warnings.
- **Visit / admission:** collects `pregnancy_profile_id` across all six maternity tables for that visit/admission; **one** unique profile resolves, **more than one** returns `ambiguous`, **zero** falls through.
- **Single active profile:** `PregnancyProfile::active()` (status `active` **or** `high_risk`); exactly one resolves, more than one is `ambiguous`, none is `none`.

Guarantees (all tested): never persists a link; never creates a maternity record; never picks "latest" among candidates; never infers pregnancy from sex, complaint, diagnosis, pregnancy test or specialty; never starts a maternity workflow. Surrounding records are fetched bounded (latest-of-each) and eager-loaded.

## 9. Ambiguity behaviour

`ConsultationMaternityContext::ambiguous()` carries `candidateProfiles` plus a localised warning and leaves `pregnancyProfile` **null**. The caller (14R.3 UI) must present an explicit selector. The resolver never chooses.

## 10. Link / relink / unlink

| Op | Behaviour |
|---|---|
| `link` | Validates, derives, `lockForUpdate()`s the active row. Same target → **idempotent** (returns existing). Different target → **`RELINK_REQUIRED`** (never silently replaces a clinician's link). Otherwise inserts with `active_slot = 1`. |
| `relink` | **Reason mandatory.** Transactionally retires the old row (`active_slot = null`, `unlinked_*` set), inserts the new active row, **preserves the old row**, logs old + new ids. |
| `unlink` | **Reason mandatory.** Sets actor/time and `active_slot = null`. **Never deletes.** |

All three run inside `DB::transaction` with row locking.

## 11. Activity logging

Actions `CONSULTATION_MATERNITY_CONTEXT_LINKED` / `_RELINKED` / `_UNLINKED` on `LogModule::CONSULTATION`, via the existing `ActivityLogService`.

Metadata: consultation route id, context type, link role, target record id, pregnancy profile id, previous link/target id on relink, actor, reason. **Deliberately identifiers-only — no clinical notes or maternity content.**

> Note: the project stores the action in the Spatie `event` column with the module in `log_name`.

## 12. Permissions

Added additively: `consultation.maternity_context.view`, `.link`, `.unlink`.

- Admin / Super Admin receive all.
- Other roles receive `.view` only if they already hold consultation access **and** maternity view; `.link`/`.unlink` additionally require `maternity.pregnancy.update`.
- The migration reads each role's own permission names rather than calling `hasPermissionTo()`, which throws when a permission name is absent in an installation.
- **The bridge never escalates access to Maternity.** A future UI action must require **both** the bridge permission and the underlying maternity permission.
- `create_profile` / `record_anc` / `start_labor` were **not** added — they belong to 14R.3.

## 13. Localisation

`lang/en/consultation_maternity.php` and `lang/fr/consultation_maternity.php` in strict parity: context types, link roles, resolution sources, statuses, linked/relinked/unlinked, link/unlink reason, all seven error codes, and warnings (multiple active profiles, multiple candidates, missing target, patient mismatch, source-of-truth, duplicate-data). All service/domain messages are localisable; no UI was added.

## 14. Tests and checks run

**New suite — `tests/Feature/ConsultationMaternityBridgePhase14R2Test.php`: 22 passed (63 assertions).**

Covers: link pregnancy profile · ANC derives profile · labor/delivery/newborn/postnatal targets · unsupported target rejected · patient mismatch rejected · newborn validates against mother · idempotent same-target link · different target requires relink · relink preserves history · one active row per context type · multiple historical rows allowed · unlink requires reason and never deletes · all three activity logs written · resolver `none` · explicit first · visit fallback · **admission fallback** · single-active-profile fallback · ambiguous for multiple profiles · resolver creates nothing · pregnancy signals cause no creation/linking · link survives completion · no specialty entry created by bridge ops.

**Regression (targeted only, per phase boundary):**

| Suite | Result |
|---|---|
| Bridge + `MaternityFoundationPhase8Test` + `LaborDeliveryFoundationPhase10Test` | **36 passed, 0 failed** |
| `tests/Feature/Consultations` | 230 passed, **22 failed — pre-existing** |
| `AntenatalCarePhase9Test` | 4 passed, **1 failed — pre-existing** |

Both failure sets were **baselined by removing every 14R.2 change** (migrations moved aside, tracked edits stashed) and re-running: **identical counts**. This phase introduced **zero new failures**.

Also run: `php -l` on all 13 changed/new PHP files (clean) · `php artisan route:list --name=consultation` / `--name=maternity` · `php artisan view:clear` · `php artisan config:clear` · `git diff --check -- . ':!docs/prompt.md'` (clean).

**`composer test:wide` was not run**, per the phase boundary.

## 15. Existing workflows protected

Unchanged and verified: Obstetrics and Gynaecology workspace behaviour · specialty sections and entries (no conversion, no deletion) · consultation completion and readiness · consultation summary · maternity pages and services · admission and emergency workflows · orders (investigations/prescriptions/procedures/tasks) · Phase 14.1 preview-only billing (`billing.maternity_billing.enabled` still defaults **false**; `postForSource` still returns `STATUS_POSTING_NOT_IMPLEMENTED`).

The bridge is **inert** until a later phase consumes it: nothing calls the resolver or link service in production code yet.

## 16. Known risks

| # | Risk | Mitigation |
|---|---|---|
| K1 | The bridge is unused code until 14R.3. | Intentional. Fully tested; no production call sites, so no runtime exposure. |
| K2 | `PregnancyProfile::active()` includes `high_risk`. A patient with an active **and** a high-risk profile resolves as ambiguous. | Correct and deliberate — ambiguity must be explicit. Documented here so 14R.3's selector expects it. |
| K3 | Resolver's visit/admission fallback queries six tables per call. | Bounded + distinct + indexed FKs. Should be measured when 14R.3 puts it on the workspace hot path; consider caching per request. |
| K4 | `active_slot` is a deliberate schema idiom that reads oddly without context. | Documented in the migration docblock, the model, and §5 here. |
| K5 | Permission grants depend on role names present in the installation. | Migration is idempotent and skips roles lacking prerequisites; admins always receive all three. |

## 17. Intentionally deferred

Obstetrics ribbon/panels and section conversion (**14R.3**) · `dating_method` → Pregnancy Profile (**14R.3**) · Gynaecology separation, explicit pregnancy transition, order-set retargeting R5 (**14R.4**) · admission/emergency/labor/delivery/postnatal handoffs (**14R.5**) · readiness, summary projection, **immutable versioned completion snapshot R6**, historical reconciliation dry-run, billing de-duplication policy (**14R.6**) · manual test data + wide regression (**14R.7**).

Also deferred by design: explicit LMP-adoption UI (R2), `create_profile`/`record_anc`/`start_labor` permissions, any maternity billing posting change.

## 18. Next recommended phase

**14R.3 — Obstetrics workspace integration.** It is the first phase that changes clinician-facing behaviour, so it should ship behind a flag with the context ribbon and read-only projections landing **before** any section is made non-editable, and every hidden field must gain its maternity action in the same panel.
