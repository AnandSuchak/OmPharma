@extends('layouts.app')

@section('title', 'Sales Bills')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="fa-solid fa-receipt nav-icon"></i> All Sales Bills</h3>
        <a href="{{ route('sales.create') }}" class="btn btn-primary">
            <i class="fa fa-plus me-1"></i> Create New Sale
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
                        <th>📄 Bill #</th>
                        <th>👤 Customer</th>
                        <th>📅 Date</th>
                        <th>ℹ️ Status</th>
                        <th class="text-end">💰 Total</th>
                        <th class="text-center">⚙️ Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td>#{{ $sale->bill_number }}</td>
                            <td>{{ $sale->customer->name ?? '-' }}</td>
                            <td>{{ $sale->sale_date->format('d M, Y') }}</td>
                            <td>
                                <span class="badge bg-success rounded-pill">Completed</span>
                            </td>
                            <td class="text-end">₹{{ number_format($sale->total_amount, 2) }}</td>
                            <td class="text-center">
                                <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-sm btn-outline-info me-1" title="View">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="{{ route('sales.edit', $sale->id) }}" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                    <i class="fa fa-pen-to-square"></i>
                                </a>
                                {{-- The Print Bill button has been added back here --}}
                                <a href="{{ route('sales.print', $sale->id) }}" class="btn btn-sm btn-outline-dark me-1" target="_blank" title="Print Bill">
                                    <i class="fa fa-print"></i>
                                </a>
                                <form action="{{ route('sales.destroy', $sale->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this sale?')">
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
                            <td colspan="6" class="text-center text-muted">No sales found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Pagination --}}
            <div class="mt-3 px-2">
                {{ $sales->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection
