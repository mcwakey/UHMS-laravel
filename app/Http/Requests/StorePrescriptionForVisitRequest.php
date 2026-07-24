<?php

namespace App\Http\Requests;

class StorePrescriptionForVisitRequest extends StorePrescriptionRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'visit_id' => ['required', 'integer', 'exists:visits,id'],
        ]);
    }
}
