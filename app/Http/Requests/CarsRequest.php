<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CarsRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'car_list_id'=>'required|integer',
            'driver_id'=>'required|integer',
            'status_id'=>'required|integer',
            'color_list_id'=>'required|integer',
            'class_list_id'=>'required|integer',
            'reg_certificate'=>'required|string',
            'reg_certificate_image'=>'nullable|mimes:jpeg,jpg,png,webp|max:10240',
            'images'=>'nullable|array',
        ];
    }
}
