<?php

/*
| Department type labels, keyed by the App\Enums\DepartmentType value.
| Resolved via DepartmentType::translatedLabel() → departments.types.{value}.
*/

return [
    'switch_department' => 'Switch Department',
    'current_department' => 'Current Department',
    'primary_department' => 'Primary Department',
    'available_departments' => 'Available Departments',

    'types' => [
        'consultation' => 'Consultation',
        'emergency' => 'Emergency',
        'investigation' => 'Investigation',
        'radiology' => 'Radiology',
        'procedure' => 'Procedure',
        'theatre' => 'Theatre',
        'treatment' => 'Treatment',
        'nursing' => 'Nursing',
        'pharmacy' => 'Pharmacy',
        'inpatient' => 'Inpatient',
        'maternity' => 'Maternity',
        'blood_bank' => 'Blood Bank',
        'mortuary' => 'Mortuary',
        'ambulance' => 'Ambulance',
        'records' => 'Records',
        'finance' => 'Finance',
        'stores' => 'Stores',
        'support' => 'Support',
        'administrative' => 'Administrative',
    ],

    // Department-type dashboard names (keyed by DepartmentType value).
    'dashboards' => [
        'consultation' => ['name' => 'Consultation Dashboard'],
        'emergency' => ['name' => 'Emergency Dashboard'],
        'investigation' => ['name' => 'Investigation Dashboard'],
        'radiology' => ['name' => 'Radiology Dashboard'],
        'procedure' => ['name' => 'Procedure Dashboard'],
        'theatre' => ['name' => 'Theatre Dashboard'],
        'treatment' => ['name' => 'Treatment Dashboard'],
        'nursing' => ['name' => 'Nursing Dashboard'],
        'pharmacy' => ['name' => 'Pharmacy Dashboard'],
        'inpatient' => ['name' => 'Inpatient Dashboard'],
        'maternity' => ['name' => 'Maternity Dashboard'],
        'blood_bank' => ['name' => 'Blood Bank Dashboard'],
        'mortuary' => ['name' => 'Mortuary Dashboard'],
        'ambulance' => ['name' => 'Ambulance Dashboard'],
        'records' => ['name' => 'Records Dashboard'],
        'finance' => ['name' => 'Finance Dashboard'],
        'stores' => ['name' => 'Stores Dashboard'],
        'support' => ['name' => 'Support Dashboard'],
        'administrative' => ['name' => 'Administrative Dashboard'],
        'generic' => ['name' => 'Department Dashboard'],
    ],

    // Personalisation strings (department name injected at runtime).
    'dashboard' => [
        'subtitle' => ':department · :type',
        'welcome_user' => 'Welcome, :name',
        'welcome_to_department' => 'Welcome to :department',
        'scoped_to_department_name' => 'Showing data scoped to :department only',
        'viewing_department_data_only' => 'You are viewing :department data only.',
        'viewing_as_department' => 'Viewing as :department',
        'global_preview_mode' => 'Global Preview',
        'no_department_assigned_dashboard' => 'No department assigned — showing a general view.',
    ],

    // Personalised menu / dashboard headings (:department injected).
    'menu_profiles' => [
        'workbench' => ':department Workbench',
        'command_center' => ':department Command Center',
        'operations' => ':department Operations',
        'control_room' => ':department Control Room',
        'inventory' => ':department Inventory',
        'records_office' => ':department Office',
        'generic' => ':department Dashboard',
    ],

    // Layout family labels.
    'families' => [
        'clinical_queue' => 'Clinical Queue',
        'emergency_command' => 'Emergency Command',
        'diagnostic_workbench' => 'Diagnostic Workbench',
        'imaging_workbench' => 'Imaging Workbench',
        'surgery_board' => 'Surgery Board',
        'ward_board' => 'Ward Board',
        'dispensing_stock' => 'Dispensing & Stock',
        'finance_control' => 'Finance Control',
        'stores_inventory' => 'Stores Inventory',
        'records_office' => 'Records Office',
        'generic_department' => 'Department',
    ],

    // Layout section titles.
    'sections' => [
        'samples' => 'Samples',
        'imaging_schedule' => 'Imaging Schedule',
        'dispensing_queue' => 'Dispensing Queue',
        'bed_occupancy' => 'Bed Occupancy',
        'triage_status' => 'Triage Status',
        'surgery_schedule' => 'Surgery Schedule',
        'stock_movements' => 'Stock Movements',
        'folder_requests' => 'Folder Requests',
        'cashier_sessions' => 'Cashier Sessions',
        'priority_alerts' => 'Priority Alerts',
        'rapid_actions' => 'Rapid Actions',
    ],
];
