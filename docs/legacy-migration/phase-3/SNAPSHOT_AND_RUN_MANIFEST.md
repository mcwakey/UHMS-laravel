# Snapshot and run manifest

A run pins the source schema fingerprint, source query/result hashes, target schema fingerprint, target-collision snapshot, contract bundle, transformation/canonicalization/HMAC versions and configuration fingerprint. All timestamps are UTC and all artifact hashes use canonical JSON.

Source and target coordinates must be captured as one coordinated preflight. A changed coordinate invalidates downstream idempotency and resume compatibility. Cohort manifest registration is an interface only: Phase 3 does not select Cohort B and rejects registration while any of 24 predicate hashes, capacity evidence, disjoint precedence proof, D-101 verification or protected manifest capability is missing.

Run manifests and registered snapshots are immutable; later observations are appended as new records.

Current source/target manager classes validate caller-supplied hashes and build deterministic manifest objects. They are not wired to read-only database extraction or the snapshot repositories, and preflight does not persist a coordinated capture. Authoritative capture/persistence is therefore a blocking implementation gap.
