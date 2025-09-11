<?php

namespace App\Http\Requests;

use App\Models\Bank;
use App\Utils\Service\V1\Payment\PaystackService;
use Illuminate\Foundation\Http\FormRequest;

class WithdrawRequest extends FormRequest
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
            'amount' => [
                'required',
                'numeric',
                'min:100',
                'max:1000000',
            ],
            'bank_code' => [
                'required',
                'string',
            ],
            'account_number' => [
                'required',
                'string',
                'max:10',
                'regex:/^[0-9]+$/',
            ],
            'account_name' => [
                'required',
                'string',
            ],

        ];
    }


    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'The withdrawal amount is required.',
            'amount.numeric' => 'The withdrawal amount must be a valid number.',
            'amount.min' => 'The minimum withdrawal amount is ₦1,000.',
            'amount.max' => 'The maximum withdrawal amount is ₦10,000,000.',
            'bank_account_id.string' => 'The bank account ID must be a string.',
            'bank_account_id.exists' => 'The selected bank account does not exist.',
            'bank_code.required_without' => 'Bank code is required when not using a saved bank account.',
            'bank_code.regex' => 'Bank code must contain only numbers.',
            'account_number.required_without' => 'Account number is required when not using a saved bank account.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'amount' => 'withdrawal amount',
            'bank_account_id' => 'bank account',
            'account_number' => 'account number',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert amount to float if it's a string
        if ($this->has('amount') && is_string($this->amount)) {
            $this->merge([
                'amount' => (float) $this->amount,
            ]);
        }
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check if user has sufficient balance
            $user = auth('web')->user();
            $amount = $this->input('amount');

            if ($user && $amount && $user->wallet->balance < $amount) {
                $validator->errors()->add('amount', 'Insufficient wallet balance for this withdrawal.');
            }
        });
    }
}
