{{-- File: resources/views/customers/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">👥 Customers</h3>
        <a href="{{ route('customers.create') }}" class="btn btn-primary">
            <i class="fa fa-plus me-1"></i> Add New Customer
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-3">
        <input type="text" id="customer-search" class="form-control" placeholder="Search by name, phone, or email...">
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone Number</th>
                    <th>Email</th>
                    <th style="width: 220px;">Actions</th>
                </tr>
            </thead>
            <tbody id="customer-table-body">
                @include('customers.partials.customer_table_rows', ['customers' => $customers])
            </tbody>
        </table>
    </div>

    <div id="pagination-links" class="d-flex justify-content-center">
        {{ $customers->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('customer-search');
    const tableBody = document.getElementById('customer-table-body');
    const paginationLinks = document.getElementById('pagination-links');

    let searchTimeout;

    function fetchCustomers(url = '{{ route("customers.index") }}', query = '') {
        const fullUrl = new URL(url);
        if (query) {
            fullUrl.searchParams.set('search', query);
        }

        fetch(fullUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(response => response.json())
        .then(data => {
            tableBody.innerHTML = data.html;
            paginationLinks.innerHTML = data.pagination;
        })
        .catch(error => console.error('Error fetching customers:', error));
    }

    searchInput.addEventListener('keyup', function () {
        clearTimeout(searchTimeout);
        const query = this.value;
        searchTimeout = setTimeout(() => {
            fetchCustomers('{{ route("customers.index") }}', query);
        }, 300); // Debounce for 300ms
    });

    // Handle pagination clicks
    document.addEventListener('click', function (e) {
        if (e.target.closest('#pagination-links a')) {
            e.preventDefault();
            const url = e.target.closest('a').href;
            const query = searchInput.value;
            fetchCustomers(url, query);
        }
    });
});
</script>
@endpush
