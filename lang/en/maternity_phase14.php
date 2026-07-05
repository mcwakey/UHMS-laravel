<?php

return [
    'maternity_billing' => 'Maternity Billing',
    'billing_preview' => 'Billing Preview',
    'billing_posting_disabled' => 'Posting disabled',
    'billing_preview_no_posting' => 'Preview only. This panel does not create invoices, post charges, recalculate balances, dispense stock, or change the ledger.',
    'billing_event' => 'Billing Event',
    'billing_audit' => 'Billing Audit',
    'duplicate_prevented' => 'Duplicate prevented',
    'ready_to_post' => 'Ready to post',
    'mother_billing' => 'Mother billing',
    'newborn_billing' => 'Newborn billing',
    'newborn_billing_disabled' => 'Newborn billing disabled',
    'newborn_billing_to_mother' => 'Newborn charge will stay on the mother context unless a linked newborn billing policy is enabled.',
    'newborn_billing_if_linked' => 'Bill newborn if a linked newborn patient is available',
    'posting_not_implemented' => 'Posting is reserved for a later controlled manual-posting phase.',

    'billing_preview_statuses' => [
        'missing_mapping' => 'Mapping missing',
        'mapping_disabled' => 'Mapping disabled',
        'service_inactive' => 'Service inactive',
        'already_posted' => 'Already posted',
        'billing_disabled' => 'Billing disabled',
        'newborn_billing_disabled' => 'Newborn billing disabled',
        'ready_to_post' => 'Ready to post',
        'posting_not_implemented' => 'Posting not implemented',
    ],
    'billing_preview_reasons' => [
        'missing_mapping' => 'No active service mapping has been configured for this event.',
        'mapping_disabled' => 'The mapping exists but is disabled.',
        'service_inactive' => 'The mapped service is inactive.',
        'already_posted' => 'A posted maternity billing event or invoice item already exists for this source.',
        'billing_disabled' => 'Maternity billing is disabled by configuration.',
        'newborn_billing_disabled' => 'The newborn billing policy disables newborn charges.',
        'ready_to_post' => 'Mapping and context are ready, but no posting action is exposed in this phase.',
        'posting_not_implemented' => 'Posting will be implemented in a later controlled manual-posting phase.',
    ],
    'newborn_billing_policies' => [
        'mother' => 'Bill newborn care to mother',
        'newborn_if_linked' => 'Bill newborn if linked',
        'disabled' => 'Disable newborn billing',
    ],
];
