<?php

namespace App\Http\Requests\Coupon;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class CouponStoreRequest extends FormRequest
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
            'name' => 'required|string|max:45',
            'amount' => 'required',
            'dedution' => 'required',
            'event_id' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('The name field is required.'),
            'name.*.max' => __('The maximum number of characters is 45.'),

            'amount.required' => __('The amount field is required.'),
            'dedution.required' => __('The dedution field is required.'),
            'event_id.required' => __('The event field is required.'),
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
