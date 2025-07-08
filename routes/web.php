<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\PurchaseBillController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CustomerController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// --- USER INTERFACE ROUTES (Return full pages) ---

// The main dashboard route
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Resourceful routes for all major features
Route::resource('customers', CustomerController::class);
Route::resource('suppliers', SupplierController::class);
Route::resource('medicines', MedicineController::class);
Route::resource('purchase_bills', PurchaseBillController::class);
Route::resource('sales', SaleController::class);

// Custom route for showing detailed inventory for a specific medicine
Route::resource('inventories', InventoryController::class)->only(['index']);
Route::get('/inventories/{medicine}', [InventoryController::class, 'show'])->name('inventories.show');

// Custom route for generating a printable sales bill
Route::get('/sales/{sale}/bill', [SaleController::class, 'generateBill'])->name('sales.bill');

// Custom route for the print view of a sale
Route::get('/sales/{sale}/print', [SaleController::class, 'print'])->name('sales.print');


// --- AJAX / DATA-FETCHING ROUTES ---
// These routes are called by your JavaScript to get data.

// Route to get all batches for a specific medicine ID
Route::get('/api/medicines/{medicine}/batches', [MedicineController::class, 'getBatches']);

// Route to get the GST rate for a specific medicine
Route::get('/api/medicines/{medicine}/gst', [MedicineController::class, 'getGstRate'])->name('api.medicines.gst');

// Original route for the Select2 AJAX search
Route::get('/api/medicines/search', [MedicineController::class, 'search'])->name('api.medicines.search');

// Route to get batch info from a purchase bill
Route::get('/api/batches/info', [SaleController::class, 'getBatchDetailsFromPurchase']);

// Route to get available quantity for a specific batch
Route::get('/api/sales/medicines/{medicineId}/batches/{batch}/expiry/{expiry}/quantity', [SaleController::class, 'getAvailableQuantity']);


// --- NEW ROUTES FOR PACK SELECTION ---

// New route to search for unique medicine names
Route::get('/api/medicines/search-names', [MedicineController::class, 'searchNames'])->name('api.medicines.search-names');

// New route to get all packs for a given medicine name
Route::get('/api/medicines/packs', [MedicineController::class, 'getPacksForName'])->name('api.medicines.packs');

// In routes/api.php
Route::get('/medicines/{medicine}/details', [MedicineController::class, 'getDetails']);