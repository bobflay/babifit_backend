<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MealResource;
use App\Models\Meal;
use App\Models\Photo;
use App\Services\FoodRecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class MealController extends Controller
{
    /** GET /meals?date=YYYY-MM-DD */
    public function index(Request $request): JsonResponse
    {
        $date = $this->date($request);
        $user = $request->user();

        $meals = $user->meals()
            ->with('photo')
            ->whereDate('date', $date)
            ->orderBy('time')
            ->get();

        $totals = [
            'kcal' => (int) $meals->sum('kcal'),
            'protein' => (int) $meals->sum('protein'),
            'carbs' => (int) $meals->sum('carbs'),
            'fat' => (int) $meals->sum('fat'),
        ];

        $target = (int) ($user->target?->calories ?? 0);

        return response()->json([
            'date' => $date->toDateString(),
            'totals' => $totals,
            'remaining' => $target - $totals['kcal'],
            'meals' => MealResource::collection($meals)->resolve(),
        ]);
    }

    /** GET /meals/summary?date=YYYY-MM-DD */
    public function summary(Request $request): JsonResponse
    {
        $date = $this->date($request);
        $user = $request->user();

        $meals = $user->meals()->whereDate('date', $date)->get();
        $kcal = (int) $meals->sum('kcal');
        $target = (int) ($user->target?->calories ?? 0);
        $vsTarget = $kcal - $target;

        return response()->json([
            'kcal' => $kcal,
            'meals' => $meals->count(),
            'protein' => (int) $meals->sum('protein'),
            'vsTarget' => $vsTarget,
            'status' => match (true) {
                $target === 0 => 'NO_TARGET',
                abs($vsTarget) <= 150 => 'ON_TRACK',
                $vsTarget > 150 => 'OVER',
                default => 'UNDER',
            },
        ]);
    }

    /** POST /meals/recognize (multipart) — AI food detection. */
    public function recognize(Request $request, FoodRecognitionService $recognizer): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,heic', 'max:20480'],
        ]);

        $user = $request->user();
        $file = $request->file('file');
        $path = $file->store('dishes', 'public');

        $photo = Photo::create([
            'user_id' => $user->id,
            'kind' => 'dish',
            'disk' => 'public',
            'path' => $path,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        $result = $recognizer->recognize(hash_file('md5', $file->getRealPath()) ?: $photo->id);

        return response()->json([
            'confidence' => $result['confidence'],
            'name' => $result['name'],
            'kcal' => $result['kcal'],
            'macros' => $result['macros'],
            'items' => $result['items'],
            // Extension: pass this back to POST /meals to attach the dish photo.
            'photoId' => $photo->id,
        ]);
    }

    /** POST /meals — log a meal. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'name' => ['required', 'string', 'max:255'],
            'kcal' => ['required', 'integer', 'min:0'],
            'protein' => ['sometimes', 'integer', 'min:0'],
            'carbs' => ['sometimes', 'integer', 'min:0'],
            'fat' => ['sometimes', 'integer', 'min:0'],
            'photoId' => ['sometimes', 'nullable', 'string'],
        ]);

        $meal = $request->user()->meals()->create([
            'date' => $data['date'],
            'time' => $data['time'],
            'name' => $data['name'],
            'kcal' => $data['kcal'],
            'protein' => $data['protein'] ?? 0,
            'carbs' => $data['carbs'] ?? 0,
            'fat' => $data['fat'] ?? 0,
            'photo_id' => $this->validPhotoId($request, $data['photoId'] ?? null),
        ]);

        return response()->json(['id' => $meal->id], Response::HTTP_CREATED);
    }

    /** PATCH /meals/{id} */
    public function update(Request $request, string $meal): MealResource
    {
        $model = $request->user()->meals()->findOrFail($meal);

        $data = $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'time' => ['sometimes', 'date_format:H:i'],
            'name' => ['sometimes', 'string', 'max:255'],
            'kcal' => ['sometimes', 'integer', 'min:0'],
            'protein' => ['sometimes', 'integer', 'min:0'],
            'carbs' => ['sometimes', 'integer', 'min:0'],
            'fat' => ['sometimes', 'integer', 'min:0'],
            'photoId' => ['sometimes', 'nullable', 'string'],
        ]);

        if (array_key_exists('photoId', $data)) {
            $data['photo_id'] = $this->validPhotoId($request, $data['photoId']);
            unset($data['photoId']);
        }

        $model->fill($data)->save();

        return new MealResource($model->fresh('photo'));
    }

    /** DELETE /meals/{id} */
    public function destroy(Request $request, string $meal): Response
    {
        $request->user()->meals()->findOrFail($meal)->delete();

        return response()->noContent();
    }

    private function date(Request $request): Carbon
    {
        $request->validate(['date' => ['sometimes', 'date_format:Y-m-d']]);

        return $request->filled('date')
            ? Carbon::createFromFormat('Y-m-d', $request->query('date'))
            : Carbon::today();
    }

    /** Only accept a photo id owned by the requesting user. */
    private function validPhotoId(Request $request, ?string $photoId): ?string
    {
        if (! $photoId) {
            return null;
        }

        return Photo::where('user_id', $request->user()->id)
            ->whereKey($photoId)
            ->exists() ? $photoId : null;
    }
}
