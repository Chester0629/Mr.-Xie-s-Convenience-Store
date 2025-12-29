<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\WalletService;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('memberLevel')
            ->withCount('orders')
            ->withSum(['orders' => function ($query) {
                $query->where('status', '!=', 'cancelled');
            }], 'total_amount');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Allow custom per_page, default 50, max 100
        $perPage = min($request->input('per_page', 50), 100);

        return $query->latest()->paginate($perPage);
    }

    public function show($id)
    {
        return User::withCount('orders')
            ->withSum(['orders' => function ($query) {
                $query->whereIn('status', ['processing', 'shipped', 'completed']);
            }], 'total_amount')
            ->with(['orders' => function ($query) {
                $query->latest();
            }, 'walletTransactions' => function ($query) {
                $query->latest();
            }, 'memberLevel'])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'name' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $user = new User();
        $user->email = $request->email;
        $user->name = $request->name;
        $user->password = Hash::make($request->password);
        $user->phone = $request->phone;
        $user->role = 'customer';
        $user->save();

        return $user;
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'email' => 'required|email|unique:users,email,' . $id,
            'name' => 'required|string',
        ]);

        $user->email = $request->email;
        $user->name = $request->name;
        if ($request->has('password') && $request->password) {
            $user->password = Hash::make($request->password);
        }
        if ($request->has('phone')) $user->phone = $request->phone;
        if ($request->has('status')) $user->status = $request->status;
        if ($request->has('member_level')) {
            // Set member_level_id via MemberLevel model lookup
            $level = \App\Models\MemberLevel::where('slug', $request->member_level)->first();
            if ($level) {
                $user->member_level_id = $level->id;
            }
        }
        if ($request->has('is_level_locked')) $user->is_level_locked = $request->boolean('is_level_locked');

        $user->save();

        return $user;
    }

    public function walletTransaction(Request $request, $id, WalletService $walletService)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'type' => 'required|in:deposit,withdraw,adjustment',
            'description' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($id);

        // For adjustments, amount can be negative (debit) or positive (credit)
        // For deposit/withdraw, amount must be positive
        $amount = (int) $request->amount;
        if ($request->type !== 'adjustment' && $amount <= 0) {
            return response()->json(['message' => 'Amount must be greater than 0 for deposit/withdraw'], 422);
        }

        try {
            if ($request->type === 'deposit') {
                $walletService->deposit(
                    $user,
                    $amount,
                    $request->description
                );
            } elseif ($request->type === 'withdraw') {
                $walletService->withdraw(
                    $user,
                    abs($amount), // Ensure positive for withdraw method
                    $request->description
                );
            } elseif ($request->type === 'adjustment') {
                $walletService->adjust(
                    $user,
                    $amount, // Can be positive or negative
                    $request->description
                );
            }

            $user->refresh();
            return response()->json([
                'message' => 'Wallet updated successfully',
                'balance' => $user->balance,
                'type' => $request->type
            ]);
        } catch (\App\Exceptions\InsufficientBalanceException $e) {
            return response()->json(['message' => '餘額不足'], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Update user role (admin, staff, customer)
     */
    public function updateRole(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'role' => 'required|in:admin,staff,customer',
        ]);

        // Prevent demoting yourself
        if ($user->id === $request->user()->id && $request->role !== 'admin') {
            return response()->json(['message' => '無法降級自己的權限'], 400);
        }

        $oldRole = $user->role;
        $user->role = $request->role;
        $user->save();

        return response()->json([
            'message' => "角色已從 {$oldRole} 更新為 {$request->role}",
            'user' => $user,
        ]);
    }

    /**
     * Delete a user account
     * Prevents deletion of:
     * - Current logged-in admin
     * - Users with non-zero wallet balance
     * - Users with orders (can be changed to soft delete)
     */
    public function destroy(Request $request, $id)
    {
        $user = User::withCount('orders')->findOrFail($id);

        // Prevent deleting yourself
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => '無法刪除自己的帳號'], 400);
        }

        // Prevent deleting admin users (safety)
        if ($user->role === 'admin') {
            return response()->json(['message' => '無法刪除管理員帳號'], 400);
        }

        // Check if user has wallet balance
        if ($user->balance > 0) {
            return response()->json([
                'message' => '用戶錢包尚有餘額，請先處理餘額後再刪除',
                'balance' => $user->balance
            ], 400);
        }

        // Check if user has orders (optional: you may want to allow deletion anyway)
        if ($user->orders_count > 0) {
            return response()->json([
                'message' => '用戶有訂單記錄，是否確定刪除？',
                'orders_count' => $user->orders_count,
                'requires_confirmation' => true
            ], 400);
        }

        // Delete related data first
        // Cart is hasOne, need to get the instance first
        $cart = $user->cart;
        if ($cart) {
            $cart->items()->delete();
            $cart->delete();
        }
        $user->favorites()->delete();
        $user->addresses()->delete();
        $user->tokens()->delete();

        // Soft delete or hard delete the user
        $user->delete();

        return response()->json(['message' => '用戶已刪除成功']);
    }

    /**
     * Force delete a user (bypasses order check, can bypass balance check with confirmation)
     * Use query param ?confirm_balance=true to delete users with balance
     */
    public function forceDestroy(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Prevent deleting yourself
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => '無法刪除自己的帳號'], 400);
        }

        // Prevent deleting admin users
        if ($user->role === 'admin') {
            return response()->json(['message' => '無法刪除管理員帳號'], 400);
        }

        // Check balance - can be bypassed with confirm_balance=true
        if ($user->balance > 0 && !$request->boolean('confirm_balance')) {
            return response()->json([
                'message' => '用戶錢包尚有餘額，請先處理餘額後再刪除，或使用 ?confirm_balance=true 強制刪除',
                'balance' => $user->balance,
                'requires_confirmation' => true
            ], 400);
        }

        // Delete all related data
        // Cart is hasOne, need to get the instance first
        $cart = $user->cart;
        if ($cart) {
            // Delete cart items first
            $cart->items()->delete();
            $cart->delete();
        }

        $user->favorites()->delete();
        $user->addresses()->delete();
        $user->walletTransactions()->delete();

        // Note: Orders have onDelete('cascade') so they will be automatically deleted with the user
        // This is a limitation of the current schema - orders cannot be preserved after user deletion

        // Delete user's tokens
        $user->tokens()->delete();

        // Delete the user (this will cascade delete orders due to FK constraint)
        $user->delete();

        return response()->json([
            'message' => '用戶及相關資料已刪除成功（包含訂單記錄）'
        ]);
    }
}
