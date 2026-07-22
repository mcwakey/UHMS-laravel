# Historical identity classification

Eleven deterministic classes are defined in [staff_identity_classes.json](specifications/staff_identity_classes.json):

| Class | Outcome |
|---|---|
| `MATCH_EXISTING_ACTIVE_USER` | Protected link to one verified active target; target unchanged |
| `MATCH_EXISTING_INACTIVE_USER` | Protected link to one verified inactive/suspended target; target unchanged |
| `CREATE_DISABLED_HISTORICAL_IDENTITY_CANDIDATE` | Verified staff, no target; Phase 3 non-login representation required |
| `LEGACY_ACTOR_UNKNOWN` | Single controlled identity, only for permitted fields |
| `AMBIGUOUS_STAFF_IDENTITY` | Quarantine |
| `UNVERIFIED_CLASSIC_USER` | Quarantine pending HR verification |
| `DUPLICATE_CLASSIC_IDENTITY` | Quarantine pending reviewed identity outcome |
| `SECURITY_EXCLUDED_RECORD` | Terminal security exclusion |
| `ACTOR_REFERENCE_SENTINEL` | Field-specific null/zero/blank disposition |
| `ACTOR_REFERENCE_ORPHAN` | Protected provenance and field-specific quarantine |
| `FREE_TEXT_ACTOR_UNRESOLVED` | No automatic actor FK; protected text provenance |

Each class specifies eligibility, representation, authentication/authorization state, department/specialty handling, attribution eligibility, review, exception, reconciliation and release condition. No class grants access from Classic evidence.

