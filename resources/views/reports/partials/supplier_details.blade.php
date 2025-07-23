<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Supplier Ledger: <span class="text-primary">{{ $supplier->name }}</span></h2>
        <div class="text-end">
            <h4 class="mb-0">Total Business</h4>
            <p class="fs-3 fw-bold text-success mb-0">₹{{ number_format($totalBusiness, 2) }}</p>
        </div>
    </div>

    {{-- Purchase History --}}
    <div class="card shadow-sm">
        <div class="card-header fw-bold">
            Complete Purchase History
        </div>
        <div class="card-body">
            <div class="accordion" id="supplierPurchaseAccordion">
                @forelse($purchases as $purchase)
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#purchase-{{ $purchase->id }}">
                                <div class="w-100 d-flex justify-content-between pe-3">
                                    <strong>Bill No: {{ $purchase->bill_no }}</strong>
                                    <span>Date: {{ $purchase->bill_date->format('d-M-Y') }}</span>
                                    <span>Amount: <span class="badge bg-info">₹{{ number_format($purchase->total_amount, 2) }}</span></span>
                                </div>
                            </button>
                        </h2>
                        <div id="purchase-{{ $purchase->id }}" class="accordion-collapse collapse" data-bs-parent="#supplierPurchaseAccordion">
                            <div class="accordion-body">
                                <h5>Items in this Bill</h5>
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Medicine</th>
                                            <th>Batch</th>
                                            <th>Quantity</th>
                                            <th>Purchase Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($purchase->purchaseBillItems as $item)
                                            <tr>
                                                <td>{{ $item->medicine?->name ?? 'N/A' }}</td>
                                                <td>{{ $item->batch_number }}</td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>₹{{ number_format($item->purchase_price, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted p-3">No purchase history found for this supplier.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
