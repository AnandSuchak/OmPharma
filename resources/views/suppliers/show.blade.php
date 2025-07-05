@extends('layouts.app')

@section('title', 'Supplier Details')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">🏢 Supplier Details</h3>
        <div>
            <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn btn-warning">
                <i class="fa fa-pen-to-square me-1"></i> Edit
            </a>
            <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body px-4 py-4">
            <div class="mb-4">
                <h4 class="text-primary fw-semibold">
                    <i class="fa fa-user-tie me-2"></i> {{ $supplier->name }}
                </h4>
                <hr>
            </div>

            <div class="row gy-4">
                <div class="col-md-6 col-lg-4">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-phone text-muted me-2"></i>
                        <strong class="me-2">Phone:</strong>
                        <span>{{ $supplier->phone_number ?? '-' }}</span>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-envelope text-muted me-2"></i>
                        <strong class="me-2">Email:</strong>
                        <span>{{ $supplier->email ?? '-' }}</span>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-file-invoice text-muted me-2"></i>
                        <strong class="me-2">GST No.:</strong>
                        <span class="badge bg-light border text-dark">{{ $supplier->gst ?? '-' }}</span>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-capsules text-muted me-2"></i>
                        <strong class="me-2">DLN:</strong>
                        <span class="badge bg-light border text-dark">{{ $supplier->dln ?? '-' }}</span>
                    </div>
                </div>

                <div class="col-md-12 col-lg-8">
                    <div class="d-flex">
                        <i class="fa fa-map-marker-alt text-muted me-2 mt-1"></i>
                        <div>
                            <strong>Address:</strong>
                            <div class="text-muted mt-1">{{ $supplier->address ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
