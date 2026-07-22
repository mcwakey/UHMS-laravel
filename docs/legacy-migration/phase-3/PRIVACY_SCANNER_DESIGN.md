# Privacy scanner design

The scanner builds an artifact manifest, compares expected versus scanned coverage and applies structured detectors for secrets, patient/contact/insurance values, raw IDs, row tokens, unsafe low-entropy hashes and cross-domain token misuse. Synthetic fixtures are allowed only within the rigid declared namespace.

Coverage includes `docs/legacy-migration/`, foundation configuration templates, reports, focused-test artifacts and generated dry-run reports. Diagnostics contain file/rule/location and a redacted fingerprint only—never the matched value.

Any unallowlisted finding or coverage gap blocks release and returns containment/remediation/rescan instructions.
