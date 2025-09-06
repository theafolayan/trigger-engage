<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->is_admin) {
            Log::warning('Non-admin user blocked from admin panel', [
                'user_id' => $user?->id,
                'email' => $user?->email,
            ]);

            abort(403, 'Only administrators may access the admin panel.');
        }

        Log::info('Admin access granted', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $next($request);
    }
}
