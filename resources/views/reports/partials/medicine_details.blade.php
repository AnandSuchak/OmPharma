<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Medicine Ledger: <span class="text-primary">{{ $medicine->name }}</span></h2>
        <div class="text-end">
            <h4 class="mb-0">Total Available Stock</h4>
            <p class="fs-3 fw-bold text-success mb-0">{{ $totalStock }}</p>
        </div>
    </div>

    {{-- Batch-wise Stock & Sales Details --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header fw-bold">
            Batch-wise Stock Details
        </div>
        <div class="card-body">
            <div class="accordion" id="batchAccordion">
                @forelse($inventoryBatches as $batch)
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-{{ $loop->index }}">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $loop->index }}">
                                <div class="w-100 d-flex justify-content-between pe-3">
                                    <strong>Batch: {{ $batch->batch_number }}</strong>
                                    <span>Current Stock: <span class="badge bg-info">{{ $batch->quantity }}</span></span>
                                    <span>Total Sold: <span class="badge bg-warning text-dark">{{ $batch->total_sold }}</span></span>
                                    <span>Expiry: <span class="badge bg-danger">{{ \Carbon\Carbon::parse($batch->expiry_date)->format('d-M-Y') }}</span></span>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse-{{ $loop->index }}" class="accordion-collapse collapse" data-bs-parent="#batchAccordion">
                            <div class="accordion-body">
                                <h5>Sales from this Batch</h5>
                                @if($batch->sales->isNotEmpty())
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Bill No.</th>
                                                <th>Date</th>
                                                <th>Customer</th>
                                                <th>Qty Sold</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($batch->sales as $saleItem)
                                                <tr>
                                                    <td>{{ $saleItem->sale->bill_number }}</td>
                                                    <td>{{ $saleItem->sale->sale_date->format('d-m-Y') }}</td>
                                                    <td>{{ $saleItem->sale->customer->name ?? 'N/A' }}</td>
                                                    <td>{{ $saleItem->quantity }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <p class="text-muted">No sales have been recorded from this batch yet.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted p-3">No active inventory batches found for this medicine.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Purchase History --}}
    <div class="card shadow-sm">
        <div class="card-header fw-bold">
            Complete Purchase History
        </div>
        <div class="card-body">
            <div class="table-responsive" style="max-height: 400px;">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Bill No.</th>
                            <th>Bill Date</th>
                            <th>Supplier</th>
                            <th>Batch No.</th>
                            <th>Qty Purchased</th>
                            <th>Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseHistory as $item)
                            <tr>
                                <td>{{ $item->purchaseBill->bill_no }}</td>
                                <td>{{ $item->purchaseBill->bill_date->format('d-m-Y') }}</td>
                                <td>{{ $item->purchaseBill->supplier->name ?? 'N/A' }}</td>
                                <td>{{ $item->batch_number }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ \Carbon\Carbon::parse($item->expiry_date)->format('d-M-Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No purchase history found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
