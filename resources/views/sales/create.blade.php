@extends('layouts.app')

@section('title', isset($sale) ? 'Edit Sale' : 'Create New Sale')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">{{ isset($sale) ? '✏️ Edit Sale' : '📝 Create New Sale' }}</h3>
        <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left me-1"></i> Back
        </a>
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
        @if(isset($sale))
            @method('PUT')
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0 text-primary"><i class="fa fa-info-circle me-1"></i>Sale Details</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="customer_id" class="form-label fw-semibold">👤 Customer</label>
                        <select class="form-select select2" id="customer_id" name="customer_id" data-placeholder="Select Customer" required>
                            <option value="">Select Customer</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" {{ (isset($sale) && $sale->customer_id == $customer->id) ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="sale_date" class="form-label fw-semibold">📅 Sale Date</label>
                        <input type="date" class="form-control" id="sale_date" name="sale_date"
                               value="{{ $sale->sale_date ?? old('sale_date') ?? now()->toDateString() }}" required>
                    </div>
                </div>
            </div>
        </div>

        <h5 class="mb-3"><i class="fa fa-capsules me-1"></i>Sale Items</h5>

        <div id="sale_items_container">
            @if(isset($sale) && $sale->saleItems->isNotEmpty())
                @foreach ($sale->saleItems as $index => $item)
                    @include('sales.partials.sale_item', ['index' => $index, 'item' => $item, 'medicines' => $medicines])
                @endforeach
            @endif
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <button type="button" id="add_new_item" class="btn btn-success">
                <i class="fa fa-plus me-1"></i> Add Item
            </button>

            <div class="text-end">
                <p class="mb-1"><strong>Subtotal:</strong> ₹<span id="subtotal">0.00</span></p>
                <p class="mb-1"><strong>Total GST:</strong> ₹<span id="total_gst">0.00</span></p>
                <h5 class="mb-0"><strong>Grand Total:</strong> ₹<span id="grand_total">0.00</span></h5>
            </div>
        </div>

        <hr class="my-4">

        <div class="text-end">
            <button type="submit" class="btn btn-primary btn-lg">
                {{ isset($sale) ? 'Update Sale' : 'Create Sale' }}
            </button>
        </div>
    </form>
</div>
<style>
    .select2-container--bootstrap-5 .select2-selection {
    min-height: 38px;
    padding: 0.375rem 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    font-size: 1rem;
    line-height: 1.5;
    background-color: #fff;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    padding: 0;
    margin: 0;
    line-height: 1.5;
}

.select2-container--bootstrap-5 .select2-selection--single {
    height: auto;
    display: flex;
    align-items: center;
}

.select2-container--bootstrap-5 .select2-selection__arrow {
    height: 100%;
    right: 0.75rem;
    top: 0.5rem;
}

</style>
<template id="sale_item_template">
    @include('sales.partials.sale_item_template', ['index' => '__INDEX__', 'medicines' => $medicines])
</template>
@endsection

@push('scripts')
<script src="{{ asset('js/sale-items.js') }}"></script>
@endpush
