<?php

// File: app/Http/Requests/StoreMedicineRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Set to true to allow anyone to use this form.
        // You can add your own authorization logic here later (e.g., check user roles).
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
            'hsn_code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'gst_rate' => 'nullable|numeric|min:0|max:100',
            'pack' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
        ];
    }
}
