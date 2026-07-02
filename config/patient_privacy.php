<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Patient sensitive field registry
    |--------------------------------------------------------------------------
    |
    | Levels:
    | 0 = safe operational identifiers
    | 1 = operational demographics
    | 2 = personally identifiable information
    | 3 = highly sensitive clinical/confidential information
    |
    */
    'fields' => [
        'id' => ['level' => 0, 'permission' => null, 'edit_permission' => null, 'mask' => 'none'],
        'patient_number' => ['level' => 0, 'permission' => null, 'edit_permission' => null, 'mask' => 'none'],
        'age' => ['level' => 0, 'permission' => null, 'edit_permission' => null, 'mask' => 'none'],
        'gender' => ['level' => 0, 'permission' => null, 'edit_permission' => null, 'mask' => 'none'],
        'date_of_birth' => ['level' => 0, 'permission' => null, 'edit_permission' => null, 'mask' => 'date'],

        'nationality' => ['level' => 1, 'permission' => null, 'edit_permission' => null, 'mask' => 'none'],
        'occupation' => ['level' => 1, 'permission' => null, 'edit_permission' => null, 'mask' => 'none'],
        'marital_status' => ['level' => 1, 'permission' => null, 'edit_permission' => null, 'mask' => 'none'],
        'religion' => ['level' => 1, 'permission' => null, 'edit_permission' => null, 'mask' => 'none'],
        'language' => ['level' => 1, 'permission' => null, 'edit_permission' => null, 'mask' => 'none'],

        'phone' => ['level' => 2, 'permission' => 'patients.contact.view', 'edit_permission' => 'patients.contact.edit', 'create_capture' => true, 'mask' => 'phone'],
        'phone_secondary' => ['level' => 2, 'permission' => 'patients.contact.view', 'edit_permission' => 'patients.contact.edit', 'create_capture' => true, 'mask' => 'phone'],
        'email' => ['level' => 2, 'permission' => 'patients.contact.view', 'edit_permission' => 'patients.contact.edit', 'create_capture' => true, 'mask' => 'email'],
        'address' => ['level' => 2, 'permission' => 'patients.address.view', 'edit_permission' => 'patients.address.edit', 'mask' => 'hidden'],
        'city' => ['level' => 1, 'permission' => null, 'edit_permission' => null, 'mask' => 'none'],
        'town' => ['level' => 1, 'permission' => null, 'edit_permission' => null, 'mask' => 'none'],
        'region' => ['level' => 1, 'permission' => null, 'edit_permission' => null, 'mask' => 'none'],
        'digital_address' => ['level' => 2, 'permission' => 'patients.address.view', 'edit_permission' => 'patients.address.edit', 'mask' => 'hidden'],
        'ghana_card_number' => ['level' => 2, 'permission' => 'patients.identity.view', 'edit_permission' => 'patients.identity.edit', 'mask' => 'identifier'],
        'passport_number' => ['level' => 2, 'permission' => 'patients.identity.view', 'edit_permission' => 'patients.identity.edit', 'mask' => 'identifier'],
        'driving_license_number' => ['level' => 2, 'permission' => 'patients.identity.view', 'edit_permission' => 'patients.identity.edit', 'mask' => 'identifier'],
        'voter_id_number' => ['level' => 2, 'permission' => 'patients.identity.view', 'edit_permission' => 'patients.identity.edit', 'mask' => 'identifier'],

        'membership_number' => ['level' => 2, 'permission' => 'patients.insurance.view', 'edit_permission' => 'patients.insurance.edit', 'mask' => 'identifier'],
        'policy_number' => ['level' => 2, 'permission' => 'patients.insurance.view', 'edit_permission' => 'patients.insurance.edit', 'mask' => 'identifier'],
        'ccc_code' => ['level' => 2, 'permission' => 'patients.insurance.view', 'edit_permission' => 'patients.insurance.edit', 'mask' => 'identifier'],
        'emergency_contact_phone' => ['level' => 2, 'permission' => 'patients.emergency_contact.view', 'edit_permission' => 'patients.emergency_contact.edit', 'mask' => 'phone'],
        'emergency_contact_address' => ['level' => 2, 'permission' => 'patients.emergency_contact.view', 'edit_permission' => 'patients.emergency_contact.edit', 'mask' => 'hidden'],

        'allergies' => ['level' => 3, 'permission' => 'patients.clinical_sensitive.view', 'edit_permission' => 'patients.clinical_sensitive.edit', 'mask' => 'hidden'],
        'chronic_conditions' => ['level' => 3, 'permission' => 'patients.clinical_sensitive.view', 'edit_permission' => 'patients.clinical_sensitive.edit', 'mask' => 'hidden'],
        'confidential_clinical_notes' => ['level' => 3, 'permission' => 'patients.clinical_sensitive.view', 'edit_permission' => 'patients.clinical_sensitive.edit', 'mask' => 'hidden'],
        'mental_health_markers' => ['level' => 3, 'permission' => 'patients.clinical_sensitive.view', 'edit_permission' => 'patients.clinical_sensitive.edit', 'mask' => 'hidden'],
        'sexual_health_markers' => ['level' => 3, 'permission' => 'patients.clinical_sensitive.view', 'edit_permission' => 'patients.clinical_sensitive.edit', 'mask' => 'hidden'],
        'hiv_indicators' => ['level' => 3, 'permission' => 'patients.clinical_sensitive.view', 'edit_permission' => 'patients.clinical_sensitive.edit', 'mask' => 'hidden'],
        'child_protection_flags' => ['level' => 3, 'permission' => 'patients.clinical_sensitive.view', 'edit_permission' => 'patients.clinical_sensitive.edit', 'mask' => 'hidden'],
        'court_restrictions' => ['level' => 3, 'permission' => 'patients.clinical_sensitive.view', 'edit_permission' => 'patients.clinical_sensitive.edit', 'mask' => 'hidden'],
    ],

    'aggregate_permissions' => [
        2 => 'patients.pii.view',
        3 => 'patients.clinical_sensitive.view',
    ],

    'aggregate_edit_permissions' => [
        2 => 'patients.pii.edit',
        3 => 'patients.clinical_sensitive.edit',
    ],

    'permissions' => [
        'patients.pii.view',
        'patients.contact.view',
        'patients.identity.view',
        'patients.address.view',
        'patients.insurance.view',
        'patients.emergency_contact.view',
        'patients.clinical_sensitive.view',
        'patients.export_sensitive.view',
        'patients.pii.edit',
        'patients.contact.edit',
        'patients.identity.edit',
        'patients.address.edit',
        'patients.insurance.edit',
        'patients.emergency_contact.edit',
        'patients.clinical_sensitive.edit',
        'patients.privacy.break_glass',
        'patients.privacy_directives.view',
        'patients.privacy_directives.manage',
        'patients.privacy_audit.view',
    ],

    'break_glass' => [
        'duration_minutes' => 30,
        'view_levels' => [2],
    ],

    'audit' => [
        'profile_view_action' => 'PATIENT_SENSITIVE_PROFILE_VIEWED',
        'pii_action' => 'PATIENT_PII_VIEWED',
        'identity_action' => 'PATIENT_IDENTITY_VIEWED',
        'address_action' => 'PATIENT_ADDRESS_VIEWED',
        'clinical_sensitive_action' => 'PATIENT_CLINICAL_SENSITIVE_VIEWED',
    ],
];
