<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    // /**
    //  * Determine if the user is authorized to make this request.
    //  */
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
            'from_id' => 'required|integer',
            'to_id' => 'required|integer',
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'car_id' => 'required|integer',
            'seats' => 'required|integer',
            'back_date' => 'nullable|date_format:Y-m-d H:i:s',
            'options' => 'nullable|max:1000',
            // 'options' => 'nullable|json',
            'price' => 'nullable|numeric',
            'price_type' => 'nullable|integer',
            'tarif_id' => 'nullable|integer',
        ];
    }
}
