<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    /**
     * Process checkout for multiple items with ACID compliance and row locking.
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     * @throws CheckoutException
     */
    public function checkout(array $items): Transaction
    {
        return DB::transaction(function () use ($items) {
            // 1. Gabungkan kuantitas untuk product_id yang sama jika ada
            $groupedQuantities = [];
            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $quantity = (int) $item['quantity'];
                $groupedQuantities[$productId] = ($groupedQuantities[$productId] ?? 0) + $quantity;
            }

            // 2. Urutkan berdasarkan product_id (menghindari deadlock antar transaksi konkuren)
            ksort($groupedQuantities);

            $total = 0;
            $itemsToCreate = [];

            // 3. Kunci baris dengan lockForUpdate() dan validasi ketersediaan stok
            foreach ($groupedQuantities as $productId => $quantity) {
                $product = Product::where('id', $productId)
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    throw new CheckoutException('Barang tidak ditemukan', 404);
                }

                if (!$product->is_active) {
                    throw new CheckoutException("Barang {$product->name} tidak tersedia", 409);
                }

                if ($product->stock < $quantity) {
                    throw new CheckoutException("Stok {$product->name} tidak cukup", 409);
                }

                // Kurangi stok produk
                $product->stock -= $quantity;
                $product->save();

                // Hitung subtotal dari harga asli di database (jangan percaya klien)
                $priceAtPurchase = $product->price;
                $subtotal = $priceAtPurchase * $quantity;
                $total += $subtotal;

                $itemsToCreate[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price_at_purchase' => $priceAtPurchase,
                    'subtotal' => $subtotal,
                ];
            }

            // 4. Buat header transaksi
            $transaction = Transaction::create([
                'total' => $total,
            ]);

            // 5. Buat detail transaksi
            foreach ($itemsToCreate as $itemData) {
                $transaction->items()->create($itemData);
            }

            return $transaction;
        });
    }
}
