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
                                <option value="{{ $supplier->id }}" {{ old('supplier_id', $purchaseBill->supplier_id) == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
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
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Status:</label>
                        <select class="form-select" id="status" name="status">
                            <option value="Received" {{ old('status', $purchaseBill->status) == 'Received' ? 'selected' : '' }}>Received</option>
                            <option value="Pending" {{ old('status', $purchaseBill->status) == 'Pending' ? 'selected' : '' }}>Pending</option>
                            <option value="Cancelled" {{ old('status', $purchaseBill->status) == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label for="notes" class="form-label">Notes:</label>
                        <textarea class="form-control" id="notes" name="notes" rows="1">{{ old('notes', $purchaseBill->notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Items Section --}}
        <h5 class="mb-3">Purchase Bill Items</h5>
        <div id="purchase_items_container" data-search-url="{{ route('api.medicines.search-names') }}">
            {{-- Loop through existing items from the database or old input --}}
            @php
                $items = old('existing_items', $purchaseBill->purchaseBillItems->mapWithKeys(function ($item) {
                    return [$item->id => $item->toArray()];
                }));
            @endphp
            @foreach ($items as $itemData)
                @include('purchase_bills.partials.existing_item_row', ['item' => (object)$itemData, 'medicines' => $medicines])
            @endforeach
        </div>
        
        <div id="new_items_container">
             {{-- This is for repopulating *new* items if validation fails --}}
             @if(!empty(old('new_purchase_items')))
                @foreach(old('new_purchase_items') as $index => $itemData)
                    {{-- The JS will handle re-creating these items --}}
                @endforeach
            @endif
        </div>

        {{-- Add Button + Totals --}}
        <div class="row mt-4 align-items-start">
            <div class="col-md-6">
                <button type="button" id="add_new_item" class="btn btn-success"><i class="fa fa-plus me-1"></i> Add New Item</button>
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
                                <input type="number" step="0.01" class="form-control" id="subtotal_amount" name="subtotal_amount" value="{{ old('subtotal_amount') }}" readonly>
                            </div>
                            <div class="col-12">
                                <label for="total_gst_amount" class="form-label small">Total GST</label>
                                <input type="number" step="0.01" class="form-control" id="total_gst_amount" name="total_gst_amount" value="{{ old('total_gst_amount') }}" readonly>
                            </div>
                            <div class="col-12">
                                <label for="total_amount" class="form-label small fw-bold">Grand Total</label>
                                <input type="number" step="0.01" class="form-control fw-bold" id="total_amount" name="total_amount" value="{{ old('total_amount') }}" readonly>
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
                <div class="col-md-4">
                    <label class="form-label">Medicine Name:</label>
                    <select class="form-select medicine-name-select" required></select>
                </div>
                <div class="col-md-2 pack-selector-container" style="display: none;">
                    <label class="form-label">Pack:</label>
                    <select class="form-select pack-select" name="new_purchase_items[__INDEX__][medicine_id]" required></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Batch Number:</label>
                    <input type="text" class="form-control" name="new_purchase_items[__INDEX__][batch_number]" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Expiry Date:</label>
                    <input type="date" class="form-control expiry-date" name="new_purchase_items[__INDEX__][expiry_date]" >
                </div>
            </div>
            <div class="row mb-2">
                <div class="col">
                    <label class="form-label">Qty:</label>
                    <input type="number" class="form-control item-calc" name="new_purchase_items[__INDEX__][quantity]" value="1" min="1" required>
                </div>
                <div class="col">
                    <label class="form-label">Purchase Price:</label>
                    <input type="number" class="form-control item-calc" name="new_purchase_items[__INDEX__][purchase_price]" step="0.01" min="0" required>
                </div>
                <div class="col">
                    <label class="form-label">MRP:</label>
                    <input type="number" class="form-control" name="new_purchase_items[__INDEX__][ptr]" step="0.01" min="0">
                </div>
                <div class="col">
                    <label class="form-label">Selling Price:</label>
                    <input type="number" class="form-control" name="new_purchase_items[__INDEX__][sale_price]" step="0.01" min="0" required>
                </div>
                <div class="col">
                    <label class="form-label">Discount (%):</label>
                    <input type="number" class="form-control item-calc" name="new_purchase_items[__INDEX__][discount_percentage]" value="0" step="0.01" min="0">
                </div>
                <div class="col">
                    <label class="form-label">GST Rate (%):</label>
                    <input type="number" class="form-control item-calc gst-rate" name="new_purchase_items[__INDEX__][gst_rate]" step="0.01" min="0" readonly>
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
    // Pass old data for any *new* items that failed validation
    window.oldPurchaseItems = @json(old('new_purchase_items', []));
</script>
<script src="{{ asset('js/purchase-items.js') }}"></script>
@endpush