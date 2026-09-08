<?php

namespace App\Http\Controllers;

use App\Services\TrendingService;
use Illuminate\Http\JsonResponse;

class ExploreController extends Controller
{
    public function __construct(
        private readonly TrendingService $trending
    ) {}

    public function trending(): JsonResponse
    {
        return response()->json([
            'data' => [
                'trends' => $this
                    ->trending
                    ->hashtags()
                    ->all(),
            ],
        ]);
    }
}
