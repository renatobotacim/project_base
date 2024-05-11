<?php

namespace App\Http\Requests\Address;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class AddressStoreRequest extends FormRequest
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
            'name' => 'required|string|max:100',
            'road' => 'required|string|max:100',
            'number' => 'numeric',
            'city_id' => 'required|numeric',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('The name field is required.'),
            'name.*.max' => __('The maximum number of characters is 255.'),
            'road.required' => __('The name field is required.'),
            'road.*.max' => __('The maximum number of characters is 255.'),
            'city_id.numeric' => __('This field expects to receive an integer.'),
        ];
    }

    /**
     * @return void
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'message' => __('You are not authorized to perform this action.')
        ], HTTP_RESPONSE::HTTP_UNAUTHORIZED));
    }

    /**
     * @param Validator $validator
     * @return void
     */
    public function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => __('Attention, the information provided has inconsistencies. Check the fields and send again.'),
            'errors' => $validator->errors()
        ], HTTP_RESPONSE::HTTP_BAD_REQUEST));
    }
}
