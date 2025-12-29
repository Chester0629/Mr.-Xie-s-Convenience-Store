<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepositRequest;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    /**
     * Get user wallet info.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $balance = $this->walletService->getBalance($user);

        // Retrieve recent transactions
        $transactions = $user->walletTransactions()
            ->latest()
            ->take(10) // Limit to last 10 for dashboard
            ->get();

        return response()->json([
            'balance' => $balance,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Deposit funds (Mock Implementation).
     */
    public function deposit(DepositRequest $request): JsonResponse
    {
        $user = $request->user();
        // Amount is in TWD (Taiwan Dollars) - no cent conversion needed
        $amount = (int) $request->input('amount');
        $description = $request->input('description', 'Manual Deposit');

        try {
            $transaction = $this->walletService->deposit($user, $amount, $description);

            return response()->json([
                'message' => 'Deposit successful',
                'balance' => $this->walletService->getBalance($user),
                'transaction' => $transaction,
            ]);
        } catch (\App\Exceptions\InvalidWalletOperationException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
