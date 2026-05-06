<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default verification driver
    |--------------------------------------------------------------------------
    | Used when an insurance provider has no verification_driver column value.
    */
    'default' => env('INSURANCE_VERIFICATION_DEFAULT', 'manual'),

    /*
    |--------------------------------------------------------------------------
    | Available drivers
    |--------------------------------------------------------------------------
    | Add new verification *styles* here. Each maps a key to a class that
    | implements App\Contracts\InsuranceVerificationDriver. NEVER add provider-
    | specific names ("nhia", "acme") here — providers pick a *style* via the
    | insurance_providers.verification_driver column.
    */
    'drivers' => [
        'manual' => App\Services\Insurance\Verification\Drivers\ManualVerificationDriver::class,
        'code'   => App\Services\Insurance\Verification\Drivers\CodeVerificationDriver::class,
        'api'    => App\Services\Insurance\Verification\Drivers\ApiVerificationDriver::class,
    ],
];
