<div class="sale-item-wrapper">
    <div class="card mb-3 sale-item">
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-6">
                    <label class="form-label">Medicine:</label>
                    <select class="form-select medicine-select select2-medicine" name="sale_items[__INDEX__][medicine_id]" required>
                        <option></option>
                        @foreach ($medicines as $medicine)
                            <option value="{{ $medicine->id }}">
                                {{ $medicine->name }} ({{ $medicine->company_name ?? 'Generic' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Batch Number:</label>
                    <select class="form-select batch-select select2-batch" name="sale_items[__INDEX__][batch_number]" required>
                        <option></option>
                        <!-- Populated via JS -->
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Expiry Date:</label>
                    <input type="date" class="form-control expiry-date" name="sale_items[__INDEX__][expiry_date]" readonly>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3">
                    <label class="form-label">Quantity:</label>
                    <input type="number" class="form-control quantity-input" name="sale_items[__INDEX__][quantity]" value="1" min="1" required>
                    <small class="form-text text-muted available-quantity"></small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">PTR:</label>
                    <input type="number" class="form-control ptr-input" name="sale_items[__INDEX__][ptr]" step="0.01" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Selling Price:</label>
                    <input type="number" class="form-control selling-price-input" name="sale_items[__INDEX__][sale_price]" step="0.01" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Discount (%):</label>
                    <input type="number" class="form-control discount-input" name="sale_items[__INDEX__][discount_percentage]" value="0" step="0.01" min="0">
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3">
                    <label class="form-label">GST Rate (%):</label>
                    <input type="number" class="form-control gst-input" name="sale_items[__INDEX__][gst_rate]" step="0.01" min="0">
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 text-end">
                    <button type="button" class="btn btn-danger remove-new-item">Remove</button>
                </div>
            </div>
        </div>
    </div>
</div>
