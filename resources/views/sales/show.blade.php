@extends('layouts.app')

@section('title', 'Sale Details')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">🧾 Sale Details</h3>
        <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left me-1"></i> Back to Sales
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light fw-semibold">🗂️ Sale Information</div>
        <div class="card-body">
            <div class="row gy-3">
                <div class="col-md-4"><strong>Bill Number:</strong> {{ $sale->bill_number }}</div>
                <div class="col-md-4"><strong>Customer Name:</strong> {{ $sale->customer->name ?? 'N/A' }}</div>
                <div class="col-md-4"><strong>Sale Date:</strong> {{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</div>
                <div class="col-md-4"><strong>Status:</strong>
                    <span class="badge bg-{{ $sale->status === 'Completed' ? 'success' : ($sale->status === 'Pending' ? 'warning text-dark' : 'secondary') }}">
                        {{ $sale->status }}
                    </span>
                </div>
                <div class="col-md-4"><strong>Total Amount:</strong> ₹{{ number_format($sale->total_amount, 2) }}</div>
                <div class="col-md-4"><strong>Total GST:</strong> ₹{{ number_format($sale->total_gst_amount, 2) }}</div>
                <div class="col-md-12"><strong>Notes:</strong> {{ $sale->notes ?? '—' }}</div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light fw-semibold">🧪 Sale Items</div>
        <div class="card-body table-responsive p-0">
            @if ($sale->saleItems->isNotEmpty())
                <table class="table table-striped table-hover table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Medicine</th>
                            <th>Batch</th>
                            <th>Expiry</th>
                            <th>Qty</th>
                            <th>Selling Price</th>
                            <th>PTR</th>
                            <th>GST %</th>
                            <th>Discount %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sale->saleItems as $item)
                            <tr>
                                <td>{{ $item->medicine->name }}</td>
                                <td>{{ $item->batch_number }}</td>
                                <td>{{ \Carbon\Carbon::parse($item->expiry_date)->format('M Y') }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>₹{{ number_format($item->sale_price, 2) }}</td>
                                <td>{{ $item->ptr ?? '—' }}</td>
                                <td>{{ $item->gst_rate ?? '—' }}</td>
                                <td>{{ $item->discount_percentage ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted m-3">No items in this sale.</p>
            @endif
        </div>
    </div>
</div>
@endsection
