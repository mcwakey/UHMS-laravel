<?php

namespace App\Http\Requests\Consultations;

use Illuminate\Foundation\Http\FormRequest;

class TransitionConsultationRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}

