<?php

namespace App\Http\Requests\Owner;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class OwnerUpdateRequest extends FormRequest
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
            'name' => 'string|max:100',
            'cpf' => 'max:15|string|unique:users,cpf',
            'phone' => 'max:15',
            'email' => 'required|max:45|email',
            'address_id' => 'numeric'

        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('The name field is required.'),
            'name.*.max' => __('The maximum number of characters is 100.'),
            'address_id.numeric' => __('This field expects to receive an integer.'),
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
