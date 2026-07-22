# Insurance exception catalogue

The normative catalogue contains 41 stable `LEGACY-INSURANCE-*` codes covering keys, patient chains, provider ambiguity and duplicate-source groups, types, missing and ambiguous scheme evidence, member numbers, dates, consolidation, existing-target conflict, prohibited mutation, idempotency, eligibility, text, drift, extraction and privacy.

Every code has severity, trigger/scope, primary disposition, retryability, owner/SLA, evidence-based release, reconciliation bucket, chain behavior and privacy/provenance requirements. Critical drift, mutation, fallback-actor and privacy failures stop affected processing. Provider/member/date/currentness uncertainty withholds the group. Informational unknown dates/member numbers remain explicit absence when target nullability supports them.

An expired SLA never authorizes defaults, discard, reassignment, fabricated values, eligibility assumptions or release.
