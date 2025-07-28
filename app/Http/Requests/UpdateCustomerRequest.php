<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Get the ID of the customer being updated from the route parameter
        $customerId = $this->route('customer')->id;

        return [
            'name' => 'required|string|max:255',
            // This rule ensures the contact number is unique, but ignores the current customer's record
            'contact_number' => ['required', 'string', 'max:255', Rule::unique('customers')->ignore($customerId)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers')->ignore($customerId)],
            'address' => 'nullable|string',
            'dln' => 'nullable|string|max:255',
        ];
    }
}
