<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\PurchaseBillController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\InventoryLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Platform\ShopController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- GUEST ROUTES ---
// Accessible by anyone who is not logged in.
Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('login', [AuthenticatedSessionController::class, 'store']);
Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');


// --- PLATFORM OWNER ROUTES ---
// ONLY accessible to the user with the 'platform-owner' role.
Route::middleware(['auth', 'platform.owner'])->prefix('platform')->name('platform.')->group(function () {
    Route::get('/', fn() => redirect()->route('platform.shops.index'));
    Route::resource('shops', ShopController::class);
});


// --- TENANT (SHOP) ROUTES ---
// Accessible to all logged-in shop users (super-admin, admin, salesman).
Route::middleware('auth')->group(function () {
    // Redirect the root URL to the dashboard for logged-in shop users
    Route::get('/', fn () => redirect()->route('dashboard.index'));

    // Dashboard route
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Resource routes
    Route::resource('customers', CustomerController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('medicines', MedicineController::class);
    Route::resource('purchase_bills', PurchaseBillController::class);
    Route::resource('sales', SaleController::class);
    Route::resource('inventories', InventoryController::class)->only(['index', 'show']);

    // Sale print routes
    Route::get('/sales/{sale}/print', [SaleController::class, 'print'])->name('sales.print');
    Route::get('/sales/{sale}/print-pdf', [SaleController::class, 'printPdf'])->name('sales.print.pdf');

    // Inventory log route
    Route::get('/inventory-logs', [InventoryLogController::class, 'index'])->name('inventory_logs.index');

    // API Routes
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/medicines/search', [MedicineController::class, 'search'])->name('medicines.search');
        Route::get('/medicines/{medicine}/batches', [MedicineController::class, 'getBatches'])->name('medicines.batches');
        Route::get('/medicines/{medicine}/batches-for-edit', [MedicineController::class, 'getBatchesForEdit'])->name('medicines.batches-for-edit');
        Route::get('/medicines/{medicine}/gst', [MedicineController::class, 'getGstRate'])->name('medicines.gst');
        Route::get('/medicines/search-names', [MedicineController::class, 'searchNames'])->name('medicines.search-names');
        Route::get('/medicines/packs', [MedicineController::class, 'getPacksForName'])->name('medicines.packs');
        Route::get('/medicines/{medicine}/details', [MedicineController::class, 'getDetails'])->name('medicines.details');
        Route::get('/medicines-search', [MedicineController::class, 'search_medicines_ajax'])->name('medicines.search_ajax');
        Route::get('/medicines/search-with-qty', [MedicineController::class, 'searchWithQty'])->name('medicines.searchWithQty');
        Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
        Route::get('/suppliers/search', [SupplierController::class, 'search'])->name('suppliers.search');
    });

    // Medicine fallback details (non-named route)
    Route::get('/medicines/{id}/fallback_details', [MedicineController::class, 'fallbackDetails']);

    // Reports routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::post('/top-medicines', [ReportController::class, 'fetchTopMedicines'])->name('fetch.top-medicines');
        Route::post('/medicine-comparison', [ReportController::class, 'fetchMedicineComparison'])->name('fetch.medicine-comparison');
        Route::post('/medicine-details', [ReportController::class, 'fetchMedicineDetails'])->name('fetch.medicine-details');
        Route::post('/customer-details', [ReportController::class, 'fetchCustomerDetails'])->name('fetch.customer-details');
        Route::post('/supplier-details', [ReportController::class, 'fetchSupplierDetails'])->name('fetch.supplier-details');
    });
});
