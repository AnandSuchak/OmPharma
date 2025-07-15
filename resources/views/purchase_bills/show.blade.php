@extends('layouts.app')

@section('title', 'Purchase Bill Details')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">📄 Purchase Bill Details</h3>
        <div>
            <a href="{{ route('purchase_bills.edit', $purchaseBill->id) }}" class="btn btn-warning">
                <i class="fa fa-pen-to-square me-1"></i> Edit
            </a>
            <a href="{{ route('purchase_bills.index') }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    {{-- Summary Section --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h4 class="text-primary fw-semibold mb-4">
                <i class="fa-solid fa-receipt me-2"></i> Bill #{{ $purchaseBill->bill_number }}
            </h4>
            <div class="row gy-3">
                <div class="col-md-6 col-lg-4">
                    <div><strong>🏢 Supplier:</strong> {{ $purchaseBill->supplier->name }}</div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div><strong>🗓️ Bill Date:</strong> {{ $purchaseBill->bill_date->format('d M, Y') }}</div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div><strong>ℹ️ Status:</strong> <span class="badge bg-primary rounded-pill">{{ $purchaseBill->status }}</span></div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div><strong>💰 Total Amount:</strong> ₹{{ number_format($purchaseBill->total_amount, 2) }}</div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div><strong>🧾 Total GST:</strong> ₹{{ number_format($purchaseBill->total_gst_amount, 2) }}</div>
                </div>
                <div class="col-md-12 col-lg-8">
                    <div><strong>📝 Notes:</strong> {{ $purchaseBill->notes ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bill Items --}}
    <h4 class="mb-3">Bill Items</h4>
    <div class="card shadow-sm">
        <div class="table-responsive-wrapper">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Medicine</th>
                        <th>Batch #</th>
                        <th>Expiry</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">FQ</th>
                        <th class="text-end">Purchase Price</th>
                        <th class="text-end">MRP</th>
                        <th class="text-end">Selling Price</th>
                        <th class="text-end">GST (%)</th>
                        <th class="text-end">Cust. Disc (%)</th>
                        <!-- This is the new column header -->
                        <th class="text-end">Our Disc (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchaseBill->purchaseBillItems as $item)
                        <tr>
                            <td>{{ $item->medicine->name }}</td>
                            <td>{{ $item->batch_number }}</td>
                            <td>{{ $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('M Y') : 'N/A' }}</td>
                            <td class="text-end">{{ $item->quantity }}</td>
                            <td class="text-end">{{ $item->free_quantity ?? 0 }}</td>
                            <td class="text-end">₹{{ number_format($item->purchase_price, 2) }}</td>
                            <td class="text-end">₹{{ number_format($item->ptr, 2) ?? '-' }}</td>
                            <td class="text-end">₹{{ number_format($item->sale_price, 2) ?? '-' }}</td>
                            <td class="text-end">{{ $item->gst_rate ?? '-' }}%</td>
                            <td class="text-end">{{ $item->discount_percentage ?? '-' }}%</td>
                            <!-- This is the new column data -->
                            <td class="text-end">{{ $item->our_discount_percentage ?? '-' }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4">No items in this purchase bill.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
