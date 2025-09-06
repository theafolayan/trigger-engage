<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Services\StatsService;
use Symfony\Component\HttpFoundation\Response;

class StatsController extends Controller
{
    public function __invoke(StatsService $stats): Response
    {
        return response()->json([
            'data' => $stats->totals(currentWorkspace()),
        ]);
    }
}
