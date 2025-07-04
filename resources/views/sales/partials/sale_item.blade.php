<div class="card mb-3 sale-item">
    <div class="card-body">
        <input type="hidden" name="existing_sale_items[{{ $index }}][id]" value="{{ $item->id }}">

        <div class="row mb-2">
            <div class="col-md-6">
                <label for="medicine_id" class="form-label">Medicine:</label>
                <select class="form-control medicine-select" name="existing_sale_items[{{ $index }}][medicine_id]" required>
                    <option value="">Select Medicine</option>
                    @foreach ($medicines as $medicine)
                        <option value="{{ $medicine->id }}" {{ $item->medicine_id == $medicine->id ? 'selected' : '' }}>
                            {{ $medicine->name }} ({{ $medicine->company_name ?? 'Generic' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Batch Number:</label>
                <input type="text" class="form-control batch-number" name="existing_sale_items[{{ $index }}][batch_number]" value="{{ $item->batch_number }}" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Expiry Date:</label>
                <input type="date" class="form-control expiry-date" name="existing_sale_items[{{ $index }}][expiry_date]" value="{{ \Carbon\Carbon::parse($item->expiry_date)->format('Y-m-d') }}" required>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-md-3">
                <label class="form-label">Quantity:</label>
                <input type="number" class="form-control quantity-input" name="existing_sale_items[{{ $index }}][quantity]" value="{{ $item->quantity }}" min="1" required>
                <small class="form-text text-muted available-quantity"></small>
            </div>

            <div class="col-md-3">
                <label class="form-label">PTR:</label>
                <input type="number" class="form-control" name="existing_sale_items[{{ $index }}][ptr]" step="0.01" min="0" value="{{ $item->ptr }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Selling Price:</label>
                <input type="number" class="form-control" name="existing_sale_items[{{ $index }}][sale_price]" step="0.01" min="0" value="{{ $item->sale_price }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Discount (%):</label>
                <input type="number" class="form-control" name="existing_sale_items[{{ $index }}][discount_percentage]" step="0.01" min="0" value="{{ $item->discount_percentage }}">
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-md-3">
                <label class="form-label">GST Rate (%):</label>
                <input type="number" class="form-control" name="existing_sale_items[{{ $index }}][gst_rate]" step="0.01" min="0" value="{{ $item->gst_rate ?? 0 }}">
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 text-right">
                <button type="button" class="btn btn-danger remove-existing-item">Remove</button>
            </div>
        </div>
    </div>
</div>
