<div class="card mb-3 sale-item">
    <div class="card-body p-3">
        @if(isset($item))
            <input type="hidden" name="{{ $prefix }}[id]" value="{{ $item->id }}">
        @endif

        {{-- CRITICAL FIX: Hidden Medicine ID input. This MUST be named correctly. --}}
        <input type="hidden" class="medicine-id-input" name="{{ $prefix }}[medicine_id]" 
               value="{{ old("{$prefix}.medicine_id", $item->medicine_id ?? '') }}">

        {{-- Hidden fields for other data passed from backend/old input --}}
        <input type="hidden" class="gst-rate-input" name="{{ $prefix }}[gst_rate]" value="{{ old("{$prefix}.gst_rate", $item->gst_rate ?? 0) }}">
        <input type="hidden" class="mrp-input-hidden" name="{{ $prefix }}[ptr]" value="{{ old("{$prefix}.ptr", $item->ptr ?? '') }}">
        <input type="hidden" class="pack-input" name="{{ $prefix }}[pack]" value="{{ old("{$prefix}.pack", $item->pack ?? '') }}">
        <input type="hidden" class="applied-extra-discount-percentage" name="{{ $prefix }}[applied_extra_discount_percentage]" value="{{ old("{$prefix}.applied_extra_discount_percentage", $item->applied_extra_discount_percentage ?? 0.00) }}">


        <div class="row g-2 align-items-end mb-3">
            {{-- Medicine Name (for Select2 to initialize) - NO NAME ATTRIBUTE HERE --}}
            <div class="col-md-4">
                <label for="medicine_{{ $index }}" class="form-label">Medicine Name:</label>
                <select id="medicine_{{ $index }}" class="form-select medicine-name-select" required>
                    <option></option>
                </select>
            </div>

            {{-- Pack Selector - Name should be [pack_id] if you want to submit it --}}
            <div class="col-md-2 pack-selector-container">
                <label for="pack_select_{{ $index }}" class="form-label">Pack:</label>
                {{-- CRITICAL FIX: Changed name to [pack_id] --}}
                <select id="pack_select_{{ $index }}" class="form-select pack-select" name="{{ $prefix }}[pack_id]" required>
                    <option></option>
                </select>
            </div>

            {{-- Batch Number --}}
            <div class="col-md-3">
                <label for="batch_{{ $index }}" class="form-label">Batch Number:</label>
                <select id="batch_{{ $index }}" class="form-select batch-number-select select2-batch" name="{{ $prefix }}[batch_number]" required>
                    <option></option>
                </select>
            </div>

            {{-- Expiry Date (Readonly) --}}
            <div class="col-md-3">
                <label for="expiry_date_{{ $index }}" class="form-label">Expiry Date:</label>
                <input type="date" id="expiry_date_{{ $index }}" class="form-control expiry-date-input" name="{{ $prefix }}[expiry_date]" readonly>
            </div>
        </div>

        <div class="row g-2 align-items-end mb-3">
            {{-- Quantity --}}
            <div class="col-6 col-sm-2 col-md-1">
                <label for="qty_{{ $index }}" class="form-label">Qty</label>
                <input type="number" id="qty_{{ $index }}" class="form-control quantity-input item-calc" name="{{ $prefix }}[quantity]" value="{{ old("{$prefix}.quantity", isset($item) ? $item->quantity : 1.00) }}" min="0.01" step="0.01" required>
                <small class="form-text text-muted available-quantity"></small>
            </div>

            {{-- Free Quantity --}}
            <div class="col-6 col-sm-2 col-md-1">
                <label for="fq_{{ $index }}" class="form-label">FQ</label>
                <input type="number" id="fq_{{ $index }}" class="form-control free-qty-input item-calc" name="{{ $prefix }}[free_quantity]" value="{{ old("{$prefix}.free_quantity", isset($item) ? $item->free_quantity : 0.00) }}" min="0" step="0.01">
            </div>

            {{-- PTR (MRP Display) --}}
            <div class="col-md-2">
                <label for="mrp_display_{{ $index }}" class="form-label">MRP / PTR:</label>
                <input type="text" id="mrp_display_{{ $index }}" class="form-control mrp-input" value="{{ old("{$prefix}.ptr", isset($item) ? $item->ptr : 'N/A') }}" readonly>
            </div>

            {{-- Selling Price (Editable) --}}
            <div class="col-md-2">
                <label for="sale_price_{{ $index }}" class="form-label">Selling Price:</label>
                <input type="number" id="sale_price_{{ $index }}" class="form-control sale-price-input item-calc" name="{{ $prefix }}[sale_price]" value="{{ old("{$prefix}.sale_price", isset($item) ? $item->sale_price : '') }}" step="0.01" min="0">
            </div>

           {{-- Discount (%) --}}
            <div class="col-md-2">
                <label for="discount_{{ $index }}" class="form-label">Discount (%):</label>
                <input type="number" id="discount_{{ $index }}" class="form-control discount-percentage-input item-calc" name="{{ $prefix }}[discount_percentage]" value="{{ old("{$prefix}.discount_percentage", isset($item) ? $item->discount_percentage : 0) }}" step="0.01" min="0">
            </div>

            {{-- NEW: Applied Extra Discount Percentage Display (per item) --}}
            <div class="col-md-2">
                <label for="applied_extra_discount_percentage_{{ $index }}" class="form-label">Applied Extra Disc%:</label>
                <input type="number" id="applied_extra_discount_percentage_{{ $index }}" class="form-control applied-extra-discount-percentage-display" 
                       value="{{ old("{$prefix}.applied_extra_discount_percentage", isset($item) ? $item->applied_extra_discount_percentage : 0.00) }}" step="0.01" min="0" readonly>
                {{-- This field is read-only as its value is controlled by the checkbox and constant --}}
            </div>

            {{-- GST Rate (%) Display --}}
            <div class="col-md-2">
                <label for="gst_percent_display_{{ $index }}" class="form-label">GST Rate (%):</label>
                <input type="text" id="gst_percent_display_{{ $index }}" class="form-control gst-percent-input" value="{{ old("{$prefix}.gst_rate", isset($item) ? $item->gst_rate : '0') }}%" readonly>
            </div>
        </div>

        <div class="row g-2 align-items-end">
            {{-- GST Amount (Display) --}}
            <div class="col-md-3">
                <label for="gst_amount_display_{{ $index }}" class="form-label">GST Amount (₹):</label>
                <input type="text" id="gst_amount_display_{{ $index }}" class="form-control gst-amount-input" readonly>
            </div>
            {{-- New: Extra Discount Checkbox --}}
            <div class="col-md-4 align-self-end mb-2">
                <div class="form-check">
                    <input class="form-check-input extra-discount-checkbox" type="checkbox" id="extra_disc_{{ $index }}"
                            name="{{ $prefix }}[is_extra_discount_applied]"
                            value="1" {{ old("{$prefix}.is_extra_discount_applied", isset($item) && $item->is_extra_discount_applied) ? 'checked' : '' }}>
                    <label class="form-check-label" for="extra_disc_{{ $index }}">
                        Apply Extra 3% Discount
                    </label>
                    {{-- Hidden input to store the actual applied percentage --}}
                    <input type="hidden" class="applied-extra-discount-percentage"
                            name="{{ $prefix }}[applied_extra_discount_percentage]"
                            value="{{ old("{$prefix}.applied_extra_discount_percentage", isset($item) ? $item->applied_extra_discount_percentage : 0.00) }}">
                </div>
            </div>
        </div>

        {{-- No longer needed here, moved to top hidden inputs --}}
        {{-- <input type="hidden" class="gst-rate-input" name="{{ $prefix }}[gst_rate]" value="{{ old("{$prefix}.gst_rate", isset($item) ? $item->gst_rate : 0) }}"> --}}
        {{-- <input type="hidden" class="mrp-input-hidden" name="{{ $prefix }}[ptr]" value="{{ old("{$prefix}.ptr", isset($item) ? $item->ptr : '') }}"> --}}
        {{-- <input type="hidden" class="pack-input" name="{{ $prefix }}[pack]" value="{{ old("{$prefix}.pack", isset($item) ? $item->pack : '') }}"> --}}

    </div>
    <div class="card-footer text-end">
        <button type="button" class="btn btn-danger remove-new-item">Remove</button>
    </div>
</div>