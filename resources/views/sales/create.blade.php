@extends('layouts.app')

@section('title', isset($sale) ? 'Edit Sale' : 'Create New Sale')

@push('styles')
<style>
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 38px;
        padding-top: 0.2rem;
        padding-bottom: 0.2rem;
    }
    .sale-item-wrapper.border-danger {
        border: 2px solid var(--bs-danger) !important;
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-danger-rgb), .25) !important;
    }
</style>
@endpush

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">{{ isset($sale) ? '✏️ Edit Sale' : '📝 Create New Sale' }}</h3>
        <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i> Back</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Whoops!</strong> Please fix the following issues:
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ isset($sale) ? route('sales.update', $sale->id) : route('sales.store') }}" method="POST">
        @csrf
        @if(isset($sale)) @method('PUT') @endif
        <input type="hidden" id="deleted_items" name="deleted_items" value="">

        {{-- Sale Details --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light"><h5 class="card-title mb-0 text-primary"><i class="fa fa-info-circle me-1"></i>Sale Details</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="customer_id" class="form-label fw-semibold">👤 Customer</label>
                        <select class="form-select" id="customer_id" name="customer_id" data-placeholder="Select or search customer..." required>
                            <option></option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id', $sale->customer_id ?? '') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="sale_date" class="form-label fw-semibold">📅 Sale Date</label>
                        <input type="date" class="form-control" id="sale_date" name="sale_date" value="{{ old('sale_date', isset($sale) ? $sale->sale_date->toDateString() : now()->toDateString()) }}" required>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sale Items --}}
        <h5 class="mb-3"><i class="fa fa-capsules me-1"></i>Sale Items</h5>
        <div id="sale_items_container" 
             data-search-url="{{ route('api.medicines.search') }}" 
             data-batch-base-url="{{ route('api.medicines.batches', ['medicine' => 'PLACEHOLDER']) }}"> 

            @if(isset($sale) && !old('new_sale_items') && !old('existing_sale_items'))
                @foreach ($sale->saleItems as $item)
                    <div class="sale-item-wrapper" 
                        data-existing-item="true"
                        data-item-id="{{ $item->id }}"
                        data-medicine-id="{{ $item->medicine_id }}"
                        data-medicine-name="{{ $item->medicine->name_and_company }}"
                        data-batch-number="{{ $item->batch_number }}"
                        data-quantity="{{ $item->quantity }}"
                        data-free-quantity="{{ $item->free_quantity }}"
                        data-sale-price="{{ $item->sale_price }}"
                        data-gst-rate="{{ $item->gst_rate }}"
                        data-discount-percentage="{{ $item->discount_percentage }}"
                        data-ptr="{{ $item->ptr ?? '' }}"
                        data-pack="{{ $item->medicine?->pack ?? '' }}">
                        @include('sales.partials.sale_item_row', [
                            'item' => $item,
                            'index' => $loop->index,
                            'prefix' => "existing_sale_items[{$item->id}]"
                        ])
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Action Buttons and Totals --}}
        <div class="d-flex justify-content-between align-items-center mt-3">
            <button type="button" id="add_new_item" class="btn btn-success"><i class="fa fa-plus me-1"></i> Add Item</button>
            <div class="text-end" style="width: 250px;">
                <div class="d-flex justify-content-between mb-1">
                    <strong>Subtotal:</strong>
                    <span>₹<span id="subtotal">0.00</span></span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <strong>Total GST:</strong>
                    <span>₹<span id="total_gst">0.00</span></span>
                </div>
                <hr class="my-1">
                <div class="d-flex justify-content-between h5 mb-0">
                    <strong>Grand Total:</strong>
                    <strong>₹<span id="grand_total">0.00</span></strong>
                </div>
            </div>
        </div>
        <hr class="my-4">
        <div class="text-end">
            <button type="submit" class="btn btn-primary btn-lg">{{ isset($sale) ? 'Update Sale' : 'Create Sale' }}</button>
        </div>
    </form>
</div>

{{-- Template for JavaScript to clone --}}
<template id="sale_item_template">
    <div class="sale-item-wrapper">
        @include('sales.partials.sale_item_row', [
            'item' => null,
            'index' => '__INDEX__',
            'prefix' => 'new_sale_items[__PREFIX__]'
        ])
    </div>
</template>
@endsection

@push('scripts')
<script>
    window.oldInput = {
        new_items: @json(old('new_sale_items')),
        existing_items: @json(old('existing_sale_items'))
    };
</script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('js/sale-items.js?v=1.6') }}"></script>
@endpush
