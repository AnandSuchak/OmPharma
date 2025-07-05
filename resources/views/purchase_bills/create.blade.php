@extends('layouts.app')

@section('title', 'Create New Purchase Bill')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">📝 Create New Purchase Bill</h3>
        <a href="{{ route('purchase_bills.index') }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left me-1"></i> Back
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Whoops!</strong> Please fix the following issues:
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('purchase_bills.store') }}" method="POST">
        @csrf

        {{-- Bill Details --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h5 class="mb-0">Bill Details</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="supplier_id" class="form-label">Supplier:</label>
                        <select class="form-select" id="supplier_id" name="supplier_id" required>
                            <option value="">Select Supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="bill_number" class="form-label">Bill Number:</label>
                        <input type="text" class="form-control" id="bill_number" name="bill_number" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="bill_date" class="form-label">Bill Date:</label>
                        <input type="date" class="form-control" id="bill_date" name="bill_date" value="{{ now()->toDateString() }}" required>
                    </div>
                </div>
            </div>
        </div>

        {{-- Items --}}
        <h5 class="mb-3">Purchase Bill Items</h5>
        <div id="purchase_items_container"></div>

        {{-- Totals --}}
        <div class="row mt-4">
            <div class="col-md-6">
                <button type="button" id="add_new_item" class="btn btn-success">
                    <i class="fa fa-plus me-1"></i> Add Item
                </button>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <h5 class="card-title mb-0">Totals</h5>
                            <button type="button" id="toggle_manual_edit" class="btn btn-sm btn-outline-warning">
                                <i class="fa fa-pencil-alt"></i> Manual Edit
                            </button>
                        </div>
                        <div class="row g-2">
                            <div class="col-12">
                                <label for="subtotal_amount" class="form-label small">Subtotal (w/o GST)</label>
                                <input type="number" step="0.01" class="form-control" id="subtotal_amount" readonly>
                            </div>
                            <div class="col-12">
                                <label for="total_gst_amount" class="form-label small">Total GST</label>
                                <input type="number" step="0.01" class="form-control" id="total_gst_amount" name="total_gst_amount" readonly>
                            </div>
                            <div class="col-12">
                                <label for="total_amount" class="form-label small fw-bold">Grand Total</label>
                                <input type="number" step="0.01" class="form-control fw-bold" id="total_amount" name="total_amount" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <div class="text-end">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fa fa-check-circle me-1"></i> Submit Bill
            </button>
        </div>
    </form>
</div>

{{-- Template --}}
<template id="purchase_item_template">
    <div class="card mb-3 purchase-item">
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-5">
                    <label class="form-label">Medicine:</label>
                    <select class="form-select medicine-select" name="purchase_items[__INDEX__][medicine_id]" required></select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Batch Number:</label>
                    <input type="text" class="form-control" name="purchase_items[__INDEX__][batch_number]" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Expiry Date:</label>
                    <input type="date" class="form-control expiry-date" name="purchase_items[__INDEX__][expiry_date]" required>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col">
                    <label class="form-label">Qty:</label>
                    <input type="number" class="form-control item-calc" name="purchase_items[__INDEX__][quantity]" value="1" min="1" required>
                </div>
                <div class="col">
                    <label class="form-label">Purchase Price:</label>
                    <input type="number" class="form-control item-calc" name="purchase_items[__INDEX__][purchase_price]" step="0.01" min="0" required>
                </div>
                <div class="col">
                    <label class="form-label">PTR:</label>
                    <input type="number" class="form-control" name="purchase_items[__INDEX__][ptr]" step="0.01" min="0">
                </div>
                <div class="col">
                    <label class="form-label">Selling Price:</label>
                    <input type="number" class="form-control" name="purchase_items[__INDEX__][sale_price]" step="0.01" min="0" required>
                </div>
                <div class="col">
                    <label class="form-label">Discount (%):</label>
                    <input type="number" class="form-control item-calc" name="purchase_items[__INDEX__][discount_percentage]" value="0" step="0.01" min="0">
                </div>
                <div class="col">
                    <label class="form-label">GST Rate (%):</label>
                    <input type="number" class="form-control item-calc gst-rate" name="purchase_items[__INDEX__][gst_rate]" step="0.01" min="0" readonly>
                </div>
            </div>
            <div class="text-end">
                <button type="button" class="btn btn-danger btn-sm remove-item">
                    <i class="fa fa-trash"></i> Remove
                </button>
            </div>
        </div>
    </div>
</template>
@endsection
@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('purchase_items_container');
    const addItemBtn = document.getElementById('add_new_item');
    const template = document.getElementById('purchase_item_template').content;
    let itemCount = 0;
    let isManualMode = false;

    const subtotalInput = document.getElementById('subtotal_amount');
    const gstInput = document.getElementById('total_gst_amount');
    const totalInput = document.getElementById('total_amount');

    function calculateTotals() {
        if (isManualMode) return;
        let subtotal = 0, totalGst = 0;

        document.querySelectorAll('.purchase-item').forEach(item => {
            const qty = parseFloat(item.querySelector('[name*="[quantity]"]').value) || 0;
            const price = parseFloat(item.querySelector('[name*="[purchase_price]"]').value) || 0;
            const disc = parseFloat(item.querySelector('[name*="[discount_percentage]"]').value) || 0;
            const gstRate = parseFloat(item.querySelector('[name*="[gst_rate]"]').value) || 0;

            const base = qty * price;
            const afterDisc = base - (base * disc / 100);
            const gst = afterDisc * (gstRate / 100);

            subtotal += afterDisc;
            totalGst += gst;
        });

        subtotalInput.value = subtotal.toFixed(2);
        gstInput.value = totalGst.toFixed(2);
        totalInput.value = (subtotal + totalGst).toFixed(2);
    }

    function attachListeners(wrapper) {
        wrapper.querySelector('.remove-item').addEventListener('click', () => {
            wrapper.remove();
            calculateTotals();
        });

        const select = wrapper.querySelector('.medicine-select');
        const gstRateField = wrapper.querySelector('.gst-rate');

        // Initialize Select2
        $(select).select2({
            theme: 'bootstrap-5',
            placeholder: 'Search medicine',
            ajax: {
                url: '{{ url("/api/medicines/search") }}',
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term }),
                processResults: data => ({
                    results: data.map(med => ({
                        id: med.id,
                        text: `${med.name} (${med.company_name ?? 'Generic'})`
                    }))
                }),
                cache: true
            }
        });

        // Attach correct Select2 event
        $(select).on('select2:select', function (e) {
            const medicineId = e.params.data.id;
            console.log("➡️ Medicine selected:", medicineId);

            if (medicineId) {
                fetch(`/api/medicines/${medicineId}/gst`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log("✅ GST data:", data);
                        gstRateField.value = data.gst_rate;
                        calculateTotals();
                    })
                    .catch(error => {
                        console.error("❌ Error fetching GST:", error);
                        gstRateField.value = 0;
                    });
            }
        });

        // Auto-calculate when input fields are edited
        wrapper.querySelectorAll('.item-calc').forEach(el => {
            el.addEventListener('input', calculateTotals);
        });
    }

    function addItem() {
        const clone = template.cloneNode(true);
        const wrapper = clone.querySelector('.purchase-item');

        wrapper.querySelectorAll('select, input').forEach(el => {
            const name = el.name;
            if (name) el.name = name.replace('__INDEX__', itemCount);
        });

        container.appendChild(wrapper);
        attachListeners(wrapper);
        itemCount++;
    }

    // Add initial item
    addItemBtn.addEventListener('click', addItem);
    document.getElementById('toggle_manual_edit').addEventListener('click', function () {
        isManualMode = !isManualMode;
        const fields = [subtotalInput, gstInput, totalInput];
        fields.forEach(field => field.readOnly = !isManualMode);
        this.innerHTML = isManualMode
            ? '<i class="fa fa-lock"></i> Lock Totals'
            : '<i class="fa fa-pencil-alt"></i> Manual Edit';
    });

    $('#supplier_id').select2({ theme: 'bootstrap-5' });

    // Add default first item
    addItem();
});

// Refill old inputs if validation fails
const oldPurchaseItems = @json(old('purchase_items', []));
if (oldPurchaseItems.length > 0) {
    container.innerHTML = ''; // clear default item
    itemCount = 0;
    oldPurchaseItems.forEach(item => {
        const clone = template.cloneNode(true);
        const wrapper = clone.querySelector('.purchase-item');

        wrapper.querySelectorAll('select, input').forEach(el => {
            const name = el.name;
            if (name) el.name = name.replace('__INDEX__', itemCount);
        });

        // Fill values
        wrapper.querySelector('[name$="[batch_number]"]').value = item.batch_number ?? '';
        wrapper.querySelector('[name$="[expiry_date]"]').value = item.expiry_date ?? '';
        wrapper.querySelector('[name$="[quantity]"]').value = item.quantity ?? 1;
        wrapper.querySelector('[name$="[purchase_price]"]').value = item.purchase_price ?? 0;
        wrapper.querySelector('[name$="[ptr]"]').value = item.ptr ?? '';
        wrapper.querySelector('[name$="[sale_price]"]').value = item.sale_price ?? '';
        wrapper.querySelector('[name$="[discount_percentage]"]').value = item.discount_percentage ?? 0;
        wrapper.querySelector('[name$="[gst_rate]"]').value = item.gst_rate ?? 0;

        container.appendChild(wrapper);
        attachListeners(wrapper);

        // Initialize Select2 with preselected medicine (if possible)
        const medSelect = wrapper.querySelector('.medicine-select');
        if (item.medicine_id) {
            const option = new Option(item.medicine_name ?? 'Selected Medicine', item.medicine_id, true, true);
            medSelect.append(option);
        }

        itemCount++;
    });
}


</script>
@endpush
