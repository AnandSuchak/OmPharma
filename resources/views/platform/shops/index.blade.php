{{-- We will create this platform layout in the next step --}}
@extends('layouts.platform')

@section('title', 'Manage Shops')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Manage Shops</h1>
        <a href="{{ route('platform.shops.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus me-2"></i> Create New Shop
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Shop Name</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($shops as $shop)
                            <tr>
                                <td>{{ $shop->id }}</td>
                                <td>{{ $shop->name }}</td>
                                <td>
                                    @if ($shop->status == 'active')
                                        <span class="badge bg-success">Active</span>
                                    @elseif ($shop->status == 'suspended')
                                        <span class="badge bg-danger">Suspended</span>
                                    @else
                                        <span class="badge bg-warning">Trial</span>
                                    @endif
                                </td>
                                <td>{{ $shop->created_at->format('d-M-Y') }}</td>
                                <td>
                                    <a href="{{ route('platform.shops.show', $shop) }}" class="btn btn-sm btn-outline-info">View</a>
                                    <a href="{{ route('platform.shops.edit', $shop) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('platform.shops.destroy', $shop) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this shop and all its data?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No shops found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $shops->links() }}
            </div>
        </div>
    </div>
@endsection
