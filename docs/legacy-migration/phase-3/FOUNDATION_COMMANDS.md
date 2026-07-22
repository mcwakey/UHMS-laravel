# Foundation commands

All commands default to zero-write inspection and return a failing exit code on missing authority or evidence.

| Command | Purpose | Writes |
|---|---|---|
| `legacy-migration:foundation-preflight` | Validate environment, exact source/target and pinned configuration | None |
| `legacy-migration:verify-source-account` | Verify D-101 grants and read-only behavior | None |
| `legacy-migration:foundation-status` | Report redacted capability/gate status | None |
| `legacy-migration:privacy-scan` | Scan the declared artifact manifest | None |
| `legacy-migration:foundation-reconcile` | Evaluate stored/supplied aggregate measurements | None by default |
| `legacy-migration:foundation-recovery-audit` | Classify incomplete intents/checkpoints without recovery mutation | None |

No importer or pilot command exists.
