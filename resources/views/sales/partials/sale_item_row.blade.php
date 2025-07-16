{{-- Change this line in resources/views/sales/partials/sale_item_row.blade.php --}}
<div class="card mb-3"> {{-- REMOVED 'sale-item-wrapper' class from this div --}}
    <div class="card-body p-3">
        @if(isset($item))
            <input type="hidden" name="{{ $prefix }}[id]" value="{{ $item->id }}">
        @endif

        <div class="row g-2 align-items-end">
            {{-- Medicine --}}
            <div class="col-md-4">
                <label for="medicine_{{ $index }}" class="form-label">Medicine</label>
                <select id="medicine_{{ $index }}" class="form-select medicine-name-select" name="{{ $prefix }}[medicine_id]" required></select>
            </div>

            {{-- Batch --}}
            <div class="col-md-3">
                <label for="batch_{{ $index }}" class="form-label">Batch No.</label>
                <select id="batch_{{ $index }}" class="form-select batch-number-select" name="{{ $prefix }}[batch_number]" required></select>
            </div>

            {{-- Qty --}}
            <div class="col-6 col-sm-2 col-md-1">
                <label for="qty_{{ $index }}" class="form-label">Qty</label>
                <input type="number" id="qty_{{ $index }}" class="form-control quantity-input item-calc" name="{{ $prefix }}[quantity]" value="{{ old("{$prefix}.quantity", isset($item) ? $item->quantity : 1) }}" min="1" required>
            </div>

            {{-- Free Qty --}}
            <div class="col-6 col-sm-2 col-md-1">
                <label for="fq_{{ $index }}" class="form-label">FQ</label>
                <input type="number" id="fq_{{ $index }}" class="form-control free-quantity-input" name="{{ $prefix }}[free_quantity]" value="{{ old("{$prefix}.free_quantity", isset($item) ? $item->free_quantity : 0) }}" min="0">
            </div>

            {{-- Price --}}
            <div class="col-sm-4 col-md-2">
                <label for="price_{{ $index }}" class="form-label">Price</label>
                <input type="number" id="price_{{ $index }}" class="form-control sale-price-input item-calc" name="{{ $prefix }}[sale_price]" step="0.01" value="{{ old("{$prefix}.sale_price", isset($item) ? $item->sale_price : '') }}" required>
            </div>

            {{-- Remove --}}
            <div class="col-sm-2 col-md-1">
                <label for="remove_{{ $index }}" class="form-label invisible d-none d-md-block">Del</label>
                <button type="button" id="remove_{{ $index }}" class="btn btn-danger remove-item w-100" aria-label="Remove Item"><i class="fa fa-trash"></i></button>
            </div>
        </div>

        {{-- Additional row --}}
        <div class="row g-2 mt-2">
            <div class="col-md-4">
            <div class="col-sm-4 col-md-2">
                <label for="pack_{{ $index }}" class="form-label">Pack</label>
                <input type="text" id="pack_{{ $index }}" class="form-control pack-input" readonly>
            </div>

        </div>
            <div class="col-md-3">
                <label for="mrp_{{ $index }}" class="form-label">MRP</label>
                <input type="text" id="mrp_{{ $index }}" class="form-control mrp-input" readonly>
            </div>
            <div class="col-6 col-sm-2 col-md-1">
                <label for="gst_percent_{{ $index }}" class="form-label">GST %</label>
                <input type="text" id="gst_percent_{{ $index }}" class="form-control gst-percent-input" readonly>
            </div>
            <div class="col-6 col-sm-2 col-md-2">
                <label for="gst_amount_{{ $index }}" class="form-label">GST Amt.</label>
                <input type="text" id="gst_amount_{{ $index }}" class="form-control gst-amount-input" readonly>
            </div>
            <div class="col-sm-4 col-md-2">
                <label for="discount_{{ $index }}" class="form-label">Discount %</label>
                <input type="number" id="discount_{{ $index }}" class="form-control discount-percentage-input item-calc" name="{{ $prefix }}[discount_percentage]" step="0.01" value="{{ old("{$prefix}.discount_percentage", isset($item) ? $item->discount_percentage : 0) }}">
            </div>

        </div>

        {{-- Hidden Fields --}}
        <input type="hidden" class="gst-rate-input item-calc" name="{{ $prefix }}[gst_rate]" value="{{ old("{$prefix}.gst_rate", isset($item) ? $item->gst_rate : 0) }}">
        <input type="hidden" class="expiry-date-input" name="{{ $prefix }}[expiry_date]" value="{{ old("{$prefix}.expiry_date", isset($item) ? $item->expiry_date : '') }}">
        <input type="hidden" class="mrp-input-hidden" name="{{ $prefix }}[ptr]" value="{{ old("{$prefix}.ptr", isset($item) ? $item->ptr : '') }}">
    </div>
</div>