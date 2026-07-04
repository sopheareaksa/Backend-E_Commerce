<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,user_email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'user_name' => $data['name'],
            'user_email' => $data['email'],
            'user_password' => Hash::make($data['password']),
            'is_admin' => false,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Admin fallback from env
        $adminEmail = env('ADMIN_EMAIL', 'admin123@gmail.com');
        $adminPassword = env('ADMIN_PASSWORD', 'admin123');

        if ($data['email'] === $adminEmail && $data['password'] === $adminPassword) {
            $admin = User::firstOrCreate(
                ['user_email' => $adminEmail],
                [
                    'user_name' => 'Admin User',
                    'user_password' => Hash::make($adminPassword),
                    'is_admin' => true,
                ]
            );
            $token = $admin->createToken('auth_token')->plainTextToken;
            return response()->json([
                'user' => $admin,
                'token' => $token,
            ]);
        }

        $user = User::where('user_email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->user_password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'user_name' => 'sometimes|string|max:255',
            'user_email' => 'sometimes|email|unique:users,user_email,' . $user->user_id . ',user_id',
            'user_phone' => 'sometimes|nullable|string|max:50',
        ]);

        $user->update($data);
        return response()->json($user);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'old_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($data['old_password'], $user->user_password)) {
            return response()->json(['message' => 'Old password is incorrect.'], 422);
        }

        $user->update(['user_password' => Hash::make($data['password'])]);
        return response()->json(['message' => 'Password updated.']);
    }
}
