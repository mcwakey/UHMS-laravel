# Foundation test matrix

The focused suite exercises all 25 mandatory groups: environment/production rejection; exact schema; privileges; read-only transactions; drift; HMAC separation; store constraints; crosswalk cardinality; quarantine integrity; zero-difference enforcement; idempotency; checkpoints; ten crash-boundary classifications; recovery policy; sequential number allocation/rerun/rollback logic; dry-run zero writes; existing-target immutability; isolation interfaces; patient-state and insurance blockers; scanner coverage; no PHI/secrets; no business rows; no Classic writes; and no production writes.

All fixtures are synthetic. The final focused suite passes 164 tests and 644 assertions. It does **not** prove persistent crash/restart recovery, concurrent MariaDB allocation/rollback, protected-store guard integration, physical server identity or real application-bound side-effect controls. Those are blocking gaps, not covered controls.

The broad suite was not run because the required workstream and independent-review gates did not pass. Final evidence is recorded in `PHASE_3_EXIT_REPORT.md`, the machine test-coverage manifest and `reviews/`.
