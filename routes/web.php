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

// --- Main App Routes ---
// Redirect the root URL to the dashboard for a better user experience.
Route::get('/', fn() => redirect()->route('dashboard.index'));

// Define the main dashboard route.
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

// Resourceful routes for all major features.
Route::resource('customers', CustomerController::class);
Route::resource('suppliers', SupplierController::class);
Route::resource('medicines', MedicineController::class);
Route::resource('purchase_bills', PurchaseBillController::class);
Route::resource('sales', SaleController::class);
Route::resource('inventories', InventoryController::class)->only(['index', 'show']);

// --- Custom Routes ---
Route::get('/sales/{sale}/print', [SaleController::class, 'print'])->name('sales.print');
Route::get('/sales/{sale}/print-pdf', [SaleController::class, 'printPdf'])->name('sales.print.pdf');


// --- API / DATA-FETCHING ROUTES ---
// Group all AJAX/API routes under a common prefix and name for consistency.
Route::prefix('api')->name('api.')->group(function () {
    // Medicine related API routes
    Route::get('/medicines/search', [MedicineController::class, 'search'])->name('medicines.search');
    Route::get('/medicines/{medicine}/batches', [MedicineController::class, 'getBatches'])->name('medicines.batches');
    Route::get('/medicines/{medicine}/gst', [MedicineController::class, 'getGstRate'])->name('medicines.gst');
    Route::get('/medicines/search-names', [MedicineController::class, 'searchNames'])->name('medicines.search-names');
    Route::get('/medicines/packs', [MedicineController::class, 'getPacksForName'])->name('medicines.packs');
    Route::get('/medicines/{medicine}/details', [MedicineController::class, 'getDetails'])->name('medicines.details');
    Route::get('/medicines/search-with-qty', [MedicineController::class, 'searchWithQty'])->name('medicines.searchWithQty');
    Route::get('/medicines-search', [MedicineController::class, 'search_medicines_ajax'])->name('medicines.search_ajax');
    Route::get('/medicines/{medicine}/batches-for-edit', [MedicineController::class, 'getBatchesForEdit'])->name('medicines.batches-for-edit');
    
    // CORRECTED: Customer and Supplier search routes are now correctly named.
    Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::get('/suppliers/search', [SupplierController::class, 'search'])->name('suppliers.search');
});


// --- REPORTS ---
// This group ensures all report URLs start with /reports and are named reports.*
Route::prefix('reports')->name('reports.')->group(function () {
    // Main page for reports
    Route::get('/', [ReportController::class, 'index'])->name('index');

    // API-like routes for fetching report data
    Route::post('/top-medicines', [ReportController::class, 'fetchTopMedicines'])->name('fetch.top-medicines');
    Route::post('/medicine-comparison', [ReportController::class, 'fetchMedicineComparison'])->name('fetch.medicine-comparison');
    Route::post('/medicine-details', [ReportController::class, 'fetchMedicineDetails'])->name('fetch.medicine-details');
    Route::post('/customer-details', [ReportController::class, 'fetchCustomerDetails'])->name('fetch.customer-details');
    Route::post('/supplier-details', [ReportController::class, 'fetchSupplierDetails'])->name('fetch.supplier-details');
});


Route::get('/medicines/{medicine}/details', [MedicineController::class, 'getMedicineDetails']);
Route::get('/inventory-logs', [InventoryLogController::class, 'index'])->name('inventory_logs.index');