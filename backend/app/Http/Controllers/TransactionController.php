<?php

namespace App\Http\Controllers;

use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    /**
     * Display a listing of all transactions with details.
     */
    public function index(): JsonResponse
    {
        $transactions = $this->transactionService->getAllTransactions();

        return response()->json($transactions);
    }

    /**
     * Display the specified transaction details.
     */
    public function show(int $id): JsonResponse
    {
        $transaction = $this->transactionService->getTransactionById($id);

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaksi tidak ditemukan',
            ], 404);
        }

        return response()->json($transaction);
    }
}
