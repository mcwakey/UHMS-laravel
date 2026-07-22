# Phase 2F policy binding

Status: implemented and fail-closed; deployment configuration remains required.

`AuthoritativePolicyBundleLoader` requires the exact 21 Phase 2F JSON specifications plus `APPROVED_DECISION_SPECIFICATIONS.md` and `DECISIONS.md`. For every artifact it verifies the repository-relative path, SHA-256, specification version, complete contract-ID set, record-count shape and non-empty approval reference. Missing, additional, changed, version-drifted, unapproved or contract-drifted artifacts fail closed. The canonical artifact-coordinate set produces one bundle hash, and `VerifiedPolicyBundle::assertPinned()` detects changes after run pinning.

The patient-state and insurance-initialization validators now require a verified bundle and compare their runtime rule ID/field set to the exact authoritative artifacts. The unresolved patient status, active, temporary, merge, deceased and timestamp representations, and the unresolved insurance member type, active, primary and timestamp representations remain commit blockers. Recommendations were not promoted to approved values.

Runtime loading now uses `Phase2FPolicyConfigurationFactory` and an absolute external non-secret JSON manifest reference (`phase2f-policy-manifest/1`). The manifest contains the exact 23 artifact definitions, expected canonical bundle hash and deployment approval reference. Those last two values must exactly match `phase2f_policy.expected_bundle_hash` and `phase2f_policy.approval_reference`; the path comes from `phase2f_policy.artifact_manifest`. Repository-local, malformed, unauthorized or drifted manifests fail closed. Credentials are neither accepted nor stored in this manifest.
