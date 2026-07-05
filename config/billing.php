<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Manual discount policy
    |--------------------------------------------------------------------------
    |
    | Users with billing.discount.apply may discount invoice items up to this
    | percentage of the line total. Larger discounts require
    | billing.discount.override_limit and are logged as high-risk actions.
    |
    */
    'discount' => [
        'max_without_override_percent' => env('BILLING_DISCOUNT_MAX_WITHOUT_OVERRIDE_PERCENT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maternity billing posting safeguards
    |--------------------------------------------------------------------------
    |
    | Maternity billing starts in preview-only mode. Posting is deliberately
    | disabled by default so clinical maternity records cannot create charges
    | until mappings, duplicate guards, and site billing policy are approved.
    |
    */
    'maternity_billing' => [
        'enabled' => env('MATERNITY_BILLING_ENABLED', false),
        'auto_post' => env('MATERNITY_BILLING_AUTO_POST', false),
        'newborn_billing_policy' => env('MATERNITY_NEWBORN_BILLING_POLICY', 'mother'),
    ],
];
