# Insurance consolidation contract

Group only by resolved Phase 2C patient identity plus resolved Phase 2A provider identity. At most one target membership can represent a group because the database enforces unique patient/provider.

A current representation is selectable only for a newly migrated patient from one unique, known-date active-looking, compatible, structurally valid equivalence class at the pinned snapshot/evaluation coordinate and a proven target-provider crosswalk. Unknown-date rows are historical-only in Phase 2E. Classic has no current/status flag; dates do not prove eligibility. Multiple incompatible current-looking classes are quarantined. Exact duplicates preserve every source row; smallest INS_ID may order provenance but never establishes currentness.

Confirmed diagnostics: 37 duplicate patient/type/company groups / 90 rows; 36 groups / 88 rows including Scheme; 32 exact complete duplicate groups / 80 rows; four conflicting tuple groups / eight rows. Baseline aggregates do not authorize row selection; the protected coordinated row snapshot is mandatory.

An explicitly linked pre-existing target patient receives no new membership or general child under Phase 2C; retain comparison/provenance only. Target `member_type=holder` and `is_active=true` defaults are unsafe and must be bypassed by reviewed Phase 3 design. No tier, policy, CCC, card-holder relation, verification or eligibility is inferred.
