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
            <strong>Whoops!</strong> There were some problems with your input.<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('purchase_bills.store') }}" method="POST">
        @csrf

        {{-- Bill Details Section --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Bill Details</h5>
            </div>
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
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Status:</label>
                        <select class="form-select" id="status" name="status">
                            <option value="Received">Received</option>
                            <option value="Pending">Pending</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label for="notes" class="form-label">Notes:</label>
                        <textarea class="form-control" id="notes" name="notes" rows="1"></textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bill Items Section --}}
        <h5 class="mb-3">Purchase Bill Items</h5>
        <div id="purchase_items_container"></div>

        {{-- Totals Section --}}
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
                            <button type="button" id="toggle_manual_edit" class="btn btn-sm btn-outline-warning" title="Allow manual editing of totals">
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
                    <select class="form-select medicine-select" name="purchase_items[__INDEX__][medicine_id]" required>
                        <option value="">Select Medicine</option>
                        @foreach ($medicines as $medicine)
                            <option value="{{ $medicine->id }}" data-gst="{{ $medicine->gst_rate }}">{{ $medicine->name }} ({{ $medicine->company_name ?? 'Generic' }})</option>
                        @endforeach
                    </select>
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
                    <input type="number" class="form-control item-calc gst-rate" name="purchase_items[__INDEX__][gst_rate]" step="0.01" min="0">
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
<!-- Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const itemsContainer = document.getElementById('purchase_items_container');
    const addItemButton = document.getElementById('add_new_item');
    const itemTemplate = document.getElementById('purchase_item_template').content;
    const subtotalInput = document.getElementById('subtotal_amount');
    const totalGstInput = document.getElementById('total_gst_amount');
    const totalAmountInput = document.getElementById('total_amount');
    const manualEditButton = document.getElementById('toggle_manual_edit');

    let itemCount = 0;
    let isManualMode = false;

    const calculateTotals = () => {
        if (isManualMode) return;
        let subtotal = 0, totalGst = 0;

        document.querySelectorAll('.purchase-item').forEach(item => {
            const quantity = parseFloat(item.querySelector('[name*="[quantity]"]').value) || 0;
            const price = parseFloat(item.querySelector('[name*="[purchase_price]"]').value) || 0;
            const discount = parseFloat(item.querySelector('[name*="[discount_percentage]"]').value) || 0;
            const gstRate = parseFloat(item.querySelector('[name*="[gst_rate]"]').value) || 0;

            const basePrice = quantity * price;
            const discountAmount = basePrice * (discount / 100);
            const priceAfterDiscount = basePrice - discountAmount;
            const gstAmount = priceAfterDiscount * (gstRate / 100);

            subtotal += priceAfterDiscount;
            totalGst += gstAmount;
        });

        const grandTotal = subtotal + totalGst;
        subtotalInput.value = subtotal.toFixed(2);
        totalGstInput.value = totalGst.toFixed(2);
        totalAmountInput.value = grandTotal.toFixed(2);
    };

    const attachListeners = (context) => {
        context.querySelector('.remove-item').addEventListener('click', () => {
            context.remove();
            calculateTotals();
        });

        const medicineSelect = context.querySelector('.medicine-select');
        const gstInput = context.querySelector('.gst-rate');

        $(medicineSelect).select2({ theme: 'bootstrap-5', placeholder: 'Select Medicine' });
        medicineSelect.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const gstRate = selectedOption.getAttribute('data-gst') || '0.00';
            gstInput.value = gstRate;
            calculateTotals();
        });

        context.querySelectorAll('.item-calc').forEach(input => {
            input.addEventListener('input', calculateTotals);
        });
    };

    const addNewItem = () => {
        const newItem = itemTemplate.cloneNode(true);
        const wrapper = newItem.querySelector('.purchase-item');

        wrapper.querySelectorAll('select, input').forEach(el => {
            const name = el.getAttribute('name');
            if (name) el.setAttribute('name', name.replace('__INDEX__', itemCount));
        });

        itemsContainer.appendChild(wrapper);
        attachListeners(wrapper);
        itemCount++;
        calculateTotals();
    };

    addItemButton.addEventListener('click', addNewItem);

    manualEditButton.addEventListener('click', function () {
        isManualMode = !isManualMode;
        const fields = [subtotalInput, totalGstInput, totalAmountInput];

        if (isManualMode) {
            this.innerHTML = '<i class="fa fa-lock"></i> Lock Totals';
            this.classList.remove('btn-outline-warning');
            this.classList.add('btn-warning');
            fields.forEach(f => f.readOnly = false);
        } else {
            this.innerHTML = '<i class="fa fa-pencil-alt"></i> Manual Edit';
            this.classList.remove('btn-warning');
            this.classList.add('btn-outline-warning');
            fields.forEach(f => f.readOnly = true);
            calculateTotals();
        }
    });

    $('#supplier_id').select2({ theme: 'bootstrap-5', placeholder: 'Select Supplier' });
    addNewItem(); // Add one item on load
});
</script>
@endpush
