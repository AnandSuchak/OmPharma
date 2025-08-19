@extends('layouts.platform')

@section('title', 'Create New Shop')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Create New Shop</h1>
                <a href="{{ route('platform.shops.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i> Back to List
                </a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <p class="fw-bold">Please fix the following errors:</p>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('platform.shops.store') }}" method="POST">
                        @csrf
                        
                        <h5 class="mb-3">Shop Details</h5>
                        <div class="mb-4">
                            <label for="shop_name" class="form-label">Shop Name</label>
                            <input type="text" class="form-control" id="shop_name" name="shop_name" value="{{ old('shop_name') }}" required>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">Super Admin User for this Shop</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="admin_name" class="form-label">Admin Name</label>
                                <input type="text" class="form-control" id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="admin_email" class="form-label">Admin Email</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" value="{{ old('admin_email') }}" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="admin_password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label for="admin_password_confirmation" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="admin_password_confirmation" name="admin_password_confirmation" required>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Create Shop and Admin User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
