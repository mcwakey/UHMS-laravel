# Insurance scheme and plan mapping

**Confirmed:** Scheme has 21,444 blank, 1,954 hyphen and 111 substantive distinct values. Plan has 31,299 blank and seven substantive distinct values. Joint rows are 21,443 both blank, 9,856 scheme-only, one plan-only, one equal and six different.

The installed target has no membership scheme/plan destination. Blank and `-` are absence sentinels for Scheme; only blank is evidenced as absence for Plan. A Scheme sentinel never propagates to Plan. Substantive values remain protected source history. Scheme and Plan are never concatenated and never mapped to provider, `insurance_tier_id`, `policy_number`, coverage or eligibility. A later representation needs separate target design and reviewed crosswalk.
