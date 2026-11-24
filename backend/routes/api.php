<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SimulationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/ping', function () {
    return response()->json(['message' => 'koperasi api ok']);
});

Route::get('/products', [ProductController::class, 'index']);
Route::post('/checkout', [CheckoutController::class, 'checkout']);
Route::post('/simulate-online-purchase', [SimulationController::class, 'simulateOnlinePurchase']);
