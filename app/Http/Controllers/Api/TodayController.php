<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TodayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TodayController extends Controller
{
    public function __construct(private readonly TodayService $today) {}

    /** GET /today?date=YYYY-MM-DD&days=N */
    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'days' => ['sometimes', 'integer', 'min:1', 'max:366'],
        ]);

        $date = $request->filled('date')
            ? Carbon::createFromFormat('Y-m-d', $request->query('date'))
            : Carbon::today();

        $user = $request->user()->load('target');
        $windowDays = $request->filled('days') ? (int) $request->integer('days') : null;

        return response()->json($this->today->build($user, $date, $windowDays));
    }
}
