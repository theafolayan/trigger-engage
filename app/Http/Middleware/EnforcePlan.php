<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\PlanEnforcer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePlan
{
    public function __construct(private PlanEnforcer $enforcer)
    {
    }

    public function handle(Request $request, Closure $next, string $feature = ''): Response
    {
        if ($feature !== '' && currentWorkspace() !== null) {
            $this->enforcer->ensureFeature(currentWorkspace(), $feature);
        }

        return $next($request);
    }
}
