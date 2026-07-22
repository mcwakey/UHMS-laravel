# Remediation store design

Remediation is an explicit protected input, never a silent repair. Each record pins the protected source token, field/rule identifier, reason, authority reference, evidence version, effective scope, state, checksum and retention class.

Approved remediation is consumed only when the mapping contract names it. Original source evidence remains separately preserved. Rejection, expiry and supersession are append-only outcomes. Raw values are encrypted and hidden; reports expose counts by reason/status only.

The current repository persists encrypted evidence and immutable lineage but does not yet enforce approval, expiry, revocation, supersession/conflict semantics or protected-store access authority. No remediation may be consumed until those admission checks are implemented and reviewed.
