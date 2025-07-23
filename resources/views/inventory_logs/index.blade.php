@extends('layouts.app') {{-- Assuming you have a master layout --}}

@section('title', 'Inventory & Log Comparison')

@push('styles')
<style>
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 38px;
        padding-top: 0.2rem;
        padding-bottom: 0.2rem;
    }
    .table-match-success {
        background-color: #d4edda !important; /* Bootstrap .bg-success-subtle */
    }
    .table-match-danger {
        background-color: #f8d7da !important; /* Bootstrap .bg-danger-subtle */
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <h3 class="mb-4">📊 Inventory & Log Comparison</h3>

    {{-- Filter/Search Form --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0 text-primary"><i class="fa fa-filter me-1"></i> Filter Data</h5>
        </div>
        <div class="card-body">
            <form id="filterForm" action="{{ route('inventory_logs.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="medicine_id" class="form-label">Medicine Name:</label>
                    <select name="medicine_id" id="medicine_id" class="form-select select2-medicine">
                        <option value="">-- Select Medicine --</option>
                        @foreach($medicines as $medicine)
                            <option value="{{ $medicine->id }}" {{ $selectedMedicineId == $medicine->id ? 'selected' : '' }}>
                                {{ $medicine->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="batch_number" class="form-label">Batch Number:</label>
                    <input type="text" name="batch_number" id="batch_number" class="form-control" value="{{ $selectedBatchNumber }}" placeholder="Enter Batch Number">
                </div>
                <div class="col-md-2 align-self-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="show_only_unmatched" name="show_only_unmatched" value="true" {{ $showOnlyUnmatched ? 'checked' : '' }}>
                        <label class="form-check-label" for="show_only_unmatched">
                            Show Only Unmatched
                        </label>
                    </div>
                </div>
                <div class="col-md-3 align-self-end">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search me-1"></i> Filter</button>
                    <a href="{{ route('inventory_logs.index') }}" class="btn btn-outline-secondary"><i class="fa fa-undo me-1"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        {{-- Inventory (Current Stock) Section --}}
        <div class="col-lg-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Current Inventory Stock</h5>
                </div>
                <div class="card-body">
                    @if($inventories->isEmpty())
                        <p class="text-center">No current inventory records found for the selected filters.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Medicine</th>
                                        <th>Batch</th>
                                        <th>Expiry Date</th>
                                        <th>Current Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($inventories as $inv)
                                    <tr>
                                        <td>{{ $inv->medicine->name ?? 'N/A' }}</td>
                                        <td>{{ $inv->batch_number }}</td>
                                        <td>{{ $inv->expiry_date ? $inv->expiry_date->format('Y-m-d') : 'N/A' }}</td>
                                        <td>{{ $inv->quantity }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Inventory Logs History Section --}}
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">Inventory Log History</h5>
                </div>
                <div class="card-body">
                    @if($inventoryLogs->isEmpty())
                        <p class="text-center">No inventory log entries found for the selected filters.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>Medicine</th>
                                        <th>Batch</th>
                                        <th>Change</th>
                                        <th>Log Qty</th>
                                        <th>Current Inv Qty</th> {{-- NEW COLUMN HEADER --}}
                                        <th class="text-center">Match?</th> {{-- NEW COLUMN HEADER --}}
                                        <th>Type</th>
                                        <th>Reference</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($inventoryLogs as $log)
                                    <tr class="{{ $log->is_matched ? 'table-match-success' : 'table-match-danger' }}"> {{-- Apply coloring --}}
                                        <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td>{{ $log->medicine->name ?? $log->medicine_id }}</td>
                                        <td>{{ $log->batch_number }}</td>
                                        <td>
                                            @if($log->quantity_change > 0)
                                                <span class="text-success">+{{ $log->quantity_change }}</span>
                                            @elseif($log->quantity_change < 0)
                                                <span class="text-danger">{{ $log->quantity_change }}</span>
                                            @else
                                                {{ $log->quantity_change }}
                                            @endif
                                        </td>
                                        <td>{{ $log->new_quantity_on_hand }}</td>
                                        <td>{{ $log->current_inventory_qty }}</td> {{-- NEW COLUMN DATA --}}
                                        <td class="text-center">
                                            @if($log->is_matched)
                                                <i class="fa-solid fa-check text-success"></i>
                                            @else
                                                <i class="fa-solid fa-xmark text-danger"></i>
                                            @endif
                                        </td> {{-- NEW COLUMN DATA --}}
                                        <td>{{ ucwords(str_replace('_', ' ', $log->transaction_type)) }}</td>
                                        <td>
                                            @if($log->transaction_reference)
                                                {{ str_replace('App\\Models\\', '', $log->transaction_reference_type) }} #{{ $log->transaction_reference_id }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ $log->notes ?? 'N/A' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $inventoryLogs->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
{{-- Corrected URL for select2-bootstrap-5-theme.min.js - if it's still 404, you might omit this or download locally --}}
<script src="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.js"></script> 

<script>
    $(document).ready(function() {
        $('.select2-medicine').select2({
            theme: 'bootstrap-5',
            placeholder: 'Search for medicine...',
            allowClear: true,
        });

        // JavaScript for "Show Only Unmatched" checkbox
        $('#show_only_unmatched').on('change', function() {
            $('#filterForm').submit(); // Submit the form when the checkbox changes
        });
    });
</script>
@endpush