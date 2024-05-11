<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class AuthLoginRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    #[ArrayShape(['email' => "string", 'password' => "string"])] public function rules(): array
    {
        return [
            'email' => 'required|max:255|email',
            'password' => 'required|max:255'
        ];
    }

    public function messages()
    {
        return [
            'email.required' => __('Email is required.'),
            'email.email' => __('Email must be a valid email address.'),
            'password.required' => __('Password is required.'),
        ];
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

