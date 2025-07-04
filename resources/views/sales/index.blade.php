@extends('layouts.app')

@section('title', 'Sales')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">📦 Sales</h3>
        <a href="{{ route('sales.create') }}" class="btn btn-primary">
            <i class="fa fa-plus me-1"></i> Create New Sale
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>🧾 Bill #</th>
                        <th>👤 Customer</th>
                        <th>📅 Sale Date</th>
                        <th>📌 Status</th>
                        <th>💰 Total Amount</th>
                        <th class="text-center">⚙️  Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td>{{ $sale->bill_number }}</td>
                            <td>{{ $sale->customer->name ?? 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</td>
                            <td>
                                <span class="badge bg-{{ $sale->status === 'Completed' ? 'success' : ($sale->status === 'Pending' ? 'warning text-dark' : 'secondary') }}">
                                    {{ $sale->status }}
                                </span>
                            </td>
                            <td>₹{{ number_format($sale->total_amount, 2) }}</td>
                            <td class="text-center">
                                <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-sm btn-info me-1">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="{{ route('sales.edit', $sale->id) }}" class="btn btn-sm btn-warning me-1">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <form action="{{ route('sales.destroy', $sale->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fa fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No sales records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
