<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Exceptions\CheckoutException;
use App\Services\CheckoutService;

$action = $argv[1] ?? 'checkout'; // 'checkout' atau 'simulate'
$productId = (int) ($argv[2] ?? 0);
$quantity = (int) ($argv[3] ?? 1);

$service = app(CheckoutService::class);

try {
    if ($action === 'simulate') {
        $product = $service->simulateOnlinePurchase($productId, $quantity);
        echo json_encode([
            'status' => 'success',
            'type' => 'simulate',
            'product_id' => $product->id,
            'stock' => $product->stock,
        ]);
    } else {
        $transaction = $service->checkout([
            ['product_id' => $productId, 'quantity' => $quantity],
        ]);
        echo json_encode([
            'status' => 'success',
            'type' => 'checkout',
            'transaction_id' => $transaction->id,
            'total' => $transaction->total,
        ]);
    }
} catch (CheckoutException $e) {
    echo json_encode([
        'status' => 'error',
        'code' => $e->getStatusCode(),
        'message' => $e->getMessage(),
    ]);
} catch (\Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'code' => 500,
        'message' => $e->getMessage(),
    ]);
}
