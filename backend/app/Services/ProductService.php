<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    /**
     * Get list of all active products.
     *
     * @return Collection<int, Product>
     */
    public function getActiveProducts(): Collection
    {
        return Product::where('is_active', true)
            ->orderBy('id', 'asc')
            ->get();
    }
}
