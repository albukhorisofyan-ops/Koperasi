<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Transaction;
use Symfony\Component\Process\Process;

echo "=======================================================\n";
echo " UJI KONFLIK STOK BERSAMAAN (CONCURRENCY & ROW LOCKING)\n";
echo "=======================================================\n\n";

// 1. Siapkan produk dengan stok sisa 1
$product = Product::create([
    'name' => 'Demo Pulpen Rebutan',
    'price' => 5000,
    'stock' => 1,
    'is_active' => true,
]);

echo "1. Produk dibuat: '{$product->name}' (ID: {$product->id})\n";
echo "   Stok awal: {$product->stock}\n\n";

$phpBinary = PHP_BINARY;
$workerScript = __DIR__ . '/concurrency_worker.php';

echo "2. Meluncurkan 2 proses secara BERSAMAAN untuk memperebutkan stok 1:\n";
echo "   - Proses A: Kasir Checkout (quantity = 1)\n";
echo "   - Proses B: Pembeli Online Simulasi (quantity = 1)\n\n";

$processA = new Process([$phpBinary, $workerScript, 'checkout', (string) $product->id, '1']);
$processB = new Process([$phpBinary, $workerScript, 'simulate', (string) $product->id, '1']);

$startTime = microtime(true);

$processA->start();
$processB->start();

$processA->wait();
$processB->wait();

$duration = round((microtime(true) - $startTime) * 1000, 2);

$resA = json_decode($processA->getOutput(), true);
$resB = json_decode($processB->getOutput(), true);

echo "3. Hasil Eksekusi ({$duration} ms):\n";
echo "   - Proses A: " . json_encode($resA, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
echo "   - Proses B: " . json_encode($resB, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

$product->refresh();
echo "4. Status Akhir Database:\n";
echo "   - Sisa Stok Produk: {$product->stock} (Wajib 0, dilarang negatif)\n";

$successCount = 0;
$failCount = 0;

if (($resA['status'] ?? '') === 'success') $successCount++;
if (($resA['status'] ?? '') === 'error') $failCount++;
if (($resB['status'] ?? '') === 'success') $successCount++;
if (($resB['status'] ?? '') === 'error') $failCount++;

echo "\n5. Kesimpulan Validasi:\n";
if ($successCount === 1 && $failCount === 1 && $product->stock === 0) {
    echo "   [BERHASIL] Uji konflik lolos sempurna! Tepat satu request sukses, satu ditolak, dan stok tidak negatif.\n";
} else {
    echo "   [GAGAL] Hasil tidak sesuai kriteria integritas data.\n";
}

// Cleanup
Transaction::whereHas('items', function ($query) use ($product) {
    $query->where('product_id', $product->id);
})->delete();
$product->delete();

echo "\nData uji dibersihkan.\n";
