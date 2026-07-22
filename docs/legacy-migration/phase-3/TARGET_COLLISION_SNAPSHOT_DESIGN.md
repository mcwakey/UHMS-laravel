# Target-collision snapshot design

Before any future domain boundary can write, it must pin current target namespaces relevant to that unit: protected target record keys, patient-number sequence coordinate, patient/archive/alias number namespaces, normalized typed aliases, contact primary counts, patient/provider membership uniqueness and existing-target immutability fingerprints.

A snapshot stores only aggregate counts, hashes and protected references. Freshness is stage-specific. Any changed coordinate invalidates the decision and requires reclassification; a database unique-key error is never accepted as proof of compatible prior ownership.
