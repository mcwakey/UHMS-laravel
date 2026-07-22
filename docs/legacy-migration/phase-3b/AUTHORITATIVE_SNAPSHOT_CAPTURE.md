# Authoritative snapshot capture

Status: implemented foundation adapter; external capture remains blocked.

## Confirmed implementation

`AuthoritativeSnapshotCapture` obtains observations directly from an active read-only `MetadataConnection`. Source capture first runs the single authoritative `SourceAccountVerifier`, then captures the structural fingerprint, MariaDB transaction/GTID availability, and complete static, version-controlled consumed-column result sets for patient roots, dependent attendances and insurance. Its query-manifest identity and code hash are derived from the executing adapter rather than accepted from the caller. Target capture re-fingerprints the installed target, then requires a `PhysicalIdentityTargetSnapshotAuthority` to verify the active connection through the independently pinned physical-server contract before it reads the patient, archive, alias, contact, insurance, provider, number-sequence, foundation and row-schema collision sets.

Result rows may exist only transiently in process memory. Each complete result set is converted immediately to a domain/environment/key-version-separated `artifact_integrity` HMAC envelope. `SnapshotManifestIntegrityService` also seals the complete manifest, including its direct-capture authority and physical/privilege reference. The manifest contains those envelopes, query hashes and aggregate schema coordinates; it never contains source or target row values. Caller-supplied hashes remain available only through the older non-authoritative DTO builders and are explicitly rejected by `RunManifestService`; altered authoritative metadata cannot replay a prior seal.

The coordinated run requires both snapshots to have `authoritative_direct_capture` status, protected result tokens, an authority reference, one exact verified Phase 2F policy bundle, and verified cohort, HMAC, environment, remediation and target-state-policy references. Bundle drift invalidates compatibility.

`AuthoritativeRunCaptureCoordinator` is now the supported capture-to-storage authority boundary. It accepts only `VerifiedCaptureConfiguration`, the exact verified Phase 2F bundle and the concrete `PhysicalIdentityTargetSnapshotAuthority`; it opens and closes both read-only database snapshots itself. An internal random capture nonce makes repeated before/after observations distinct. The coordinator derives typed authorities, creates no caller-supplied digest/boolean DTO, and atomically uses only protected repository methods. The exact contract bundle is securely reused, while each run, source snapshot, target snapshot and all ten target-collision namespaces receive keyed envelopes. No domain row is written.

Activation fails closed unless authoritative capture, physical identity and protected persistence are enabled while execution remains dry-run-only with commit, Cohort B, importers and production disabled.

Protected repository access additionally requires the externally approved `snapshots.persistence_authority_reference` (`LEGACY_MIGRATION_SNAPSHOT_PERSISTENCE_AUTHORITY_REFERENCE`). The protected-store authority must pin that exact reference for purpose `authoritative_run_capture`, operations `read`/`write`, and domains `migration_run`, `source_snapshot`, `target_snapshot` and `target_collision`. This storage authority does not replace the independently derived source-account and physical-target authorities recorded in the run manifest.

## External activation blockers

- D-101 credentials have not been supplied or used.
- No approved target physical-identity adapter or live target connection was invoked in this workstream.
- No live snapshot was persisted; only the synthetic SQLite end-to-end authority test populated protected foundation tables.
- Target-state and insurance initialization recommendations remain unapproved commit blockers.

No Classic schema other than the statically qualified `uuhms` queries is accepted. Tests use synthetic `MetadataConnection` doubles only.
