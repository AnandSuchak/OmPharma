@extends('layouts.app')

@section('title', 'Inventory Details')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">📄 Inventory Details: <span class="text-primary">{{ $medicine->name }}</span></h3>
        <a href="{{ route('inventories.index') }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left me-1"></i> Back to Inventory
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>🔢 Batch Number</th>
                        <th>⏳ Expiry Date</th>
                        <th>📦 Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inventoryDetails as $item)
                        <tr>
                            <td>{{ $item->batch_number ?? '-' }}</td>
                            <td>{{ $item->expiry_date ? $item->expiry_date->format('d M, Y') : '-' }}</td>
                            <td>{{ $item->quantity }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted">No batch details found for this medicine.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
