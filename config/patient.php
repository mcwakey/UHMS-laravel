<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Patient ID / Number Generation
    |--------------------------------------------------------------------------
    |
    | Controls how the system generates a unique permanent Patient ID when
    | a patient record is created.
    |
    | Supported pattern placeholders:
    |   {PREFIX}    - the prefix string below
    |   {YEAR}      - 4-digit year  (e.g. 2026)
    |   {YY}        - 2-digit year  (e.g. 26)
    |   {MONTH}     - 2-digit month (e.g. 05)
    |   {DAY}       - 2-digit day   (e.g. 21)
    |   {SEQUENCE}  - zero-padded sequence number
    |
    | Reset periods:
    |   never   - sequence never resets, monotonically increasing
    |   yearly  - resets to 1 on the first registration of each year
    |   monthly - resets to 1 on the first registration of each month
    |   daily   - resets to 1 on the first registration of each day
    |
    */

    'id_prefix'          => env('PATIENT_ID_PREFIX', 'UHMS'),
    'id_pattern'         => env('PATIENT_ID_PATTERN', '{PREFIX}-{YEAR}-{SEQUENCE}'),
    'id_sequence_length' => (int) env('PATIENT_ID_SEQUENCE_LENGTH', 6),
    'id_reset_period'    => env('PATIENT_ID_RESET_PERIOD', 'yearly'),   // never|yearly|monthly|daily
];
