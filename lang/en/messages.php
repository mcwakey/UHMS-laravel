<?php

return [

    /* ------------------------------------------------------------------ */
    /* Accounting (journal entries, fiscal years, periods, accounts, etc.) */
    /* ------------------------------------------------------------------ */
    'accounting' => [
        'journal_drafted'          => 'Journal entry saved as draft.',
        'journal_updated'          => 'Journal entry updated.',
        'journal_posted'           => 'Journal entry posted.',
        'journal_reversed'         => 'Journal entry reversed.',
        'journal_cancelled'        => 'Draft journal cancelled.',
        'journal_cannot_edit'      => 'Posted, reversed, or cancelled journal entries cannot be edited.',
        'fiscal_year_created'      => 'Fiscal year created.',
        'fiscal_year_closed'       => 'Fiscal year closed.',
        'period_created'           => 'Accounting period created.',
        'period_closed'            => 'Accounting period closed.',
        'account_created'          => 'Account created.',
        'account_updated'          => 'Account updated.',
        'account_disabled'         => 'Account disabled.',
        'account_reactivated'      => 'Account reactivated.',
        'settings_updated'         => 'Accounting settings updated.',
        'posting_retried'          => 'Accounting posting retried successfully (:number).',
        'posting_retry_failed'     => 'Accounting retry did not produce a journal entry. Review the posting status/error on the source record.',
        'supplier_payment_recorded'=> 'Supplier payment :number recorded.',
        'supplier_payment_reversed'=> 'Payment :number reversed.',
    ],

    /* ------------------------------------------------------------------ */
    /* Accounts / Categories                                                */
    /* ------------------------------------------------------------------ */
    'accounts' => [
        'category_created'        => 'Category created successfully.',
        'category_updated'        => 'Category updated successfully.',
        'category_status_updated' => 'Category status updated.',
    ],

    /* ------------------------------------------------------------------ */
    /* Admissions                                                           */
    /* ------------------------------------------------------------------ */
    'admissions' => [
        'admitted'          => 'Patient admitted successfully. Admission #:number',
        'discharged'        => 'Patient discharged successfully.',
        'charge_added'      => 'Service charge added.',
        'vitals_recorded'   => 'Vitals recorded.',
        'ward_round_saved'  => 'Ward round recorded successfully.',
    ],

    /* ------------------------------------------------------------------ */
    /* Analyzers                                                            */
    /* ------------------------------------------------------------------ */
    'analyzers' => [
        'created'               => 'Analyzer device added successfully.',
        'updated'               => 'Analyzer device updated successfully.',
        'deleted'               => 'Analyzer device deleted.',
        'status_changed'        => 'Analyzer :name :status.',
        'cannot_delete_processing' => 'Cannot delete analyzer with messages currently being processed.',
        'message_requeued'      => 'Message #:id queued for reprocessing.',
        'cannot_reprocess'      => 'Only failed or received messages can be reprocessed.',
        'mapping_added'         => 'Test mapping added.',
        'mapping_updated'       => 'Test mapping updated.',
        'mapping_removed'       => 'Test mapping removed.',
    ],

    /* ------------------------------------------------------------------ */
    /* Appointments                                                         */
    /* ------------------------------------------------------------------ */
    'appointments' => [
        'created'           => 'Appointment scheduled successfully.',
        'updated'           => 'Appointment updated successfully.',
        'cancelled'         => 'Appointment cancelled.',
        'no_show'           => 'Appointment marked as no-show.',
        'status_changed'    => 'Appointment status changed to :status.',
        'checked_in'        => 'Patient checked in and visit created successfully.',
        'doctor_conflict'   => 'The selected doctor has a conflicting appointment at that time.',
    ],

    /* ------------------------------------------------------------------ */
    /* Attendance                                                           */
    /* ------------------------------------------------------------------ */
    'attendance' => [
        'recorded' => 'Attendance recorded successfully.',
    ],

    /* ------------------------------------------------------------------ */
    /* Blood bank                                                           */
    /* ------------------------------------------------------------------ */
    'blood_bank' => [
        'crossmatch_verified'      => 'Crossmatch verified.',
        'donation_recorded'        => 'Donation recorded and unit quarantined pending screening.',
        'screening_updated'        => 'Donation screening updated.',
        'screening_result_saved'   => 'Screening test result saved.',
        'screening_verified'       => 'Screening test verified.',
        'donor_registered'         => 'Blood donor registered.',
        'donor_eligibility_saved'  => 'Donor eligibility decision recorded.',
        'donor_questionnaire_saved'=> 'Donor questionnaire saved.',
        'physical_assessment_saved'=> 'Physical assessment recorded.',
        'unit_issued'              => 'Blood unit issued.',
        'transfusion_outcome'      => 'Transfusion outcome recorded.',
        'transfusion_reaction'     => 'Transfusion reaction recorded.',
        'request_created'          => 'Blood request and recipient details created.',
        'request_approved'         => 'Blood request approved.',
        'recipient_updated'        => 'Recipient details updated.',
        'location_added'           => 'Storage location added.',
        'location_updated'         => 'Storage location updated.',
        'location_status_updated'  => 'Storage location :status.',
        'unit_discarded'           => 'Blood unit discarded.',
        'cannot_discard_issued'    => 'Issued or transfused units cannot be discarded.',
    ],

    /* ------------------------------------------------------------------ */
    /* Billing (invoices, payments, credit notes, sponsors, receivables)   */
    /* ------------------------------------------------------------------ */
    'invoices' => [
        'created'            => 'Invoice :number created successfully.',
        'updated'            => 'Invoice :number updated.',
        'cancelled'          => 'Invoice :number has been cancelled.',
        'cannot_edit'        => 'This invoice can no longer be edited.',
        'cannot_cancel'      => 'Cannot cancel a fully paid invoice.',
        'item_not_belong'    => 'Item does not belong to this invoice.',
        'discount_applied'   => 'Discount applied successfully.',
        'discount_removed'   => 'Discount removed successfully.',
        'manual_discount_note' => 'Manual discounts must be applied to invoice items with a reason after invoice creation.',
    ],

    'payments' => [
        'recorded'           => 'Payment :number of :amount recorded successfully.',
        'reversed'           => 'Payment :number reversed (reversal :reversal).',
        'cannot_record'      => 'Cannot record payment on this invoice.',
        'exceeds_balance'    => 'Payment amount exceeds outstanding balance of :balance',
        'open_shift_required'=> 'Open a cashier shift before accepting cash payments.',
    ],

    'billing' => [
        'credit_note_issued'     => ':type :number issued.',
        'credit_note_cancelled'  => 'Credit note :number cancelled.',
        'receivable_reallocated' => 'Payer responsibility reallocated successfully.',
        'sponsor_created'        => 'Sponsor :name created.',
        'sponsor_updated'        => 'Sponsor :name updated.',
        'sponsor_toggled'        => 'Sponsor :name :status.',
    ],

    /* ------------------------------------------------------------------ */
    /* Cashier                                                              */
    /* ------------------------------------------------------------------ */
    'cashier' => [
        'shift_opened'  => 'Shift opened successfully.',
        'shift_closed'  => 'Shift closed. Variance calculated.',
        'shift_verified'=> 'Shift verified.',
    ],

    /* ------------------------------------------------------------------ */
    /* Claims                                                               */
    /* ------------------------------------------------------------------ */
    'claims' => [
        'already_exists'       => 'A claim already exists for this invoice.',
        'ready_for_review'     => 'Insurance claim is ready for review.',
        'no_invoice'           => 'This visit has no invoice to prepare a claim from.',
        'prepared_from_visit'  => 'Insurance claim prepared from visit.',
        'created'              => 'Claim created successfully.',
        'validation_passed'    => 'Claim validation passed.',
        'validation_issues'    => 'Claim validation has required issues.',
        'ready_for_submission' => 'Claim marked ready for submission.',
        'submitted'            => 'Claim submitted.',
        'cannot_review_status' => 'This claim cannot be reviewed in its current status.',
        'review_completed'     => 'Claim review completed.',
        'marked_paid'          => 'Claim marked as paid.',
        'payment_recorded'     => 'Claim payment recorded separately from patient invoice payments.',
        'appealed'             => 'Claim has been appealed and sent for re-review.',
        'item_added'           => 'Item added to claim.',
        'item_removed'         => 'Item removed from claim.',
        'cannot_modify'        => 'Cannot modify items on this claim.',
        'field_updated'        => ':label updated.',
        'items_pending'        => 'Please review all items. :count item(s) still pending.',
    ],

    /* ------------------------------------------------------------------ */
    /* Complaints catalogue                                                 */
    /* ------------------------------------------------------------------ */
    'complaints_catalogue' => [
        'added'          => 'Complaint added to the catalogue.',
        'updated'        => 'Complaint catalogue entry updated.',
        'status_updated' => 'Complaint catalogue status updated.',
    ],

    /* ------------------------------------------------------------------ */
    /* Consultations                                                        */
    /* ------------------------------------------------------------------ */
    'consultations' => [
        'started'                   => 'Consultation started.',
        'route_queued'              => 'Consultation session queued.',
        'route_activated'           => 'Consultation session activated.',
        'route_activated_queued'    => 'Consultation route activated and queued.',
        'route_completed'           => 'Consultation session completed.',
        'route_cancelled'           => 'Consultation session cancelled.',
        'followup_saved'            => 'Next appointment / follow-up saved.',
        'followup_updated'          => 'Next appointment / follow-up updated.',
        'followup_cancelled'        => 'Next appointment / follow-up cancelled.',
        'next_patient_opened'       => 'Next patient opened.',
        'completed_next_opened'     => 'Consultation completed and next patient opened.',
        'complaint_added'           => 'Complaint added.',
        'complaint_updated'         => 'Complaint updated.',
        'complaint_removed'         => 'Complaint removed.',
        'hopc_added'                => 'History of presenting complaint added.',
        'hopc_updated'              => 'History of presenting complaint updated.',
        'hopc_removed'              => 'History of presenting complaint removed.',
        'examination_added'         => 'Examination findings added.',
        'examination_updated'       => 'Examination findings updated.',
        'examination_removed'       => 'Examination findings removed.',
        'diagnosis_added'           => 'Diagnosis added.',
        'diagnosis_updated'         => 'Diagnosis updated.',
        'diagnosis_removed'         => 'Diagnosis removed.',
        'primary_diagnosis_set'     => 'Primary diagnosis set.',
        'treatment_added'           => 'Treatment added.',
        'treatment_updated'         => 'Treatment updated.',
        'treatment_removed'         => 'Treatment removed.',
        'prescription_created'      => 'Prescription :number created and sent to pharmacy.',
        'prescription_updated'      => 'Prescription updated.',
        'prescription_deleted'      => 'Prescription deleted.',
        'prescription_cannot_delete'=> 'Cannot delete a dispensed or cancelled prescription.',
        'investigation_removed'     => 'Investigation removed.',
        'investigation_updated'     => 'Investigation updated.',
        'investigations_added'      => ':count investigation(s) added.',
        'investigations_added_with_request' => ':count investigation(s) added — request :number sent to investigation department.',
        'lab_request_sent'          => 'Investigation request :number sent to :department.',
        'lab_request_updated'       => 'Investigation request updated.',
        'procedure_submitted'       => 'Procedure request submitted (:number).',
        'procedure_updated'         => 'Procedure request updated.',
        'visit_transitioned'        => 'Visit moved to :status.',
        'referred_activated'        => 'Patient sent to :department and session activated.',
        'referred_queued'           => 'Patient queued for :department.',
        'sent_to_investigation'     => 'Patient sent to :department for investigation.',
    ],

    /* ------------------------------------------------------------------ */
    /* Consultation tasks                                                   */
    /* ------------------------------------------------------------------ */
    'consultation_tasks' => [
        'created' => 'Task created.',
        'updated' => 'Task updated.',
        'deleted' => 'Task deleted.',
        'toggled' => 'Task status toggled.',
    ],

    /* ------------------------------------------------------------------ */
    /* Counter sales                                                        */
    /* ------------------------------------------------------------------ */
    'counter_sales' => [
        'created' => 'Counter sale created. Collect payment to complete.',
    ],

    /* ------------------------------------------------------------------ */
    /* Departments                                                          */
    /* ------------------------------------------------------------------ */
    'departments' => [
        'created'       => 'Department created successfully.',
        'updated'       => 'Department updated successfully.',
        'deleted'       => 'Department deleted successfully.',
        'cannot_delete' => 'Cannot delete department with assigned users.',
    ],

    /* ------------------------------------------------------------------ */
    /* Designations                                                         */
    /* ------------------------------------------------------------------ */
    'designations' => [
        'created'       => 'Designation created successfully.',
        'updated'       => 'Designation updated successfully.',
        'deleted'       => 'Designation deleted successfully.',
        'cannot_delete' => 'Cannot delete designation with assigned users.',
    ],

    /* ------------------------------------------------------------------ */
    /* Drugs / pharmacy catalogue                                           */
    /* ------------------------------------------------------------------ */
    'drugs' => [
        'category_created'       => 'Category created successfully.',
        'category_updated'       => 'Category updated successfully.',
        'category_deleted'       => 'Category deleted successfully.',
        'category_cannot_delete' => 'Cannot delete category with existing drugs. Remove or reassign drugs first.',
        'created'                => 'Drug created successfully.',
        'updated'                => 'Drug updated successfully.',
        'toggled'                => 'Drug status toggled.',
        'status_toggled'         => 'Drug status toggled.',
    ],

    /* ------------------------------------------------------------------ */
    /* Emergency                                                            */
    /* ------------------------------------------------------------------ */
    'emergency' => [
        'case_created'             => 'Emergency case :number created.',
        'case_updated'             => 'Emergency case updated.',
        'bay_assigned'             => 'Emergency bay assigned.',
        'bay_created'              => 'Emergency bay created.',
        'ward_bed_updated'         => 'Ward / bed updated.',
        'billing_service_added'    => 'Emergency billable service added.',
        'consumable_recorded'      => 'Emergency consumable recorded.',
        'contact_added'            => 'Emergency contact added.',
        'contact_updated'          => 'Emergency contact updated.',
        'contact_removed'          => 'Emergency contact removed.',
        'disposition_recorded'     => 'Emergency disposition recorded.',
        'case_marked_for_admission'=> 'Emergency case marked for admission. Complete admission placement.',
        'investigation_requested'  => 'Emergency investigation(s) requested.',
        'medication_ordered'       => 'Emergency medication ordered and MAR schedule updated.',
        'note_added'               => 'Emergency note added.',
        'identity_confirmed'       => 'Emergency identity confirmed and merged under :number.',
        'patient_registered'       => 'Emergency patient registered and linked under :number.',
        'procedure_requested'      => 'Emergency procedure(s) requested.',
        'task_added'               => 'Monitoring task added.',
        'task_updated'             => 'Task updated.',
        'triage_recorded'          => 'Emergency triage recorded.',
        'vitals_recorded'          => 'Emergency vitals recorded.',
        'bed_updated'              => 'Ward / bed updated.',
        'billable_added'           => 'Emergency billable service added.',
        'admit_disposition'        => 'Emergency case marked for admission. Complete admission placement.',
        'monitoring_added'         => 'Monitoring task added.',
    ],

    /* ------------------------------------------------------------------ */
    /* Employees                                                            */
    /* ------------------------------------------------------------------ */
    'employees' => [
        'created' => 'Employee created successfully.',
        'updated' => 'Employee updated successfully.',
    ],

    /* ------------------------------------------------------------------ */
    /* Financial entries                                                    */
    /* ------------------------------------------------------------------ */
    'financial_entries' => [
        'recorded' => ':type entry recorded successfully.',
        'approved' => 'Entry approved.',
        'deleted'  => 'Entry deleted.',
    ],

    /* ------------------------------------------------------------------ */
    /* ICD codes                                                            */
    /* ------------------------------------------------------------------ */
    'icd_codes' => [
        'created'        => 'ICD-10 code added successfully.',
        'updated'        => 'ICD-10 code updated successfully.',
        'deleted'        => 'ICD-10 code deleted.',
        'cannot_delete'  => 'Cannot delete: this ICD code is linked to existing diagnoses.',
    ],

    /* ------------------------------------------------------------------ */
    /* Insurance                                                            */
    /* ------------------------------------------------------------------ */
    'insurance' => [
        'provider_created'      => 'Insurance provider created. A Standard tier has been added — configure its limits from the provider dropdown.',
        'provider_updated'      => 'Insurance provider updated successfully.',
        'provider_status'       => 'Provider status updated.',
        'tier_created'          => 'Tier created successfully.',
        'tier_updated'          => 'Tier updated.',
        'tier_deleted'          => 'Tier deleted.',
        'tier_cannot_delete'    => 'Cannot delete tier: patients are currently enrolled on it.',
        'patient_added'         => 'Insurance added to patient.',
        'patient_updated'       => 'Insurance updated.',
        'patient_removed'       => 'Insurance removed.',
        'primary_updated'       => 'Primary insurance updated.',
        'cannot_remove_default' => 'Cannot remove the default Cash & Carry insurance.',
    ],

    /* ------------------------------------------------------------------ */
    /* Insurance providers (used by InsuranceProviderController)           */
    /* ------------------------------------------------------------------ */
    'insurance_providers' => [
        'created'        => 'Insurance provider created. A Standard tier has been added — configure its limits from the provider dropdown.',
        'updated'        => 'Insurance provider updated successfully.',
        'status_updated' => 'Provider status updated.',
    ],

    /* ------------------------------------------------------------------ */
    /* Insurance tiers (used by InsuranceTierController)                   */
    /* ------------------------------------------------------------------ */
    'insurance_tiers' => [
        'created'        => 'Tier created successfully.',
        'updated'        => 'Tier updated.',
        'deleted'        => 'Tier deleted.',
        'cannot_delete'  => 'Cannot delete tier: patients are currently enrolled on it.',
    ],

    /* ------------------------------------------------------------------ */
    /* Patient insurance (used by PatientInsuranceController)              */
    /* ------------------------------------------------------------------ */
    'patient_insurance' => [
        'added'                 => 'Insurance added to patient.',
        'updated'               => 'Insurance updated.',
        'removed'               => 'Insurance removed.',
        'primary_updated'       => 'Primary insurance updated.',
        'cannot_remove_default' => 'Cannot remove the default Cash & Carry insurance.',
    ],

    /* ------------------------------------------------------------------ */
    /* Investigation catalogue                                              */
    /* ------------------------------------------------------------------ */
    'investigation_catalogue' => [
        'consumable_saved'   => 'Consumable saved.',
        'consumable_removed' => 'Consumable removed.',
    ],

    /* ------------------------------------------------------------------ */
    /* Lab requests / results                                               */
    /* ------------------------------------------------------------------ */
    'lab' => [
        'request_accepted'              => 'Lab request accepted and is now ready for result entry.',
        'request_cancelled'             => 'Lab request cancelled.',
        'result_saved'                  => 'Result saved successfully.',
        'results_saved'                 => 'Results saved successfully.',
        'result_verified'               => 'Result verified successfully.',
        'accept_items_first'            => 'Accept and bill investigation items before entering results.',
        'items_accepted'                => 'Accepted :count item(s) (no billable services).',
        'items_accepted_with_invoice'   => 'Accepted :count item(s) — invoice :number generated.',
    ],

    /* ------------------------------------------------------------------ */
    /* Lab tests                                                            */
    /* ------------------------------------------------------------------ */
    'lab_tests' => [
        'category_created'       => 'Category created successfully.',
        'category_updated'       => 'Category updated successfully.',
        'category_deleted'       => 'Category deleted successfully.',
        'category_cannot_delete' => 'Cannot delete category with existing tests. Remove or reassign tests first.',
        'created'                => 'Lab test created successfully.',
        'updated'                => 'Lab test updated successfully.',
        'toggled'                => 'Lab test status toggled.',
        'status_toggled'         => 'Lab test status toggled.',
    ],

    /* ------------------------------------------------------------------ */
    /* Leave                                                                */
    /* ------------------------------------------------------------------ */
    'leave' => [
        'submitted' => 'Leave request submitted successfully.',
        'approved'  => 'Leave request approved.',
        'rejected'  => 'Leave request rejected.',
    ],

    /* ------------------------------------------------------------------ */
    /* Logs                                                                 */
    /* ------------------------------------------------------------------ */
    'logs' => [
        'retention_updated' => 'Retention overrides updated.',
    ],

    /* ------------------------------------------------------------------ */
    /* Log retention (used by LogRetentionController)                      */
    /* ------------------------------------------------------------------ */
    'log_retention' => [
        'updated' => 'Retention overrides updated.',
    ],

    /* ------------------------------------------------------------------ */
    /* Medical patterns                                                     */
    /* ------------------------------------------------------------------ */
    'patterns' => [
        'created'                => 'Pattern ":name" created.',
        'saved_from_consultation'=> 'Pattern ":name" saved from consultation.',
        'updated'                => 'Pattern ":name" updated.',
        'toggled'                => 'Pattern ":name" :status.',
        'deleted'                => 'Pattern ":name" deleted.',
        'applied'                => 'Pattern ":name" applied successfully.',
    ],

    /* ------------------------------------------------------------------ */
    /* Pharmacy dispensing                                                  */
    /* ------------------------------------------------------------------ */
    'pharmacy' => [
        'item_dispensed'  => 'Item dispensed successfully.',
        'items_dispensed' => 'Items dispensed successfully.',
    ],

    /* ------------------------------------------------------------------ */
    /* Prescriptions                                                        */
    /* ------------------------------------------------------------------ */
    'prescriptions' => [
        'cancelled' => 'Prescription :number cancelled.',
        'billed'    => ':count item(s) billed. Collect payment, then dispense at the pharmacy.',
    ],

    /* ------------------------------------------------------------------ */
    /* Theatre                                                              */
    /* ------------------------------------------------------------------ */
    'theatre' => [
        'room_created'              => 'Theatre room created.',
        'room_updated'              => 'Theatre room updated.',
        'room_status_updated'       => 'Theatre room status updated.',
        'room_block_recorded'       => 'Theatre room block recorded.',
        'room_block_removed'        => 'Theatre room block removed.',
        'procedure_submitted'       => 'Procedure request submitted.',
        'procedure_submitted_number'=> 'Procedure request submitted (:number).',
        'report_unavailable'        => 'Full procedure report is only available for completed procedures.',
        'procedure_accepted'        => 'Procedure accepted.',
        'procedure_rejected'        => 'Procedure rejected.',
        'procedure_billed'          => 'Procedure billed on visit invoice.',
        'procedure_scheduled'       => 'Procedure scheduled.',
        'procedure_rescheduled'     => 'Procedure rescheduled.',
        'preop_saved'               => 'Pre-op vitals and checklist saved.',
        'anaesthesia_saved'         => 'Anaesthesia note saved.',
        'surgery_started'           => 'Surgery started.',
        'operative_note_saved'      => 'Operative note saved.',
        'surgery_done'              => 'Surgery marked as done.',
        'postop_saved'              => 'Post-op note saved.',
        'procedure_completed'       => 'Procedure completed.',
        'procedure_cancelled'       => 'Procedure cancelled.',
    ],

    /* ------------------------------------------------------------------ */
    /* MAR / Medication administration                                     */
    /* ------------------------------------------------------------------ */
    'mar' => [
        'recorded'         => 'Medication administration recorded.',
        'prn_recorded'     => 'PRN/SOS medication administration recorded.',
        'corrected'        => 'Administration record corrected with audit log.',
        'order_held'       => 'Medication order held.',
        'order_stopped'    => 'Medication order stopped and future doses cancelled.',
    ],

    /* ------------------------------------------------------------------ */
    /* Medication administration (used by MedicationAdministrationController) */
    /* ------------------------------------------------------------------ */
    'medication_administration' => [
        'recorded'      => 'Medication administration recorded.',
        'prn_recorded'  => 'PRN/SOS medication administration recorded.',
        'corrected'     => 'Administration record corrected with audit log.',
        'order_held'    => 'Medication order held.',
        'order_stopped' => 'Medication order stopped and future doses cancelled.',
    ],

    /* ------------------------------------------------------------------ */
    /* Modules                                                              */
    /* ------------------------------------------------------------------ */
    'modules' => [
        'cache_flushed'           => 'Module cache flushed.',
        'cannot_disable_core'     => 'Cannot disable core module \':name\'.',
        'disable_dependents_first'=> 'Disable dependent modules first: :modules',
        'disabled'                => 'Module \':name\' disabled.',
        'enable_parent_first'     => 'Enable parent module \':name\' first.',
        'enabled'                 => 'Module \':name\' enabled.',
        'cannot_disable'          => 'Cannot disable core module \':name\'.',
        'status_changed'          => ':message',
    ],

    /* ------------------------------------------------------------------ */
    /* Notifications                                                        */
    /* ------------------------------------------------------------------ */
    'notifications' => [
        'broadcast_sent'  => 'Broadcast sent to :count user(s).',
        'preferences_saved' => 'Notification preferences saved.',
    ],

    /* ------------------------------------------------------------------ */
    /* Notification preferences (used by NotificationPreferenceController) */
    /* ------------------------------------------------------------------ */
    'notification_preferences' => [
        'saved' => 'Notification preferences saved.',
    ],

    /* ------------------------------------------------------------------ */
    /* Patients                                                             */
    /* ------------------------------------------------------------------ */
    'patients' => [
        'registered'                   => 'Patient :number registered successfully.',
        'updated'                      => 'Patient updated successfully.',
        'status_changed'               => 'Patient status changed to :status.',
        'marked_deceased'              => ':name has been marked as deceased.',
        'already_deceased'             => 'Patient is already marked as deceased.',
        'cannot_change_deceased_status'=> 'Cannot change the status of a deceased patient.',
        'deceased'                     => ':name has been marked as deceased.',
        'cannot_change_deceased'       => 'Cannot change the status of a deceased patient.',
        'complaint_recorded'           => 'Complaint recorded.',
        'complaint_updated'            => 'Complaint updated.',
        'complaint_removed'            => 'Complaint removed.',
        'merged'                       => 'Patient folders merged successfully.',
        'merge_requested'              => 'Patient merge request created.',
    ],

    /* ------------------------------------------------------------------ */
    /* Patient complaints (used by PatientComplaintController)             */
    /* ------------------------------------------------------------------ */
    'patient_complaints' => [
        'recorded' => 'Complaint recorded.',
        'updated'  => 'Complaint updated.',
        'removed'  => 'Complaint removed.',
    ],

    /* ------------------------------------------------------------------ */
    /* Patient merge (used by PatientMergeController)                      */
    /* ------------------------------------------------------------------ */
    'patient_merge' => [
        'merged'          => 'Patient folders merged successfully.',
        'request_created' => 'Patient merge request created.',
    ],

    /* ------------------------------------------------------------------ */
    /* Payroll                                                              */
    /* ------------------------------------------------------------------ */
    'payroll' => [
        'processed'   => 'Payroll processed for :count employees.',
        'approved'    => ':count payroll records approved.',
        'marked_paid' => ':count payroll records marked as paid.',
        'paid'        => ':count payroll record(s) marked as paid.',
    ],

    /* ------------------------------------------------------------------ */
    /* Permissions                                                          */
    /* ------------------------------------------------------------------ */
    'permissions' => [
        'audit_refreshed' => 'Permissions audit refreshed.',
        'direct_updated'  => 'Direct permissions updated.',
    ],

    /* ------------------------------------------------------------------ */
    /* User permissions (used by UserPermissionController)                 */
    /* ------------------------------------------------------------------ */
    'user_permissions' => [
        'updated' => 'Direct permissions updated.',
    ],

    /* ------------------------------------------------------------------ */
    /* Procedure catalogue                                                  */
    /* ------------------------------------------------------------------ */
    'procedure_catalogue' => [
        'section_added'      => 'Section added.',
        'section_updated'    => 'Section updated.',
        'section_removed'    => 'Section removed.',
        'field_added'        => 'Field added.',
        'field_updated'      => 'Field updated.',
        'field_removed'      => 'Field removed.',
        'consumable_saved'   => 'Consumable saved.',
        'consumable_removed' => 'Consumable removed.',
    ],

    /* ------------------------------------------------------------------ */
    /* Procedures                                                           */
    /* ------------------------------------------------------------------ */
    'procedures' => [
        'created'          => 'Procedure added successfully.',
        'updated'          => 'Procedure updated successfully.',
        'cancelled'        => 'Procedure cancelled.',
        'completed'        => 'Procedure completed.',
        'started'          => 'Procedure started.',
        'scheduled'        => 'Procedure scheduled.',
        'toggled'          => 'Procedure :name :status.',
        'consent_required' => 'This procedure requires signed consent before scheduling.',
        'added'            => 'Procedure added successfully.',
        'status_changed'   => 'Procedure :name :status.',
    ],

    /* ------------------------------------------------------------------ */
    /* Products                                                             */
    /* ------------------------------------------------------------------ */
    'products' => [
        'created'        => 'Product created.',
        'updated'        => 'Product updated.',
        'toggled'        => 'Product status toggled.',
        'status_toggled' => 'Product status toggled.',
    ],

    /* ------------------------------------------------------------------ */
    /* Product pricing                                                      */
    /* ------------------------------------------------------------------ */
    'product_pricing' => [
        'base_updated'          => 'Base pricing updated.',
        'prices_updated'        => 'Prices for ":name" updated.',
        'price_removed'         => 'Price removed.',
        'type_price_added'      => 'Insurance type price added.',
        'type_price_updated'    => 'Insurance type price updated.',
        'type_price_removed'    => 'Insurance type price removed.',
        'provider_price_added'  => 'Provider-specific price added.',
        'provider_price_updated'=> 'Provider price updated.',
        'provider_price_removed'=> 'Provider price removed.',
        'bulk_updated'          => 'Pricing for ":name" updated.',
        'insurance_type_added'  => 'Insurance type price added.',
        'insurance_type_updated'=> 'Insurance type price updated.',
        'insurance_type_removed'=> 'Insurance type price removed.',
        'provider_added'        => 'Provider-specific price added.',
        'provider_updated'      => 'Provider price updated.',
        'provider_removed'      => 'Provider price removed.',
    ],

    /* ------------------------------------------------------------------ */
    /* Stock (StockController)                                              */
    /* ------------------------------------------------------------------ */
    'stock' => [
        'location_created'        => 'Stock location created.',
        'location_updated'        => 'Stock location updated.',
        'adjustment_recorded'     => 'Stock adjustment :number recorded.',
        'return_recorded'         => 'Stock return :number recorded.',
        'transfer_recorded'       => 'Stock transfer :number recorded.',
        'received'                => 'Stock received.',
        'adjusted'                => 'Stock adjusted.',
        'transferred'             => 'Transfer recorded.',
        'returned'                => 'Return recorded.',
        'adjustment_batch'        => 'Stock adjustment :batch recorded.',
        'return_batch'            => 'Stock return :batch recorded.',
        'transfer_batch'          => 'Stock transfer :batch recorded.',
        'location_status'         => 'Status toggled.',
        'cannot_deactivate_main'  => 'Main Store cannot be deactivated.',
        'cannot_edit_main'        => 'Main Store is a protected system location and cannot be edited here.',
        'requisition_submitted'   => 'Stock requisition submitted.',
        'requisition_approved'    => 'Stock requisition approved.',
        'requisition_cancelled'   => 'Stock requisition cancelled.',
        'requisition_issued'      => 'Stock issued from Main Store. Department stock will update after acknowledgement.',
        'requisition_acknowledged'=> 'Department stock acknowledged and updated.',
    ],

    /* ------------------------------------------------------------------ */
    /* Product stock (used by ProductStockController)                      */
    /* ------------------------------------------------------------------ */
    'product_stock' => [
        'received'    => 'Stock received.',
        'adjusted'    => 'Stock adjusted.',
        'transferred' => 'Transfer recorded.',
        'returned'    => 'Return recorded.',
    ],

    /* ------------------------------------------------------------------ */
    /* Stock locations (used by StockLocationController)                   */
    /* ------------------------------------------------------------------ */
    'stock_locations' => [
        'created'              => 'Stock location created.',
        'updated'              => 'Stock location updated.',
        'toggled'              => 'Status toggled.',
        'cannot_edit_main'     => 'Main Store is a protected system location and cannot be edited here.',
        'cannot_deactivate_main' => 'Main Store cannot be deactivated.',
    ],

    /* ------------------------------------------------------------------ */
    /* Stock requisitions (used by StockRequisitionController)             */
    /* ------------------------------------------------------------------ */
    'stock_requisitions' => [
        'submitted'    => 'Stock requisition submitted.',
        'approved'     => 'Stock requisition approved.',
        'issued'       => 'Stock issued from Main Store. Department stock will update after acknowledgement.',
        'acknowledged' => 'Department stock acknowledged and updated.',
        'cancelled'    => 'Stock requisition cancelled.',
    ],

    /* ------------------------------------------------------------------ */
    /* Profile                                                              */
    /* ------------------------------------------------------------------ */
    'profile' => [
        'updated'          => 'Profile updated successfully.',
        'password_updated' => 'Password updated successfully.',
    ],

    /* ------------------------------------------------------------------ */
    /* Purchase orders                                                      */
    /* ------------------------------------------------------------------ */
    'purchase_orders' => [
        'created'        => 'Purchase order created successfully.',
        'submitted'      => 'Purchase order submitted for approval.',
        'approved'       => 'Purchase order approved.',
        'items_received' => 'Items received successfully.',
        'cancelled'      => 'Purchase order cancelled.',
        'item_added'     => 'Item added to purchase order.',
        'item_removed'   => 'Item removed from purchase order.',
        'cannot_modify'  => 'Cannot modify items on this purchase order.',
        'received'       => 'Items received successfully.',
    ],

    /* ------------------------------------------------------------------ */
    /* Purchase returns                                                     */
    /* ------------------------------------------------------------------ */
    'purchase_returns' => [
        'created'   => 'Purchase return created.',
        'approved'  => 'Purchase return approved.',
        'cancelled' => 'Purchase return cancelled.',
        'posted'    => 'Purchase return posted to stock and supplier ledger.',
    ],

    /* ------------------------------------------------------------------ */
    /* Queue                                                                */
    /* ------------------------------------------------------------------ */
    'queue' => [
        'now_serving'  => 'Now serving #:number — :name',
        'requeued'     => 'Patient re-queued as #:number.',
        'completed'    => 'Queue #:number marked as completed.',
        'skipped'      => 'Queue #:number skipped.',
        'none_waiting' => 'No patients waiting in this department queue.',
        're_queued'    => 'Patient re-queued as #:number.',
    ],

    /* ------------------------------------------------------------------ */
    /* Roles                                                                */
    /* ------------------------------------------------------------------ */
    'roles' => [
        'created'              => 'Role created successfully.',
        'updated'              => 'Role updated successfully.',
        'deleted'              => 'Role deleted successfully.',
        'permissions_updated'  => 'Permissions updated successfully.',
        'cannot_delete_assigned' => 'Cannot delete role with assigned users.',
        'cannot_delete_system'   => 'Cannot delete system roles.',
        'cannot_rename_system'   => 'Cannot rename system roles.',
        'cannot_delete_users'    => 'Cannot delete role with assigned users.',
    ],

    /* ------------------------------------------------------------------ */
    /* Service catalogue                                                    */
    /* ------------------------------------------------------------------ */
    'service_catalogue' => [
        'added'          => 'Service added successfully.',
        'updated'        => 'Service updated successfully.',
        'status_changed' => 'Service :name :status.',
        'price_removed'  => 'Price entry removed.',
        'message'        => ':message',
    ],

    /* ------------------------------------------------------------------ */
    /* Service catalog (used by ServiceCatalogController)                  */
    /* ------------------------------------------------------------------ */
    'service_catalog' => [
        'created'        => 'Service added successfully.',
        'updated'        => 'Service updated successfully.',
        'toggled'        => 'Service :name :status.',
        'prices_updated' => 'Prices for ":name" updated.',
        'price_removed'  => 'Price entry removed.',
    ],

    /* ------------------------------------------------------------------ */
    /* Service Catalog                                                      */
    /* (service_renderings key kept for BC; real keys are service_rendering)*/
    /* ------------------------------------------------------------------ */

    /* ------------------------------------------------------------------ */
    /* Settings                                                             */
    /* ------------------------------------------------------------------ */
    'settings' => [
        'organization_updated'   => 'Organization settings updated successfully.',
        'invoice_updated'        => 'Invoice settings updated successfully.',
        'payment_methods_updated'=> 'Payment method settings updated successfully.',
        'ward_updated'           => 'Ward & Admissions settings updated successfully.',
        'organisation_updated'   => 'Organization settings updated successfully.',
        'payment_updated'        => 'Payment method settings updated successfully.',
    ],

    /* ------------------------------------------------------------------ */
    /* Specialties                                                          */
    /* ------------------------------------------------------------------ */
    'specialties' => [
        'created'        => 'Specialty created successfully.',
        'updated'        => 'Specialty updated successfully.',
        'toggled'        => 'Specialty :name :status.',
        'status_changed' => 'Specialty :name :status.',
    ],

    /* ------------------------------------------------------------------ */
    /* Suppliers                                                            */
    /* ------------------------------------------------------------------ */
    'suppliers' => [
        'created'               => 'Supplier created successfully.',
        'updated'               => 'Supplier updated successfully.',
        'status_updated'        => 'Supplier status updated.',
        'ledger_entry_recorded' => 'Ledger entry recorded.',
        'ledger_recorded'       => 'Ledger entry recorded.',
    ],

    /* ------------------------------------------------------------------ */
    /* Triage                                                               */
    /* ------------------------------------------------------------------ */
    'triage' => [
        'not_awaiting'   => 'This visit is not awaiting triage.',
        'not_in_triage'  => 'This visit is not in TRIAGE status.',
        'completed'      => 'Triage completed. Visit moved to :status.',
    ],

    /* ------------------------------------------------------------------ */
    /* Users                                                                */
    /* ------------------------------------------------------------------ */
    'users' => [
        'created'        => 'User created successfully.',
        'updated'        => 'User updated successfully.',
        'status_updated' => 'User status updated.',
    ],

    /* ------------------------------------------------------------------ */
    /* Visits                                                               */
    /* ------------------------------------------------------------------ */
    'visits' => [
        'scheduled'          => 'Visit :number scheduled successfully.',
        'created_waiting'    => 'Visit :number created and patient added to triage queue.',
        'created_no_triage'  => 'Visit :number created. No consultation service selected — triage skipped.',
        'updated'            => 'Visit :number updated.',
        'create_failed'      => 'Failed to create visit. Please try again.',
        'update_failed'      => 'Failed to update visit: :error',
        'sent_to_department' => 'Patient sent to department and queue entry created.',
        'status_updated'     => 'Visit status updated to :status.',
        'cannot_transition'  => 'Cannot transition from :from to :to.',
        'insurance_changed'  => 'Visit insurance changed to :provider. Existing billed items were not changed.',
        'active_insurance_changed' => 'Visit active insurance changed',
        'active_insurance_changed_future_items' => 'Visit active insurance changed for future billed items only.',
        'insurance_already_set' => 'Visit insurance is already set to the selected option.',
        'failed_create'      => 'Failed to create visit. Please try again.',
        'failed_update'      => 'Failed to update visit: :error',
        'invalid_transition' => 'Cannot transition from :from to :to.',
        'insurance_unchanged'=> 'Visit insurance is already set to the selected option.',
    ],

    /* ------------------------------------------------------------------ */
    /* Vitals                                                               */
    /* ------------------------------------------------------------------ */
    'vitals' => [
        'recorded_triage'       => 'Vitals recorded for :name. Please direct patient to a consultation department.',
        'recorded'              => 'Vitals recorded for :name.',
        'assigned_consultation' => 'Patient assigned to consultation queue.',
        'priority_updated'      => 'Priority updated to :priority.',
        'recorded_with_queue'   => 'Vitals recorded for :name. Please direct patient to a consultation department.',
        'assigned_to_queue'     => 'Patient assigned to consultation queue.',
    ],

    /* ------------------------------------------------------------------ */
    /* Wards                                                                */
    /* ------------------------------------------------------------------ */
    'wards' => [
        'created'        => 'Ward \':name\' created successfully.',
        'updated'        => 'Ward \':name\' updated successfully.',
        'toggled'        => 'Ward \':name\' :status.',
        'bed_created'    => 'Bed created successfully.',
        'bed_updated'    => 'Bed updated successfully.',
        'status_changed' => 'Ward \':name\' :status.',
    ],

    /* ------------------------------------------------------------------ */
    /* Service Renderings (BC alias — canonical key is service_rendering)  */
    /* ------------------------------------------------------------------ */
    'service_renderings' => [
        'billed'        => 'Billed ":name" to :visit. A rendering task was created for the department.',
        'started'       => 'Service rendering started.',
        'rendered'      => 'Service marked as rendered.',
        'not_rendered'  => 'Service marked as not rendered.',
        'cancelled'     => 'Service rendering cancelled.',
        'notes_updated' => 'Rendering notes updated.',
    ],

    /* ------------------------------------------------------------------ */
    /* Service Rendering (billed via ServiceRenderingController)            */
    /* ------------------------------------------------------------------ */
    'service_rendering' => [
        'billed'        => 'Billed ":service" to :visit. A rendering task was created for the department.',
        'started'       => 'Service rendering started.',
        'rendered'      => 'Service marked as rendered.',
        'not_rendered'  => 'Service marked as not rendered.',
        'cancelled'     => 'Service rendering cancelled.',
        'notes_updated' => 'Rendering notes updated.',
    ],

    /* ------------------------------------------------------------------ */
    /* Activity logs, statistics, and shared validation                     */
    /* ------------------------------------------------------------------ */
    'activity_logs' => [
        'exported_to_csv' => 'Activity logs exported to CSV',
    ],

    'statistics' => [
        'report_exported_to_csv' => "Statistics report ':report' exported to CSV.",
    ],

    'validation' => [
        'correct_highlighted_fields' => 'Please correct the highlighted fields.',
    ],

];
