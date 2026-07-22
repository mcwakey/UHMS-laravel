# Insurance eligibility boundary

Classic insurance membership and date evidence does not establish eligibility verification. Phase 2E creates no `insurance_verifications` record and assigns no verification status, actor, time, result or visit.

Never use the current user, importer, first user, administrator or Legacy Actor Unknown as verifier. No CCC/API request, notification, event, queue or audit-forwarding action is permitted. Migration execution belongs only in the separate migration audit. If future persistence requires non-null verification facts, that is a Phase 3 target-representation prerequisite—not authority to invent them.
