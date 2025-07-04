@extends('layouts.app')

@section('title', 'Edit Purchase Bill')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">✏️ Edit Purchase Bill</h3>
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

    <form action="{{ route('purchase_bills.update', $purchaseBill->id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Bill Details --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h5 class="card-title mb-0">Bill Details</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="supplier_id" class="form-label">Supplier:</label>
                        <select class="form-select select2-basic" id="supplier_id" name="supplier_id" required>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ $purchaseBill->supplier_id == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="bill_number" class="form-label">Bill Number:</label>
                        <input type="text" class="form-control" id="bill_number" name="bill_number" value="{{ $purchaseBill->bill_number }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="bill_date" class="form-label">Bill Date:</label>
                        <input type="date" class="form-control" id="bill_date" name="bill_date" value="{{ $purchaseBill->bill_date->toDateString() }}" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Status:</label>
                        <select class="form-select" id="status" name="status">
                            <option value="Received" {{ $purchaseBill->status == 'Received' ? 'selected' : '' }}>Received</option>
                            <option value="Pending" {{ $purchaseBill->status == 'Pending' ? 'selected' : '' }}>Pending</option>
                            <option value="Cancelled" {{ $purchaseBill->status == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label for="notes" class="form-label">Notes:</label>
                        <textarea class="form-control" id="notes" name="notes" rows="1">{{ $purchaseBill->notes ?? '' }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Items Section --}}
        <h5 class="mb-3">Purchase Bill Items</h5>
        <div id="purchase_items_container">
            @foreach ($purchaseBill->purchaseBillItems as $item)
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
                                <input type="text" class="form-control" name="existing_items[{{ $item->id }}][batch_number]" value="{{ $item->batch_number }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Expiry Date:</label>
                                <input type="date" class="form-control" name="existing_items[{{ $item->id }}][expiry_date]" value="{{ $item->expiry_date->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col"><label class="form-label">Qty:</label><input type="number" class="form-control item-calc" name="existing_items[{{ $item->id }}][quantity]" value="{{ $item->quantity }}" required></div>
                            <div class="col"><label class="form-label">Purchase Price:</label><input type="number" class="form-control item-calc" name="existing_items[{{ $item->id }}][purchase_price]" value="{{ $item->purchase_price }}" required></div>
                            <div class="col"><label class="form-label">PTR:</label><input type="number" class="form-control" name="existing_items[{{ $item->id }}][ptr]" value="{{ $item->ptr }}"></div>
                            <div class="col"><label class="form-label">Selling Price:</label><input type="number" class="form-control" name="existing_items[{{ $item->id }}][sale_price]" value="{{ $item->sale_price }}" required></div>
                            <div class="col"><label class="form-label">Discount %:</label><input type="number" class="form-control item-calc" name="existing_items[{{ $item->id }}][discount_percentage]" value="{{ $item->discount_percentage }}"></div>
                            <div class="col"><label class="form-label">GST Rate %:</label><input type="number" class="form-control gst-rate item-calc" name="existing_items[{{ $item->id }}][gst_rate]" value="{{ $item->gst_rate }}"></div>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-danger btn-sm remove-item"><i class="fa fa-trash"></i> Remove</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Add Button + Totals --}}
        <div class="row mt-4 align-items-start">
            <div class="col-md-6">
                <button type="button" id="add_new_item" class="btn btn-success"><i class="fa fa-plus me-1"></i> Add New Item</button>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-2">Totals</h5>
                        <div class="mb-2"><label class="form-label">Subtotal:</label><input type="number" step="0.01" id="subtotal_amount" class="form-control" name="subtotal_amount" readonly></div>
                        <div class="mb-2"><label class="form-label">Total GST:</label><input type="number" step="0.01" id="total_gst_amount" class="form-control" name="total_gst_amount" readonly></div>
                        <div><label class="form-label">Grand Total:</label><input type="number" step="0.01" id="total_amount" class="form-control fw-bold" name="total_amount" readonly></div>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">
        <div class="text-end">
            <button type="submit" class="btn btn-primary"><i class="fa fa-check-circle me-1"></i> Update Bill</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const itemTemplate = `
        <div class="card mb-3 purchase-item">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-md-5">
                        <label class="form-label">Medicine:</label>
                        <select class="form-select select2-medicine" name="new_items[__INDEX__][medicine_id]" required>
                            <option value="">Select Medicine</option>
                            @foreach ($medicines as $medicine)
                                <option value="{{ $medicine->id }}" data-gst="{{ $medicine->gst_rate }}">{{ $medicine->name }} ({{ $medicine->company_name ?? 'Generic' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Batch Number:</label>
                        <input type="text" class="form-control" name="new_items[__INDEX__][batch_number]" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Expiry Date:</label>
                        <input type="date" class="form-control" name="new_items[__INDEX__][expiry_date]" required>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col"><input type="number" class="form-control item-calc" name="new_items[__INDEX__][quantity]" placeholder="Qty" required></div>
                    <div class="col"><input type="number" class="form-control item-calc" name="new_items[__INDEX__][purchase_price]" placeholder="Purchase Price" required></div>
                    <div class="col"><input type="number" class="form-control" name="new_items[__INDEX__][ptr]" placeholder="PTR"></div>
                    <div class="col"><input type="number" class="form-control" name="new_items[__INDEX__][sale_price]" placeholder="Selling Price" required></div>
                    <div class="col"><input type="number" class="form-control item-calc" name="new_items[__INDEX__][discount_percentage]" placeholder="Discount %"></div>
                    <div class="col"><input type="number" class="form-control gst-rate item-calc" name="new_items[__INDEX__][gst_rate]" placeholder="GST %"></div>
                </div>
                <div class="text-end">
                    <button type="button" class="btn btn-danger btn-sm remove-item"><i class="fa fa-trash"></i> Remove</button>
                </div>
            </div>
        </div>
    `;

    let itemIndex = 0;
    const container = document.getElementById('purchase_items_container');

    const recalculateTotals = () => {
        let subtotal = 0, totalGst = 0;
        document.querySelectorAll('.purchase-item').forEach(item => {
            const q = parseFloat(item.querySelector('[name*="[quantity]"]')?.value) || 0;
            const price = parseFloat(item.querySelector('[name*="[purchase_price]"]')?.value) || 0;
            const gst = parseFloat(item.querySelector('[name*="[gst_rate]"]')?.value) || 0;
            const disc = parseFloat(item.querySelector('[name*="[discount_percentage]"]')?.value) || 0;

            const base = q * price;
            const discounted = base - (base * disc / 100);
            subtotal += discounted;
            totalGst += discounted * gst / 100;
        });
        document.getElementById('subtotal_amount').value = subtotal.toFixed(2);
        document.getElementById('total_gst_amount').value = totalGst.toFixed(2);
        document.getElementById('total_amount').value = (subtotal + totalGst).toFixed(2);
    };

    document.getElementById('add_new_item').addEventListener('click', () => {
        const html = itemTemplate.replace(/__INDEX__/g, itemIndex++);
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        const element = wrapper.firstElementChild;
        container.appendChild(element);

        $(element).find('.select2-medicine').select2({ theme: 'bootstrap-5', width: '100%' });
        element.querySelectorAll('.item-calc').forEach(input => input.addEventListener('input', recalculateTotals));
        element.querySelector('.remove-item').addEventListener('click', () => {
            element.remove();
            recalculateTotals();
        });
    });

    document.querySelectorAll('.item-calc').forEach(i => i.addEventListener('input', recalculateTotals));
    document.querySelectorAll('.remove-item').forEach(btn => btn.addEventListener('click', e => {
        e.target.closest('.purchase-item').remove();
        recalculateTotals();
    }));
    $('.select2-medicine, .select2-basic').select2({ theme: 'bootstrap-5', width: '100%' });
    recalculateTotals();
});
</script>
@endpush
