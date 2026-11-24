<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Transaction;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrencyTest extends TestCase
{
    /**
     * Uji dua request bersamaan untuk stok terakhir (sisa 1).
     * Selesai jika: hanya satu berhasil, stok tidak pernah negatif.
     */
    public function test_concurrent_requests_for_last_stock(): void
    {
        // 1. Buat produk dengan stok awal tepat 1
        $product = Product::create([
            'name' => 'Barang Stok Rebutan T9',
            'price' => 10000,
            'stock' => 1,
            'is_active' => true,
        ]);

        $phpBinary = PHP_BINARY;
        $workerScript = base_path('concurrency_worker.php');

        // Request 1: Kasir Checkout 1 pcs
        $process1 = new Process([$phpBinary, $workerScript, 'checkout', (string) $product->id, '1']);

        // Request 2: Pembeli Online simulasi beli 1 pcs
        $process2 = new Process([$phpBinary, $workerScript, 'simulate', (string) $product->id, '1']);

        // Jalankan kedua proses secara bersamaan
        $process1->start();
        $process2->start();

        // Tunggu hingga kedua proses selesai
        $process1->wait();
        $process2->wait();

        $output1 = json_decode($process1->getOutput(), true);
        $output2 = json_decode($process2->getOutput(), true);

        $statuses = [$output1['status'] ?? 'unknown', $output2['status'] ?? 'unknown'];

        // Verifikasi: Tepat 1 request berhasil dan tepat 1 request ditolak
        $this->assertContains('success', $statuses, 'Harus ada 1 transaksi yang berhasil');
        $this->assertContains('error', $statuses, 'Harus ada 1 transaksi yang ditolak karena konflik');

        $failedOutput = ($output1['status'] === 'error') ? $output1 : $output2;
        $this->assertEquals(409, $failedOutput['code']);
        $this->assertStringContainsString("Stok {$product->name} tidak cukup", $failedOutput['message']);

        // Verifikasi: Stok akhir tepat 0 dan tidak pernah negatif
        $product->refresh();
        $this->assertEquals(0, $product->stock);
        $this->assertGreaterThanOrEqual(0, $product->stock);

        // Bersihkan data pengujian
        Transaction::whereHas('items', function ($query) use ($product) {
            $query->where('product_id', $product->id);
        })->delete();
        $product->delete();
    }
}
