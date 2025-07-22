@extends('layouts.app')

@section('title', 'Medicines')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between mb-3">
                <h4 class="mb-0">💊 All Medicines</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Medicines</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="card-title mb-0">Medicine List</h3>
                        <a href="{{ route('medicines.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus me-1"></i> Create New Medicine
                        </a>
                    </div>

                    {{-- Alerts --}}
                    @if ($message = Session::get('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fa fa-check-circle me-1"></i> {{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if ($message = Session::get('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fa fa-exclamation-triangle me-1"></i> {{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Search --}}
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-6 col-lg-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa fa-search"></i></span>
                                <input type="text" id="medicine-search-input" class="form-control" placeholder="Search by Name, Company, or HSN Code...">
                            </div>
                        </div>
                    </div>

                    {{-- Table Container --}}
                    <div id="medicines-table-container">
                        @include('medicines.partials.medicine_table')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const fetch_medicines = (url) => {
        $.ajax({
            url: url,
            method: 'GET',
            success: function(data) {
                $('#medicines-table-container').html(data);
            },
            error: function(xhr) {
                console.error("AJAX Error:", xhr.responseText);
                alert("An error occurred while fetching medicines. Please try again.");
            }
        });
    }

    let debounceTimer;
    $('#medicine-search-input').on('keyup', function() {
        clearTimeout(debounceTimer);
        const query = $(this).val();
        debounceTimer = setTimeout(function() {
            let url = "{{ route('medicines.index') }}?search=" + encodeURIComponent(query);
            fetch_medicines(url);
        }, 300);
    });

    $(document).on('click', '#medicines-table-container .pagination a', function(event) {
        event.preventDefault();
        let url = $(this).attr('href');
        const currentSearchQuery = $('#medicine-search-input').val();
        if (currentSearchQuery) {
            url = new URL(url);
            url.searchParams.set('search', currentSearchQuery);
            url = url.toString();
        }
        fetch_medicines(url);
    });
});
</script>
@endpush
