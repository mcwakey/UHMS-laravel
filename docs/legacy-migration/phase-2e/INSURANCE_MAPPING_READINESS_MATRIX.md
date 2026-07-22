# Insurance mapping readiness matrix

| Capability | Evidence | Status | Remaining prerequisite |
|---|---|---|---|
| Source schema/aggregates | 15-query/versioned/hash-bound read-only `uuhms` evidence bundle | ready | future runtime fingerprint guard |
| Patient parent/quarantine | Phase 2C | ready | Phase 3 crosswalk runtime |
| Provider crosswalk | Phase 2A | ready for specification | runtime + unresolved rows |
| Type/payer crosswalks | explicit/count-backed | ready | versioned implementation |
| Scheme/plan | no target destination | history-only | separate target design if needed |
| Member number | rules/diagnostics complete | ready | protected token runtime |
| Date/current classification | exact disjoint chronology/current-state evidence | ready for protected snapshot | preserve pinned coordinate/version |
| Consolidation | deterministic fail-closed | ready for pilot contract | row snapshot + member_type representation |
| Existing target | immutable contract | ready | refreshed collision snapshot |
| Eligibility/verification | explicitly not evidenced | closed as non-import | nullable/absent representation |
| Exceptions/reconciliation | specified | ready | Phase 3 storage/execution |
| Importer/persistence | not authorized | blocked | Phase 3 foundation and patient-pilot approval |

Independent review passed with no findings. Phase 2E may hand off to Phase 2F specification; no line authorizes an importer.
