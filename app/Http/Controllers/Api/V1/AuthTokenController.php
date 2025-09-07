<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthTokenController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $workspace = currentWorkspace();

        $user = User::where('account_id', $workspace->account_id)
            ->where('email', $credentials['email'])
            ->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'errors' => [
                    ['status' => '401', 'title' => 'Invalid credentials'],
                ],
            ], 401);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
            ],
        ]);
    }
}
