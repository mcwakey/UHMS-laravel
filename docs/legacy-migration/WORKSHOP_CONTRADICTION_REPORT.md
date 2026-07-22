# Workshop contradiction report

## Result

No unresolved business-policy contradiction remains across the nine packs after the consolidated direct-owner decision of 2026-07-21. No workshop is represented as having occurred. Technical detail still to be specified in Phase 2 is not treated as a governance contradiction or an implied mapping.

| Shared item | Prior tension | Authoritative resolution | Residual Phase 2 work |
|---|---|---|---|
| D-205 / Q-004 | One global status map could erase domain semantics | Separate explicit crosswalks for visits, triage, payer, and claims; unknowns fail closed | Enumerate values and target semantics per field/domain |
| D-206 / Q-301 | A global zero/null/date coercion rule could falsify chronology | Field-specific rules only; valid dates preserved; unsupported/invalid dates quarantined | Specify target representation and validation per field |
| D-214 / Q-203 / Q-011 | Operational services are unsafe, while target requires invariants and clinical grouping | Migration-specific validated persistence plus evidence-based grouping; no global validation weakening | Design persistence boundary, grouping predicates, invariants, and isolation tests |
| Q-102 | Reported claim total and calculated components can disagree | Preserve both; calculate with decimal arithmetic; quarantine differences over the fixed 0.01 tolerance from posting | Define exact fields/signs and the shared reconciliation output without changing the tolerance |
| Q-204 / D-106 | Target audit mechanisms could create false contemporaneous history | Import evidenced facts only and record a separate labelled migration audit | Define migration-audit schema, retention, and access |
| D-203 and opening stock/finance | Reconstructing histories could invent facts or double count openings | Unsupported histories start empty; only labelled, reconciled openings may be created | Produce opening contracts and anti-double-count gates |
| D-202 and D-008 | Actor attribution needs target users, but Classic credentials/roles are unsafe | Disabled verified historical identities, constrained unknown actor, separate renewed access provisioning | Verify staff crosswalk and historical-identity controls |
| Q-403 approval roles | Packs previously asked for committee/workshop authority | Project owner is final go/no-go authority; no committee, quorum, or signature is required | Define operational thresholds and capture the future go/no-go decision |

## Confirmed non-contradictions

- Detailed-mapping entry is not importer authorization.
- Manual review is an operational exception control, not an additional policy approver.
- A Phase 2 field-level rule must implement the accepted policy and cannot silently reinterpret it.
- The current broad-privilege Classic account remains prohibited even though it was used for read-only discovery under transactional guards.
- The direct-owner decision supersedes the workshop process; it does not claim a meeting, participants, quorum, or signed minutes.

## Unresolved technical items

Per-field mappings, per-relationship sentinel predicates, target unknown-value representations, exception taxonomy, manual-review SLAs, reconciliation contracts, opening equations, extraction strategy per table, and the isolated-runtime/cutover runbook remain Phase 2 deliverables. They do not reopen the approved business policies unless implementation evidence demonstrates a genuine contradiction.
