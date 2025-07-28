{{-- resources/views/purchase_bills/partials/existing_item_row.blade.php --}}

<div class="card mb-3 purchase-item" data-item-id="{{ $item->id }}">
    <div class="card-body">
        <div class="purchase-item row g-2 align-items-end mb-3 p-2 border rounded shadow-sm bg-white">
            
            <input type="hidden" name="{{ $prefix }}[{{ $index }}][id]" value="{{ $item->id }}">

            <input type="hidden" class="medicine-name-hidden-input" name="{{ $prefix }}[{{ $index }}][medicine_name]" value="{{ old($prefix.'.'.$index.'.medicine_name', $item->medicine->name ?? '') }}">
            <input type="hidden" class="medicine-id-hidden-input" name="{{ $prefix }}[{{ $index }}][medicine_id]" value="{{ old($prefix.'.'.$index.'.medicine_id', $item->medicine_id) }}">

            {{-- Medicine Name --}}
            <div class="col-md-3 col-lg-2">
                <label class="form-label small">Medicine Name:</label>
                {{-- This visible select now has NO name attribute --}}
                <select class="form-select medicine-name-select" required data-selected-id="{{ old($prefix.'.'.$index.'.medicine_id', $item->medicine_id) }}" data-selected-text="{{ old($prefix.'.'.$index.'.medicine_name', $item->medicine->name ?? '') }}">
                    {{-- JS will populate the selected option --}}
                </select>
            </div>

            {{-- Pack Selector --}}
            <div class="col-md-1 col-lg-1 pack-selector-container" style="display: none;">
                <label class="form-label small">Pack:</label>
                {{-- This visible select now has NO name attribute --}}
                <select class="form-select pack-select"></select>
            </div>

            {{-- Batch Number --}}
            <div class="col-md-1 col-lg-2">
                <label class="form-label small">Batch No.:</label>
                <input type="text" class="form-control" name="{{ $prefix }}[{{ $index }}][batch_number]" value="{{ old($prefix . '.' . $index . '.batch_number', $item->batch_number ?? '') }}">
            </div>

            {{-- Expiry Date --}}
            <div class="col-md-2 col-lg-1">
                <label class="form-label small">Expiry:</label>
                <input type="text" class="form-control expiry-date" value="{{ old($prefix . '.' . $index . '.expiry_date', $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('m/y') : '') }}" placeholder="MM/YY">
                <input type="hidden" name="{{ $prefix }}[{{ $index }}][expiry_date]" value="{{ old($prefix . '.' . $index . '.expiry_date', $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('Y-m-d') : '') }}">
            </div>

            {{-- Quantity --}}
            <div class="col-6 col-sm-2 col-md-1">
                <label class="form-label small">Qty:</label>
                <input type="number" class="form-control item-calc" name="{{ $prefix }}[{{ $index }}][quantity]" value="{{ old($prefix . '.' . $index . '.quantity', $item->quantity ?? 1) }}" min="0" step="0.01" required>
            </div>

            {{-- Free Quantity --}}
            <div class="col-6 col-sm-2 col-md-1">
                <label class="form-label small">FQ:</label>
                <input type="number" class="form-control" name="{{ $prefix }}[{{ $index }}][free_quantity]" value="{{ old($prefix . '.' . $index . '.free_quantity', $item->free_quantity ?? 0) }}" min="0" step="0.01">
            </div>

            {{-- Purchase Price --}}
            <div class="col-md-2 col-lg-1">
                <label class="form-label small">Price:</label>
                <input type="number" class="form-control item-calc" name="{{ $prefix }}[{{ $index }}][purchase_price]" value="{{ old($prefix . '.' . $index . '.purchase_price', $item->purchase_price ?? '') }}" step="0.01" min="0" required>
            </div>

            {{-- MRP --}}
            <div class="col-md-2 col-lg-1">
                <label class="form-label small">MRP:</label>
                <input type="number" class="form-control" name="{{ $prefix }}[{{ $index }}][ptr]" value="{{ old($prefix . '.' . $index . '.ptr', $item->ptr ?? '') }}" step="0.01" min="0">
            </div>

            {{-- Sell Price --}}
            <div class="col-md-2 col-lg-1">
                <label class="form-label small">Sell Price:</label>
                <input type="number" class="form-control" name="{{ $prefix }}[{{ $index }}][sale_price]" value="{{ old($prefix . '.' . $index . '.sale_price', $item->sale_price ?? '') }}" step="0.01" min="0" required>
            </div>

            {{-- Customer Discount % --}}
            <div class="col-md-2 col-lg-1">
                <label class="form-label small">Cust. Disc%:</label>
                <input type="number" class="form-control" name="{{ $prefix }}[{{ $index }}][discount_percentage]" value="{{ old($prefix . '.' . $index . '.discount_percentage', $item->discount_percentage ?? 0) }}" step="0.01" min="0">
            </div>

            {{-- Our Discount % --}}
            <div class="col-md-2 col-lg-1">
                <label class="form-label small">Our Disc%:</label>
                <input type="number" class="form-control item-calc our-discount-percentage-input" name="{{ $prefix }}[{{ $index }}][our_discount_percentage]" value="{{ old($prefix . '.' . $index . '.our_discount_percentage', $item->our_discount_percentage ?? 0) }}" step="0.01" min="0">
            </div>

            {{-- Our Discount (₹) --}}
            <div class="col-md-2 col-lg-1">
                <label class="form-label small">Our Disc (₹):</label>
                <input type="number" class="form-control item-calc our-discount-amount-input" value="0.00" step="0.01" min="0">
            </div>
            
            {{-- GST % --}}
            <div class="col-md-2 col-lg-1">
                <label class="form-label small">GST%:</label>
                <input type="number" class="form-control item-calc gst-rate" name="{{ $prefix }}[{{ $index }}][gst_rate]" value="{{ old($prefix . '.' . $index . '.gst_rate', $item->gst_rate ?? '') }}" step="0.01" min="0" readonly>
            </div>

            {{-- Row Total --}}
            <div class="col-md-2 col-lg-1">
                <label class="form-label small">Row Total (₹):</label>
                <input type="text" class="form-control row-total" readonly>
            </div>

            {{-- Remove Button --}}
            <div class="col-auto">
                <button type="button" class="btn btn-danger btn-sm remove-item mt-3"><i class="fa fa-trash"></i></button>
            </div>
        </div>
    </div>
</div>