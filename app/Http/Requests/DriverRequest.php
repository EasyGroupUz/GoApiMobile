<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DriverRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    // public function authorize(): bool
    // {
    //     return false;
    // }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'phone_number' => 'required|string|max:255',
            // 'avatar' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|integer',
            'status_id' => 'nullable|integer',
            'serial_number' => 'nullable|string|max:255',
            'issued_by' => 'nullable|string|max:255',
            'passport_expired_date' => 'nullable|date',
            'license_number' => 'nullable|string|max:255',
            'license_expired_date' => 'nullable|date',
            'personal_account' => 'nullable|integer',
        ];
    }
}
