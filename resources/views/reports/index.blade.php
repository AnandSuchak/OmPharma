@extends('layouts.app')

@section('title', 'Reports')

@push('styles')
    {{-- Add CSRF Token for AJAX requests --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single { height: 38px; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
        #report-results { min-height: 200px; padding: 20px; background-color: #f8f9fa; border-radius: 5px; border: 1px solid #dee2e6; }
        .select2-container--open .select2-dropdown { width: auto !important; min-width: 100%; }
    </style>
@endpush

@section('content')
{{-- This main container now holds all the URLs our JS file needs --}}
<div class="container-fluid" id="reports-container"
    data-search-url="{{ route('api.medicines.search') }}"
    data-top-medicines-url="{{ route('reports.fetch.top-medicines') }}"
    data-comparison-url="{{ route('reports.fetch.medicine-comparison') }}"
    data-details-url="{{ route('reports.fetch.medicine-details') }}"
>
    <h1 class="mb-4">Generate Reports</h1>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="form-group">
                        <label class="d-block mb-2 fw-bold">Select Report Type</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="report_type" id="report_top_medicines" value="top_medicines">
                            <label class="form-check-label" for="report_top_medicines">Top Medicines</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="report_type" id="report_medicine_comparison" value="medicine_comparison">
                            <label class="form-check-label" for="report_medicine_comparison">Medicine Comparison</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="report_type" id="report_medicine_details" value="medicine_details">
                            <label class="form-check-label" for="report_medicine_details">Medicine Details</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters for "Top Medicines" Report --}}
    <div id="top_medicines_filters" class="mt-2" style="display: none;">
        <div class="card">
            <div class="card-header"><h4>Top Medicines Filters</h4></div>
            <div class="card-body">
                <form id="top_medicines_form">
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label for="top_medicines_basis">Basis</label><select id="top_medicines_basis" name="basis" class="form-control"><option value="sale">Top Selling</option><option value="purchase">Top Purchased</option></select></div></div>
                        <div class="col-md-6"><div class="form-group"><label for="top_medicines_limit">Number of Medicines</label><select id="top_medicines_limit" name="limit" class="form-control"><option value="10">Top 10</option><option value="20">Top 20</option><option value="50">Top 50</option></select></div></div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Generate Report</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Filters for "Medicine Comparison" Report --}}
    <div id="medicine_comparison_filters" class="mt-2" style="display: none;">
        <div class="card">
            <div class="card-header"><h4>Medicine Comparison Filters</h4></div>
            <div class="card-body">
                <form id="medicine_comparison_form">
                    <div class="row">
                        <div class="col-md-5"><div class="form-group"><label>Medicine 1</label><select name="medicine_id_1" class="form-control medicine-search" required></select></div></div>
                        <div class="col-md-5"><div class="form-group"><label>Medicine 2</label><select name="medicine_id_2" class="form-control medicine-search" required></select></div></div>
                        <div class="col-md-2"><div class="form-group"><label for="comparison_period">Period</label><select id="comparison_period" name="period" class="form-control"><option value="6">Last 6 Months</option><option value="12" selected>Last 12 Months</option><option value="24">Last 24 Months</option></select></div></div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Generate Comparison Chart</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Filters for "Medicine Details" Report --}}
    <div id="medicine_details_filters" class="mt-2" style="display: none;">
        <div class="card">
            <div class="card-header"><h4>Medicine Details Filter</h4></div>
            <div class="card-body">
                <form id="medicine_details_form">
                    <div class="form-group"><label>Select a Medicine</label><select name="medicine_id" class="form-control medicine-search" required></select></div>
                    <button type="submit" class="btn btn-primary mt-3">Generate Detailed Report</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Results Area --}}
    <div class="row mt-4"><div class="col-12"><div id="report-results"><p class="text-muted text-center">Your report will be displayed here.</p></div></div></div>
</div>
@endsection

@push('scripts')
{{-- All third-party libraries --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

{{-- CORRECTED: Moved JavaScript back inside the view to avoid using mix() --}}
<script>
$(document).ready(function() {
    // --- Global Setup ---
    const reportsContainer = document.getElementById('reports-container');
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const searchUrl = reportsContainer.dataset.searchUrl;
    const topMedicinesUrl = reportsContainer.dataset.topMedicinesUrl;
    const comparisonUrl = reportsContainer.dataset.comparisonUrl;
    const detailsUrl = reportsContainer.dataset.detailsUrl;

    // --- Initialize Select2 for Medicine Search ---
    $('.medicine-search').select2({
        placeholder: 'Search for a medicine',
        minimumInputLength: 2,
        width: '100%',
        dropdownAutoWidth: true,
        ajax: {
            url: searchUrl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return {
                    results: $.map(data, function (item) {
                        return { text: item.text, id: item.id }
                    })
                };
            },
            cache: true
        }
    });

    // --- Main Report Type Switcher ---
    $('input[name="report_type"]').on('change', function () {
        const type = $(this).val();
        $('#top_medicines_filters, #medicine_comparison_filters, #medicine_details_filters').hide();
        $('#report-results').html('<p class="text-muted text-center">Your report will be displayed here.</p>');

        if (type === 'top_medicines') {
            $('#top_medicines_filters').slideDown();
        } else if (type === 'medicine_comparison') {
            $('#medicine_comparison_filters').slideDown();
        } else if (type === 'medicine_details') {
            $('#medicine_details_filters').slideDown();
        }
    });

    // --- Reusable AJAX Function ---
    function generateReport(form, url, resultsDiv) {
        const formData = $(form).serialize();
        resultsDiv.html('<p class="text-center">Generating report...</p>');

        $.ajax({
            type: 'POST',
            url: url,
            data: formData,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (response) {
                resultsDiv.html(response);
            },
            error: function (xhr) {
                let errorString = 'An error occurred. Please check your inputs.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorString = Object.values(xhr.responseJSON.errors).join('<br>');
                }
                resultsDiv.html('<p class="text-center text-danger">' + errorString + '</p>');
            }
        });
    }

    // --- Form Submission Handlers ---
    $('#top_medicines_form').on('submit', function (e) {
        e.preventDefault();
        generateReport(this, topMedicinesUrl, $('#report-results'));
    });

    $('#medicine_details_form').on('submit', function (e) {
        e.preventDefault();
        generateReport(this, detailsUrl, $('#report-results'));
    });

    // --- Handle "Medicine Comparison" Form Submission ---
    let comparisonChart;
    $('#medicine_comparison_form').on('submit', function (e) {
        e.preventDefault();
        const formData = $(this).serialize();
        const resultsDiv = $('#report-results');
        resultsDiv.html('<p class="text-center">Generating chart...</p>');

        $.ajax({
            type: 'POST',
            url: comparisonUrl,
            data: formData,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (data) {
                resultsDiv.html('<canvas id="comparisonChartCanvas"></canvas>');
                const ctx = document.getElementById('comparisonChartCanvas').getContext('2d');
                if (comparisonChart) {
                    comparisonChart.destroy();
                }
                comparisonChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [
                            { label: data.medicine1.name + ' - Sales', data: data.medicine1.sales, borderColor: 'rgba(75, 192, 192, 1)', backgroundColor: 'rgba(75, 192, 192, 0.2)', tension: 0.1 },
                            { label: data.medicine1.name + ' - Purchases', data: data.medicine1.purchases, borderColor: 'rgba(54, 162, 235, 1)', backgroundColor: 'rgba(54, 162, 235, 0.2)', tension: 0.1 },
                            { label: data.medicine2.name + ' - Sales', data: data.medicine2.sales, borderColor: 'rgba(255, 99, 132, 1)', backgroundColor: 'rgba(255, 99, 132, 0.2)', tension: 0.1 },
                            { label: data.medicine2.name + ' - Purchases', data: data.medicine2.purchases, borderColor: 'rgba(255, 159, 64, 1)', backgroundColor: 'rgba(255, 159, 64, 0.2)', tension: 0.1 }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { position: 'top' }, title: { display: true, text: 'Medicine Sales & Purchase Comparison' } },
                        scales: { y: { beginAtZero: true, title: { display: true, text: 'Quantity' } } }
                    }
                });
            },
            error: function (xhr) {
                let errorString = 'An error occurred. Please check your inputs.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorString = Object.values(xhr.responseJSON.errors).join('<br>');
                }
                resultsDiv.html('<p class="text-center text-danger">' + errorString + '</p>');
            }
        });
    });
});
</script>
@endpush
