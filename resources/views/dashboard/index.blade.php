@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid py-3">
    {{-- Date Filter --}}
    <div class="card shadow-sm rounded-3 mb-4">
        <div class="card-body">
            <form action="{{ route('dashboard') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="start_date" class="form-label fw-bold text-muted">From</label>
                    <input type="date" name="start_date" id="start_date" class="form-control"
                        value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label fw-bold text-muted">To</label>
                    <input type="date" name="end_date" id="end_date" class="form-control"
                        value="{{ request('end_date', now()->endOfMonth()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4 text-end">
                    <button type="submit" class="btn btn-outline-primary px-4">Apply Filter</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card shadow-sm rounded-3 border-0">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Total Sales</h6>
                    <h4 class="fw-bold text-success">₹{{ number_format($totalSales, 2) }}</h4>
                    <p class="text-muted small mb-0">GST Received: ₹{{ number_format($totalGstReceived, 2) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm rounded-3 border-0">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Total Purchases</h6>
                    <h4 class="fw-bold text-info">₹{{ number_format($totalPurchases, 2) }}</h4>
                    <p class="text-muted small mb-0">GST Paid: ₹{{ number_format($totalGstPaid, 2) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm rounded-3 border-0">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Total Purchase Items</h6>
                    <h4 class="fw-bold text-warning">{{ $totalPurchaseItems }}</h4>
                    <p class="text-muted small mb-0">Items purchased in the selected period</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
    <div class="card shadow-sm rounded-3 border-0">
        <div class="card-body text-center">
            <h6 class="text-muted mb-2">Total Bills Generated</h6>
            <h4 class="fw-bold text-primary">{{ $totalBillsGenerated }}</h4>
            <p class="text-muted small mb-0">Bills generated in the selected period</p>
        </div>
    </div>
</div>
    </div>

    {{-- Lists --}}
    <div class="row g-4 mt-2">
        <div class="col-md-6">
            <div class="card shadow-sm rounded-3 border-0">
                <div class="card-header bg-light fw-bold">Most Selling Products</div>
                <ul class="list-group list-group-flush">
                    @forelse($mostSellingProducts as $item)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{ $item->medicine->name }}
                            <span class="badge bg-primary rounded-pill">{{ $item->total_quantity_sold }} sold</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No sales data for this period.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm rounded-3 border-0">
                <div class="card-header bg-light fw-bold">Products Expiring Soon (Next 30 Days)</div>
                <ul class="list-group list-group-flush">
                    @forelse($expiringSoon as $inventory)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $inventory->medicine->name }}</strong><br>
                                <small class="text-muted">Batch: {{ $inventory->batch_number }} | Quantity: {{ $inventory->quantity }}</small>
                            </div>
                            <span class="badge bg-danger rounded-pill">
                                {{ \Carbon\Carbon::parse($inventory->expiry_date)->format('d-M-Y') }}
                            </span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No products expiring soon.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card shadow-sm rounded-3 border-0">
            <div class="card-header bg-light fw-bold">Top 5 Selling Products (Last 28 Days)</div>
            <div class="card-body d-flex justify-content-center">
                <div style="width: 350px; height: 350px;">
                    <canvas id="topSellingChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm rounded-3 border-0">
            <div class="card-header bg-light fw-bold">Top 5 Purchased Products (Last 28 Days)</div>
            <div class="card-body d-flex justify-content-center">
                <div style="width: 350px; height: 350px;">
                    <canvas id="topPurchasedChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
    {{-- Purchase Trends Chart --}}
    <div class="row mt-5">
        <div class="col-12">
            <div class="card shadow-sm rounded-3 border-0">
                <div class="card-header bg-light fw-bold">
                    Purchase Trends (Last 28 Days)
                </div>
                <div class="card-body">
                    <canvas id="purchaseChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Sales Trends Chart --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm rounded-3 border-0">
                <div class="card-header bg-light fw-bold">
                    Sales Trends (Last 28 Days)
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Top Selling Products Chart
    const topSellingCtx = document.getElementById('topSellingChart').getContext('2d');
    const topSellingData = @json($topSellingProducts);

    new Chart(topSellingCtx, {
        type: 'pie',
        data: {
            labels: topSellingData.map(item => item.name),
            datasets: [{
                data: topSellingData.map(item => item.quantity),
                backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#9966FF'],
            }]
        },
        options: {
            plugins: {
                datalabels: {
                    formatter: (value, ctx) => {
                        const dataArr = ctx.chart.data.datasets[0].data;
                        const total = dataArr.reduce((acc, val) => acc + val, 0);
                        return ((value / total) * 100).toFixed(1) + "%";
                    },
                    color: '#fff',
                    font: { weight: 'bold', size: 13 }
                },
                legend: { position: 'bottom' }
            }
        },
        plugins: [ChartDataLabels]
    });

    // Top Purchased Products Chart
    const topPurchasedCtx = document.getElementById('topPurchasedChart').getContext('2d');
    const topPurchasedData = @json($topPurchasedProducts);

    new Chart(topPurchasedCtx, {
        type: 'pie',
        data: {
            labels: topPurchasedData.map(item => item.name),
            datasets: [{
                data: topPurchasedData.map(item => item.quantity),
                backgroundColor: ['#FF9F40', '#FFCD56', '#4BC0C0', '#36A2EB', '#9966FF'],
            }]
        },
        options: {
            plugins: {
                datalabels: {
                    formatter: (value, ctx) => {
                        const dataArr = ctx.chart.data.datasets[0].data;
                        const total = dataArr.reduce((acc, val) => acc + val, 0);
                        return ((value / total) * 100).toFixed(1) + "%";
                    },
                    color: '#fff',
                    font: { weight: 'bold', size: 13 }
                },
                legend: { position: 'bottom' }
            }
        },
        plugins: [ChartDataLabels]
    });

    // Purchase Chart
    const purchaseCtx = document.getElementById('purchaseChart').getContext('2d');
    const purchaseData = @json($purchaseTrends);

    new Chart(purchaseCtx, {
        type: 'bar',
        data: {
            labels: purchaseData.map(item => item.date),
            datasets: [{
                label: 'Total Purchases (₹)',
                data: purchaseData.map(item => item.total),
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                borderRadius: 5,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => '₹' + v } } }
        }
    });

    // Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesData = @json($salesTrends);

    new Chart(salesCtx, {
        type: 'bar',
        data: {
            labels: salesData.map(item => item.date),
            datasets: [{
                label: 'Total Sales (₹)',
                data: salesData.map(item => item.total),
                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1,
                borderRadius: 5,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => '₹' + v } } }
        }
    });
});
</script>
@endpush
@endsection

