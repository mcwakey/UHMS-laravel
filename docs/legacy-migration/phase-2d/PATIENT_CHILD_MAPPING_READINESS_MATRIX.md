# Phase 2D mapping readiness matrix

| Domain | Source evidence | Target evidence | Mapping/validation | Exceptions | Reconciliation | Extraction/privacy | Phase 2D status |
|---|---|---|---|---|---|---|---|
| Phase 2C OPD alias integration | complete | complete | referenced, not reopened | Phase 2C + guards | complete | shared snapshot | Ready for later implementation design |
| NOK/contact tuple | complete aggregate profile | installed aggregate confirmed | deterministic tuple/phone/name/relationship rules | complete | complete | protected/shared | Ready for later implementation design |
| Occupation | complete | nullable free text confirmed | direct text; no catalogue | complete | complete | protected/shared | Ready |
| Address | complete | unstructured field confirmed | no parsing/locality inference | complete | complete | protected/shared | Ready |
| Religion | complete four-state partition | nullable free text confirmed | explicit allow-list | complete | complete | protected/shared | Ready |
| Marital status | complete four-state partition | enum confirmed | explicit crosswalk | complete | complete | protected/shared | Ready |
| Existing-target children | Phase 2C consumed | target state confirmed | zero mutation | complete | zero assertions | HMAC comparison | Ready |
| Parent quarantine/release | Phase 2C consumed | FK confirmed | topological/crosswalk-first | complete | complete | root token/shared snapshot | Ready |

“Ready” means specification input for Phase 2E/Phase 3 design. It does not authorize importer implementation or target writes.

Independent review: **PASS** — Critical 0, High 0, Medium 0, Low 2. Phase 2E specification work may begin.

## Implementation blockers

D-101 least-privilege credentials; physical protected crosswalk/provenance/quarantine/reconciliation storage; contact idempotency; migration persistence boundary; one-primary atomic validator; enum/Unicode/phone validators; target-state guards; event/activity/queue/archive isolation; HMAC key management; rollback/resume; target route/privacy hardening review; artifact-wide PHI regression scanning; and UI round-trip validation for preserved free-text relationships.
