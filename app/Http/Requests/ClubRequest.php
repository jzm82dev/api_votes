<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ClubRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // data
            'name' => 'required|max:191',
            'mobile' => 'required|max:50',
            'email' => 'required|max:191',
            // additional data
            /*
            'address' => 'required|max:191',
            'address_2' => 'max:191',
            'city' => 'required|max:50',
            'province' => 'required|max:50',
            'postal_code' => 'required|max:191',
            */
        ];
    }


   
    public function failedValidation(Validator $validator){

        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Validation errors',
            'data'      => $validator->errors()
        ]));

    }

    
    public function messages(){

        return [
            //'title.required' => 'Title is required',
            'imagen.required' => 'Avatar is required',
            'imagen.max:2' => 'El tamaño de la imagen'
        ];

    }
    

}
