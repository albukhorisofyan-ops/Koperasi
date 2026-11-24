<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Pulpen Gel 0.5mm',
                'price' => 3500,
                'stock' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Buku Tulis 38 Lembar',
                'price' => 5000,
                'stock' => 40,
                'is_active' => true,
            ],
            [
                'name' => 'Penghapus Pensil',
                'price' => 2000,
                'stock' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Penggaris 30cm',
                'price' => 4000,
                'stock' => 25,
                'is_active' => true,
            ],
            [
                'name' => 'Correction Tape (Tipe-X)',
                'price' => 8000,
                'stock' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Air Mineral 600ml',
                'price' => 3000,
                'stock' => 60,
                'is_active' => true,
            ],
            [
                'name' => 'Roti Cokelat',
                'price' => 6000,
                'stock' => 15,
                'is_active' => true,
            ],
            [
                'name' => 'Kopi Kemasan 200ml',
                'price' => 7500,
                'stock' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Keripik Singkong',
                'price' => 5000,
                'stock' => 25,
                'is_active' => true,
            ],
            [
                'name' => 'Map Kancing Folio',
                'price' => 2500,
                'stock' => 35,
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['name' => $product['name']],
                $product
            );
        }
    }
}
