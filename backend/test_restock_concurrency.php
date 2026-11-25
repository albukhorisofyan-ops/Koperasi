<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Transaction;
use Symfony\Component\Process\Process;

echo "===============================================================\n";
echo " UJI KONKURENSI: RESTOCK BERSAMAAN DENGAN PENJUALAN (ACID)\n";
echo "===============================================================\n\n";

// 1. Buat produk dengan stok awal = 10
$product = Product::create([
    'name' => 'Demo Pulpen Konkuren Restock',
    'price' => 4000,
    'stock' => 10,
    'is_active' => true,
]);

echo "1. Produk dibuat: '{$product->name}' (ID: {$product->id})\n";
echo "   Stok awal: {$product->stock}\n\n";

$phpBinary = PHP_BINARY;
$workerScript = __DIR__ . '/concurrency_worker.php';

echo "2. Meluncurkan 2 proses secara BERSAMAAN:\n";
echo "   - Proses A: Restock (+5 pcs)\n";
echo "   - Proses B: Kasir Checkout (-3 pcs)\n\n";

$processA = new Process([$phpBinary, $workerScript, 'restock', (string) $product->id, '5']);
$processB = new Process([$phpBinary, $workerScript, 'checkout', (string) $product->id, '3']);

$startTime = microtime(true);

$processA->start();
$processB->start();

$processA->wait();
$processB->wait();

$duration = round((microtime(true) - $startTime) * 1000, 2);

$resA = json_decode($processA->getOutput(), true);
$resB = json_decode($processB->getOutput(), true);

echo "3. Hasil Eksekusi ({$duration} ms):\n";
echo "   - Proses A (Restock): " . json_encode($resA, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
echo "   - Proses B (Checkout): " . json_encode($resB, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

$product->refresh();
echo "4. Status Akhir Database:\n";
echo "   - Sisa Stok Produk: {$product->stock}\n";
echo "   - Perhitungan Matematika: 10 (awal) + 5 (restock) - 3 (jual) = 12\n\n";

echo "5. Kesimpulan Validasi:\n";
if (($resA['status'] ?? '') === 'success' && ($resB['status'] ?? '') === 'success' && $product->stock === 12) {
    echo "   [BERHASIL] Row locking (lockForUpdate) dan DB::transaction berhasil menjaga integritas data!\n";
    echo "   Kedua transaksi berhasil tanpa ada pembaruan yang hilang (no lost update).\n";
} else {
    echo "   [GAGAL] Terjadi race condition atau inkonsistensi stok.\n";
}

// Cleanup
Transaction::whereHas('items', function ($query) use ($product) {
    $query->where('product_id', $product->id);
})->delete();
$product->delete();

echo "\nData uji dibersihkan.\n";
