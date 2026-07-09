<?php

return [

    // ── Sample status labels (App\Enums\SampleStatus) ──────────────────
    'status' => [
        'pending'   => 'Awaiting Collection',
        'collected' => 'Collected',
        'received'  => 'Received',
        'rejected'  => 'Rejected',
        'disposed'  => 'Disposed',
    ],

    // ── Specimen type labels (config/specimens.php) ────────────────────
    'specimen' => [
        'whole_blood' => 'Whole Blood',
        'serum'       => 'Serum',
        'plasma'      => 'Plasma',
        'urine'       => 'Urine',
        'stool'       => 'Stool',
        'csf'         => 'CSF',
        'sputum'      => 'Sputum',
        'swab'        => 'Swab',
        'tissue'      => 'Tissue',
        'other'       => 'Other',
    ],

    // ── Request-page panel ─────────────────────────────────────────────
    'panel_title'          => 'Specimens / Samples',
    'generate_btn'         => 'Generate Samples',
    'generate_missing_btn' => 'Generate Missing',
    'none_yet'             => 'No samples generated yet for this request.',
    'sample_col'           => 'Sample',
    'specimen_col'         => 'Specimen',
    'items_col'            => 'Tests',
    'status_col'           => 'Status',
    'chain_col'            => 'Chain of Custody',
    'actions_col'          => 'Actions',
    'unassigned_note'      => ':count test(s) are not yet linked to a sample. Generate samples to include them.',

    // ── Actions & modals ───────────────────────────────────────────────
    'collect_btn'            => 'Collect',
    'collect_title'          => 'Collect Sample',
    'barcode_label'          => 'Barcode / Sample ID',
    'barcode_hint'           => 'Used to match analyzer results back to this sample. Defaults to the sample number.',
    'container_label'        => 'Container / Tube',
    'notes_label'            => 'Notes',
    'cancel_btn'             => 'Cancel',
    'mark_collected_btn'     => 'Mark Collected',
    'receive_btn'            => 'Receive',
    'receive_confirm_title'  => 'Receive this sample?',
    'receive_confirm_text'   => 'Sample :number will be accessioned as received in the lab. Results can then be entered.',
    'reject_btn'             => 'Reject',
    'reject_title'           => 'Reject Sample',
    'reject_hint'            => 'A rejected sample must be re-collected before results can be entered.',
    'reject_confirm_text'    => 'Reject sample :number? A reason is required.',
    'rejection_reason_label' => 'Rejection reason',
    'select_reason'          => '— Select a reason —',
    'rejection_reasons'      => [
        'Haemolysed',
        'Insufficient quantity',
        'Clotted',
        'Wrong container',
        'Mislabelled / unlabelled',
        'Leaked in transit',
        'Contaminated',
        'Expired / delayed transport',
    ],
    'dispose_btn'            => 'Dispose',
    'dispose_confirm_title'  => 'Dispose this sample?',
    'dispose_confirm_text'   => 'Mark sample :number as disposed. This is final.',
    'collect_confirm_title'  => 'Collect this sample?',
    'collect_confirm_text'   => 'Mark sample :number as collected using its default barcode and container.',

    // ── Result gating badges ───────────────────────────────────────────
    'awaiting_sample_badge'  => 'Awaiting sample',
    'awaiting_sample_title'  => 'The specimen for this test has not yet been received in the lab.',

    // ── Queue page ─────────────────────────────────────────────────────
    'queue_title'          => 'Specimen Tracking',
    'queue_subtitle'       => 'Collect, receive, reject and dispose laboratory specimens.',
    'stat_pending'         => 'Awaiting Collection',
    'stat_collected'       => 'Collected',
    'stat_received_today'  => 'Received Today',
    'stat_rejected'        => 'Rejected',
    'search_placeholder'   => 'Search sample #, barcode, patient, request #...',
    'all_status'           => 'All Statuses',
    'all_specimens'        => 'All Specimens',
    'none_found'           => 'No samples found.',
    'view_request'         => 'Open request',

    // ── Test catalogue: default specimen ───────────────────────────────
    'default_specimen_label' => 'Default specimen type',
    'default_specimen_none'  => '— None (defaults at generation) —',
    'default_specimen_hint'  => 'The specimen this test is normally drawn on. Drives how a request is grouped into samples.',

    // ── Controller flash messages ──────────────────────────────────────
    'generated_count'    => ':count sample(s) generated.',
    'collected_success'  => 'Sample :number marked as collected.',
    'received_success'   => 'Sample :number received.',
    'rejected_success'   => 'Sample :number rejected.',
    'disposed_success'   => 'Sample :number disposed.',

    // ── Errors ─────────────────────────────────────────────────────────
    'errors' => [
        'terminal'                  => 'This sample is in a final state and cannot change.',
        'receive_requires_collected'=> 'Only a collected sample can be received.',
        'already_disposed'          => 'This sample has already been disposed.',
        'result_blocked'            => 'Specimen not received: this test\'s sample must be received in the lab before results can be entered.',
    ],

    // ── Activity log / pathway ─────────────────────────────────────────
    'log' => [
        'generated' => 'Generated :count sample(s) for :number',
        'collected' => 'Sample collected: :number',
        'received'  => 'Sample received: :number',
        'rejected'  => 'Sample rejected: :number (:reason)',
        'disposed'  => 'Sample disposed: :number',
    ],
    'pathway' => [
        'collected' => 'Specimen collected',
        'received'  => 'Specimen received',
        'rejected'  => 'Specimen rejected',
    ],
];
