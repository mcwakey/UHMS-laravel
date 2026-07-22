# Foundation privacy model

Repository artifacts and ordinary reports are aggregate-only. Patient names, phones, addresses, OPD numbers, member numbers, raw source/target identifiers, clinical text and credentials are prohibited.

Record linkage uses protected domain-separated tokens. Raw remediation/provenance content may exist only in encrypted protected storage, never in logs or model arrays. Synthetic fixtures use the declared `SYNTHETIC-ONLY-P2F` namespace. A privacy finding blocks release, requires containment and must be rescanned after correction.

Retention is classification-driven. Purge is never implicit and requires explicit authority; purging migration metadata must never cascade into business records.
