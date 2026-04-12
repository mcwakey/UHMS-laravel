<?php

namespace App\Http\Requests;

use App\Enums\BedStatus;
use App\Enums\BedType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreBedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('beds.manage');
    }

    public function rules(): array
    {
        return [
            'ward_id' => ['required', 'exists:wards,id'],
            'bed_number' => ['required', 'string', 'max:20'],
            'bed_type' => ['required', new Enum(BedType::class)],
            'status' => ['nullable', new Enum(BedStatus::class)],
            'daily_rate' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
