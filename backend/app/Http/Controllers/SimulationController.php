<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Http\Requests\SimulateOnlinePurchaseRequest;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;

class SimulationController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService
    ) {}

    /**
     * Handle online purchase simulation request.
     */
    public function simulateOnlinePurchase(SimulateOnlinePurchaseRequest $request): JsonResponse
    {
        try {
            $product = $this->checkoutService->simulateOnlinePurchase(
                $request->validated('product_id'),
                $request->validated('quantity')
            );

            return response()->json([
                'message' => 'Pembelian online berhasil',
                'data' => $product,
            ], 200);
        } catch (CheckoutException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        }
    }
}
