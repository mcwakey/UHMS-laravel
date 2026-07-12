<?php

/*
|--------------------------------------------------------------------------
| Payment Timing Operational Cutover (Payment Timing Policy Phase 8)
|--------------------------------------------------------------------------
|
| The environment kill switch. When PAYMENT_TIMING_FORCE_LEGACY is true the
| effective master cutover mode is forced to `disabled` regardless of any
| database setting — an immediate, DB-independent rollback to legacy behaviour.
| It does NOT modify approved arrangements or operation settings.
|
| Precedence: environment force-legacy → master cutover setting → per-operation
| mode → runtime eligibility.
|
| Safe default is false (master mode still defaults to `disabled`, which is
| legacy-equivalent). Set PAYMENT_TIMING_FORCE_LEGACY=true in production until
| go-live, and as the emergency rollback lever.
|
*/

return [
    'force_legacy' => env('PAYMENT_TIMING_FORCE_LEGACY', false),
];
