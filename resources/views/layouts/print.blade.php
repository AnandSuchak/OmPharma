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
            size: A5 landscape;
            margin: 5mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            font-size: 11px;
            color: #000;
            background-color: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .invoice-container {
            width: 100%;
        }
        
        table {
            font-size: 10px;
            page-break-inside: avoid !important;
        }

        /* NEW: Rule to make the header row taller */
        table thead th {
            padding-top: 8px;
            padding-bottom: 8px;
            vertical-align: middle; /* Ensures text stays centered vertically */
        }
        
        table.table-bordered th,
        table.table-bordered td {
            border: 1px solid #000 !important;
            vertical-align: middle;
        }
        
        .text-center-column {
            text-align: center;
        }

        .no-print {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 999;
        }

        .printable-page {
            page-break-inside: avoid;
            border: 2px solid #000;
        }
        .printable-page + .printable-page {
            page-break-before: always;
        }

        .printable-page .container.border {
            border: 1.5px solid #000 !important;
        }

        .row > .border-end {
            border-color: #000 !important;
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