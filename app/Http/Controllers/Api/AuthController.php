<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RefreshToken;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function login(Request $request): Response
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();
        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'errors' => [
                    ['status' => '401', 'title' => 'Invalid credentials'],
                ],
            ], 401);
        }

        $accessToken = $this->issueAccessToken($user);
        $refreshToken = Str::random(64);

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $refreshToken),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'expires_at' => now()->addSeconds((int) config('jwt.refresh_ttl')),
        ]);

        return response()->json([
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
            ],
        ]);
    }

    public function refresh(Request $request): Response
    {
        $data = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $hash = hash('sha256', $data['refresh_token']);

        $stored = RefreshToken::where('token', $hash)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($stored === null) {
            return response()->json([
                'errors' => [
                    ['status' => '401', 'title' => 'Invalid refresh token'],
                ],
            ], 401);
        }

        $stored->update(['revoked_at' => now()]);

        $user = $stored->user;

        $accessToken = $this->issueAccessToken($user);
        $newRefresh = Str::random(64);

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $newRefresh),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'expires_at' => now()->addSeconds((int) config('jwt.refresh_ttl')),
        ]);

        return response()->json([
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $newRefresh,
            ],
        ]);
    }

    public function logout(Request $request): Response
    {
        $data = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $hash = hash('sha256', $data['refresh_token']);

        RefreshToken::where('token', $hash)->delete();

        return response()->noContent();
    }

    private function issueAccessToken(User $user): string
    {
        $payload = [
            'sub' => $user->id,
            'exp' => now()->addSeconds((int) config('jwt.access_ttl'))->timestamp,
        ];

        return JWT::encode($payload, (string) config('jwt.secret'), 'HS256');
    }
}
