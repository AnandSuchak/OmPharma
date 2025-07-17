@extends('layouts.app')

@section('title', 'Create New Medicine')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">➕ Create New Medicine</h3>
        <a href="{{ route('medicines.index') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left me-1"></i> Back to Medicines
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Whoops!</strong> There were some problems with your input.<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('medicines.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label required">Medicine Name:</label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g., Paracetamol">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        {{-- New div for inline feedback --}}
                        <div id="medicine-suggestions" class="mt-2"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="company_name" class="form-label">Company Name:</label>
                        <input type="text" name="company_name" id="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name') }}" placeholder="e.g., ABC Pharma">
                        @error('company_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="pack" class="form-label">Pack:</label>
                        <input type="text" name="pack" id="pack" class="form-control @error('pack') is-invalid @enderror" value="{{ old('pack') }}" placeholder="e.g., 10 TAB, 20ML">
                        @error('pack')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="hsn_code" class="form-label">HSN Code:</label>
                        <input type="text" name="hsn_code" id="hsn_code" class="form-control @error('hsn_code') is-invalid @enderror" value="{{ old('hsn_code') }}" placeholder="e.g., 3004.90.11">
                        @error('hsn_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="gst_rate" class="form-label">GST Rate (%):</label>
                        <input type="number" step="0.01" name="gst_rate" id="gst_rate" class="form-control @error('gst_rate') is-invalid @enderror" value="{{ old('gst_rate') }}" placeholder="e.g., 12.00">
                        @error('gst_rate')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description:</label>
                    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Any additional notes or details">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="quantity" class="form-label">Initial Quantity (Optional):</label>
                    <input type="number" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', 0) }}" min="0" placeholder="e.g., 100">
                    @error('quantity')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">This will be the initial stock. Stock is primarily managed via purchase bills.</small>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fa fa-save me-1"></i> Add Medicine
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        let typingTimer; // Timer identifier
        const doneTypingInterval = 300; // Time in ms (0.3 seconds)

        $('#name, #company_name').on('keyup', function () {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(checkExistingMedicine, doneTypingInterval);
        });

        $('#name, #company_name').on('keydown', function () {
            clearTimeout(typingTimer);
        });

        function checkExistingMedicine() {
            const medicineName = $('#name').val().trim();
            const companyName = $('#company_name').val().trim();
            const suggestionsDiv = $('#medicine-suggestions');

            if (medicineName.length < 2) { // Require at least 2 characters to search
                suggestionsDiv.html('').removeClass('alert alert-warning');
                return;
            }

            $.ajax({
                url: '{{ route('api.medicines.packs') }}', // Your API endpoint
                method: 'GET',
                data: {
                    name: medicineName,
                    company_name: companyName // Pass company name for more precise search
                },
                success: function(response) {
                    if (response.length > 0) {
                        let packsList = response.map(function(item) {
                            return `<li>Pack: <strong>${item.pack ?? 'N/A'}</strong> (ID: ${item.id})</li>`;
                        }).join('');

                        suggestionsDiv.html(`
                            <div class="alert alert-warning">
                                <strong>Possible Duplicate Found!</strong>
                                <p>Medicine "${medicineName}" already exists with the following pack(s):</p>
                                <ul>${packsList}</ul>
                                <small>Consider updating an existing medicine instead of creating a new one.</small>
                            </div>
                        `);
                    } else {
                        suggestionsDiv.html('').removeClass('alert alert-warning');
                    }
                },
                error: function() {
                    suggestionsDiv.html('<div class="alert alert-danger">Error checking medicine. Please try again.</div>');
                }
            });
        }
    });
</script>
@endpush