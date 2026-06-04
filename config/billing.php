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
];
