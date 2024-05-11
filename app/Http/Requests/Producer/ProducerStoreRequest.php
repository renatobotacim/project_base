<?php

namespace App\Http\Requests\Producer;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class ProducerStoreRequest extends FormRequest
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
            'cnpj' => 'required|max:20|string|unique:producers,cnpj',
            'corporative_name' => 'required|string|max:100',
            'state_registration' => 'string|max:45',
            'owner_id' => 'numeric',
            'bank' => 'string|max:45',
            'agency' => 'string|max:20',
            'account' => 'string|max:20',
            'onwer_account' => 'string|max:100'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('The name field is required.'),
            'name.*.max' => __('The maximum number of characters is 100.'),

            'cnpj.required' => __('The cpf field is required.'),
            'cnpj.unique' => __('The cnpj you entered is already registered in our database.'),
            'cnpj.*.max' => __('The maximum number of characters is 15.'),

            'corporative_name.required' => __('The name field is required.'),
            'corporative_name.*.max' => __('The maximum number of characters is 100.'),

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
