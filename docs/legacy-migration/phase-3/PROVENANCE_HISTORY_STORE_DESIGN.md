# Provenance and history store design

Every consumed source fact receives one primary disposition: mapped, preserved as protected history, quarantined, explicitly excluded by approved policy, or not evidenced. Provenance records bind protected source/target tokens, column/relationship contract, transformation version, source query/result hash, atomic intent and integrity checksum.

History is append-only. It does not simulate renewed workflow, activity or audit events and does not invent clinical, financial, stock or actor facts. Protected payloads are encrypted and absent from array/JSON serialization.

Append-only database protection is implemented. Completeness/primary-disposition admission validation and repository-level HMAC access/integrity verification remain blocked implementation work.
