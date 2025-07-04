<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\PurchaseBillController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CustomerController;


Route::get('/api/medicines/{medicine}/batches', [MedicineController::class, 'getBatches']);

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::resource('customers', CustomerController::class);
Route::resource('suppliers', SupplierController::class);
Route::resource('medicines', MedicineController::class);
Route::resource('purchase_bills', PurchaseBillController::class);
Route::resource('sales', SaleController::class);

Route::resource('inventories', InventoryController::class)->only(['index']);
Route::get('/inventories/{medicine}', [InventoryController::class, 'show'])->name('inventories.show');

// This route is for batch fetch during sale — keep it if used via web (NOT API)
Route::get('/medicines/{medicineId}/batches', [SaleController::class, 'getBatchesForMedicine']);
Route::get('/sales/medicines/{medicineId}/batches/{batchNumber}/expiry/{expiryDate}/quantity', [SaleController::class, 'getAvailableQuantity']);
Route::get('/sales/{sale}/bill', [SaleController::class, 'generateBill'])->name('sales.bill');
Route::get('/api/medicines/{medicine}/gst', [MedicineController::class, 'getGstRate'])->name('api.medicines.gst');