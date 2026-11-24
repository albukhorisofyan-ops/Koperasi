<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OnlinePurchaseTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * 1. Tes simulasi online purchase sukses mengurangi stok.
     */
    public function test_online_purchase_success_reduces_stock(): void
    {
        $product = Product::create([
            'name' => 'Barang Online Uji',
            'price' => 5000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/simulate-online-purchase', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Pembelian online berhasil',
            'data' => [
                'id' => $product->id,
                'stock' => 7,
            ],
        ]);

        $this->assertEquals(7, $product->fresh()->stock);
    }

    /**
     * 2. Tes online purchase gagal saat stok tidak mencukupi (HTTP 409).
     */
    public function test_online_purchase_fails_when_insufficient_stock(): void
    {
        $product = Product::create([
            'name' => 'Barang Stok Tipis Uji',
            'price' => 5000,
            'stock' => 1,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/simulate-online-purchase', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(409);
        $response->assertJson([
            'message' => "Stok {$product->name} tidak cukup",
        ]);

        $this->assertEquals(1, $product->fresh()->stock);
    }

    /**
     * 3. Tes online purchase gagal jika barang tidak ditemukan (HTTP 404).
     */
    public function test_online_purchase_fails_when_product_not_found(): void
    {
        $response = $this->postJson('/api/simulate-online-purchase', [
            'product_id' => 999999,
            'quantity' => 1,
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Barang tidak ditemukan',
        ]);
    }

    /**
     * 4. Tes online purchase gagal jika barang nonaktif (HTTP 409).
     */
    public function test_online_purchase_fails_when_product_inactive(): void
    {
        $product = Product::create([
            'name' => 'Barang Nonaktif Online Uji',
            'price' => 5000,
            'stock' => 5,
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/simulate-online-purchase', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(409);
        $response->assertJson([
            'message' => "Barang {$product->name} tidak tersedia",
        ]);

        $this->assertEquals(5, $product->fresh()->stock);
    }

    /**
     * 5. Tes validasi input tidak valid (HTTP 422).
     */
    public function test_online_purchase_validates_input(): void
    {
        $response = $this->postJson('/api/simulate-online-purchase', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['product_id', 'quantity']);

        $response = $this->postJson('/api/simulate-online-purchase', [
            'product_id' => 1,
            'quantity' => 0,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['quantity']);
    }
}
