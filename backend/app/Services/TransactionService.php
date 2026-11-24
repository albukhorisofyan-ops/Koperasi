<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

class TransactionService
{
    /**
     * Get all transactions with their items and products ordered by newest first.
     *
     * @return Collection<int, Transaction>
     */
    public function getAllTransactions(): Collection
    {
        return Transaction::with(['items.product'])
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Get a single transaction by ID with items and products.
     */
    public function getTransactionById(int $id): ?Transaction
    {
        return Transaction::with(['items.product'])->find($id);
    }
}
