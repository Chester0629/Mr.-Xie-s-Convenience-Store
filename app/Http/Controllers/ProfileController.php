<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string',
            'district' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'detail_address' => 'nullable|string',
            'recipient_name' => 'nullable|string|max:255',
            'birthday' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other',
            // Password fields
            'current_password' => 'required_with:password|string',
            'password' => 'nullable|string|min:6',
        ]);

        // Update User Basic Info
        $user->update($request->only(['name', 'phone', 'birthday', 'gender']));

        // Handle Password Change
        if ($request->filled('password')) {
            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['目前密碼不正確'],
                ]);
            }

            // Update to new password
            $user->update([
                'password' => Hash::make($request->password)
            ]);
        }

        // Handle Structured Address
        if ($request->has('city') && $request->has('district') && $request->has('detail_address')) {
            $recipientName = $request->input('recipient_name', $user->name);
            $phone = $request->input('phone', $user->phone);

            $address = $user->addresses()->where('is_default', true)->first();

            if ($address) {
                $address->update([
                    'city' => $request->city,
                    'district' => $request->district,
                    'zip_code' => $request->zip_code,
                    'detail_address' => $request->detail_address,
                    'recipient_name' => $recipientName,
                    'phone' => $phone
                ]);
            } else {
                $user->addresses()->create([
                    'city' => $request->city,
                    'district' => $request->district,
                    'zip_code' => $request->zip_code,
                    'detail_address' => $request->detail_address,
                    'recipient_name' => $recipientName,
                    'phone' => $phone,
                    'is_default' => true
                ]);
            }
        }

        return response()->json(['message' => 'Profile updated', 'user' => $user->load('addresses')]);
    }
}
