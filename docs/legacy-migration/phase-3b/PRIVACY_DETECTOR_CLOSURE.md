# Privacy detector and target-enum closure

Status: implemented; final repository-wide rescan is an integration-stage gate.

Scanner version `P3B-PRIVACY-SCANNER-3` adds detection for colon/YAML/INI scalar patient and membership identifiers, embedded structured identifiers, SQL/CLI table output, log key/value output, authorization headers, cookie/session dumps, database DSNs, modern GitHub tokens and common cloud/provider credentials. Mandatory Phase 3 coverage now includes migrations 000110 through 000115. Diagnostics retain only detector ID, path and line/column; matched content is never returned.

Mandatory coverage now includes the evidence services and both Phase 1B evidence commands in addition to all prior Phase 3 roots. Synthetic adversarial tests cover the new formats and assert that diagnostic JSON contains none of the matched values.

Unexpected target enum-compatible values are now reported only as aggregate unexpected row count and distinct-value count. The previous stable unkeyed SHA-256 value digest was removed. No unexpected literal or digest is published.

Final independent-review scan: 785 files, coverage difference zero, unallowlisted findings zero, release blocked false. Scope-manifest hash: `b4fea9865fbe889b127b7e4624a0c469be0a9ec5fe7f7d6133f813ec83e4b678`. The scan first rejected the verbose broad-suite JUnit artifact with eight unallowlisted synthetic identifier/credential-pattern matches; that generated artifact was purged and complete rescans passed. The retained replay manifest contains stable case/file identifiers and hashes without failure output.
