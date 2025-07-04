<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MedicineController;

// Your new API route
Route::get('/medicines/{medicine}/batches', [MedicineController::class, 'getBatches']);
