<?php

namespace App\Http\Controllers;

use App\Http\Requests\RestockProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
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

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct($request->validated());

        return response()->json([
            'message' => 'Barang berhasil ditambahkan',
            'data' => $product,
        ], 201);
    }

    /**
     * Update the specified product name or price.
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->updateProduct($id, $request->validated());

        if (!$product) {
            return response()->json([
                'message' => 'Barang tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'message' => 'Barang berhasil diperbarui',
            'data' => $product,
        ], 200);
    }

    /**
     * Restock the specified product (add to current stock).
     */
    public function restock(RestockProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->restockProduct($id, $request->validated('quantity'));

        if (!$product) {
            return response()->json([
                'message' => 'Barang tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'message' => 'Stok berhasil ditambahkan',
            'data' => $product,
        ], 200);
    }

    /**
     * Deactivate the specified product.
     */
    public function deactivate(int $id): JsonResponse
    {
        $product = $this->productService->deactivateProduct($id);

        if (!$product) {
            return response()->json([
                'message' => 'Barang tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'message' => 'Barang berhasil dinonaktifkan',
            'data' => $product,
        ], 200);
    }
}
