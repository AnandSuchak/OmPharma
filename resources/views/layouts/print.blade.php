<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Print')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Optional custom styles for print --}}
    <style>
        body {
            font-size: 14px;
            color: #000;
            background-color: #fff;
            padding: 30px;
        }

        .invoice-container {
            max-width: 900px;
            margin: auto;
        }

        table th, table td {
            vertical-align: middle !important;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                margin: 0;
                padding: 0;
                font-size: 12px;
            }

            .table th,
            .table td {
                padding: 0.4rem;
            }

            .invoice-container {
                margin: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    @yield('content')

    {{-- Optional Print Button --}}
    <div class="text-center no-print mt-4">
        <button class="btn btn-outline-primary" onclick="window.print()">
            <i class="fa fa-print me-1"></i> Print this page
        </button>
    </div>
</body>
</html>
