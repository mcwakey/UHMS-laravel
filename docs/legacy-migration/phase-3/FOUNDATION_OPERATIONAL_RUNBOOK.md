# Foundation operational runbook

1. Obtain the dedicated D-101 credentials outside source control.
2. Configure explicit non-production target, expected fingerprints/versions/counts and external HMAC key ID/version/material.
3. Keep commit, importer and Cohort B flags false.
4. Run source-account verification, then foundation preflight and status. These commands are zero-write inspections.
5. Do not install/populate the foundation stores until the Phase 3 blockers in the exit report are closed and a new independent approval is recorded.
6. Run focused reconciliation, recovery audit and privacy scan only with aggregate/synthetic inputs.
7. Stop on any mismatch, missing measurement, side-effect capability, privacy finding or changed coordinate.
8. Retain redacted aggregate reports and protected ledgers according to classification.

MariaDB auto-commits DDL. The current multi-object migrations do not implement repair/resume after a partial installation, while non-test destructive `down()` is deliberately blocked. Use only an approved disposable target for future verification; do not attempt installation on a valued environment until a reviewed partial-DDL recovery procedure exists.

Recovery never guesses. Verify exact lineage, classify the crash boundary, repair metadata only where the durable atomic unit is complete, otherwise create a reviewed unit-specific compensation requirement. Never delete an existing target or recycle a committed patient number.

This runbook does not authorize a foundation exercise, Cohort B, a patient pilot or any importer.
