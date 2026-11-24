<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    /**
     * Display a listing of active products.
     */
    public function index(): JsonResponse
    {
        $products = $this->productService->getActiveProducts();

        return response()->json($products);
    }
}
