@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="fa-solid fa-truck"></i> All Suppliers</h3>
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
            <i class="fa fa-plus me-1"></i> Add Supplier
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
                <input type="text" id="supplier_search_input" class="form-control" placeholder="Search by Name, Phone, Email, or Address...">
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover table-bordered mb-0 align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th>🏷 Name</th>
                        <th>📞 Phone</th>
                        <th>📧 Email</th>
                        <th>📍 Address</th>
                        <th class="text-center" style="width: 180px;">⚙️ Actions</th>
                    </tr>
                </thead>
                <tbody id="suppliers_table_body">
                    @include('suppliers.partials.supplier_table_rows', ['suppliers' => $suppliers])
                </tbody>
            </table>

            {{-- Pagination --}}
            <div class="mt-3 px-2" id="pagination_links">
                {{ $suppliers->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const SUPPLIER_INDEX_URL = "{{ route('suppliers.index') }}";

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('supplier_search_input');
    const tableBody = document.getElementById('suppliers_table_body');
    const paginationLinks = document.getElementById('pagination_links');

    let searchTimeout;

    function fetchSuppliers(searchTerm = '', page = 1) {
        tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">Loading suppliers...</td></tr>`;
        paginationLinks.innerHTML = '';

        fetch(`${SUPPLIER_INDEX_URL}?search=${encodeURIComponent(searchTerm)}&page=${page}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error: ${response.status}`);
            return response.json();
        })
        .then(data => {
            tableBody.innerHTML = data.html;
            paginationLinks.innerHTML = data.pagination;
            attachPaginationListeners();
        })
        .catch(error => {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Error loading suppliers.</td></tr>`;
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
        const searchTerm = searchInput.value;
        fetchSuppliers(searchTerm, page);
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchSuppliers(this.value, 1), 300);
    });

    attachPaginationListeners();
});
</script>
@endpush
