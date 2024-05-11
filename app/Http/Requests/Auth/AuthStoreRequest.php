<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class AuthStoreRequest extends FormRequest
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
    // public function rules(): array
    // {
    //     return [
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|max:255|email',
    //         'password' => 'max:255'
    //     ];
    // }

    // public function messages()
    // {
    //     return [
    //         'name.required' => __('The name field is required.'),
    //         'name.*.max' => __('The maximum number of characters is 255.'),
    //         'email.required' => __('The email field is mandatory.'),
    //         'email.email' => __('The email is not valid.'),
    //         'email.*.max' => __('The maximum number of characters is 255.'),
    //     ];
    // }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|max:255|email|unique:users,email',
            'cpf' => 'max:14|unique:users,cpf',
            'password' => 'max:255'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('O campo nome é obrigatório.'),
            'name.*.max' => __('O número máximo de caracteres é 255.'),
            'email.required' => __('O campo de e-mail é obrigatório.'),
            'email.email' => __('O e-mail não é válido.'),
            'email.*.max' => __('O número máximo de caracteres é 255.'),
            'email.unique' => __('Este e-mail já está em uso.'),
            'cpf.required' => __('O campo CPF é obrigatório.'),
            'cpf.*.max' => __('O número máximo de caracteres é 14.'),
            'cpf.unique' => __('Este CPF já está em uso.'),
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
