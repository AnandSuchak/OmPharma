@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">📦 Medicine Inventory</h3>
        <input type="text" id="inventory-search" class="form-control w-25" placeholder="🔍 Search medicine...">
    </div>

    <div id="inventory-list">
        @include('inventories.partials.table', ['inventories' => $inventories])
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchBox = document.getElementById('inventory-search');
    let timer = null;

    function fetchInventory(url = "{{ route('inventories.index') }}") {
        const searchQuery = searchBox.value;

        // --- Start of Fix ---
        // Check if the URL already has a '?' and use '&' or '?' accordingly
        const separator = url.includes('?') ? '&' : '?';
        const finalUrl = `${url}${separator}search=${encodeURIComponent(searchQuery)}`;
        // --- End of Fix ---

        fetch(finalUrl, { // Use the corrected URL
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            document.getElementById('inventory-list').innerHTML = html;
            attachPaginationLinks();
        })
        .catch(error => {
            console.error('AJAX fetch error:', error);
        });
    }

    function attachPaginationLinks() {
        document.querySelectorAll('.pagination a').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const url = this.getAttribute('href');
                if (url) fetchInventory(url);
            });
        });
    }

    searchBox.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(() => fetchInventory(), 400);
    });

    attachPaginationLinks();
});
</script>
@endpush
