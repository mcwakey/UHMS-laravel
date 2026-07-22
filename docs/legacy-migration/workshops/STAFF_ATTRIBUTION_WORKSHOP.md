# Staff attribution and security workshop

## Purpose and evidence boundary

This pack separates historical attribution from renewed authentication and authorization. Classic credentials and permission flags are excluded by accepted decision D-008.

| ID | Plain-language question | Why it matters | Confirmed evidence / affected count | Options and risks | Phase 1B recommendation (superseded by accepted outcome below) | Original proposed approval owner | Domains blocked | Decision point |
|---|---|---|---|---|---|---|---|---|
| D-202 / Q-003 | How should zero, missing, obsolete or unmatched Classic user references be represented? | Target visits, medical records, admissions and many logs require an actor; using a current user invents history. | Classic has 86 users and no general audit journal. Phase 1A found large inferred user-orphan counts, including 36,741 attendance-user and 87,186 billing-user unmatched/sentinel-inclusive links; Phase 1B records exact predicates and candidate-zero counts. | Default to first/current user: prohibited invented attribution. Create one generic actor: loses distinctions. Create controlled legacy actors plus an unknown actor: preserves evidence level but requires governance. Quarantine every row: may block most history. | Map verified staff to disabled, non-login historical identities; use one clearly labelled “Legacy actor unknown” only where the source has no valid actor, with original source reference protected in the crosswalk. | HR + security + clinical governance | Visits, clinical, billing, admissions, audit | Before patient pilot for registrar/visit actors; before each later domain |
| Q-304 | What staff identity mapping is approved after credentials and Classic permissions are excluded? | Authentication, roles and clinical attribution have different evidence requirements. | Two duplicate normalized nonempty username groups cover four Classic users. All Classic permission flags are `Y`, so they are unsuitable for renewed authorization. | Match username only: collision risk. Match verified HR identity: reliable but needs HR input. Recreate Classic permissions: unsafe. | Verified employment-identity staff crosswalk; provision renewed access separately through normal security processes. Historical-only actors remain disabled and receive no roles. | HR director + security owner | Staff, access control, all actor FKs | Before patient pilot |

## Retained Phase 2 technical outputs

- Define the staff crosswalk evidence standard under the accepted policy.
- Assign the operational owner and SLA for unknown-actor exceptions.
- Verify that no Classic password or permission flag enters the renewed system.

## Controlled decision outcome records

The evidence, options, owners, decision points, and recommendations above are preserved as the Phase 1B record. The project owner exercised final authority directly on 2026-07-21; no workshop, quorum, committee approval, meeting minutes, or additional signature was required. The selected option in each record is the recommendation only as modified by the consolidated clarifications in the authoritative directive.

### Decision outcome: D-202 / Q-003

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-202` | Related open-question IDs | `Q-003` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Map verified Classic staff to disabled, non-login historical identities with no renewed permissions. Use one clearly labelled Legacy Actor Unknown identity only when no valid actor is determinable; preserve the original actor reference in the protected crosswalk and never use a current, first, administrator, or importer user. |
| Rejected alternatives | Current-user, first-user, administrator, importer, or guessed attribution; granting historical actors operational access. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must define verified matching evidence, historical identity controls, unknown-actor eligibility, and protected provenance. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 86 Classic users and source rows containing actor links, including 36,741 attendance-user and 87,186 billing-user unmatched/sentinel-inclusive candidates. | Target domains affected | Staff, visits, clinical, billing, admissions, audit. |
| Transformation consequence | Create disabled historical identities only from verified crosswalks; use the unknown identity only under the approved rule. | Exception consequence | Unverified mappings become actor exceptions or the approved unknown-actor case; original references remain protected. |
| Reconciliation consequence | Reconcile every actor-bearing row to a verified historical identity, approved unknown identity, or classified exception. | Manual-review owner | Historical attribution review owner designated for each domain. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Staff identity and historical-attribution mapping |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: Q-304 / Q-304

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `Q-304` | Related open-question IDs | `Q-304` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Match staff using verified employment identity, never username alone. Duplicate usernames cannot match automatically; renewed credentials, roles, and permissions are provisioned independently, while Classic passwords and permission flags are permanently excluded. |
| Rejected alternatives | Username-only matching, credential migration, Classic permission recreation, or enabling historical-only identities. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must define HR-verifiable attributes and collision review without exposing credentials or personal data in documentation. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 86 Classic users, including four users in two duplicate normalized username groups. | Target domains affected | Staff crosswalk, access control, every actor foreign key. |
| Transformation consequence | Build a verified employment-identity crosswalk separate from renewed access provisioning. | Exception consequence | Duplicate or unverifiable identities require classified review; no credentials or Classic permission flags enter migration outputs. |
| Reconciliation consequence | Reconcile all source staff to verified mappings, historical-only identities, unknown-actor eligibility, or exceptions. | Manual-review owner | HR/security staff-crosswalk review owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Staff identity and historical-attribution mapping |
| Decision status | Accepted | Approval method | Direct project-owner decision |

## Workshop completion record

No physical or virtual workshop was conducted. This pack was resolved by direct project-owner decision and is therefore superseded as a workshop instrument.

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Workshop date | Not conducted; final-authority directive dated 2026-07-21. | Participants | No workshop held. |
| Stakeholder roles represented | Not applicable; the project owner exercised final authority directly. | Quorum or required authority present | Not required; final authority exercised directly. |
| Decisions accepted | D-202/Q-003; Q-304 | Decisions partially accepted | None. |
| Decisions deferred | None at business-policy level; technical specifications remain Phase 2 tasks. | Contradictions discovered | No unresolved policy contradiction after the consolidated directive. |
| Actions assigned | Define the verified staff crosswalk and historical-actor persistence contract in Phase 2. | Action owners | Phase 2 delivery owners to be assigned; project owner retains final decision authority. |
| Action deadlines | Set during Phase 2 planning. | Follow-up workshop required | No. |
| Workshop chair approval | Not applicable; no workshop occurred. | Records/governance approval | mcwakey (established repository identity), Project Owner and Final Decision Authority |
| Workshop status | Superseded by final-authority decision | Approval method | Direct project-owner decision |
