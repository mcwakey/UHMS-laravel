# Patient pilot provenance interface

## Boundary

This specifies protected provenance/history messages only. It creates no store or migration. It composes D-106, Phase 2C `PATIENT-PRIV-*`, Phase 2D `PATIENT-CHILD-PRIV-*` and Phase 2E `INS-HISTORY-*` / `INS-PRIV-*` without replacing them.

Normative machine contract: `specifications/patient_pilot_provenance_interface.json` version 2F.1.0.

## Source outcome record

Each protected source outcome contains:

- a domain-specific source-row token and authoritative patient/orphan root;
- optional typed subchain token;
- reference to protected raw payload, never the payload itself;
- query/hash and coordinated source-snapshot identity;
- exactly one primary disposition per consumed field plus ordered secondary exceptions;
- transformation version and projected/persisted target outcome;
- protected target-outcome reference where applicable;
- exception, reconciliation, retention and access metadata.

Allowed outcomes are none, candidate, created, linked existing, withheld, historical only, quarantined or failed. A dry-run marks all results as projections and never implies persistence.

## Completeness

Every pilot patient source row has an outcome. Every one of the 31,307 baseline Classic insurance rows remains represented in protected provenance under `INS-HISTORY-001` and `INS-REC-014`, including exact duplicates and rows for which no membership is projected.

No history row is removed because a group selects one current representation. Source-reported values are preserved in protected storage and are not overwritten by target projection.

## Audit and failure

Run, mapping, transformation, exception and reconciliation facts belong in a separately labelled migration audit. No Classic event is made to look like contemporaneous renewed operational activity.

Missing, unverifiable or version-mismatched provenance holds the affected root/subchain. The process never reconstructs a missing source fact from target state. Repository output is aggregate-only.

