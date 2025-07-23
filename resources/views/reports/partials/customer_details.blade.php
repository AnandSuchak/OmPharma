<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Customer Ledger: <span class="text-primary">{{ $customer->name }}</span></h2>
        <div class="text-end">
            <h4 class="mb-0">Total Business</h4>
            <p class="fs-3 fw-bold text-success mb-0">₹{{ number_format($totalBusiness, 2) }}</p>
        </div>
    </div>

    {{-- Sales History --}}
    <div class="card shadow-sm">
        <div class="card-header fw-bold">
            Complete Sales History
        </div>
        <div class="card-body">
            <div class="accordion" id="customerSalesAccordion">
                @forelse($sales as $sale)
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sale-{{ $sale->id }}">
                                <div class="w-100 d-flex justify-content-between pe-3">
                                    <strong>Bill No: {{ $sale->bill_number }}</strong>
                                    <span>Date: {{ $sale->sale_date->format('d-M-Y') }}</span>
                                    <span>Amount: <span class="badge bg-info">₹{{ number_format($sale->total_amount, 2) }}</span></span>
                                </div>
                            </button>
                        </h2>
                        <div id="sale-{{ $sale->id }}" class="accordion-collapse collapse" data-bs-parent="#customerSalesAccordion">
                            <div class="accordion-body">
                                <h5>Items in this Bill</h5>
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Medicine</th>
                                            <th>Batch</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sale->saleItems as $item)
                                            <tr>
                                                <td>{{ $item->medicine?->name ?? 'N/A' }}</td>
                                                <td>{{ $item->batch_number }}</td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>₹{{ number_format($item->price, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted p-3">No sales history found for this customer.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
