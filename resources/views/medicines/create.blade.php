@extends('layouts.app')

@section('title', 'Create New Medicine')

@section('content')
    <div class="card-box">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">📝 Create New Medicine</h3>
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

        <form action="{{ route('medicines.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Name:</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="hsn_code" class="form-label">HSN Code:</label>
                    <input type="text" class="form-control" id="hsn_code" name="hsn_code" value="{{ old('hsn_code') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="gst_rate" class="form-label">GST Rate (%):</label>
                    <input type="number" class="form-control" id="gst_rate" name="gst_rate" min="0" max="100" step="0.01" value="{{ old('gst_rate') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="pack" class="form-label">Pack:</label>
                    <input type="text" class="form-control" id="pack" name="pack" value="{{ old('pack') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="company_name" class="form-label">Company Name:</label>
                    <input type="text" class="form-control" id="company_name" name="company_name" value="{{ old('company_name') }}">
                </div>
                <div class="col-md-12 mb-3">
                    <label for="description" class="form-label">Description:</label>
                    <textarea class="form-control" id="description" name="description">{{ old('description') }}</textarea>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="fa fa-check-circle me-1"></i> Submit</button>
            </div>
        </form>
    </div>
@endsection