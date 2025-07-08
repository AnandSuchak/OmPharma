<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Print')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Font Awesome for a nice print icon --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    {{-- Final Print Styles --}}
    <style>
        @page {
            /* Key Change: Reduced margins to give content more space */
            size: A5 landscape;
            margin: 5mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            font-size: 9.5px; /* Slightly smaller base font for compactness */
            color: #000;
            background-color: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .invoice-container {
            width: 100%; /* Use the full printable area */
        }

        table, tr, td, th {
            page-break-inside: avoid !important;
            font-size: 9px; /* Smaller font for the table */
            vertical-align: middle;
        }

        .no-print {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 999;
        }

        /* --- Simplified & More Reliable Page Break Logic --- */
        /* This wrapper will contain one full page's content */
        .printable-page {
            page-break-inside: avoid;
        }
        /* This adds a page break *before* any subsequent pages */
        .printable-page + .printable-page {
            page-break-before: always;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            .container, .invoice-container {
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button class="btn btn-primary shadow-sm" onclick="window.print()">
            <i class="fa fa-print me-1"></i> Print Invoice
        </button>
    </div>

    @yield('content')
</body>
</html>
