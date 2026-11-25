<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Transaction;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class RestockTest extends TestCase
{
    /**
     * 1. Tes restock normal: stok naik sesuai jumlah, bukan menimpa nilai lama.
     */
    public function test_restock_normal_increments_stock_and_does_not_overwrite(): void
    {
        $product = Product::create([
            'name' => 'Buku Gambar Uji T16',
            'price' => 10000,
            'stock' => 10,
            'is_active' => true,
        ]);

        // Restock pertama (+5): 10 + 5 = 15
        $res1 = $this->postJson("/api/products/{$product->id}/restock", [
            'quantity' => 5,
        ]);
        $res1->assertStatus(200);
        $res1->assertJson([
            'message' => 'Stok berhasil ditambahkan',
            'data' => [
                'id' => $product->id,
                'stock' => 15,
            ],
        ]);
        $this->assertEquals(15, $product->fresh()->stock);

        // Restock kedua (+7): 15 + 7 = 22 (membuktikan sifat akumulatif stock + n, bukan menimpa menjadi 7)
        $res2 = $this->postJson("/api/products/{$product->id}/restock", [
            'quantity' => 7,
        ]);
        $res2->assertStatus(200);
        $res2->assertJson([
            'message' => 'Stok berhasil ditambahkan',
            'data' => [
                'id' => $product->id,
                'stock' => 22,
            ],
        ]);
        $this->assertEquals(22, $product->fresh()->stock);

        $product->delete();
    }

    /**
     * 2. Tes jumlah restock tidak valid: 0, negatif, kosong, atau string harus mengembalikan 422.
     */
    public function test_restock_fails_with_invalid_quantity(): void
    {
        $product = Product::create([
            'name' => 'Pulpen Uji T16',
            'price' => 3000,
            'stock' => 10,
            'is_active' => true,
        ]);

        // Kuantitas 0
        $resZero = $this->postJson("/api/products/{$product->id}/restock", [
            'quantity' => 0,
        ]);
        $resZero->assertStatus(422);
        $resZero->assertJsonValidationErrors(['quantity']);

        // Kuantitas negatif (-5)
        $resNegative = $this->postJson("/api/products/{$product->id}/restock", [
            'quantity' => -5,
        ]);
        $resNegative->assertStatus(422);
        $resNegative->assertJsonValidationErrors(['quantity']);

        // Kuantitas kosong
        $resEmpty = $this->postJson("/api/products/{$product->id}/restock", []);
        $resEmpty->assertStatus(422);
        $resEmpty->assertJsonValidationErrors(['quantity']);

        // Kuantitas bukan bilangan bulat
        $resString = $this->postJson("/api/products/{$product->id}/restock", [
            'quantity' => 'abc',
        ]);
        $resString->assertStatus(422);
        $resString->assertJsonValidationErrors(['quantity']);

        // Pastikan stok di database tidak berubah sama sekali
        $this->assertEquals(10, $product->fresh()->stock);

        $product->delete();
    }

    /**
     * 3. Tes restock bersamaan dengan penjualan tidak menghilangkan salah satunya (no lost update).
     * Skenario: Stok awal = 10. Restock (+5) dan Checkout (-3) berjalan secara paralel.
     * Hasil akhir yang diharapkan: Stok = 12 (10 + 5 - 3).
     */
    public function test_concurrent_restock_and_sale_preserves_both_updates(): void
    {
        $product = Product::create([
            'name' => 'Barang Konkuren Restock T16',
            'price' => 5000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $phpBinary = PHP_BINARY;
        $workerScript = base_path('concurrency_worker.php');

        // Proses 1: Restock 5 pcs
        $processRestock = new Process([$phpBinary, $workerScript, 'restock', (string) $product->id, '5']);

        // Proses 2: Checkout 3 pcs
        $processCheckout = new Process([$phpBinary, $workerScript, 'checkout', (string) $product->id, '3']);

        // Luncurkan kedua proses secara bersamaan
        $processRestock->start();
        $processCheckout->start();

        // Tunggu kedua proses selesai
        $processRestock->wait();
        $processCheckout->wait();

        $outputRestock = json_decode($processRestock->getOutput(), true);
        $outputCheckout = json_decode($processCheckout->getOutput(), true);

        // Keduanya harus berhasil
        $this->assertEquals('success', $outputRestock['status'] ?? null, 'Restock harus sukses');
        $this->assertEquals('success', $outputCheckout['status'] ?? null, 'Checkout harus sukses');

        // Stok akhir harus tepat 12: 10 + 5 - 3 = 12 (kedua operasi tersimpan tanpa ada yang hilang)
        $product->refresh();
        $this->assertEquals(12, $product->stock, 'Stok akhir harus tepat 12 (10 + 5 - 3)');

        // Cleanup
        Transaction::whereHas('items', function ($query) use ($product) {
            $query->where('product_id', $product->id);
        })->delete();
        $product->delete();
    }
}
