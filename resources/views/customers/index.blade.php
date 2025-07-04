@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">👥 All Customers</h3>
        <a href="{{ route('customers.create') }}" class="btn btn-primary">
            <i class="fa fa-plus me-1"></i> Add Customer
        </a>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <i class="fa fa-check-circle me-1"></i> {{ $message }}
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>👤 Name</th>
                        <th>📞 Phone</th>
                        <th>✉️ Email</th>
                        <th>🏠 Address</th>
                        <th style="width: 180px;">⚙️ Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->phone_number }}</td>
                            <td>{{ $customer->email ?? '-' }}</td>
                            <td>{{ $customer->address ?? '-' }}</td>
                 <td>
    <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-sm btn-outline-info me-1" title="View">
        <i class="fa fa-eye"></i>
    </a>
    <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-sm btn-outline-primary me-1" title="Edit">
        <i class="fa fa-edit"></i>
    </a>
    <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this customer?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
            <i class="fa fa-trash"></i>
        </button>
    </form>
</td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
