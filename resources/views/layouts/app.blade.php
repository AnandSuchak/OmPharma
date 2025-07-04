<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PharmaPro')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS Frameworks -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-5-theme/1.3.0/select2-bootstrap-5-theme.min.css" rel="stylesheet"/>

    <!-- Custom CSS -->
    <link href="{{ asset('css/app-custom.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <div class="page-wrapper d-flex">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="/" class="sidebar-brand">
                    <i class="fa-solid fa-pills"></i>
                    <span>PharmaPro</span>
                </a>
            </div>
            <ul class="sidebar-nav">
                <li class="nav-item"><a class="nav-link" href="#"><i class="fa-solid fa-tachometer-alt nav-icon"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('sales.index') }}"><i class="fa-solid fa-cart-shopping nav-icon"></i> Sales</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('purchase_bills.index') }}"><i class="fa-solid fa-receipt nav-icon"></i> Purchases</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('inventories.index') }}"><i class="fa-solid fa-boxes-stacked nav-icon"></i> Inventory</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('medicines.index') }}"><i class="fa-solid fa-tablets nav-icon"></i> Medicines</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('suppliers.index') }}"><i class="fa-solid fa-truck-field nav-icon"></i> Suppliers</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('customers.index') }}"><i class="fa-solid fa-users nav-icon"></i> Customers</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content flex-grow-1">
            <div class="content-wrapper px-4 py-4">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- JS Scripts -->
    <!-- Load jQuery before Select2 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.select2, .select2-medicine, .select2-batch').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: function () {
                    return $(this).data('placeholder') || 'Select an option';
                }
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
