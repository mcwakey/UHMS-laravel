# Insurance sentinel rules

Sentinels are field/relationship-specific under D-207/Q-005. PAT_ID has no approved zero sentinel. Blank Company is provider not evidenced, not cash. Blank/unknown InsType is an exception. Scheme blank/`-` are Scheme-only absence; Plan has blank absence only and a hyphen remains substantive protected evidence. Blank MemberNo is membership number not evidenced, not missing provider. Zero IssueDate/ExpiryDate is unknown only for that date. Blank BillStatus is no payer evidence; CASH AND CARRY is payer class self, not a provider sentinel. Patient quarantine and orphan tokens preserve distinct chains.

No rule propagates globally and no artificial patient/provider is created.
