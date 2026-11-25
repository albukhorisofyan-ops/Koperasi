<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * Get list of all active products.
     *
     * @return Collection<int, Product>
     */
    public function getActiveProducts(): Collection
    {
        return Product::where('is_active', true)
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Create a new product.
     *
     * @param  array{name: string, price: int, stock: int}  $data
     */
    public function createProduct(array $data): Product
    {
        return Product::create([
            'name' => $data['name'],
            'price' => (int) $data['price'],
            'stock' => (int) $data['stock'],
            'is_active' => true,
        ]);
    }

    /**
     * Update product name or price.
     *
     * @param  array{name?: string, price?: int}  $data
     */
    public function updateProduct(int $id, array $data): ?Product
    {
        $product = Product::find($id);

        if (!$product) {
            return null;
        }

        $updateData = [];
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['price'])) {
            $updateData['price'] = (int) $data['price'];
        }

        if (!empty($updateData)) {
            $product->update($updateData);
        }

        return $product;
    }

    /**
     * Restock product by adding quantity to current stock with row locking (lockForUpdate).
     */
    public function restockProduct(int $id, int $quantity): ?Product
    {
        return DB::transaction(function () use ($id, $quantity) {
            $product = Product::where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                return null;
            }

            // Menambah stok saat ini (bukan menimpa)
            $product->stock += $quantity;
            $product->save();

            return $product;
        });
    }
}
