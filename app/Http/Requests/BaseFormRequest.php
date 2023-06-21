<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class BaseFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
//     public function authorize()
//     {
//         return true;
//     }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */

    public function rules()
    {
        if ($this->method() === 'POST') return $this->store();
        if ($this->method() === 'PUT' || $this->method() === 'PATCH') return $this->update();
    }

     /**
     * Get the validation rules that apply to the get request.
     *
     * @return array
     */
    public function view()
    {
        return [
            //
        ];
    }

    /**
     * Get the validation rules that apply to the post request.
     *
     * @return array
     */
    public function store()
    {
        return [
            //
        ];
    }

    /**
     * Get the validation rules that apply to the put/patch request.
     *
     * @return array
     */
    public function update()
    {
        return [
            //
        ];
    }

    /**
     * Get the validation rules that apply to the delete request.
     *
     * @return array
     */
    public function destroy()
    {
        return [
            //
        ];
    }


    protected function failedValidation(Validator $validator)
    {
        if($this->wantsJson())
        {
            $response = response()->json([
                'success' => false,
                'message' => 'Ops! Some errors occurred',
                'errors' => $validator->errors()
            ]);
        }

        $response = back()->with('error', __('locale.ops'))->withErrors($validator)->withInput();

        throw (new ValidationException($validator, $response))
            ->errorBag($this->errorBag)
            ->redirectTo($this->getRedirectUrl());
    }


    //boshqa error message chiqarish uchun
    // public function messages()
    // {
    //     return [
    //         'name.required' => 'A name is required',
    //         'body.required'  => 'A message is required',
    //     ];
    // }

    // public function messages()
    // {
    //     if(request()->isMethod('post')) {
    //         return [
    //             'name.required' => 'Name is required!',
    //             'image.required' => 'Image is required!',
    //             'description.required' => 'Descritpion is required!'
    //         ];
    //     } else {
    //         return [
    //             'name.required' => 'Name is required!',
    //             'description.required' => 'Descritpion is required!'
    //         ];
    //     }
    // }

    // 'message' =>$validator->errors()->first(), bu faqat bitta ustunni errorini qaytaradi
    //  pastdagi funksiya ichiga yoziladi
//    public function failedValidation(Validator $validator)
//    {
//
//        throw new HttpResponseException(response()->json([
//            'success' => false,
//            'message' =>$validator->errors(),
//            'data' => null,
//        ], 422));
//
//    }

//    protected function failedValidation(Validator $validator)
//    {
//        if($this->wantsJson())
//        {
//            $response = response()->json([
//                'success' => false,
//                'message' => 'Ops! Some errors occurred',
//                'errors' => $validator->errors()
//            ]);
//        }else{
//            $response = redirect()
//                ->route('guest.login')
//                ->with('message', 'Ops! Some errors occurred')
//                ->withErrors($validator);
//        }
//
//        throw (new ValidationException($validator, $response))
//            ->errorBag($this->errorBag)
//            ->redirectTo($this->getRedirectUrl());
//    }
    // public function destroy()
    // {
    //     return [
    //         'id' => 'required|integer|exists:App\Models\Category,id'
    //     ];
    // }


    // bu php8 da ishlaydi match funksiya php8 da qo'shilgan
    // public function rules()
    // {
    //     return match($this->method()){
    //         'POST' => $this->store(),
    //         'PUT', 'PATCH' => $this->update(),
    //         'DELETE' => $this->destroy(),
    //         default => $this->view()
    //     }

    // }

    // public function rules()
    // {
    //     $result_method = '';
    //     if ($this->method() === 'POST') $result_method = $this->store();
    //     if ($this->method() === 'PUT' || $this->method() === 'PATCH') $result_method = $this->update();
    //     if ($this->method() === 'DELETE') $result_method = $this->destroy();
    //     // return [
    //     //     'name' => 'string|required|max:255',
    //     // ] + $result_method;
    //     return $result_method;
    // }

    // GET|HEAD this->method() === 'GET' || this->method() === 'HEAD'
//    public function rules()
//    {
//        if ($this->method() === 'POST') return $this->store();
//        if ($this->method() === 'PUT' || $this->method() === 'PATCH') return $this->update();
//        // if ($this->method() === 'GET') return $this->view();
//        // if ($this->method() === 'DELETE') return $this->destroy();
//    }



}
