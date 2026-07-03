<?php

namespace App\Http\Requests\Consultations;

use Illuminate\Foundation\Http\FormRequest;

class CompleteConsultationRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

