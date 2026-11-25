<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_list_only_active_products(): void
    {
        // Nonaktifkan produk lain jika ada agar pengujian fragment/count terisolasi
        Product::query()->update(['is_active' => false]);

        $active1 = Product::create([
            'name' => 'Pulpen Uji',
            'price' => 3000,
            'stock' => 10,
            'is_active' => true,
        ]);
        $active2 = Product::create([
            'name' => 'Buku Uji',
            'price' => 5000,
            'stock' => 5,
            'is_active' => true,
        ]);

        Product::create([
            'name' => 'Barang Nonaktif Uji',
            'price' => 10000,
            'stock' => 0,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment([
            'id' => $active1->id,
            'name' => 'Pulpen Uji',
            'price' => 3000,
            'stock' => 10,
            'is_active' => true,
        ]);
        $response->assertJsonFragment([
            'id' => $active2->id,
            'name' => 'Buku Uji',
            'price' => 5000,
            'stock' => 5,
            'is_active' => true,
        ]);
        $response->assertJsonMissing([
            'name' => 'Barang Nonaktif Uji',
        ]);
    }

    /**
     * POST /api/products - Sukses menambah barang baru.
     */
    public function test_can_create_new_product_with_valid_data(): void
    {
        $payload = [
            'name' => 'Spidol Papan Tulis',
            'price' => 8500,
            'stock' => 20,
        ];

        $response = $this->postJson('/api/products', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Barang berhasil ditambahkan',
            'data' => [
                'name' => 'Spidol Papan Tulis',
                'price' => 8500,
                'stock' => 20,
                'is_active' => true,
            ],
        ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Spidol Papan Tulis',
            'price' => 8500,
            'stock' => 20,
            'is_active' => true,
        ]);
    }

    /**
     * POST /api/products - Validasi harga > 0 dan stok >= 0.
     */
    public function test_create_product_validates_input(): void
    {
        // Field kosong
        $response = $this->postJson('/api/products', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'price', 'stock']);

        // Harga <= 0 dan stok negatif
        $response = $this->postJson('/api/products', [
            'name' => 'Barang Salah',
            'price' => 0,
            'stock' => -1,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['price', 'stock']);
    }

    /**
     * PUT /api/products/{id} - Sukses mengubah nama atau harga.
     */
    public function test_can_update_product_name_and_price(): void
    {
        $product = Product::create([
            'name' => 'Penggaris Plastik',
            'price' => 3000,
            'stock' => 15,
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'Penggaris Besi 30cm',
            'price' => 6000,
        ];

        $response = $this->putJson("/api/products/{$product->id}", $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Barang berhasil diperbarui',
            'data' => [
                'id' => $product->id,
                'name' => 'Penggaris Besi 30cm',
                'price' => 6000,
                'stock' => 15, // Stok tidak terpengaruh
            ],
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Penggaris Besi 30cm',
            'price' => 6000,
        ]);
    }

    /**
     * PUT /api/products/{id} - 404 ketika ID barang tidak ditemukan.
     */
    public function test_update_product_returns_404_when_not_found(): void
    {
        $response = $this->putJson('/api/products/999999', [
            'name' => 'Nama Baru',
            'price' => 5000,
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Barang tidak ditemukan',
        ]);
    }

    /**
     * POST /api/products/{id}/restock - Menambah stok saat ini (bukan menimpa).
     */
    public function test_can_restock_product_adds_to_current_stock(): void
    {
        $product = Product::create([
            'name' => 'Buku Gambar A4',
            'price' => 10000,
            'stock' => 12,
            'is_active' => true,
        ]);

        // Restock kirim kuantitas tambahan = 8 (bukan nilai akhir)
        $response = $this->postJson("/api/products/{$product->id}/restock", [
            'quantity' => 8,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Stok berhasil ditambahkan',
            'data' => [
                'id' => $product->id,
                'stock' => 20, // 12 + 8 = 20
            ],
        ]);

        $this->assertEquals(20, $product->fresh()->stock);
    }

    /**
     * POST /api/products/{id}/restock - Validasi kuantitas restock > 0.
     */
    public function test_restock_product_validates_quantity(): void
    {
        $product = Product::create([
            'name' => 'Penghapus Papan',
            'price' => 5000,
            'stock' => 5,
            'is_active' => true,
        ]);

        // Kuantitas 0
        $response = $this->postJson("/api/products/{$product->id}/restock", [
            'quantity' => 0,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['quantity']);

        // Kuantitas negatif
        $response = $this->postJson("/api/products/{$product->id}/restock", [
            'quantity' => -3,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['quantity']);
    }

    /**
     * POST /api/products/{id}/restock - 404 ketika ID barang tidak ditemukan.
     */
    public function test_restock_product_returns_404_when_not_found(): void
    {
        $response = $this->postJson('/api/products/999999/restock', [
            'quantity' => 5,
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Barang tidak ditemukan',
        ]);
    }
}
