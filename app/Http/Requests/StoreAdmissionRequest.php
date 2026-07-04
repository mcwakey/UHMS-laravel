<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ward.admit');
    }

    public function rules(): array
    {
        return [
            'admission_request_id'      => ['nullable', 'exists:admission_requests,id'],
            'visit_id'                  => ['required', 'exists:visits,id'],
            'patient_id'                => ['required', 'exists:patients,id'],
            'bed_id'                    => ['required', 'exists:beds,id'],
            'admission_type'            => ['required', 'in:admission,detention'],
            'admitting_diagnosis'       => ['nullable', 'string', 'max:2000'],
            'admission_date'            => ['nullable', 'date'],
            'expected_discharge_date'   => ['nullable', 'date', 'after:today'],
            'admission_fee_service_id'  => ['nullable', 'exists:service_catalog,id'],
            'consumable_fee_service_id' => ['nullable', 'exists:service_catalog,id'],
            // Invoice line overrides from summary
            'admission_fee_amount'      => ['nullable', 'numeric', 'min:0'],
            'bed_fee_amount'            => ['nullable', 'numeric', 'min:0'],
            'consumable_fee_amount'     => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
