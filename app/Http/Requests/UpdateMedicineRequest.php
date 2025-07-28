<?php

// File: app/Http/Requests/UpdateMedicineRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Set to true to allow anyone to use this form.
        // You can add your own authorization logic here later.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // The rules are the same as storing, but they could be different.
        // For example, if 'name' had to be unique, you would modify it here
        // to ignore the current medicine's ID.
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
