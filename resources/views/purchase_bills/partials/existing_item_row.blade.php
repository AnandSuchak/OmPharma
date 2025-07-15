<div class="card mb-3 purchase-item">
    <div class="card-body">
        <input type="hidden" name="existing_items[{{ $item->id }}][id]" value="{{ $item->id }}">
        <div class="row mb-2">
            <div class="col-md-5">
                <label class="form-label">Medicine:</label>
                <select class="form-select select2-medicine" name="existing_items[{{ $item->id }}][medicine_id]" required>
                    <option value="">Select Medicine</option>
                    @foreach ($medicines as $medicine)
                        <option value="{{ $medicine->id }}" data-gst="{{ $medicine->gst_rate }}" {{ $medicine->id == $item->medicine_id ? 'selected' : '' }}>
                            {{ $medicine->name }} ({{ $medicine->company_name ?? 'Generic' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Batch Number:</label>
                <input type="text" class="form-control" name="existing_items[{{ $item->id }}][batch_number]" value="{{ $item->batch_number }}" >
            </div>
            <div class="col-md-3">
                <label class="form-label">Expiry Date:</label>
                <input type="date" class="form-control" name="existing_items[{{ $item->id }}][expiry_date]" value="{{ $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('Y-m-d') : '' }}" required>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col"><label class="form-label">Qty:</label><input type="number" class="form-control item-calc" name="existing_items[{{ $item->id }}][quantity]" value="{{ $item->quantity }}" required></div>
            <div class="col"><label class="form-label">Purchase Price:</label><input type="number" class="form-control item-calc" name="existing_items[{{ $item->id }}][purchase_price]" value="{{ $item->purchase_price }}" required></div>
            <div class="col"><label class="form-label">MRP:</label><input type="number" class="form-control" name="existing_items[{{ $item->id }}][ptr]" value="{{ $item->ptr }}"></div>
            <div class="col"><label class="form-label">Selling Price:</label><input type="number" class="form-control" name="existing_items[{{ $item->id }}][sale_price]" value="{{ $item->sale_price }}" required></div>
            <div class="col"><label class="form-label">Discount %:</label><input type="number" class="form-control item-calc" name="existing_items[{{ $item->id }}][discount_percentage]" value="{{ $item->discount_percentage }}"></div>
            <div class="col"><label class="form-label">Our Disc%:</label><input type="number" class="form-control item-calc" name="existing_items[{{ $id }}][our_discount_percentage]" value="{{ $itemData['our_discount_percentage'] ?? 0 }}"></div>
            <div class="col"><label class="form-label">GST Rate %:</label><input type="number" class="form-control gst-rate item-calc" name="existing_items[{{ $item->id }}][gst_rate]" value="{{ $item->gst_rate }}"></div>
        </div>
        <div class="text-end">
            <button type="button" class="btn btn-danger btn-sm remove-item"><i class="fa fa-trash"></i> Remove</button>
        </div>
    </div>
</div>