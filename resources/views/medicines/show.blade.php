@extends('layouts.app')

@section('title', 'Medicine Details')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">💊 Medicine Details</h3>
        <div>
            <a href="{{ route('medicines.edit', $medicine->id) }}" class="btn btn-warning">
                <i class="fa fa-pen-to-square me-1"></i> Edit
            </a>
            <a href="{{ route('medicines.index') }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    {{-- Summary Section --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h4 class="text-primary fw-semibold mb-4">
                <i class="fa fa-capsules me-2"></i> {{ $medicine->name }}
            </h4>

            <div class="row gy-3">
                <div class="col-md-6 col-lg-4">
                    <div><strong>🏷️ HSN Code:</strong> {{ $medicine->hsn_code ?? '-' }}</div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div><strong>📦 Pack:</strong> {{ $medicine->pack ?? '-' }}</div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div><strong>💰 GST Rate:</strong> 
                        <span class="badge bg-success">{{ $medicine->gst_rate ? $medicine->gst_rate . '%' : 'N/A' }}</span>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div><strong>🏢 Company:</strong> {{ $medicine->company_name ?? '-' }}</div>
                </div>
                <div class="col-md-12 col-lg-8">
                    <div>
                        <strong>📝 Description:</strong> 
                        <div class="text-muted">{{ $medicine->description ?? 'No description available.' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection