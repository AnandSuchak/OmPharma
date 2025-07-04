@extends('layouts.app')

@section('title', 'Customer Details')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">👤 Customer Details</h3>
        <div>
            <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-warning">
                <i class="fa fa-pen-to-square me-1"></i> Edit
            </a>
            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="text-primary fw-semibold mb-4">
                <i class="fa fa-user me-2"></i> {{ $customer->name }}
            </h4>

            <div class="row gy-3">
                <div class="col-md-6 col-lg-4">
                    <div><strong>📞 Contact Number:</strong> {{ $customer->contact_number ?? '-' }}</div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div><strong>✉️ Email:</strong> {{ $customer->email ?? '-' }}</div>
                </div>
                <div class="col-md-12 col-lg-8">
                    <div>
                        <strong>📍 Address:</strong>
                        <div class="text-muted">{{ $customer->address ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div><strong>🧾 GST Number:</strong> {{ $customer->gst_number ?? '-' }}</div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div><strong>🏬 Firm Name:</strong> {{ $customer->firm_name ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
