# Quarantine and exception ledger

A quarantine root represents one held source chain. Dependent records attach topologically to that root and cannot be released independently of an approved parent repair. One active root is enforced for a compatible protected chain.

Exceptions carry approved code, severity, domain, source relationship, blocking stage, evidence checksum, manual-review owner/SLA where specified, and state. Expected classified exceptions remain distinct from unexplained failures.

Release requires an explicit reviewed transition, satisfied parent dependencies, refreshed collision evidence and passing reconciliation. No artificial parent, guessed patient, convenient default or silent discard is permitted.
