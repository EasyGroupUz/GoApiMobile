<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function store()
    {
        return [
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'middle_name' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'status_id' => 'nullable|integer',
            'gender' => 'nullable|integer',
            'birth_date' => 'nullable|date',
            'email' => 'required|string|unique:yy_staffs',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|integer',
            'company_id' => 'nullable|integer',
            'avatar' => 'nullable|mimes:jpeg,jpg,png,webp|max:10240',
        ];
    }
    public function update()
    {
        return [
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'middle_name' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'status_id' => 'nullable|integer',
            'gender' => 'nullable|integer',
            'birth_date' => 'nullable|date',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('yy_staffs')->ignore($this->user)],
            'password' => 'nullable|string|min:8|confirmed',
            'role_id' => 'required|integer',
            'company_id' => 'nullable|integer',
            'avatar' => 'nullable|mimes:jpeg,jpg,png,webp|max:10240',
        ];
    }

}
