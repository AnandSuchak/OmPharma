<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            // This rule ensures the contact number is unique in the entire customers table
            'contact_number' => 'required|string|max:255|unique:customers,contact_number',
            'email' => 'nullable|email|max:255|unique:customers,email',
            'address' => 'nullable|string',
            'dln' => 'nullable|string|max:255',
        ];
    }
}