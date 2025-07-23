// Wait for the DOM to be fully loaded before running scripts
document.addEventListener('DOMContentLoaded', function () {

    // --- Global Setup ---
    // Read required data (routes, csrf token) from the main container div in the Blade file
    const reportsContainer = document.getElementById('reports-container');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
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
