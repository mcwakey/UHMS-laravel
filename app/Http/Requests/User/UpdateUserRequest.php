<?php

namespace App\Http\Requests\User;

use App\Enums\Gender;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('users.edit');
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', Password::defaults()],
            'gender' => ['nullable', new Enum(Gender::class)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'status' => ['nullable', new Enum(UserStatus::class)],
            'employee_id' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($userId)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
