<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class AuthUpdatePasswordRequest extends FormRequest
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
        $user = $this->user();
        return [
            'password' => [
                'required',
                function ($attribute, $value, $fail) use ($user) {
                    if (!Hash::check($value, $user->password)) {
                        $fail('A senha atual não corresponde à senha armazenada.');
                    }
                },
            ],
            'new_password' => [
                'required',
                'string',
                'min:8',
                'different:password',
            ],
            'password_confirmation' => 'required|same:new_password',
        ];
    }

    /**
     * Definir as mensagens de validação personalizadas.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'password.required' => 'O campo de senha atual é obrigatório.',
            'password.different' => 'A nova senha deve ser diferente da senha atual.',
            'new_password.required' => 'O campo de nova senha é obrigatório.',
            'new_password.string' => 'O campo de nova senha deve ser uma sequência de caracteres.',
            'new_password.min' => 'A nova senha deve ter no mínimo 8 caracteres.',
            'password_confirmation.required' => 'O campo de confirmação de senha é obrigatório.',
            'password_confirmation.same' => 'A confirmação de senha deve ser igual à nova senha.',
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
