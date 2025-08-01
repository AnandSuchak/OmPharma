<?php

// File: app/Http/Requests/UpdateSaleRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleRequest extends FormRequest
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
            // Sale-level rules
            'customer_id' => 'required|exists:customers,id',
            'sale_date' => 'required|date',
            'notes' => 'nullable|string',

                    // Extra discount at sale-level
        'discount_percentage' => 'nullable|numeric|min:0|max:100',
        'is_extra_discount_applied' => 'nullable|boolean',
        'applied_extra_discount_percentage' => 'nullable|numeric|min:0|max:100',
        
            // A string of comma-separated IDs for items to be deleted
            'deleted_items' => 'nullable|string',

            // Validation for existing items that might be updated
            'existing_sale_items' => 'nullable|array',
            'existing_sale_items.*.id' => 'required|exists:sale_items,id',
            'existing_sale_items.*.medicine_id' => 'required|exists:medicines,id',
            'existing_sale_items.*.batch_number' => 'required|string',
            'existing_sale_items.*.quantity' => 'required|numeric|min:0.01',
            'existing_sale_items.*.free_quantity' => 'nullable|numeric|min:0',
            'existing_sale_items.*.sale_price' => 'required|numeric|min:0',
            'existing_sale_items.*.gst_rate' => 'nullable|numeric|min:0',
            'existing_sale_items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',

            // Validation for brand new items being added during the update
            'new_sale_items' => 'nullable|array',
            'new_sale_items.*.medicine_id' => 'required|exists:medicines,id',
            'new_sale_items.*.batch_number' => 'required|string',
            'new_sale_items.*.quantity' => 'required|numeric|min:0.01',
            'new_sale_items.*.free_quantity' => 'nullable|numeric|min:0',
            'new_sale_items.*.sale_price' => 'required|numeric|min:0',
            'new_sale_items.*.gst_rate' => 'nullable|numeric|min:0',
            'new_sale_items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
