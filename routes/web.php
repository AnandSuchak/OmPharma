<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MedicineController; // Ensure this is imported
use App\Http\Controllers\PurchaseBillController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CustomerController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- USER INTERFACE ROUTES (Return full pages) ---

// The main dashboard route
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
// CORRECTED: The main dashboard route, now named 'dashboard.index'
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');


// Resourceful routes for all major features
Route::resource('customers', CustomerController::class);
Route::resource('suppliers', SupplierController::class);
Route::resource('medicines', MedicineController::class);
Route::resource('purchase_bills', PurchaseBillController::class);
Route::resource('sales', SaleController::class);

// Custom route for showing detailed inventory for a specific medicine
Route::resource('inventories', InventoryController::class)->only(['index']);
Route::get('/inventories/{medicine}', [InventoryController::class, 'show'])->name('inventories.show');

// --- Custom Sale Routes ---
Route::get('/sales/{sale}/print', [SaleController::class, 'print'])->name('sales.print');
// ADDED: Route for generating the PDF invoice for a sale
Route::get('/sales/{sale}/print-pdf', [SaleController::class, 'printPdf'])->name('sales.print.pdf');


// --- API / DATA-FETCHING ROUTES ---
// These routes are called by your JavaScript to get data.

// CORRECTED: Route to get all batches for a specific medicine ID for the sales form
// It MUST point to MedicineController and its getBatches method
Route::get('/api/medicines/{medicine}/batches', [MedicineController::class, 'getBatches'])->name('api.medicines.batches');

// Route to get the GST rate for a specific medicine
Route::get('/api/medicines/{medicine}/gst', [MedicineController::class, 'getGstRate'])->name('api.medicines.gst');

// Original route for the Select2 AJAX search (for Sales Bill medicine search)
Route::get('/api/medicines/search', [MedicineController::class, 'search'])->name('api.medicines.search');

// New route to search for unique medicine names (likely for Purchase Bill)
Route::get('/api/medicines/search-names', [MedicineController::class, 'searchNames'])->name('api.medicines.search-names');

// New route to get all packs for a given medicine name (likely for Purchase Bill)
Route::get('/api/medicines/packs', [MedicineController::class, 'getPacksForName'])->name('api.medicines.packs');

// Route for medicine details (likely for Purchase Bill or other info display)
Route::get('/api/medicines/{medicine}/details', [MedicineController::class, 'getDetails'])->name('api.medicines.details'); // Added name for consistency

Route::get('/sales/{id}/print', [SaleController::class, 'print'])->name('sales.print');

// NEW ROUTE: For searching medicines WITH AVAILABLE QUANTITY (for Sales form)
Route::get('/api/medicines/search-with-qty', [MedicineController::class, 'searchWithQty'])->name('api.medicines.searchWithQty');

Route::get('/api/medicines-search', [App\Http\Controllers\MedicineController::class, 'search_medicines_ajax'])->name('medicines.search_ajax');

// NEW ROUTE: For fetching batches specifically for editing a sale item
Route::get('/api/medicines/{medicine}/batches-for-edit', [MedicineController::class, 'getBatchesForEdit'])->name('api.medicines.batches-for-edit');
