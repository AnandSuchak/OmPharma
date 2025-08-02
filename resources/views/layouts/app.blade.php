<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PharmaPro')</title>

    <!-- Fonts & Styles -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-5-theme/1.3.0/select2-bootstrap-5-theme.min.css" rel="stylesheet"/>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
        }

        .navbar .nav-link.active,
        .navbar .nav-link.show {
            font-weight: bold;
            color: #0d6efd !important;
        }

        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }

        main {
            margin-top: 72px; /* To push below fixed navbar */
            padding: 1.5rem 1rem;
        }
    </style>

    <link href="{{ asset('css/app-custom.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>

<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="{{ route('dashboard.index') }}">
            <i class="fa-solid fa-pills me-2 text-primary"></i> PharmaPro
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav" aria-controls="topNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="topNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard.index') }}">Dashboard</a>
                </li>

                <!-- Sales -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('sales.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">Sales</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('sales.create') }}">New Sale</a></li>
                        <li><a class="dropdown-item" href="{{ route('sales.index') }}">View All Sales</a></li>
                    </ul>
                </li>

                <!-- Purchases -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('purchase-bills.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">Purchases</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('purchase_bills.create') }}">New Purchase</a></li>
                        <li><a class="dropdown-item" href="{{ route('purchase_bills.index') }}">View All Purchases</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('inventories.index') ? 'active' : '' }}" href="{{ route('inventories.index') }}">Inventory</a>
                </li>

                <!-- Medicines -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('medicines.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">Medicines</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('medicines.create') }}">Add Medicine</a></li>
                        <li><a class="dropdown-item" href="{{ route('medicines.index') }}">View All Medicines</a></li>
                    </ul>
                </li>

                <!-- Suppliers -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">Suppliers</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('suppliers.create') }}">Add Supplier</a></li>
                        <li><a class="dropdown-item" href="{{ route('suppliers.index') }}">View All Suppliers</a></li>
                    </ul>
                </li>

                <!-- Customers -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">Customers</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('customers.create') }}">Add Customer</a></li>
                        <li><a class="dropdown-item" href="{{ route('customers.index') }}">View All Customers</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('reports.index') ? 'active' : '' }}" href="{{ route('reports.index') }}">Reports</a>
                </li>
               
            </ul>

            <!-- Profile Dropdown -->
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-user-circle me-2"></i> {{ Auth::user()->name ?? 'User' }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- Main Content -->
<main class="container">
    @yield('content')
</main>

<!-- Scripts -->
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
