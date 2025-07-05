@extends('layouts.app')

@section('title', 'Add New Customer')

@section('content')
    <div class="card-box">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">➕ Add New Customer</h3>
            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left me-1"></i> Back
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger rounded shadow-sm">
                <strong>Whoops!</strong> Please fix the following issues:
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li><i class="fa fa-exclamation-circle text-danger me-1"></i>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('customers.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="contact_number" class="form-label">Contact Number</label>
                    <input type="text" class="form-control" id="contact_number" name="contact_number" value="{{ old('contact_number') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="dln" class="form-label">Drug License Number (DLN) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="dln" name="dln" value="{{ old('dln') }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="gst_number" class="form-label">GST Number</label>
                    <input type="text" class="form-control" id="gst_number" name="gst_number" value="{{ old('gst_number') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="pan_number" class="form-label">PAN Number</label>
                    <input type="text" class="form-control" id="pan_number" name="pan_number" value="{{ old('pan_number') }}">
                </div>
                <div class="col-md-12 mb-4">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2">{{ old('address') }}</textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary px-4">
                <i class="fa fa-plus-circle me-1"></i> Add Customer
            </button>
        </form>
    </div>
    
@endsection
@push('scripts')
<script src="{{ asset('js/customer-validation.js') }}"></script>
@endpush
