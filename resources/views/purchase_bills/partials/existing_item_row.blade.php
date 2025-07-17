<div class="card mb-3 purchase-item">
    <div class="card-body">
        <div class="row mb-2">
            <div class="col-md-4">
                <label class="form-label">Medicine Name:</label>
                @php
                    $selectedId = old($prefix . '.' . $index . '.medicine_id', $item->medicine_id ?? null);
                    $selectedText = \App\Models\Medicine::find($selectedId)?->name;
                @endphp
                <select class="form-select medicine-name-select" name="{{ $prefix }}[{{ $index }}][medicine_id]" required>
                    @if($selectedId && $selectedText)
                        <option value="{{ $selectedId }}" selected>{{ $selectedText }}</option>
                    @endif
                </select>
            </div>

            <div class="col-md-2 pack-selector-container" style="{{ isset($item->pack_id) ? '' : 'display: none;' }}">
                <label class="form-label">Pack:</label>
                <select class="form-select pack-select" name="{{ $prefix }}[{{ $index }}][pack_id]">
                    @if(isset($item->pack_id))
                        <option value="{{ $item->pack_id }}" selected>{{ \App\Models\Pack::find($item->pack_id)?->name }}</option>
                    @endif
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Batch Number:</label>
                <input type="text" class="form-control" name="{{ $prefix }}[{{ $index }}][batch_number]" value="{{ old($prefix . '.' . $index . '.batch_number', $item->batch_number ?? '') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Expiry Date:</label>
                <input type="text" class="form-control expiry-date" name="{{ $prefix }}[{{ $index }}][expiry_date]" value="{{ old($prefix . '.' . $index . '.expiry_date', $item->expiry_date ?? '') }}">
            </div>
        </div>

        <div class="row mb-2">
            <div class="col">
                <label class="form-label">Qty:</label>
                <input type="number" class="form-control item-calc" name="{{ $prefix }}[{{ $index }}][quantity]" value="{{ old($prefix . '.' . $index . '.quantity', $item->quantity ?? 1) }}" min="1" required>
            </div>

            <div class="col">
                <label class="form-label">FQ:</label>
                <input type="number" class="form-control" name="{{ $prefix }}[{{ $index }}][free_quantity]" value="{{ old($prefix . '.' . $index . '.free_quantity', $item->free_quantity ?? 0) }}" min="0">
            </div>

            <div class="col">
                <label class="form-label">Price:</label>
                <input type="number" class="form-control item-calc" name="{{ $prefix }}[{{ $index }}][purchase_price]" value="{{ old($prefix . '.' . $index . '.purchase_price', $item->purchase_price ?? '') }}" step="0.01" min="0" required>
            </div>

            <div class="col">
                <label class="form-label">MRP:</label>
                <input type="number" class="form-control" name="{{ $prefix }}[{{ $index }}][ptr]" value="{{ old($prefix . '.' . $index . '.ptr', $item->ptr ?? '') }}" step="0.01" min="0">
            </div>

            <div class="col">
                <label class="form-label">Sell Price:</label>
                <input type="number" class="form-control" name="{{ $prefix }}[{{ $index }}][sale_price]" value="{{ old($prefix . '.' . $index . '.sale_price', $item->sale_price ?? '') }}" step="0.01" min="0" required>
            </div>

            <div class="col">
                <label class="form-label">Cust. Disc%:</label>
                <input type="number" class="form-control" name="{{ $prefix }}[{{ $index }}][discount_percentage]" value="{{ old($prefix . '.' . $index . '.discount_percentage', $item->discount_percentage ?? 0) }}" step="0.01" min="0">
            </div>

            <div class="col">
                <label class="form-label">Our Disc%:</label>
                <input type="number" class="form-control item-calc" name="{{ $prefix }}[{{ $index }}][our_discount_percentage]" value="{{ old($prefix . '.' . $index . '.our_discount_percentage', $item->our_discount_percentage ?? 0) }}" step="0.01" min="0">
            </div>

            <div class="col">
                <label class="form-label">GST%:</label>
                <input type="number" class="form-control item-calc gst-rate" name="{{ $prefix }}[{{ $index }}][gst_rate]" value="{{ old($prefix . '.' . $index . '.gst_rate', $item->gst_rate ?? '') }}" step="0.01" min="0" readonly>
            </div>

            <div class="col">
                <label class="form-label">Row Total (₹):</label>
                <input type="text" class="form-control row-total" readonly>
            </div>
        </div>

        <div class="text-end">
            <button type="button" class="btn btn-danger btn-sm remove-item">
                <i class="fa fa-trash"></i> Remove
            </button>
        </div>
    </div>
</div>
