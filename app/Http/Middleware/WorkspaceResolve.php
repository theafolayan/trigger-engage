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
        $slug = $request->header('X-Workspace');

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

        app()->instance('currentWorkspace', $workspace);

        return $next($request);
    }
}
