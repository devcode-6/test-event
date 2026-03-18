<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:8',
                'phone' => 'nullable|string',
                'role' => 'required|in:admin,organizer,customer',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'role' => $validated['role'],
            ]);

            $accessToken = $user->createToken('access_token')->plainTextToken;
            $refreshToken = $user->createToken('refresh_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $user,
                    'access_token' => $accessToken,
                ]
            ], 201)->withCookie(
                \Cookie::make('refresh_token', $refreshToken, 60*24*7, null, null, false, true)
            );
        } catch (ValidationException $e) {
            return $this->error('Validation failed', $e->errors(), 422);
        }
    }

    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if (!Auth::attempt($validated)) {
                return $this->unauthorized('Invalid credentials');
            }

            $user = Auth::user();
            $accessToken = $user->createToken('access_token')->plainTextToken;
            $refreshToken = $user->createToken('refresh_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'access_token' => $accessToken,
                ]
            ])->withCookie(
                \Cookie::make('refresh_token', $refreshToken, 60*24*7, null, null, false, true)
            );
        } catch (ValidationException $e) {
            return $this->error('Validation failed', $e->errors(), 422);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        $refreshToken = $request->cookie('refresh_token');
        if ($refreshToken) {
            $tokenModel = PersonalAccessToken::findToken($refreshToken);
            if ($tokenModel) {
                $tokenModel->delete();
            }
        }

        return $this->success(null, 'Logged out successfully')
            ->withCookie(\Cookie::forget('refresh_token'));
    }

    public function me(Request $request)
    {
        return $this->success($request->user(), 'User data retrieved');
    }

    public function refreshToken(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');
        if (!$refreshToken) {
            return $this->unauthorized('Refresh token missing');
        }

        $tokenModel = PersonalAccessToken::findToken($refreshToken);
        if (!$tokenModel || !$tokenModel->tokenable) {
            return $this->unauthorized('Invalid refresh token');
        }

        $user = $tokenModel->tokenable;
        $tokenModel->delete();

        $newAccessToken = $user->createToken('access_token')->plainTextToken;
        $newRefreshToken = $user->createToken('refresh_token')->plainTextToken;

        return $this->success([
            'access_token' => $newAccessToken,
        ], 'Token refreshed')
            ->withCookie(\Cookie::make('refresh_token', $newRefreshToken, 60*24*7, null, null, false, true));
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $token = \Str::random(64);
        \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => now()]
        );

        return $this->success(null, 'Password reset link sent');
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $record = \DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->where('token', $validated['token'])
            ->first();

        if (!$record) {
            return $this->error('Invalid or expired token', null, 400);
        }

        $user = User::where('email', $validated['email'])->first();
        $user->update(['password' => Hash::make($validated['password'])]);

        \DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->delete();

        return $this->success(null, 'Password reset successfully');
    }
}
