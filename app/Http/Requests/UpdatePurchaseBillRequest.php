<?php

// File: app/Http/Requests/UpdatePurchaseBillRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseBillRequest extends FormRequest
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
        // FIXED: Access the route parameter using the route() helper.
        // The parameter name is conventionally the snake_case version of the model name.
        $purchaseBillId = $this->route('purchase_bill')->id;

        $rules = [
            // Bill-level rules
            'supplier_id' => 'required|exists:suppliers,id',
            'bill_date'   => 'required|date',
            'bill_number' => [
                'required',
                'string',
                Rule::unique('purchase_bills')
                    ->where('supplier_id', $this->input('supplier_id'))
                    ->ignore($purchaseBillId) // Ignore the current bill when checking for uniqueness
            ],
            'status'      => 'nullable|in:Pending,Received,Cancelled',
            'notes'       => 'nullable|string',
            'extra_discount_amount' => 'nullable|numeric|min:0',

            // Validation for existing items that might be updated
            'existing_items' => 'nullable|array',
            'existing_items.*.id' => 'required|exists:purchase_bill_items,id',
            'existing_items.*.medicine_id' => 'required|exists:medicines,id',
            'existing_items.*.batch_number' => 'nullable|string|max:255',
            'existing_items.*.expiry_date' => ['nullable', 'date'],
            'existing_items.*.quantity' => 'nullable|numeric|min:0',
            'existing_items.*.free_quantity' => 'nullable|numeric|min:0',
            'existing_items.*.purchase_price' => 'required|numeric|min:0',
            'existing_items.*.sale_price' => 'required|numeric|min:0',
            
            // Validation for brand new items being added during the update
            'new_purchase_items' => 'nullable|array',
            'new_purchase_items.*.medicine_id' => 'required|exists:medicines,id',
            'new_purchase_items.*.batch_number' => 'nullable|string|max:255',
            'new_purchase_items.*.expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
            'new_purchase_items.*.quantity' => 'nullable|numeric|min:0',
            'new_purchase_items.*.free_quantity' => 'nullable|numeric|min:0',
            'new_purchase_items.*.purchase_price' => 'required|numeric|min:0',
            'new_purchase_items.*.sale_price' => 'required|numeric|min:0',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'bill_number.unique' => 'This bill number already exists for the selected supplier.',
        ];
    }
}
