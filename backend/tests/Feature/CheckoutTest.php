<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * 1. Tes checkout sukses: menyimpan transaksi dan mengurangi stok.
     */
    public function test_checkout_success_stores_transaction_and_reduces_stock(): void
    {
        $productA = Product::create([
            'name' => 'Buku Uji T8',
            'price' => 5000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $productB = Product::create([
            'name' => 'Pulpen Uji T8',
            'price' => 3000,
            'stock' => 15,
            'is_active' => true,
        ]);

        $payload = [
            'items' => [
                ['product_id' => $productA->id, 'quantity' => 2],
                ['product_id' => $productB->id, 'quantity' => 3],
            ],
        ];

        $response = $this->postJson('/api/checkout', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Checkout berhasil',
        ]);

        $expectedTotal = (5000 * 2) + (3000 * 3); // 10000 + 9000 = 19000
        $this->assertEquals($expectedTotal, $response->json('transaction.total'));

        // Cek stok berkurang di database
        $this->assertEquals(8, $productA->fresh()->stock);
        $this->assertEquals(12, $productB->fresh()->stock);

        // Cek transaksi dan detail item tercatat di database
        $transactionId = $response->json('transaction.id');
        $this->assertDatabaseHas('transactions', [
            'id' => $transactionId,
            'total' => $expectedTotal,
        ]);

        $this->assertDatabaseHas('transaction_items', [
            'transaction_id' => $transactionId,
            'product_id' => $productA->id,
            'quantity' => 2,
            'price_at_purchase' => 5000,
            'subtotal' => 10000,
        ]);

        $this->assertDatabaseHas('transaction_items', [
            'transaction_id' => $transactionId,
            'product_id' => $productB->id,
            'quantity' => 3,
            'price_at_purchase' => 3000,
            'subtotal' => 9000,
        ]);
    }

    /**
     * 2. Tes all-or-nothing: jika satu item stok tidak cukup, semua rollback dan stok utuh.
     */
    public function test_checkout_rolls_back_completely_when_one_item_has_insufficient_stock(): void
    {
        $productA = Product::create([
            'name' => 'Produk Cukup T8',
            'price' => 4000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $productB = Product::create([
            'name' => 'Produk Habis T8',
            'price' => 8000,
            'stock' => 1,
            'is_active' => true,
        ]);

        $transactionCountBefore = Transaction::count();
        $itemCountBefore = TransactionItem::count();

        // Beli produk A (2 pcs, stok 10 -> cukup) dan produk B (2 pcs, stok 1 -> tidak cukup)
        $payload = [
            'items' => [
                ['product_id' => $productA->id, 'quantity' => 2],
                ['product_id' => $productB->id, 'quantity' => 2],
            ],
        ];

        $response = $this->postJson('/api/checkout', $payload);

        $response->assertStatus(409);
        $response->assertJson([
            'message' => "Stok {$productB->name} tidak cukup",
        ]);

        // Verifikasi stok produk A dan B sama sekali tidak berubah
        $this->assertEquals(10, $productA->fresh()->stock);
        $this->assertEquals(1, $productB->fresh()->stock);

        // Verifikasi tidak ada transaksi yang tersimpan
        $this->assertEquals($transactionCountBefore, Transaction::count());
        $this->assertEquals($itemCountBefore, TransactionItem::count());
    }

    /**
     * 3. Tes barang tidak ada: mengembalikan 404 dan tidak ada perubahan data.
     */
    public function test_checkout_fails_when_product_does_not_exist(): void
    {
        $transactionCountBefore = Transaction::count();

        $payload = [
            'items' => [
                ['product_id' => 999999, 'quantity' => 1],
            ],
        ];

        $response = $this->postJson('/api/checkout', $payload);

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Barang tidak ditemukan',
        ]);

        $this->assertEquals($transactionCountBefore, Transaction::count());
    }

    /**
     * 4. Tes input tidak valid: format salah, jumlah <= 0, atau kosong (HTTP 422).
     */
    public function test_checkout_validates_invalid_input(): void
    {
        // Items kosong
        $response = $this->postJson('/api/checkout', ['items' => []]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);

        // Quantity bernilai 0
        $response = $this->postJson('/api/checkout', [
            'items' => [
                ['product_id' => 1, 'quantity' => 0],
            ],
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.quantity']);

        // Quantity bernilai negatif
        $response = $this->postJson('/api/checkout', [
            'items' => [
                ['product_id' => 1, 'quantity' => -5],
            ],
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.quantity']);

        // Product ID tidak ada / tidak valid
        $response = $this->postJson('/api/checkout', [
            'items' => [
                ['quantity' => 2],
            ],
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.product_id']);
    }

    /**
     * 5. Tes harga dari klien diabaikan: total selalu dihitung dari database.
     */
    public function test_checkout_ignores_client_provided_price(): void
    {
        $product = Product::create([
            'name' => 'Kopi Mahal T8',
            'price' => 15000,
            'stock' => 10,
            'is_active' => true,
        ]);

        // Klien mencoba mengirimkan manipulasi harga 'price' = 100 dan 'subtotal' = 200
        $payload = [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'price' => 100,
                    'subtotal' => 200,
                ],
            ],
            'total' => 200,
        ];

        $response = $this->postJson('/api/checkout', $payload);

        $response->assertStatus(201);

        // Total harus tetap dihitung dari database: 15000 * 2 = 30000
        $this->assertEquals(30000, $response->json('transaction.total'));
        $this->assertEquals(30000, Transaction::find($response->json('transaction.id'))->total);
    }

    /**
     * 6. Tes barang nonaktif: tidak bisa di-checkout (HTTP 409).
     */
    public function test_checkout_fails_when_product_is_inactive(): void
    {
        $product = Product::create([
            'name' => 'Barang Nonaktif T8',
            'price' => 5000,
            'stock' => 10,
            'is_active' => false,
        ]);

        $payload = [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        $response = $this->postJson('/api/checkout', $payload);

        $response->assertStatus(409);
        $response->assertJson([
            'message' => "Barang {$product->name} tidak tersedia",
        ]);

        // Pastikan stok tidak berubah
        $this->assertEquals(10, $product->fresh()->stock);
    }
}
