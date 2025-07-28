<?php

// File: app/Http/Requests/StorePurchaseBillRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseBillRequest extends FormRequest
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
            // Bill-level rules
            'supplier_id' => 'required|exists:suppliers,id',
            'bill_date'   => 'required|date',
            'bill_number' => [
                'required',
                'string',
                // FIXED: Access request data using the input() method.
                Rule::unique('purchase_bills')->where(function ($query) {
                    return $query->where('supplier_id', $this->input('supplier_id'));
                })
            ],
            'status'      => 'nullable|in:Pending,Received,Cancelled',
            'notes'       => 'nullable|string',
            'extra_discount_amount' => 'nullable|numeric|min:0',

            // Item-level rules
            'purchase_items' => 'required|array|min:1',
            'purchase_items.*.medicine_id' => 'required|exists:medicines,id',
            'purchase_items.*.batch_number' => 'nullable|string|max:255',
            'purchase_items.*.expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
            'purchase_items.*.quantity' => 'nullable|numeric|min:0',
            'purchase_items.*.free_quantity' => 'nullable|numeric|min:0',
            'purchase_items.*.purchase_price' => 'required|numeric|min:0',
            'purchase_items.*.ptr' => 'nullable|numeric|min:0',
            'purchase_items.*.sale_price' => 'required|numeric|min:0',
            'purchase_items.*.gst_rate' => 'nullable|numeric|min:0|max:100',
            'purchase_items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'purchase_items.*.our_discount_percentage' => 'nullable|numeric|min:0|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'bill_number.unique' => 'This bill number already exists for the selected supplier.',
            'purchase_items.required' => 'You must add at least one item to the bill.',
        ];
    }
}
