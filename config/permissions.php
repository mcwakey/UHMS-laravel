<?php

/**
 * Permission metadata.
 *
 * Permission *names* live in `database/seeders/RoleSeeder.php` (canonical list,
 * idempotently created via Spatie's `firstOrCreate`). This file layers
 * additional metadata on top: human-readable description, owning module,
 * and a risk classification used by the audit command and admin UI.
 *
 * Effective risk for a permission is resolved by `App\Support\PermissionMeta`:
 *   1. `risk_overrides[<perm>]`   (explicit override)
 *   2. first matching pattern in `risk_rules` (suffix/regex match)
 *   3. fallback: 'NORMAL'
 *
 * Effective module for a permission is resolved by:
 *   1. `module_overrides[<perm>]` (explicit override)
 *   2. dot-prefix of the permission (e.g. `pharmacy.dispense.create` -> `pharmacy`)
 */

return [

    // ------------------------------------------------------------------
    // Risk levels — used to colour-code the admin UI and to decide which
    // permissions require additional confirmation in the front-end.
    // ------------------------------------------------------------------
    'risk_levels' => [
        'LOW'      => ['label' => 'Low',      'color' => 'success'],
        'NORMAL'   => ['label' => 'Normal',   'color' => 'info'],
        'HIGH'     => ['label' => 'High',     'color' => 'warning'],
        'CRITICAL' => ['label' => 'Critical', 'color' => 'danger'],
    ],

    // ------------------------------------------------------------------
    // Default risk by suffix / pattern. First match wins.
    // ------------------------------------------------------------------
    'risk_rules' => [
        // CRITICAL — irreversible / destructive / system-wide
        '/\.delete$/'                 => 'CRITICAL',
        '/\.merge\.execute$/'         => 'CRITICAL',
        '/\.payment\.reverse$/'       => 'CRITICAL',
        '/\.reverse$/'                => 'CRITICAL',
        '/\.adjust$/'                 => 'CRITICAL',
        '/\.correct(_completed)?$/'   => 'CRITICAL',
        '/\.disable$/'                => 'CRITICAL',
        '/\.override.*/'              => 'CRITICAL',
        '/^roles\./'                  => 'CRITICAL',
        '/^permissions\./'            => 'CRITICAL',
        '/^modules\./'                => 'CRITICAL',
        '/^settings\.manage$/'        => 'CRITICAL',
        '/^logs\.(delete|manage_retention)$/' => 'CRITICAL',
        '/^users\.(delete|disable|reset_password)$/' => 'CRITICAL',

        // HIGH — financial / clinical authorisation
        '/\.approve$/'                => 'HIGH',
        '/\.authorize$/'              => 'HIGH',
        '/\.cancel$/'                 => 'HIGH',
        '/\.execute$/'                => 'HIGH',
        '/\.dispense$/'               => 'HIGH',
        '/\.administer$/'             => 'HIGH',
        '/\.refund$/'                 => 'HIGH',
        '/\.write_off$/'              => 'HIGH',
        '/\.discharge$/'              => 'HIGH',
        '/\.transfer$/'               => 'HIGH',
        '/\.complete$/'               => 'HIGH',
        '/^claims\.submit$/'          => 'HIGH',
        '/^stock\.movements\.create$/'=> 'HIGH',
        '/\.manage$/'                 => 'HIGH',

        // NORMAL — routine writes
        '/\.(create|edit|update|store)$/' => 'NORMAL',
        '/\.transition$/'             => 'NORMAL',
        '/\.assign$/'                 => 'NORMAL',
        '/\.request$/'                => 'NORMAL',
        '/\.activate$/'               => 'NORMAL',
        '/\.apply$/'                  => 'NORMAL',
        '/\.switch$/'                 => 'NORMAL',
        '/\.confirm.*/'               => 'NORMAL',

        // LOW — reads
        '/\.view(_|$)/'               => 'LOW',
        '/\.history$/'                => 'LOW',
        '/\.dashboard$/'              => 'LOW',
        '/\.list$/'                   => 'LOW',
        '/\.preview$/'                => 'LOW',
        '/\.access$/'                 => 'LOW',
        '/\.queue$/'                  => 'LOW',
    ],

    // ------------------------------------------------------------------
    // Explicit risk overrides. Use sparingly — only when the rules above
    // don't reflect the operational risk for that specific permission.
    // ------------------------------------------------------------------
    'risk_overrides' => [
        'patients.merge.execute'             => 'CRITICAL',
        'patients.merge.confirm_identity'    => 'HIGH',
        'visits.create_while_admitted'       => 'HIGH',
        'visits.reopen_locked_session'       => 'HIGH',
        'consultation.entries.delete_any'    => 'CRITICAL',
        'consultation.entries.edit_any'      => 'HIGH',
        'consultation.entries.correct_completed' => 'CRITICAL',
        'consultation.followup.cancel'       => 'HIGH',
        'medication_administration.correct'  => 'CRITICAL',
        'pharmacy.dispense.reverse'          => 'CRITICAL',
        'lab.results.amend'                  => 'HIGH',
        'billing.invoice.write_off'          => 'CRITICAL',
        'billing.payment.reverse'            => 'CRITICAL',
        'billing.discount.apply'             => 'HIGH',
        'billing.discount.approve'           => 'HIGH',
        'billing.discount.remove'            => 'CRITICAL',
        'billing.discount.override_limit'    => 'CRITICAL',
        'billing.discount.report'            => 'HIGH',
        'accounting.accounts.disable'        => 'HIGH',
        'accounting.journals.post'           => 'HIGH',
        'accounting.journals.reverse'        => 'CRITICAL',
        'accounting.journals.cancel'         => 'HIGH',
        'accounting.periods.manage'          => 'CRITICAL',
        'accounting.fiscal_years.manage'     => 'CRITICAL',
        'accounting.settings.manage'         => 'CRITICAL',
        'claims.resubmit'                    => 'HIGH',
        'stock.adjustments.create'           => 'HIGH',
        'modules.override_disabled'          => 'CRITICAL',
        'permissions.assign'                 => 'CRITICAL',
        'roles.delete'                       => 'CRITICAL',
        'users.disable'                      => 'CRITICAL',
        'users.reset_password'               => 'CRITICAL',
    ],

    // ------------------------------------------------------------------
    // Module mapping for permissions whose dot-prefix doesn't match the
    // owning module slug (used by the audit command + admin UI grouping).
    // ------------------------------------------------------------------
    'module_overrides' => [
        'consultation.access'        => 'consultation',
        'consultation.dashboard'     => 'consultation',
        'consultation.queue'         => 'consultation',
        'consultations.view'         => 'consultation',
        'consultations.create'       => 'consultation',
        'consultations.edit'         => 'consultation',
        'consultation.followup.create'=> 'consultation',
        'consultation.followup.update'=> 'consultation',
        'consultation.followup.cancel'=> 'consultation',
        'medical_patterns.view'      => 'consultation',
        'medical_patterns.create'    => 'consultation',
        'medical_patterns.update'    => 'consultation',
        'medical_patterns.apply'     => 'consultation',
        'medication_administration.view'    => 'pharmacy',
        'medication_administration.record'  => 'pharmacy',
        'medication_administration.correct' => 'pharmacy',
        'mar.view'                   => 'pharmacy',
        'mar.print'                  => 'pharmacy',
        'icd.view'                   => 'consultation',
        'icd.search'                 => 'consultation',
        'theatre.view'               => 'procedures',
        'analyzer.use'               => 'lab',
        'consumable_usage.view'      => 'pharmacy',
        'consumable_usage.record'    => 'pharmacy',
        'roles.manage'               => 'users',
        'roles.create'               => 'users',
        'roles.update'               => 'users',
        'roles.delete'               => 'users',
        'roles.view'                 => 'users',
        'permissions.view'           => 'users',
        'permissions.assign'         => 'users',
        'permissions.assign_critical'=> 'users',
        'accounting.dashboard.view'  => 'accounting',
        'accounting.accounts.view'   => 'accounting',
        'accounting.accounts.create' => 'accounting',
        'accounting.accounts.edit'   => 'accounting',
        'accounting.accounts.disable'=> 'accounting',
        'accounting.journals.view'   => 'accounting',
        'accounting.journals.create' => 'accounting',
        'accounting.journals.edit'   => 'accounting',
        'accounting.journals.post'   => 'accounting',
        'accounting.journals.reverse'=> 'accounting',
        'accounting.journals.cancel' => 'accounting',
        'accounting.reports.trial_balance' => 'accounting',
        'accounting.reports.general_ledger' => 'accounting',
        'accounting.periods.view'    => 'accounting',
        'accounting.periods.manage'  => 'accounting',
        'accounting.fiscal_years.view' => 'accounting',
        'accounting.fiscal_years.manage' => 'accounting',
        'accounting.settings.view'   => 'accounting',
        'accounting.settings.manage' => 'accounting',
    ],

    // ------------------------------------------------------------------
    // Human-readable descriptions. Keys not present here fall back to a
    // descriptor derived from the permission name (verb + noun).
    // ------------------------------------------------------------------
    'descriptions' => [
        'patients.merge.execute'           => 'Permanently merge two patient folders. Irreversible.',
        'patients.merge.confirm_identity'  => 'Confirm patient identity prior to executing a folder merge.',
        'visits.create_while_admitted'     => 'Create an outpatient visit for a currently admitted patient.',
        'visits.reopen_locked_session'     => 'Reopen a consultation session that has already been locked/completed.',
        'consultation.entries.edit_any'    => 'Edit any clinician\'s consultation entry (not just the author\'s).',
        'consultation.entries.delete_any'  => 'Delete any clinician\'s consultation entry.',
        'consultation.entries.correct_completed' => 'Issue corrections against entries on a completed/locked encounter.',
        'consultation.followup.create'     => 'Create a follow-up appointment directly from a consultation session.',
        'consultation.followup.update'     => 'Update a follow-up appointment created from a consultation session.',
        'consultation.followup.cancel'     => 'Cancel a consultation follow-up appointment with a reason.',
        'appointments.update'              => 'Update appointment date, time, department, doctor, or clinical notes. Compatibility alias for appointments.edit.',
        'appointments.cancel'              => 'Cancel an appointment with a cancellation reason. Compatibility alias for appointments.edit.',
        'medication_administration.correct'=> 'Correct a previously recorded medication administration on the MAR.',
        'pharmacy.dispense.reverse'        => 'Reverse a completed pharmacy dispense (returns stock + adjusts charges).',
        'pharmacy.dispense'                => 'Dispense medication to a patient against a prescription.',
        'lab.results.amend'                => 'Amend a published lab result. Audit-logged.',
        'billing.invoice.write_off'        => 'Write off an invoice as bad debt or charity.',
        'billing.payment.reverse'          => 'Reverse a posted payment (e.g. failed/disputed transaction).',
        'billing.discount.view'            => 'View manual discount history on invoices.',
        'billing.discount.apply'           => 'Allows user to apply a discount to an invoice or invoice item within allowed limits.',
        'billing.discount.approve'         => 'Allows user to approve discounts that require authorization.',
        'billing.discount.override_limit'  => 'Allows user to exceed normal discount limits. High-risk permission.',
        'billing.discount.remove'          => 'Allows user to remove or reverse an applied discount. High-risk permission.',
        'billing.discount.report'          => 'Allows user to view discount reports and discount history.',
        'accounting.dashboard.view'        => 'View the accounting foundation dashboard and ledger balance checks.',
        'accounting.accounts.view'         => 'View the chart of accounts.',
        'accounting.accounts.create'       => 'Create chart of accounts records.',
        'accounting.accounts.edit'         => 'Edit chart of accounts records.',
        'accounting.accounts.disable'      => 'Disable chart of accounts records so they cannot be used in new journals.',
        'accounting.journals.view'         => 'View manual journal entries and their lines.',
        'accounting.journals.create'       => 'Create balanced draft manual journal entries.',
        'accounting.journals.edit'         => 'Edit draft manual journal entries before posting.',
        'accounting.journals.post'         => 'Post balanced manual journal entries into the general ledger.',
        'accounting.journals.reverse'      => 'Reverse a posted journal entry by creating a posted offsetting journal. Critical permission.',
        'accounting.journals.cancel'       => 'Cancel a draft journal entry before it affects the ledger.',
        'accounting.reports.trial_balance' => 'View the accounting trial balance report.',
        'accounting.reports.general_ledger'=> 'View the general ledger report for accounts.',
        'accounting.periods.view'          => 'View accounting periods.',
        'accounting.periods.manage'        => 'Create and close accounting periods. Critical permission.',
        'accounting.fiscal_years.view'     => 'View fiscal years.',
        'accounting.fiscal_years.manage'   => 'Create and close fiscal years. Critical permission.',
        'accounting.settings.view'         => 'View accounting posting and control account settings.',
        'accounting.settings.manage'       => 'Update accounting posting and control account settings. Critical permission.',
        'claims.submit'                    => 'Submit a finalised insurance claim batch to the payer.',
        'claims.resubmit'                  => 'Resubmit a previously rejected insurance claim.',
        'stock.adjustments.create'         => 'Adjust on-hand stock counts (gains/losses, expiry write-offs).',
        'modules.manage'                   => 'Toggle optional modules on/off.',
        'modules.override_disabled'        => 'Bypass the module-disabled gate. For emergencies only.',
        'permissions.view'                 => 'View the global permission catalogue.',
        'permissions.assign'               => 'Assign individual permissions directly to a user (outside their roles).',
        'permissions.assign_critical'      => 'Assign or remove critical permissions on roles or direct user overrides.',
        'roles.view'                       => 'View roles and their assigned permissions.',
        'roles.create'                     => 'Create a new role.',
        'roles.update'                     => 'Update a role\'s name or permission set.',
        'roles.delete'                     => 'Delete a role (only if no users are assigned).',
        'roles.manage'                     => 'Full role management (create / update / delete / assign).',
        'users.view'                       => 'View the user directory.',
        'users.create'                     => 'Create a new user account.',
        'users.update'                     => 'Update a user\'s profile or role assignments.',
        'users.delete'                     => 'Delete a user account.',
        'users.disable'                    => 'Disable (deactivate) a user account without deleting it.',
        'users.reset_password'             => 'Reset another user\'s password.',
        'settings.view'                    => 'View system-wide settings.',
        'settings.manage'                  => 'Modify system-wide settings (facility, fiscal year, integrations, etc.).',
        'logs.view'                        => 'View the application activity / audit log.',
        'logs.delete'                      => 'Delete entries from the audit log. Highly restricted.',
        'logs.manage_retention'            => 'Change audit-log retention policies.',
    ],
];
