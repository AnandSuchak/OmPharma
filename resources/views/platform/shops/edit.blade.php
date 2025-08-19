@extends('layouts.platform')

@section('title', 'Edit Shop')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Edit Shop: {{ $shop->name }}</h1>
                <a href="{{ route('platform.shops.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i> Back to List
                </a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('platform.shops.update', $shop) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Shop Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $shop->name) }}" required>
                        </div>

                        <div class="mb-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" {{ old('status', $shop->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="trial" {{ old('status', $shop->status) == 'trial' ? 'selected' : '' }}>Trial</option>
                                <option value="suspended" {{ old('status', $shop->status) == 'suspended' ? 'selected' : '' }}>Suspended</option>
                            </select>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Update Shop</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
