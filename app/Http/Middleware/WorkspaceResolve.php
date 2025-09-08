<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkspaceResolve
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->header('X-Workspace')
            ?? ($request->hasSession() ? $request->session()->get('workspace') : null);

        if ($slug === null && ($user = $request->user()) !== null && $request->hasSession()) {
            $slug = $user->workspace()->value('slug');
            if ($slug !== null) {
                $request->session()->put('workspace', $slug);
            }
        }

        if ($slug === null) {
            return response()->json([
                'errors' => [
                    ['status' => '400', 'title' => 'Workspace header missing'],
                ],
            ], 400);
        }

        $workspace = Workspace::where('slug', $slug)->first();

        if ($workspace === null) {
            return response()->json([
                'errors' => [
                    ['status' => '404', 'title' => 'Workspace not found'],
                ],
            ], 404);
        }

        $user = $request->user();

        if ($user !== null && $user->account_id !== $workspace->account_id) {
            return response()->json([
                'errors' => [
                    ['status' => '403', 'title' => 'Forbidden'],
                ],
            ], 403);
        }

        app()->instance('currentWorkspace', $workspace);

        return $next($request);
    }
}
