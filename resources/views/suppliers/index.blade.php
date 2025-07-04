@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">🏭 All Suppliers</h3>
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
            <i class="fa fa-plus me-1"></i> Create New Supplier
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
                        <th>Name</th>
                        <th>📞 Phone</th>
                        <th>✉️ Email</th>
                        <th>🧾 GST</th>
                        <th>💊 DLN</th>
                        <th style="width: 180px;">⚙️ Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td>{{ $supplier->name }}</td>
                            <td>{{ $supplier->phone_number }}</td>
                            <td>{{ $supplier->email ?? '-' }}</td>
                            <td>
                                @if ($supplier->gst)
                                    <span class="badge bg-success">{{ $supplier->gst }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if ($supplier->dln)
                                    <span class="badge bg-info text-dark">{{ $supplier->dln }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
    <a href="{{ route('suppliers.show', $supplier->id) }}" class="btn btn-sm btn-outline-info me-1" title="View">
        <i class="fa fa-eye"></i>
    </a>
    <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn btn-sm btn-outline-primary me-1" title="Edit">
        <i class="fa fa-edit"></i>
    </a>
    <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this supplier?')">
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
                            <td colspan="6" class="text-center text-muted">No suppliers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
