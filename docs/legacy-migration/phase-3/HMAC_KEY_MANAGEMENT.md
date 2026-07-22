# HMAC and key management

Keys are external configuration identified by key ID and version; no key is committed, printed or persisted with a token. The canonical message encodes canonicalization version, domain, value type and byte length before HMAC-SHA-256.

Domains include patient, staff, insurance, contact and reference source identity, target reference, idempotency and artifact integrity. Tokens from different domains or versions are deliberately unequal. Callers cannot compare raw digest bytes without the domain/version envelope.

Rotation creates new-version tokens linked through protected rotation lineage; old keys remain available only for the approved verification/retention period. Missing keys, unsupported versions, low-entropy material or a domain/canonicalization mismatch fail closed.
