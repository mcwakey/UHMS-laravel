<?php

namespace App\Http\Requests\Consultations;

use Illuminate\Foundation\Http\FormRequest;

class CancelConsultationRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

