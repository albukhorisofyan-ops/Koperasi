<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * 1. Tes menampilkan daftar riwayat transaksi beserta detail barang.
     */
    public function test_can_list_transactions_with_items(): void
    {
        $product = Product::create([
            'name' => 'Barang Riwayat Uji',
            'price' => 7000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $trx = Transaction::create([
            'total' => 14000,
        ]);

        TransactionItem::create([
            'transaction_id' => $trx->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price_at_purchase' => 7000,
            'subtotal' => 14000,
        ]);

        $response = $this->getJson('/api/transactions');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $trx->id,
            'total' => 14000,
        ]);
        $response->assertJsonFragment([
            'product_id' => $product->id,
            'quantity' => 2,
            'price_at_purchase' => 7000,
            'subtotal' => 14000,
            'name' => 'Barang Riwayat Uji',
        ]);
    }

    /**
     * 2. Tes menampilkan detail satu transaksi berdasarkan ID.
     */
    public function test_can_get_single_transaction_detail(): void
    {
        $productA = Product::create([
            'name' => 'Barang Detail A',
            'price' => 3000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $productB = Product::create([
            'name' => 'Barang Detail B',
            'price' => 4000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $trx = Transaction::create([
            'total' => 10000,
        ]);

        TransactionItem::create([
            'transaction_id' => $trx->id,
            'product_id' => $productA->id,
            'quantity' => 2,
            'price_at_purchase' => 3000,
            'subtotal' => 6000,
        ]);

        TransactionItem::create([
            'transaction_id' => $trx->id,
            'product_id' => $productB->id,
            'quantity' => 1,
            'price_at_purchase' => 4000,
            'subtotal' => 4000,
        ]);

        $response = $this->getJson("/api/transactions/{$trx->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $trx->id,
            'total' => 10000,
        ]);
        $response->assertJsonCount(2, 'items');
        $response->assertJsonFragment([
            'name' => 'Barang Detail A',
            'quantity' => 2,
            'subtotal' => 6000,
        ]);
        $response->assertJsonFragment([
            'name' => 'Barang Detail B',
            'quantity' => 1,
            'subtotal' => 4000,
        ]);
    }

    /**
     * 3. Tes ketika transaksi dengan ID yang dicari tidak ditemukan (404).
     */
    public function test_get_transaction_returns_404_when_not_found(): void
    {
        $response = $this->getJson('/api/transactions/999999');

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Transaksi tidak ditemukan',
        ]);
    }
}
