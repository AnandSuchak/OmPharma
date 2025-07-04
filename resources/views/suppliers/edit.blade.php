@extends('layouts.app')

@section('title', 'Edit Supplier')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">✏️ Edit Supplier</h3>
        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left me-1"></i> Back
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong><i class="fa fa-exclamation-triangle me-1"></i> Whoops!</strong> There were some problems with your input.
            <ul class="mt-2 mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('suppliers.update', $supplier->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row gy-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">👤 Name:</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ $supplier->name }}" required>
                    </div>

                    <div class="col-md-6">
                        <label for="phone_number" class="form-label">📞 Phone Number:</label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number" value="{{ $supplier->phone_number }}" required>
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">✉️ Email:</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ $supplier->email }}">
                    </div>

                    <div class="col-md-6">
                        <label for="gst" class="form-label">🧾 GST Number:</label>
                        <input type="text" class="form-control" id="gst" name="gst" value="{{ $supplier->gst }}" required>
                    </div>

                    <div class="col-md-12">
                        <label for="address" class="form-label">🏠 Address:</label>
                        <textarea class="form-control" id="address" name="address" rows="2">{{ $supplier->address }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label for="dln" class="form-label">💊 DLN (Drug License Number):</label>
                        <input type="text" class="form-control" id="dln" name="dln" value="{{ $supplier->dln }}" required>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa fa-check-circle me-1"></i> Update Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
