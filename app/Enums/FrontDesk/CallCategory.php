<?php

namespace App\Enums\FrontDesk;

enum CallCategory: string
{
    case APPOINTMENT_ENQUIRY = 'appointment_enquiry';
    case PATIENT_ADMISSION_ENQUIRY = 'patient_admission_enquiry';
    case BILLING_ENQUIRY = 'billing_enquiry';
    case LAB_RESULT_ENQUIRY = 'lab_result_enquiry';
    case EMERGENCY_CALL = 'emergency_call';
    case COMPLAINT = 'complaint';
    case SUPPLIER_VENDOR = 'supplier_vendor';
    case STAFF_INTERNAL = 'staff_internal';
    case GENERAL_ENQUIRY = 'general_enquiry';
    case WRONG_NUMBER = 'wrong_number';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::APPOINTMENT_ENQUIRY => 'Appointment Enquiry',
            self::PATIENT_ADMISSION_ENQUIRY => 'Patient / Admission Enquiry',
            self::BILLING_ENQUIRY => 'Billing Enquiry',
            self::LAB_RESULT_ENQUIRY => 'Lab Result Enquiry',
            self::EMERGENCY_CALL => 'Emergency Call',
            self::COMPLAINT => 'Complaint',
            self::SUPPLIER_VENDOR => 'Supplier / Vendor',
            self::STAFF_INTERNAL => 'Staff / Internal',
            self::GENERAL_ENQUIRY => 'General Enquiry',
            self::WRONG_NUMBER => 'Wrong Number',
            self::OTHER => 'Other',
        };
    }

    public function translatedLabel(): string
    {
        return __('front_desk.call_category.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::EMERGENCY_CALL => 'danger',
            self::COMPLAINT => 'warning',
            self::BILLING_ENQUIRY => 'warning',
            self::LAB_RESULT_ENQUIRY, self::PATIENT_ADMISSION_ENQUIRY => 'info',
            self::APPOINTMENT_ENQUIRY => 'primary',
            self::SUPPLIER_VENDOR, self::STAFF_INTERNAL => 'secondary',
            default => 'secondary',
        };
    }

    /** @return array<int, self> */
    public static function options(): array
    {
        return self::cases();
    }
}
