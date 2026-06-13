<?php

return [
    // Page titles / headers
    'lab_analyzers' => 'Lab Analyzers',
    'analyzers' => 'Analyzers',
    'analyzer_detail_title' => ':name — Analyzer Detail',
    'analyzer_diagnostics' => 'Analyzer Diagnostics',
    'message_log' => 'Message Log',
    'message_log_diagnostics' => 'Message Log & Diagnostics',

    // Stats - index
    'total_devices' => 'Total Devices',
    'hl7_devices' => 'HL7 Devices',
    'test_mappings' => 'Test Mappings',

    // Stats - diagnostics
    'received' => 'Received',
    'processing' => 'Processing',
    'processed' => 'Processed',
    'failed' => 'Failed',
    'today_total' => 'Today Total',
    'today_processed' => 'Today Processed',

    // Filters
    'all_analyzers' => 'All Analyzers',
    'all_protocols' => 'All Protocols',
    'duplicate' => 'Duplicate',
    'search_device' => 'Search device...',
    'sample_id_placeholder' => 'Sample ID...',

    // Table headers
    'device' => 'Device',
    'protocol' => 'Protocol',
    'connection' => 'Connection',
    'mappings' => 'Mappings',
    'messages' => 'Messages',
    'last_connected' => 'Last Connected',
    'direction' => 'Direction',
    'sample_id' => 'Sample ID',
    'attempts' => 'Attempts',
    'size' => 'Size',
    'analyzer' => 'Analyzer',

    // Values
    'never' => 'Never',
    'unknown' => 'Unknown',

    // Empty states
    'no_messages_found' => 'No messages found.',
    'no_devices' => 'No analyzer devices configured yet.',
    'no_mappings' => 'No test mappings configured. Add mappings to enable auto-result matching.',
    'no_messages_received' => 'No messages received yet.',

    // View message modal
    'reprocess' => 'Reprocess',
    'raw_message' => 'Raw Message',
    'error' => 'Error',
    'message_content' => 'Message Content',

    // Add / edit analyzer
    'add_analyzer' => 'Add Analyzer',
    'add_analyzer_device' => 'Add Analyzer Device',
    'edit_analyzer_device' => 'Edit Analyzer Device',
    'update_analyzer' => 'Update Analyzer',
    'device_name' => 'Device Name',
    'manufacturer' => 'Manufacturer',
    'model' => 'Model',
    'connection_type' => 'Connection Type',
    'ip_address' => 'IP Address',
    'port' => 'Port',
    'com_port' => 'COM Port',
    'baud_rate' => 'Baud Rate',
    'device_name_placeholder' => 'e.g. Mindray BC-5000',
    'manufacturer_placeholder' => 'e.g. Mindray',
    'model_placeholder' => 'e.g. BC-5000',
    'conn_tcp' => 'TCP/IP',
    'conn_serial' => 'Serial (COM)',
    'delete_analyzer_confirm' => 'Delete this analyzer? This will also remove all its test mappings.',

    // Show - device info
    'device_information' => 'Device Information',
    'created' => 'Created',
    'listener_command' => 'Listener Command',
    'start_tcp_listener' => 'Start the TCP listener for this analyzer:',
    'or_listen_all' => 'Or listen on all active analyzers:',

    // Mappings
    'test_code_mappings' => 'Test Code Mappings',
    'add_mapping' => 'Add Mapping',
    'analyzer_code' => 'Analyzer Code',
    'lab_test' => 'Lab Test',
    'test_code' => 'Test Code',
    'conversion_factor' => 'Conversion Factor',
    'recent_messages' => 'Recent Messages',
    'add_test_mapping' => 'Add Test Mapping',
    'edit_test_mapping' => 'Edit Test Mapping',
    'analyzer_test_code' => 'Analyzer Test Code',
    'analyzer_test_code_placeholder' => 'Code sent by analyzer (e.g. WBC, HGB, PLT)',
    'analyzer_test_code_hint' => "The test code as it appears in the analyzer's output message.",
    'map_to_lab_test' => 'Map to Lab Test',
    'select_lab_test' => '— Select Lab Test —',
    'unit_conversion_factor' => 'Unit Conversion Factor',
    'unit_conversion_hint' => 'Multiply the analyzer value by this factor. Leave as 1.0 if units match.',
    'update_mapping' => 'Update Mapping',
    'remove_mapping_confirm' => 'Remove this mapping?',
];
