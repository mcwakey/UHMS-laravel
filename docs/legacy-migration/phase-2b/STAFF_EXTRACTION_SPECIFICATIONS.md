# Staff extraction specifications

[staff_extraction_strategies.json](specifications/staff_extraction_strategies.json) contains 30 strategies: one staff master, all 28 actor/actor-like fields and one aggregate-only credential/permission exclusion count.

## Staff master

- exact source: `uuhms.users`;
- order/resume key: `USER_ID ASC`;
- full ordered snapshot; 86 rows, 500-row maximum chunk recommendation;
- `RegDate` may discover inserts with `(RegDate, USER_ID)`, but cannot prove updates/deletes;
- domain-separated HMAC-SHA-256 protected row fingerprint excludes Password and permission fields and records key/canonicalization versions;
- full key-set/hash comparison at preflight and freeze;
- never infer deletion from an absent delta.

## Actor fields

Actor extraction is co-snapshotted with each downstream table under repeatable read and its actual primary key:

`ATT_ID`, `BATCH_ID`, `BILL_ID`, `PRES_ID`, `SCL_ID`, `NUR_ID`, `REQ_ID`, `TREAT_ID`, `VIT_ID`, `CLAIM_ID`, `COMP_ID`, `DIAG_ID`, `HIST_ID`, `PRO_ID`, `REC_ID`, and `TREATP_ID`.

Additional actor-like fields use `BILL_ID`, `NOTID`, `MAFA_ID`, `ID`, `DIAG_ID` and `LDIAG_ID` for their owning-table snapshots. Dormant maternity and excluded notification/configuration content are aggregate/fingerprint evidence only; their values never enter documentation.

Every strategy records ordering/primary key, snapshot identity, resume coordinate, timestamp reliability, delete capability, source fingerprint and stop conditions. D-101 least-privilege Classic credentials remain an execution prerequisite.
