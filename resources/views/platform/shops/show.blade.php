@extends('layouts.platform')

@section('title', 'Shop Details')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Shop Details: {{ $shop->name }}</h1>
        <a href="{{ route('platform.shops.index') }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i> Back to List
        </a>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header">Shop Information</div>
                <div class="card-body">
                    <p><strong>Name:</strong> {{ $shop->name }}</p>
                    <p><strong>Status:</strong> <span class="text-capitalize">{{ $shop->status }}</span></p>
                    <p><strong>Created:</strong> {{ $shop->created_at->format('d M Y, h:i A') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">Users in this Shop</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($shop->users as $user)
                                    <tr>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td><span class="badge bg-secondary text-capitalize">{{ str_replace('-', ' ', $user->role) }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No users found for this shop.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
