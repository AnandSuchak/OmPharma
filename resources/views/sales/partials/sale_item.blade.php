<div class="sale-item-wrapper" 
    @if(isset($item)) 
        data-existing-item="true"
        data-medicine-id="{{ $item->medicine_id }}"
        data-medicine-name="{{ $item->medicine->name }} ({{ $item->medicine->company_name ?? 'Generic' }})"
        data-batch-number="{{ $item->batch_number }}"
        data-expiry-date="{{ $item->expiry_date ? $item->expiry_date->format('Y-m-d') : '' }}"
        data-quantity="{{ $item->quantity }}"
        data-free-quantity="{{ $item->free_quantity }}"
        data-sale-price="{{ $item->sale_price }}"
        data-ptr="{{ $item->ptr }}"
        data-gst-rate="{{ $item->gst_rate }}"
        data-discount-percentage="{{ $item->discount_percentage }}"
    @endif
>
    <div class="card mb-3 sale-item">
        <div class="card-body">
            {{-- This hidden input is crucial for the update method --}}
            @if(isset($item))
                <input type="hidden" name="existing_sale_items[{{ $index }}][id]" value="{{ $item->id }}">
            @endif

            <div class="row mb-2 g-3">
                <div class="col-md-4">
                    <label class="form-label">Medicine Name:</label>
                    <select class="form-select medicine-name-select select2-medicine" required>
                        {{-- This will be populated by Select2 AJAX or initialized by script for existing items --}}
                        <option></option>
                    </select>
                </div>
                <div class="col-md-2 pack-selector-container" style="display: none;">
                    <label class="form-label">Pack:</label>
                    <select class="form-select pack-select" name="{{ isset($item) ? "existing_sale_items[{$index}][medicine_id]" : "new_sale_items[{$index}][medicine_id]" }}" required></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Batch Number:</label>
                    <select class="form-select batch-select select2-batch" name="{{ isset($item) ? "existing_sale_items[{$index}][batch_number]" : "new_sale_items[{$index}][batch_number]" }}" >
                        <option></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Expiry Date:</label>
                    <input type="date" class="form-control expiry-date" name="{{ isset($item) ? "existing_sale_items[{$index}][expiry_date]" : "new_sale_items[{$index}][expiry_date]" }}" readonly>
                </div>
            </div>

            <div class="row mb-2 g-3">
                <div class="col-md-2">
                    <label class="form-label">Quantity:</label>
                    <input type="number" class="form-control quantity-input" name="{{ isset($item) ? "existing_sale_items[{$index}][quantity]" : "new_sale_items[{$index}][quantity]" }}" value="{{ $item->quantity ?? 1 }}" min="1" required>
                    <small class="form-text text-muted available-quantity"></small>
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">FQ</label>
                    <input type="number" class="form-control free-quantity-input" name="{{ isset($item) ? "existing_sale_items[{$index}][free_quantity]" : "new_sale_items[{$index}][free_quantity]" }}" value="{{ $item->free_quantity ?? 0 }}" min="0">
                </div>

                <div class="col-md-2">
                    <label class="form-label">PTR:</label>
                    <input type="number" class="form-control ptr-input" name="{{ isset($item) ? "existing_sale_items[{$index}][ptr]" : "new_sale_items[{$index}][ptr]" }}" value="{{ $item->ptr ?? '' }}" step="0.01" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Selling Price:</label>
                    <input type="number" class="form-control selling-price-input" name="{{ isset($item) ? "existing_sale_items[{$index}][sale_price]" : "new_sale_items[{$index}][sale_price]" }}" value="{{ $item->sale_price ?? '' }}" step="0.01" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Discount (%):</label>
                    <input type="number" class="form-control discount-input" name="{{ isset($item) ? "existing_sale_items[{$index}][discount_percentage]" : "new_sale_items[{$index}][discount_percentage]" }}" value="{{ $item->discount_percentage ?? 0 }}" step="0.01" min="0">
                </div>
                 <div class="col-md-2">
                    <label class="form-label">GST Rate (%):</label>
                    <input type="number" class="form-control gst-input" name="{{ isset($item) ? "existing_sale_items[{$index}][gst_rate]" : "new_sale_items[{$index}][gst_rate]" }}" value="{{ $item->gst_rate ?? '' }}" step="0.01" min="0">
                </div>
            </div>

            <div class="row">
                <div class="col-12 text-end">
                    <button type="button" class="btn btn-danger remove-item">Remove</button>
                </div>
            </div>
        </div>
    </div>
</div>
