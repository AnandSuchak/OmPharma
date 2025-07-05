@extends('layouts.app')

@section('title', 'Purchase Bills')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="fa-solid fa-receipt nav-icon"></i> All Purchase Bills</h3>
        <a href="{{ route('purchase_bills.create') }}" class="btn btn-primary">
            <i class="fa fa-plus me-1"></i> Create New Bill
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
                        <th>🏢 Supplier</th>
                        <th>📅 Date</th>
                        <th>ℹ️ Status</th>
                        <th class="text-end">💰 Total</th>
                        <th class="text-center">⚙️ Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchaseBills as $bill)
                        <tr>
                            <td>#{{ $bill->bill_number }}</td>
                            <td>{{ $bill->supplier->name }}</td>
                            <td>{{ $bill->bill_date->format('d M, Y') }}</td>
                            <td>
                                <span class="badge bg-primary rounded-pill">{{ $bill->status }}</span>
                            </td>
                            <td class="text-end">₹{{ number_format($bill->total_amount, 2) }}</td>
                            <td class="text-center">
                                <form action="{{ route('purchase_bills.destroy', $bill->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this bill?')">
                                    <a href="{{ route('purchase_bills.show', $bill->id) }}" class="btn btn-sm btn-outline-info me-1" title="View">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <a href="{{ route('purchase_bills.edit', $bill->id) }}" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                        <i class="fa fa-pen-to-square"></i>
                                    </a>
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
                            <td colspan="6" class="text-center text-muted">No purchase bills found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-3 px-2">
                {{ $purchaseBills->links() }}
            </div>

        </div>
    </div>
</div>
@endsection
