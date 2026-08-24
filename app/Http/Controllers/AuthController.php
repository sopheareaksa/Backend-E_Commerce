<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\PasswordResetOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
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

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            return response()->json([
                'message' => 'Too many requests. Please try again in a few minutes.',
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 600);
        $user = User::where('user_email', $email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'No account found with this email address. Please check your spelling or create a new account.',
            ], 404);
        }

        $otp = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(10);
        Cache::put($this->passwordResetCacheKey($email), [
            'otp_hash' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => $expiresAt,
        ], $expiresAt);

        try {
            Mail::to($user->user_email)->send(new PasswordResetOtpMail($otp));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('OTP mail failed', [
                'error' => $e->getMessage(),
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
            ]);
            return response()->json([
                'message' => 'Unable to send email. Please try again later.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json(['message' => 'If that email is registered, a verification code has been sent.']);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'otp' => ['required', 'digits:6'],
        ]);
        $key = $this->passwordResetCacheKey($data['email']);
        $reset = Cache::get($key);

        if (!$reset || ($reset['attempts'] ?? 0) >= 5 || !Hash::check($data['otp'], $reset['otp_hash'])) {
            if ($reset) {
                $reset['attempts'] = ($reset['attempts'] ?? 0) + 1;
                Cache::put($key, $reset, $reset['expires_at']);
            }
            return response()->json(['message' => 'The verification code is invalid or has expired.'], 422);
        }

        return response()->json(['message' => 'Verification code confirmed.']);
    }

    public function resetPasswordWithOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'otp' => ['required', 'digits:6'],
            'password' => 'required|string|min:6|confirmed',
        ]);
        $email = strtolower(trim($data['email']));
        $key = $this->passwordResetCacheKey($email);
        $reset = Cache::get($key);

        if (!$reset || ($reset['attempts'] ?? 0) >= 5 || !Hash::check($data['otp'], $reset['otp_hash'])) {
            return response()->json(['message' => 'The verification code is invalid or has expired.'], 422);
        }

        $user = User::where('user_email', $email)->first();
        if (!$user) {
            return response()->json(['message' => 'Unable to reset this password.'], 422);
        }

        $user->update(['user_password' => Hash::make($data['password'])]);
        Cache::forget($key);

        return response()->json(['message' => 'Password reset successfully. You can now sign in.']);
    }

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
