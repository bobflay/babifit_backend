<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ProgressController extends Controller
{
    public function __construct(private readonly ProgressService $progress) {}

    /** GET /progress/series?metric=&from=&to= */
    public function series(Request $request): JsonResponse
    {
        $data = $request->validate([
            'metric' => ['required', Rule::in($this->progress->metrics())],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        return response()->json($this->progress->series(
            $request->user(),
            $data['metric'],
            Carbon::createFromFormat('Y-m-d', $data['from']),
            Carbon::createFromFormat('Y-m-d', $data['to']),
        ));
    }

    /** GET /progress/compare?from=scan-1&to=scan-2 */
    public function compare(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['required', 'string'],
            'to' => ['required', 'string'],
        ]);

        $user = $request->user();
        $from = $user->scans()->findOrFail($data['from']);
        $to = $user->scans()->findOrFail($data['to']);

        return response()->json($this->progress->compare($from, $to));
    }

    /** GET /progress/insights */
    public function insights(Request $request): JsonResponse
    {
        $insight = $this->progress->insights($request->user());

        return response()->json($insight ?? [
            'title' => 'Keep going',
            'headline' => 'Not enough scans yet to spot a pattern.',
            'body' => 'Add another InBody scan to unlock trend insights.',
        ]);
    }
}
