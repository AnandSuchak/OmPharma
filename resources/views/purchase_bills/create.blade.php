@extends('layouts.app')

@section('title', 'Create New Purchase Bill')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">📝 Create New Purchase Bill</h3>
        <a href="{{ route('purchase_bills.index') }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left me-1"></i> Back
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Whoops!</strong> Please fix the following issues:
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('purchase_bills.store') }}" method="POST">
        @csrf

        <div class="card shadow-sm mb-4">
            <div class="card-header"><h5 class="mb-0">Bill Details</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="supplier_id" class="form-label">Supplier:</label>
                        <select class="form-select" id="supplier_id" name="supplier_id" required>
                            <option value="">Select Supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="bill_number" class="form-label">Bill Number:</label>
                        <input type="text" class="form-control" id="bill_number" name="bill_number" value="{{ old('bill_number') }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="bill_date" class="form-label">Bill Date:</label>
                        <input type="date" class="form-control" id="bill_date" name="bill_date" value="{{ old('bill_date', now()->toDateString()) }}" required>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODIFIED: Added span for item count display --}}
        <h5 class="mb-3">Purchase Bill Items (<span id="purchase_item_count_display">0</span>)</h5>
        <div id="purchase_items_container" data-search-url="{{ route('api.medicines.search-names') }}"></div>

        <div class="row mt-4">
    {{-- This is the column for the Add Item button --}}
    <div class="col-md-2">
        <button type="button" id="add_new_item" class="btn btn-success">
            <i class="fa fa-plus me-1"></i> Add Item
        </button>
    </div>

    {{-- This is the column for the Totals card --}}
    <div class="col-md-10">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <h5 class="card-title mb-0">Totals</h5>
                    <button type="button" id="toggle_manual_edit" class="btn btn-sm btn-outline-warning">
                        <i class="fa fa-pencil-alt"></i> Manual Edit
                    </button>
                </div>
                {{-- MODIFIED: This is the restructured row for the total fields --}}
                <div class="row g-2">
                    {{-- Column 1 (Left Side - 3 fields) --}}
                    <div class="col-md-6">
                        <div class="row g-2">
                            <div class="col-12">
                                <label for="extra_discount_amount" class="form-label small">Extra Discount (₹)</label>
                                <input type="number" step="0.01" class="form-control" id="extra_discount_amount" name="extra_discount_amount" value="{{ old('extra_discount_amount', $purchaseBill->extra_discount_amount ?? 0) }}">
                            </div>
                            <div class="col-12">
                                <label for="subtotal_amount" class="form-label small">Subtotal (w/o GST)</label>
                                <input type="number" step="0.01" class="form-control" id="subtotal_amount" name="subtotal_amount" value="{{ old('subtotal_amount') }}" readonly>
                            </div>
                            <div class="col-12">
                                <label for="total_gst_amount" class="form-label small">Total GST</label>
                                <input type="number" step="0.01" class="form-control" id="total_gst_amount" name="total_gst_amount" value="{{ old('total_gst_amount') }}" readonly>
                            </div>
                        </div>
                    </div>

                    {{-- Column 2 (Right Side - 3 fields) --}}
                    <div class="col-md-6">
                        <div class="row g-2">
                            <div class="col-12">
                                <label for="original_grand_total_amount" class="form-label small">Original Grand Total</label>
                                {{-- For edit, calculate original total from DB values --}}
                                <input type="number" step="0.01" class="form-control" id="original_grand_total_amount" value="{{ old('original_grand_total_amount', isset($purchaseBill) ? ($purchaseBill->total_amount - $purchaseBill->rounding_off_amount) : 0.00) }}" readonly>
                            </div>
                            <div class="col-12">
                                <label for="rounding_off_amount" class="form-label small">Rounding Off</label>
                                <input type="number" step="0.01" class="form-control" id="rounding_off_amount" name="rounding_off_amount" value="{{ old('rounding_off_amount', $purchaseBill->rounding_off_amount ?? 0.00) }}" readonly>
                            </div>
                            <div class="col-12">
                                <label for="total_amount" class="form-label small fw-bold">Grand Total</label>
                                <input type="number" step="0.01" class="form-control fw-bold" id="total_amount" name="total_amount" value="{{ old('total_amount', $purchaseBill->total_amount ?? 0.00) }}" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
        <hr class="my-4">

        <div class="text-end">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fa fa-check-circle me-1"></i> Submit Bill
            </button>
        </div>
    </form>
</div>

<template id="purchase_item_template">
    <div class="card mb-3 purchase-item">
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-4"><label class="form-label">Medicine Name:</label><select class="form-select medicine-name-select" required></select></div>
                <div class="col-md-2 pack-selector-container" style="display: none;"><label class="form-label">Pack:</label><select class="form-select pack-select" name="new_purchase_items[__INDEX__][medicine_id]"></select></div>
                <div class="col-md-3"><label class="form-label">Batch Number:</label><input type="text" class="form-control" name="new_purchase_items[__INDEX__][batch_number]"></div>
                <div class="col-md-3"><label class="form-label">Expiry Date:</label><input type="text" class="form-control expiry-date" name="new_purchase_items[__INDEX__][expiry_date]" placeholder="MM/YY" pattern="^(0[1-9]|1[0-2])\/\d{2}$"></div>
            </div>
            <div class="row mb-2">
                <div class="col"><label class="form-label">Qty:</label><input type="number" class="form-control item-calc" name="new_purchase_items[__INDEX__][quantity]" value="1" min="0" step="0.01" required></div>
                <div class="col"><label class="form-label">FQ:</label><input type="number" class="form-control" name="new_purchase_items[__INDEX__][free_quantity]" value="0" min="0" step="0.01"></div>
                <div class="col"><label class="form-label">Price:</label><input type="number" class="form-control item-calc" name="new_purchase_items[__INDEX__][purchase_price]" step="0.01" min="0" required></div>
                <div class="col"><label class="form-label">MRP:</label><input type="number" class="form-control" name="new_purchase_items[__INDEX__][ptr]" step="0.01" min="0"></div>
                <div class="col"><label class="form-label">Sell Price:</label><input type="number" class="form-control" name="new_purchase_items[__INDEX__][sale_price]" step="0.01" min="0" required></div>
                <div class="col"><label class="form-label">Cust. Disc%:</label><input type="number" class="form-control" name="new_purchase_items[__INDEX__][discount_percentage]" value="0" step="0.01" min="0"></div>
                
                {{-- Existing "Our Disc%" field --}}
                <div class="col">
                    <label class="form-label">Our Disc%:</label>
                    <input type="number" class="form-control item-calc our-discount-percentage-input" name="new_purchase_items[__INDEX__][our_discount_percentage]" value="0" step="0.01" min="0" max="100">
                </div>
                
                {{-- NEW: Our Discount (₹) field --}}
                <div class="col">
                    <label class="form-label">Our Disc (₹):</label>
                    <input type="number" class="form-control item-calc our-discount-amount-input" value="0.00" step="0.01" min="0">
                    {{-- This field's value will be calculated and is not submitted directly. --}}
                </div>
                
                <div class="col"><label class="form-label">GST%:</label><input type="number" class="form-control item-calc gst-rate" name="new_purchase_items[__INDEX__][gst_rate]" step="0.01" min="0" readonly></div>
                <div class="col"><label class="form-label">Row Total (₹):</label><input type="text" class="form-control row-total" readonly></div>
            </div>
            <div class="text-end"><button type="button" class="btn btn-danger btn-sm remove-item"><i class="fa fa-trash"></i> Remove</button></div>
        </div>
    </div>
</template>
@endsection

@push('scripts')
@push('scripts')
<script>
    // Define the URL for AJAX calls to the index page
    const PURCHASE_BILLS_INDEX_URL = "{{ route('purchase_bills.index') }}";

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('purchase_bill_search_input');
        const tableBody = document.getElementById('purchase_bills_table_body');
        const paginationLinks = document.getElementById('pagination_links');

        // Basic check to ensure elements exist before attaching listeners
        if (!searchInput || !tableBody || !paginationLinks) {
            console.error('Purchase bill search elements not found. Check IDs in Blade template.');
            return;
        }

        let searchTimeout; // For debouncing the search input

        // Function to fetch and render purchase bills via AJAX
        function fetchPurchaseBills(searchTerm = '', page = 1) {
            // Display a loading message
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Loading purchase bills...</td></tr>`;
            paginationLinks.innerHTML = ''; // Clear old pagination

            // Make the AJAX request
            fetch(`${PURCHASE_BILLS_INDEX_URL}?search=${encodeURIComponent(searchTerm)}&page=${page}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest' // Important for Laravel's $request->ajax() helper
                }
            })
            .then(response => {
                if (!response.ok) { // Check if the HTTP response was successful
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json(); // Parse the JSON response
            })
            .then(data => {
                // Update the table body with new HTML rows
                tableBody.innerHTML = data.html;
                // Update the pagination links
                paginationLinks.innerHTML = data.pagination;
                // Re-attach event listeners to the newly loaded pagination links
                attachPaginationListeners();
            })
            .catch(error => {
                console.error('Error fetching purchase bills:', error);
                // Display an error message in the table
                tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error loading purchase bills.</td></tr>`;
                paginationLinks.innerHTML = ''; // Clear pagination on error
            });
        }

        // Function to attach click listeners to pagination links
        function attachPaginationListeners() {
            // Remove any existing listeners to prevent multiple bindings if pagination is reloaded
            paginationLinks.querySelectorAll('.page-link').forEach(link => {
                link.removeEventListener('click', handlePaginationClick);
            });

            // Add click listeners to all new (or existing) pagination links
            paginationLinks.querySelectorAll('.page-link').forEach(link => {
                link.addEventListener('click', handlePaginationClick);
            });
        }

        // Handler for pagination link clicks
        function handlePaginationClick(event) {
            event.preventDefault(); // Stop the default link navigation
            const url = new URL(event.target.href); // Get the URL from the clicked link
            const page = url.searchParams.get('page') || 1; // Extract the page number
            const searchTerm = searchInput.value; // Get the current value from the search input
            fetchPurchaseBills(searchTerm, page); // Fetch data for the new page with current search term
        }

        // Event listener for the search input field (with debouncing)
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout); // Clear any previous timeout to reset the debounce timer
            const searchTerm = this.value; // Get the current value of the search input
            searchTimeout = setTimeout(() => {
                fetchPurchaseBills(searchTerm, 1); // Trigger search after a delay, always starting from page 1
            }, 300); // 300ms debounce delay
        });

        // Initial setup: Attach listeners to the pagination links that are present on the first page load
        attachPaginationListeners();
    });
</script>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    window.oldPurchaseItems = @json(old('purchase_items', []));
</script>
<script src="{{ asset('js/purchase-items.js') }}"></script>
@endpush