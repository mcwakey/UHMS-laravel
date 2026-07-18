<?php

return [
    'workspace' => ['title' => 'Inpatient Workspace'],
    'unauthorized' => 'The Inpatient workspace is only available while an Inpatient department is active.',
    'breadcrumbs' => [
        'inpatient' => 'Inpatient', 'admissions' => 'Admissions', 'wards' => 'Wards', 'beds' => 'Beds',
        'patients' => 'Patients', 'visits' => 'Visits', 'rounds' => 'Clinical Rounds', 'sessions' => 'Inpatient Sessions',
        'vitals' => 'Vital Signs', 'tasks' => 'Nursing Tasks', 'medications' => 'Medications', 'treatments' => 'Treatments',
        'procedures' => 'Procedures', 'investigations' => 'Investigations', 'handoffs' => 'Handoffs', 'transfers' => 'Transfers',
        'discharges' => 'Discharges', 'readmissions' => 'Readmission', 'reports' => 'Reports', 'details' => 'Details', 'create' => 'Create', 'edit' => 'Edit',
    ],
    'menu' => [
        'command' => 'Inpatient Command', 'dashboard' => 'Dashboard', 'active_admissions' => 'Active Admissions',
        'pending_admissions' => 'Pending Admissions', 'discharged' => 'Discharged Today', 'wards_beds' => 'Wards and Beds',
        'ward_overview' => 'Ward Overview', 'bed_availability' => 'Bed Availability', 'patient_care' => 'Patient Care',
        'patients' => 'Admitted Patients', 'visits' => 'Inpatient Visits', 'rounds' => 'Clinical Rounds',
        'sessions' => 'Inpatient Sessions', 'vitals' => 'Vital Signs', 'tasks' => 'Nursing Tasks',
        'medication_services' => 'Medication and Services', 'medications' => 'Medication Administration',
        'treatments' => 'Treatments', 'investigations' => 'Pending Investigations', 'procedures' => 'Procedures',
        'consumables' => 'Ward Consumables', 'coordination' => 'Coordination', 'handoffs' => 'Shift Handoffs',
        'transfers' => 'Transfers', 'discharge' => 'Discharge', 'discharge_readiness' => 'Discharge Readiness',
        'discharges' => 'Completed Discharges', 'readmissions' => 'Readmissions', 'reports' => 'Inpatient Reports', 'inpatient_reports' => 'Admission Report',
        'general' => 'General', 'notifications' => 'Notifications', 'profile' => 'My Profile',
    ],
    'states' => [
        'pending_acceptance' => 'Pending acceptance', 'awaiting_bed' => 'Awaiting bed', 'admitted' => 'Admitted',
        'active' => 'Active', 'on_leave' => 'On leave', 'transfer_pending' => 'Transfer pending',
        'discharge_planned' => 'Discharge planned', 'clearance_pending' => 'Clearance pending',
        'ready_for_discharge' => 'Ready for discharge', 'discharged' => 'Discharged', 'readmitted' => 'Readmitted',
    ],
    'clinical' => [
        'assessment' => 'Nursing assessment', 'care_plan' => 'Care plan', 'intake_output' => 'Intake and output',
        'observation' => 'Patient observation', 'medication_due' => 'Medication due', 'medication_overdue' => 'Medication overdue',
        'treatment_pending' => 'Treatment pending', 'investigation_pending' => 'Investigation pending',
        'session_reopen' => 'Reopen session', 'reopen_reason' => 'Reason for reopening',
    ],
    'empty' => [
        'admissions' => 'No admissions match the selected filters.', 'beds' => 'No beds match the selected filters.',
        'tasks' => 'No inpatient nursing tasks are waiting.', 'medications' => 'No medication administrations are due.',
        'investigations' => 'No investigations are awaiting follow-up.', 'handoffs' => 'No handoffs are waiting.',
    ],
    'actions' => [
        'accept_admission' => 'Accept admission', 'assign_bed' => 'Assign bed', 'transfer' => 'Transfer patient',
        'record_vitals' => 'Record vital signs', 'start_round' => 'Start clinical round', 'complete_task' => 'Complete task',
        'review_clearances' => 'Review clearances', 'discharge' => 'Discharge patient', 'readmit' => 'Readmit patient', 'view_admission' => 'View admission',
    ],
    'readmission' => [
        'title' => 'Readmit Patient', 'confirm' => 'Confirm inpatient readmission',
        'history_notice' => 'The previous discharge, clinical records, invoices, and payments will remain preserved.',
        'previous_discharge' => 'Previous discharge', 'reason' => 'Readmission reason',
        'submit' => 'Confirm readmission', 'success' => 'The patient has been readmitted for continued inpatient care.',
    ],
    'alerts' => [
        'allergy' => 'Allergy warning', 'critical_vitals' => 'Critical vital signs', 'overdue_task' => 'Overdue nursing task',
        'missing_clearance' => 'A required discharge clearance is incomplete.', 'bed_unavailable' => 'The selected bed is no longer available.',
    ],
    'reports' => [
        'admissions' => 'Admission report', 'occupancy' => 'Bed occupancy report', 'census' => 'Ward census report',
        'length_of_stay' => 'Length-of-stay report', 'nursing' => 'Nursing activity report',
        'medication' => 'Medication administration report', 'discharges' => 'Discharge report', 'readmissions' => 'Readmission report',
    ],
];
