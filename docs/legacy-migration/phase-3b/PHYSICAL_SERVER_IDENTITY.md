# Phase 3B physical target-server identity

Status: **implemented as a fail-closed identity control and integrated into the only approved foundation-DDL composition path; real approved infrastructure identity remains a release gate**  
Evidence date: 2026-07-22

## Scope and safety boundary

This control identifies the active database server independently of Laravel environment labels, connection aliases and schema shape. It does not authorize domain writes, protected-store population, an importer, Cohort B or the patient pilot.

The Classic `uuhms` connection was not accessed. Renewed production was not accessed. Verification used a fresh, local-only, explicitly marked disposable MariaDB 10.4 datadir containing synthetic foundation structures only.

## Implemented contract

`PhysicalTargetIdentityContract` pins the following versioned, owner-approved coordinates:

- target connection and exact database;
- database driver and exact version;
- protected host-identity reference and port;
- TLS requirement, protected cipher reference and, where supported, protected peer identity;
- protected MariaDB-equivalent server identity;
- approved server-role classification;
- protected network/environment identity;
- independently administered environment-attestation version;
- structural schema fingerprint and table/column counts;
- foundation schema coordinate;
- configuration fingerprint; and
- owner approval/version reference.

`LaravelPhysicalServerIdentityObserver` obtains the observation directly from the active connection. It reads database/version, `@@hostname`, `@@port`, `@@server_id`, TLS session status, the PDO connection status and exactly one active row from the separately administered `migration_environment_attestations` table. Raw host, endpoint, TLS and server values are immediately converted to domain/environment/key-version-separated HMAC references. They are not returned in reports or exception messages.

`PhysicalServerIdentityVerifier` separately compares:

1. the configured expected contract;
2. the authoritatively observed physical identity;
3. the approved attestation record; and
4. the independently captured structural identity.

Every coordinate must agree. A schema clone on another host or network, a wrong port, a wrong server-equivalent identity, a production role behind a local label, missing required TLS evidence, attestation ambiguity, schema drift or configuration drift fails with a generic redacted fault.

`IdentityBoundDdlGate` accepts only a successful `PhysicalServerIdentityVerification`; no caller boolean represents identity proof.

For interrupted installation, `SchemaFingerprintService::inspectTargetInstallationBase()` reconstructs the approved pre-install structural identity by excluding only `legacy_migration_*` tables, their `lm_*` triggers and the six immutable foundation migration-ledger rows. Every non-foundation table, column, constraint, index, trigger, event, routine and migration remains in the fingerprint. The reconstructed fingerprint and base table/column counts must still exactly equal the owner-approved target identity contract.

`InstallationPartialStateVerifier` then inspects every coordinate in both immutable DDL manifests. Each coordinate must be either absent or match its exact normalized definition hash; any drift or unknown reserved object fails closed. Its keyed `InstallationIdentityContract` seals the approved base physical identity, reconstructed base fingerprint, concrete connection instance, manifest versions and payload hashes, and the complete observed matching/absent state vector. This permits only the observed manifest delta and is not a general schema-fingerprint override.

## Application composition and non-bypassability

`FoundationInstallationCompositionService` is the repository-integrated entry point for foundation installation and partial-DDL recovery. Before observing or writing schema it requires all three independent configuration gates to be exactly `true`: `schema_writes_enabled`, `installation_journal_enabled` and `partial_repair_authorized`. It also requires the pinned manifest-bundle version.

The service applies the normal environment/schema guards, resolves the physical-identity HMAC key from its external reference at the moment of use, captures the installation-base structure read-only, observes the active server directly and verifies the complete physical identity contract. It then issues a sealed `InstallationSessionCapability` binding the keyed installation-identity contract, protected audit reference and both immutable manifest versions.

The physical observation includes an HMAC-protected `CONNECTION_ID()`-derived connection-instance reference. Every `MariaDbFoundationDdlOperationExecutor::execute()` call requires the authentic `PhysicalServerIdentityVerification`, its `IdentityBoundDdlGate` and that sealed installation session, then re-observes the physical identity through the executor's own concrete connection. Both the stable server/network/TLS/database reference and connection-instance reference must match. Wrong-key proof, wrong-key gate, wrong-key session, second-connection replay, unapproved manifest version or non-MariaDB driver is rejected before SQL. Direct construction does not create authority.

Direct execution of the Laravel foundation migrations on MariaDB/MySQL is permanently denied by `FoundationDatabaseWriteBoundary`. The SQLite exception exists only for the isolated unit-test schema path. There is no Artisan command or alternate application composition route that enables this DDL.

## Configuration and infrastructure contract

The shared configuration must remain fail-closed when any of these references is missing:

- physical identity contract/version;
- identity-reference key provider reference;
- host, port, TLS and peer requirements;
- server-equivalent and network/environment references;
- server role and attestation version;
- foundation/schema/configuration coordinates; and
- owner approval reference.

Key material must be resolved from the external key provider and must not be cached in Laravel configuration. The target network and migration credential should be technically unable to reach renewed production. That network enforcement is an external infrastructure prerequisite and cannot be proven by repository code.

The real non-production installation configuration must provide the approved identity references and external key reference. Until that configuration is present and matches the observed server, shared-target DDL remains denied.

## Verification evidence

Focused synthetic tests cover:

- correct approved identity;
- wrong host and port;
- wrong MariaDB server-equivalent identity;
- identical schema on an unapproved network;
- production role under local labels;
- missing required TLS peer identity;
- redacted report/error output; and
- proof that the DDL callback does not begin after identity rejection.
- rejection of direct executor invocation with a wrong-key gate or wrong-key session before SQL; and
- rejection of each independently disabled installation configuration gate before connection use.
- rejection of installation-session replay on a second database connection; and
- rejection of an unknown reserved object during exact partial-state verification.

The disposable MariaDB adapter test returned only redacted references:

- approved physical identity reference: `939d7a66fc82c2291a236963b1e4ced2b209b808da1a9ca508c6c39bf4457067`;
- installed structural reference: `530839f93f1a1910b9b275114abb84f9fefda6887171710c1ad681749c4c2509`; and
- synthetic configuration reference: `b7d64a9221007dfd5390f7df6cd5b8f3ea4f82faa1237141e35ebf161f5511a1`.

The disposable test did not provide TLS, so it is not evidence for a real TLS peer. The required-TLS fail-closed behavior is covered by unit tests; approved target TLS identity remains external evidence.

Focused environment/installation and installation-schema result at handoff: **44 tests passed, 243 assertions**.

## Classification

- Confirmed implementation: authoritative observation adapter, HMAC identity references, four-way verifier, redacted evidence, typed DDL gate, sealed installation session and single fail-closed composition service.
- Confirmed disposable evidence: MariaDB 10.4.32, local-only synthetic environment, direct adapter verification.
- External prerequisite: approved target identity record, TLS/peer evidence, external HMAC key custody, network denial of production.
- External release gate: the owner-approved non-production physical identity/TLS evidence, externally held HMAC key and network denial of production must be supplied and verified before an installation can run.
