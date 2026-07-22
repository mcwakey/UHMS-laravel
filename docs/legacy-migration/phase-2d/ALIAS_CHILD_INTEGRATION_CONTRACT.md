# Alias-child integration contract

Phase 2D references and does not redefine `LEGACY_OPD_ALIAS_CONTRACT`, `PATIENT-ALIAS-001..017`, canonicalizer `legacy-opd-comparison-v1`, and `PATIENT-REC-ALIAS-002`.

1. Patient mapping commits before alias processing.
2. Alias, NOK/contact and demographic outcomes reconcile independently.
3. A duplicate-withheld alias does not block otherwise valid Phase 2D outcomes.
4. Patient quarantine holds every child, including the alias outcome.
5. Direct Classic OPD aliases keep `created_by = null` and `source_patient_id = null` plus protected absence/source provenance.
6. Existing aliases, patient numbers and ownership are never edited, transferred, deleted or recycled.
7. `OriginalName` and `OriginalOpd` create zero aliases. Their captured baseline is 16,950 blanks each; any nonblank drift stops semantic review.
8. Contact/demographic similarity never influences alias ownership or patient matching.

Phase 2D itself creates zero aliases and introduces zero alternate canonicalizers. Future alias persistence is exclusively the Phase 2C stage after the parent map.

