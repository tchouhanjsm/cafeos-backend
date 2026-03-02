<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $pin   = trim($request->input('pin', ''));
        $email = trim($request->input('email', ''));

        if (empty($pin)) {
            return response()->json([
                'success' => false,
                'message' => 'PIN is required'
            ], 422);
        }

        // Find active staff
        if (!empty($email)) {
            $user = User::where('email', $email)
                ->where('is_active', 1)
                ->first();
        } else {
            $user = User::where('is_active', 1)
                ->get()
                ->first(function ($u) use ($pin) {
                    return password_verify($pin, $u->pin_code);
                });
        }

        if (!$user || !password_verify($pin, $user->pin_code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Generate JWT token properly
        $token = auth('api')->login($user);

        return response()->json([
            'success' => true,
            'token'   => $token,
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'staff'   => [
                'id'    => (int) $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ]
        ], 200);
    }
}