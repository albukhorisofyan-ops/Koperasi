<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/ping', function () {
    return response()->json(['message' => 'koperasi api ok']);
});

// Products & Management
Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);
Route::put('/products/{id}', [ProductController::class, 'update']);
Route::post('/products/{id}/restock', [ProductController::class, 'restock']);
Route::patch('/products/{id}/deactivate', [ProductController::class, 'deactivate']);

// Checkout & Simulation
Route::post('/checkout', [CheckoutController::class, 'checkout']);
Route::post('/simulate-online-purchase', [SimulationController::class, 'simulateOnlinePurchase']);

// Transactions History
Route::get('/transactions', [TransactionController::class, 'index']);
Route::get('/transactions/{id}', [TransactionController::class, 'show']);
