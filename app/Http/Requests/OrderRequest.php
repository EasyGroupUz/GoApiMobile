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
            'status_id' => 'nullable|integer',
            'cars_list_id' => 'nullable|integer',
            'from_id' => 'nullable|integer',
            'to_id' => 'nullable|integer',
            'price' => 'nullable|numeric',
            'price_type' => 'nullable|integer',
            'titla' => 'nullable|string',
            'start_date' => 'nullable|date',
            'seats' => 'nullable|array',
        ];
    }
}
