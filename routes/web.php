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

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Root route: redirect to login or dashboard
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard.index')
        : view('auth.login');
});

// Auth-protected routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Resource routes
    Route::resource('customers', CustomerController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('medicines', MedicineController::class);
    Route::resource('purchase_bills', PurchaseBillController::class); // Keep snake_case if that's what your codebase uses
    Route::resource('sales', SaleController::class);
    Route::resource('inventories', InventoryController::class)->only(['index', 'show']);

    // Sale print routes
    Route::get('/sales/{sale}/print', [SaleController::class, 'print'])->name('sales.print');
    Route::get('/sales/{sale}/print-pdf', [SaleController::class, 'printPdf'])->name('sales.print.pdf');

    // Inventory log route
    Route::get('/inventory-logs', [InventoryLogController::class, 'index'])->name('inventory_logs.index');

    // Reports routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::post('/top-medicines', [ReportController::class, 'fetchTopMedicines'])->name('fetch.top-medicines');
        Route::post('/medicine-comparison', [ReportController::class, 'fetchMedicineComparison'])->name('fetch.medicine-comparison');
        Route::post('/medicine-details', [ReportController::class, 'fetchMedicineDetails'])->name('fetch.medicine-details');
        Route::post('/customer-details', [ReportController::class, 'fetchCustomerDetails'])->name('fetch.customer-details');
        Route::post('/supplier-details', [ReportController::class, 'fetchSupplierDetails'])->name('fetch.supplier-details');
    });

    // Medicine fallback details (outside API prefix but protected)
    Route::get('/medicines/{id}/fallback_details', [MedicineController::class, 'fallbackDetails']);
});

// API Routes - also protected
Route::prefix('api')->name('api.')->middleware(['auth'])->group(function () {
    // Medicine
    Route::get('/medicines/search', [MedicineController::class, 'search'])->name('medicines.search');
    Route::get('/medicines/{medicine}/batches', [MedicineController::class, 'getBatches'])->name('medicines.batches');
    Route::get('/medicines/{medicine}/batches-for-edit', [MedicineController::class, 'getBatchesForEdit'])->name('medicines.batches-for-edit');
    Route::get('/medicines/{medicine}/gst', [MedicineController::class, 'getGstRate'])->name('medicines.gst');
    Route::get('/medicines/search-names', [MedicineController::class, 'searchNames'])->name('medicines.search-names');
    Route::get('/medicines/packs', [MedicineController::class, 'getPacksForName'])->name('medicines.packs');
    Route::get('/medicines/{medicine}/details', [MedicineController::class, 'getDetails'])->name('medicines.details');
    Route::get('/medicines-search', [MedicineController::class, 'search_medicines_ajax'])->name('medicines.search_ajax');
    Route::get('/medicines/search-with-qty', [MedicineController::class, 'searchWithQty'])->name('medicines.searchWithQty');

    // Customer & Supplier search
    Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::get('/suppliers/search', [SupplierController::class, 'search'])->name('suppliers.search');
});

// Laravel's built-in authentication routes
require __DIR__.'/auth.php';
