@extends('layouts.app')

@section('title', 'Edit Medicine')

@section('content')
    <div class="card-box">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">✏️ Edit Medicine</h3>
            <a href="{{ route('medicines.index') }}" class="btn btn-outline-secondary">
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

        <form action="{{ route('medicines.update', $medicine->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Name:</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $medicine->name) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="hsn_code" class="form-label">HSN Code:</label>
                    <input type="text" class="form-control" id="hsn_code" name="hsn_code" value="{{ old('hsn_code', $medicine->hsn_code) }}">
                </div>
                 <div class="col-md-6 mb-3">
                    <label for="quantity" class="form-label">Quantity:</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" value="{{ old('quantity', $medicine->quantity) }}" required min="0">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="gst_rate" class="form-label">GST Rate (%):</label>
                    <input type="number" class="form-control" id="gst_rate" name="gst_rate" min="0" max="100" step="0.01" value="{{ old('gst_rate', $medicine->gst_rate) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="pack" class="form-label">Pack:</label>
                    <input type="text" class="form-control" id="pack" name="pack" value="{{ old('pack', $medicine->pack) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="company_name" class="form-label">Company Name:</label>
                    <input type="text" class="form-control" id="company_name" name="company_name" value="{{ old('company_name', $medicine->company_name) }}">
                </div>
                <div class="col-md-12 mb-3">
                    <label for="description" class="form-label">Description:</label>
                    <textarea class="form-control" id="description" name="description">{{ old('description', $medicine->description) }}</textarea>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="fa fa-check-circle me-1"></i> Update</button>
            </div>
        </form>
    </div>
@endsection