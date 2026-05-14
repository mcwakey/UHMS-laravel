<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Allow direct department-to-department stock transfers
    |--------------------------------------------------------------------------
    |
    | When false (default), every product stock transfer must include the
    | Main Store on at least one side. Set to true to allow direct moves
    | between department stock locations.
    */
    'allow_inter_department_transfers' => env('STOCK_ALLOW_INTERDEPT_TRANSFER', false),
];
