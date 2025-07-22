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

        <input type="hidden" id="deleted_items" name="deleted_items" value=""> {{-- Assuming you have a hidden input for deleted item IDs --}}


        <div class="card shadow-sm mb-4">
            <div class="card-header"><h5 class="card-title mb-0">Bill Details</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="supplier_id" class="form-label">Supplier:</label>
                        <select class="form-select" id="supplier_id" name="supplier_id" required>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id', $purchaseBill->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="bill_number" class="form-label">Bill Number:</label>
                        <input type="text" class="form-control" id="bill_number" name="bill_number" value="{{ old('bill_number', $purchaseBill->bill_number) }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="bill_date" class="form-label">Bill Date:</label>
                        <input type="date" class="form-control" id="bill_date" name="bill_date" value="{{ old('bill_date', $purchaseBill->bill_date->toDateString()) }}" required>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODIFIED: Added span for item count display --}}
        <h5 class="mb-3">Purchase Bill Items (<span id="purchase_item_count_display">0</span>)</h5>
        
        <div id="purchase_items_container" data-search-url="{{ route('api.medicines.search-names') }}">
            @php
                $originalItems = $purchaseBill->purchaseBillItems->mapWithKeys(function ($item) {
                    return [$item->id => $item->load('medicine')->toArray()];
                });
                $oldInput = old('existing_items', []);
                $items = !empty($oldInput) ? $originalItems->map(function ($item, $id) use ($oldInput) {
                    return isset($oldInput[$id]) ? array_merge($item, $oldInput[$id]) : $item;
                })->all() : $originalItems->all();
            @endphp
            
            @foreach ($items as $id => $itemData)
                <div class="card mb-3 purchase-item" data-item-id="{{ $itemData['id'] }}"> {{-- Added data-item-id for JS removal --}}
                    <div class="card-body">
                        <input type="hidden" name="existing_items[{{ $id }}][id]" value="{{ $itemData['id'] }}">
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label class="form-label">Medicine Name:</label>
                                <select class="form-select medicine-name-select" required
                                    data-selected-id="{{ $itemData['medicine_id'] }}"
                                    data-selected-text="{{ $itemData['medicine']['name'] }} ({{ $itemData['medicine']['company_name'] ?? 'Generic' }})">
                                </select>
                            </div>
                            <div class="col-md-2 pack-selector-container" style="display: none;">
                                <label class="form-label">Pack:</label>
                                <select class="form-select pack-select" name="existing_items[{{ $id }}][medicine_id]"></select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Batch Number:</label>
                                <input type="text" class="form-control" name="existing_items[{{ $id }}][batch_number]" value="{{ $itemData['batch_number'] }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Expiry Date:</label>
                               <input type="text" class="form-control expiry-date" name="existing_items[{{ $id }}][expiry_date]" value="{{ \Carbon\Carbon::parse($itemData['expiry_date'])->format('m/y') }}" placeholder="MM/YY" pattern="^(0[1-9]|1[0-2])\/\d{2}$">
                            </div>
                        </div>
                       <div class="row mb-2">
                            <div class="col">
                                <label class="form-label">Qty:</label>
                                <input type="number" class="form-control item-calc" name="existing_items[{{ $id }}][quantity]"
                                    value="{{ $itemData['quantity'] }}" required min="0" step="0.01">
                            </div>
                            <div class="col">
                                <label class="form-label">FQ:</label>
                                <input type="number" class="form-control item-calc" name="existing_items[{{ $id }}][free_quantity]"
                                    value="{{ $itemData['free_quantity'] ?? 0 }}" min="0" step="0.01">
                            </div>
                            <div class="col">
                                <label class="form-label">Price:</label>
                                <input type="number" class="form-control item-calc" name="existing_items[{{ $id }}][purchase_price]"
                                    value="{{ $itemData['purchase_price'] }}" step="0.01" min="0" required>
                            </div>
                            <div class="col">
                                <label class="form-label">MRP:</label>
                                <input type="number" class="form-control item-calc" name="existing_items[{{ $id }}][ptr]"
                                    value="{{ $itemData['ptr'] }}" step="0.01" min="0">
                            </div>
                            <div class="col">
                                <label class="form-label">Sell Price:</label>
                                <input type="number" class="form-control item-calc" name="existing_items[{{ $id }}][sale_price]"
                                    value="{{ $itemData['sale_price'] }}" step="0.01" min="0" required>
                            </div>
                            <div class="col">
                                <label class="form-label">Cust. Disc%:</label>
                                <input type="number" class="form-control item-calc" name="existing_items[{{ $id }}][discount_percentage]"
                                    value="{{ $itemData['discount_percentage'] }}" step="0.01" min="0" max="100">
                            </div>
                            {{-- Existing "Our Disc%" field --}}
                            <div class="col">
                                <label class="form-label">Our Disc%:</label>
                                <input type="number" class="form-control item-calc our-discount-percentage-input" name="existing_items[{{ $id }}][our_discount_percentage]"
                                    value="{{ $itemData['our_discount_percentage'] }}" step="0.01" min="0" max="100">
                            </div>
                            {{-- NEW: Our Discount (₹) field --}}
                            <div class="col">
                                <label class="form-label">Our Disc (₹):</label>
                                <input type="number" class="form-control item-calc our-discount-amount-input" value="0.00" step="0.01" min="0">
                            </div>
                            <div class="col">
                                <label class="form-label">GST%:</label>
                                <input type="number" class="form-control gst-rate item-calc" name="existing_items[{{ $id }}][gst_rate]"
                                    value="{{ $itemData['gst_rate'] }}" step="0.01" min="0" max="100" readonly>
                            </div>
                            <div class="col">
                                <label class="form-label">Row Total (₹):</label>
                                <input type="text" class="form-control row-total" readonly>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-danger btn-sm remove-item"><i class="fa fa-trash"></i> Remove</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row mt-4 align-items-start">
    {{-- This is the column for the Add Item button --}}
    <div class="col-md-2">
        <button type="button" id="add_new_item" class="btn btn-success"><i class="fa fa-plus me-1"></i> Add New Item</button>
    </div>

    {{-- This is the column for the Totals card --}}
    <div class="col-md-10">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <h5 class="card-title mb-0">Totals</h5>
                    <button type="button" id="toggle_manual_edit" class="btn btn-sm btn-outline-warning">
                        <i class="fa fa-pencil-alt"></i> Manual Edit
                    </button>
                </div>
                {{-- This is the restructured row for the total fields --}}
                <div class="row g-2">
                    {{-- Column 1 (Left Side - 3 fields) --}}
                    <div class="col-md-6">
                        <div class="row g-2">
                            <div class="col-12">
                                <label for="extra_discount_amount" class="form-label small">Extra Discount (₹)</label>
                                <input type="number" step="0.01" class="form-control" id="extra_discount_amount" name="extra_discount_amount" value="{{ old('extra_discount_amount', $purchaseBill->extra_discount_amount ?? 0) }}">
                            </div>
                            <div class="col-12">
                                <label for="subtotal_amount" class="form-label small">Subtotal (w/o GST)</label>
                                <input type="number" step="0.01" class="form-control" id="subtotal_amount" name="subtotal_amount" value="{{ old('subtotal_amount') }}" readonly>
                            </div>
                            <div class="col-12">
                                <label for="total_gst_amount" class="form-label small">Total GST</label>
                                <input type="number" step="0.01" class="form-control" id="total_gst_amount" name="total_gst_amount" value="{{ old('total_gst_amount') }}" readonly>
                            </div>
                        </div>
                    </div>

                    {{-- Column 2 (Right Side - 3 fields) --}}
                    <div class="col-md-6">
                        <div class="row g-2">
                            <div class="col-12">
                                <label for="original_grand_total_amount" class="form-label small">Original Grand Total</label>
                                {{-- For edit, calculate original total from DB values --}}
                                <input type="number" step="0.01" class="form-control" id="original_grand_total_amount" value="{{ old('original_grand_total_amount', isset($purchaseBill) ? ($purchaseBill->total_amount - $purchaseBill->rounding_off_amount) : 0.00) }}" readonly>
                            </div>
                            <div class="col-12">
                                <label for="rounding_off_amount" class="form-label small">Rounding Off</label>
                                <input type="number" step="0.01" class="form-control" id="rounding_off_amount" name="rounding_off_amount" value="{{ old('rounding_off_amount', $purchaseBill->rounding_off_amount ?? 0.00) }}" readonly>
                            </div>
                            <div class="col-12">
                                <label for="total_amount" class="form-label small fw-bold">Grand Total</label>
                                <input type="number" step="0.01" class="form-control fw-bold" id="total_amount" name="total_amount" value="{{ old('total_amount', $purchaseBill->total_amount ?? 0.00) }}" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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

<template id="purchase_item_template">
    <div class="card mb-3 purchase-item">
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-4"><label class="form-label">Medicine Name:</label><select class="form-select medicine-name-select" required></select></div>
                <div class="col-md-2 pack-selector-container" style="display: none;"><label class="form-label">Pack:</label><select class="form-select pack-select" name="new_purchase_items[__INDEX__][medicine_id]"></select></div>
                <div class="col-md-3"><label class="form-label">Batch Number:</label><input type="text" class="form-control" name="new_purchase_items[__INDEX__][batch_number]"></div>
                <div class="col-md-3"><label class="form-label">Expiry Date:</label><input type="text" class="form-control expiry-date" name="new_purchase_items[__INDEX__][expiry_date]" placeholder="MM/YY" pattern="^(0[1-9]|1[0-2])\/\d{2}$"></div>
            </div>
            <div class="row mb-2">
                <div class="col"><label class="form-label">Qty:</label><input type="number" class="form-control item-calc" name="new_purchase_items[__INDEX__][quantity]" value="1" min="0" step="0.01" required></div>
                <div class="col"><label class="form-label">FQ:</label><input type="number" class="form-control" name="new_purchase_items[__INDEX__][free_quantity]" value="0" min="0" step="0.01"></div>
                <div class="col"><label class="form-label">Price:</label><input type="number" class="form-control item-calc" name="new_purchase_items[__INDEX__][purchase_price]" step="0.01" min="0" required></div>
                <div class="col"><label class="form-label">MRP:</label><input type="number" class="form-control" name="new_purchase_items[__INDEX__][ptr]" step="0.01" min="0"></div>
                <div class="col"><label class="form-label">Sell Price:</label><input type="number" class="form-control" name="new_purchase_items[__INDEX__][sale_price]" step="0.01" min="0" required></div>
                <div class="col"><label class="form-label">Cust. Disc%:</label><input type="number" class="form-control" name="new_purchase_items[__INDEX__][discount_percentage]" value="0" step="0.01" min="0"></div>
                <div class="col"><label class="form-label">Our Disc%:</label><input type="number" class="form-control item-calc" name="new_purchase_items[__INDEX__][our_discount_percentage]" value="0" step="0.01" min="0"></div>
                <div class="col"><label class="form-label">GST%:</label><input type="number" class="form-control item-calc gst-rate" name="new_purchase_items[__INDEX__][gst_rate]" step="0.01" min="0" readonly></div>
                <div class="col"><label class="form-label">Row Total (₹):</label><input type="text" class="form-control row-total" readonly></div>
            </div>
            <div class="text-end"><button type="button" class="btn btn-danger btn-sm remove-item"><i class="fa fa-trash"></i> Remove</button></div>
        </div>
    </div>
</template>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    window.oldNewPurchaseItems = @json(old('new_purchase_items', []));
    window.oldExistingPurchaseItems = @json(old('existing_items', [])); // ADDED for existing items on old input
</script>
<script src="{{ asset('js/purchase-items.js') }}"></script>
@endpush