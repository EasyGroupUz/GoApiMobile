<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CouponRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function store()
    {
        return [
            'name' => 'required|string|max:255',
            'percent'  => 'required',
        ];
    }

    public function update()
    {
        return [
            'name' => 'required|string|max:255',
            'percent'  => 'required',
        ];
    }
}