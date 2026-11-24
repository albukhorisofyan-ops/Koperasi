<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Http\Requests\CheckoutRequest;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService
    ) {}

    /**
     * Handle multi-item checkout request.
     */
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        try {
            $transaction = $this->checkoutService->checkout($request->validated('items'));

            return response()->json([
                'message' => 'Checkout berhasil',
                'data' => $transaction->load('items.product'),
                'transaction' => $transaction,
            ], 201);
        } catch (CheckoutException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        }
    }
}
