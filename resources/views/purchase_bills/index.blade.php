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

    {{-- Search --}}
    <div class="row mb-3 align-items-center">
        <div class="col-md-6 col-lg-4">
            <div class="input-group">
                <span class="input-group-text"><i class="fa fa-search"></i></span>
                <input type="text" id="purchase_bill_search_input" class="form-control" placeholder="Search by Bill # or Supplier Name...">
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover table-bordered mb-0 align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th>📄 Bill #</th>
                        <th>🏢 Supplier</th>
                        <th>📅 Date</th>
                        <th>ℹ️ Status</th>
                        <th class="text-end">💰 Total</th>
                        <th class="text-center" style="width: 180px;">⚙️ Actions</th>
                    </tr>
                </thead>
                <tbody id="purchase_bills_table_body">
                    @forelse ($purchaseBills as $bill)
                        <tr>
                            <td>#{{ $bill->bill_number }}</td>
                            <td>{{ $bill->supplier->name }}</td>
                            <td>{{ $bill->bill_date->format('d M, Y') }}</td>
                            <td><span class="badge bg-primary rounded-pill">{{ $bill->status }}</span></td>
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
            <div class="mt-3 px-2" id="pagination_links">
                {{ $purchaseBills->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const PURCHASE_BILL_INDEX_URL = "{{ route('purchase_bills.index') }}";

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('purchase_bill_search_input');
    const tableBody = document.getElementById('purchase_bills_table_body');
    const paginationLinks = document.getElementById('pagination_links');

    let searchTimeout;

    function fetchPurchaseBills(searchTerm = '', page = 1) {
        tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Loading bills...</td></tr>`;
        paginationLinks.innerHTML = '';

        fetch(`${PURCHASE_BILL_INDEX_URL}?search=${encodeURIComponent(searchTerm)}&page=${page}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            tableBody.innerHTML = data.html;
            paginationLinks.innerHTML = data.pagination;
            attachPaginationListeners();
        })
        .catch(() => {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error loading bills.</td></tr>`;
        });
    }

    function attachPaginationListeners() {
        paginationLinks.querySelectorAll('.page-link').forEach(link => {
            link.removeEventListener('click', handlePaginationClick);
            link.addEventListener('click', handlePaginationClick);
        });
    }

    function handlePaginationClick(event) {
        event.preventDefault();
        const url = new URL(event.currentTarget.href);
        const page = url.searchParams.get('page') || 1;
        fetchPurchaseBills(searchInput.value, page);
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchPurchaseBills(this.value, 1), 300);
    });

    attachPaginationListeners();
});
</script>
@endpush
