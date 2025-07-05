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
        <div class="card-header bg-light text-dark fw-semibold">
            <i class="fa fa-address-card me-2"></i> Basic Information
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label text-muted">Full Name</label>
                    <div class="fw-bold">{{ $customer->name }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">Contact Number</label>
                    <div>{{ $customer->contact_number ?? '-' }}</div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label text-muted">Email</label>
                    <div>{{ $customer->email ?? '-' }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">Address</label>
                    <div>{{ $customer->address ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header bg-light text-dark fw-semibold">
            <i class="fa fa-file-invoice me-2"></i> Tax & License Details
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label text-muted">Drug License Number (DLN)</label>
                    <div>{{ $customer->dln ?? '-' }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">GST Number</label>
                    <div>
                        @if ($customer->gst_number)
                            <span class="badge bg-success">{{ $customer->gst_number }}</span>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label text-muted">PAN Number</label>
                    <div>{{ $customer->pan_number ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
