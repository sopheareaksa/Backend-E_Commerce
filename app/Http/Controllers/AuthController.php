<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\PasswordResetOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private function passwordResetCacheKey(string $email): string
    {
        return 'password-reset-otp:' . hash('sha256', strtolower(trim($email)));
    }

    public function sendOtp(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);
        $email = strtolower(trim($data['email']));
        $rateLimitKey = 'password-reset-send:' . $request->ip() . ':' . hash('sha256', $email);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 15)) {
            return response()->json([
                'message' => 'Too many requests. Please try again in a few minutes.',
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 60);
        $user = User::whereRaw('LOWER(user_email) = ?', [$email])->first();

        if (!$user) {
            return response()->json([
                'message' => 'No account found with this email address. Please check your spelling or register a new account.',
            ], 404);
        }

        $otp = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(15);
        Cache::put($this->passwordResetCacheKey($email), [
            'otp_hash' => Hash::make($otp),
            'expires_at' => $expiresAt->timestamp,
        ], $expiresAt);

        try {
            Mail::to($user->user_email)->send(new PasswordResetOtpMail($otp));
        } catch (\Throwable $e) {
            Log::error('Gmail SMTP dispatch failed', [
                'error' => $e->getMessage(),
                'email' => $email,
            ]);
            return response()->json([
                'message' => 'Unable to send email. Please verify your email address and try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'A 6-digit verification code has been sent to ' . $user->user_email . '. Please check your inbox.',
        ]);
    }

    public function verifyOtp(Request $request)
    {
        try {
            $data = $request->validate([
                'email' => 'required|email',
                'otp' => 'required',
            ]);
            $email = strtolower(trim($data['email']));
            $otp = trim((string) $data['otp']);
            $key = $this->passwordResetCacheKey($email);
            $reset = Cache::get($key);

            if (!$reset || !Hash::check($otp, $reset['otp_hash'])) {
                return response()->json(['message' => 'The verification code is invalid or has expired.'], 422);
            }

            return response()->json(['success' => true, 'message' => 'Verification code confirmed.']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'The verification code is invalid or has expired.'], 422);
        }
    }

    public function resetPasswordWithOtp(Request $request)
    {
        try {
            $data = $request->validate([
                'email' => 'required|email',
                'otp' => 'required',
                'password' => 'required|string|min:6|confirmed',
            ]);
            $email = strtolower(trim($data['email']));
            $otp = trim((string) $data['otp']);
            $key = $this->passwordResetCacheKey($email);
            $reset = Cache::get($key);

            if (!$reset || !Hash::check($otp, $reset['otp_hash'])) {
                return response()->json(['message' => 'The verification code is invalid or has expired.'], 422);
            }

            $user = User::whereRaw('LOWER(user_email) = ?', [$email])->first();
            if (!$user) {
                return response()->json(['message' => 'Unable to reset this password.'], 422);
            }

            $user->update(['user_password' => Hash::make($data['password'])]);
            Cache::forget($key);

            return response()->json(['success' => true, 'message' => 'Password reset successfully. You can now sign in.']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage() ?: 'Unable to reset password.'], 422);
        }
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,user_email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $email = strtolower(trim($data['email']));

        $user = User::create([
            'user_name' => trim($data['name']),
            'user_email' => $email,
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

        $email = strtolower(trim($data['email']));
        $password = $data['password'];

        // Admin fallback from env
        $adminEmail = strtolower(trim(env('ADMIN_EMAIL', 'admin123@gmail.com')));
        $adminPassword = env('ADMIN_PASSWORD', 'admin123');

        if ($email === $adminEmail && $password === $adminPassword) {
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

        $user = User::whereRaw('LOWER(user_email) = ?', [$email])->first();

        if (!$user) {
            return response()->json([
                'message' => 'No account found with this email address. Please register.',
            ], 404);
        }

        if (!Hash::check($password, $user->user_password)) {
            return response()->json([
                'message' => 'Incorrect password. Please try again or use Forgot Password.',
            ], 401);
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

        if (isset($data['user_email'])) {
            $data['user_email'] = strtolower(trim($data['user_email']));
        }

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
