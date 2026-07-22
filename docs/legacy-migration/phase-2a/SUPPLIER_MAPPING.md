# Supplier Mapping

Classic `suppliers` has two rows. `SUP_ID` is protected identity and `SuppName` is a normalized review key. Name-only auto-merge is prohibited because target suppliers have no unique business key.

`SuppDescription` is not proven to be target contact person, phone, email or address and remains protected evidence. Classic has no evidenced target contact fields; new target contacts remain null, and existing contacts are never overwritten. `Bill`, `Paid` and `Balance` are finance data deferred from Phase 2A. Mutable `RegDate` is not creation history.

A target reuse requires a unique reviewed legal/trading-name match. Otherwise retain separate candidates; never invent contacts. Batch evidence shows all 253 supplier references source-match, but batch creation remains deferred.

Reconciliation: `2 = target-existing candidates + create candidates + exceptions`; Phase 2A writes zero. `SupplierSeeder` is prohibited as a matcher because it uses name and overwrites contact fields.
