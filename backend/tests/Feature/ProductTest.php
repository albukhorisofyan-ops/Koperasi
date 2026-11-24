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
}
