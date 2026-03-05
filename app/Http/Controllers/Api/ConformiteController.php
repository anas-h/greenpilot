<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\FiltersParGarage;
use App\Services\ConformiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConformiteController extends Controller
{
    use FiltersParGarage;

    public function score(Request $request, ConformiteService $service): JsonResponse
    {
        $garageId = $this->getGarageId($request);
        $score = $service->calculateScore($garageId);

        return response()->json(['data' => $score]);
    }
}
