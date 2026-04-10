<?php

namespace App\Http\Requests;

use App\Models\PaymentEventType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StorePaymentWebhookRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_id' => ['required', 'string', 'max:255'],
            'payment_id' => ['required', 'string', 'max:255'],
            'event' => [
                'required',
                'string',
                Rule::in(PaymentEventType::codes()),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'user_id' => ['nullable', 'integer'],
            'timestamp' => ['required', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currency' => strtoupper((string) $this->input('currency')),
        ]);
    }

    public function messages(): array
    {
        $allowed = implode(', ', PaymentEventType::codes());

        return [
            'event_id.required' => 'event_id is required.',
            'payment_id.required' => 'payment_id is required.',
            'event.required' => 'event is required.',
            'event.in' => 'event must be one of: '.$allowed.'.',
            'amount.required' => 'amount is required.',
            'amount.numeric' => 'amount must be a valid number.',
            'amount.min' => 'amount must be greater than or equal to 0.',
            'currency.required' => 'currency is required.',
            'currency.size' => 'currency must have exactly 3 letters (ISO format).',
            'currency.regex' => 'currency must contain only uppercase letters (e.g., USD).',
            'user_id.integer' => 'user_id must be an integer.',
            'timestamp.required' => 'timestamp is required.',
            'timestamp.date' => 'timestamp must be a valid date.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validation failed.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
