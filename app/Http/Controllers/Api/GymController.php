<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GymLogResource;
use App\Models\Photo;
use App\Services\GymMachineRecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class GymController extends Controller
{
    public function __construct(private readonly GymMachineRecognitionService $recognizer) {}

    /** GET /gym?date=YYYY-MM-DD — logged machine sets for a day. */
    public function index(Request $request): JsonResponse
    {
        $date = $this->date($request);
        $user = $request->user();

        $logs = $user->gymLogs()
            ->with('photo')
            ->whereDate('date', $date)
            ->orderBy('time')
            ->get();

        return response()->json([
            'date' => $date->toDateString(),
            'totals' => [
                'kcal' => (int) $logs->sum('kcal'),
                'sets' => (int) $logs->sum('sets'),
                'reps' => (int) $logs->sum('reps'),
                'machines' => $logs->count(),
            ],
            'logs' => GymLogResource::collection($logs)->resolve(),
        ]);
    }

    /** POST /gym/recognize (multipart) — AI gym-machine detection. */
    public function recognize(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,heic', 'max:20480'],
        ]);

        $user = $request->user();
        $file = $request->file('file');
        $path = $file->store('machines', 'public');

        $photo = Photo::create([
            'user_id' => $user->id,
            'kind' => 'machine',
            'disk' => 'public',
            'path' => $path,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        $result = $this->recognizer->recognize($photo, hash_file('md5', $file->getRealPath()) ?: null);

        return response()->json([
            'confidence' => $result['confidence'],
            'name' => $result['name'],
            'muscles' => $result['muscles'],
            'kcalPerSet' => $result['kcalPerSet'],
            // Pass this back to POST /gym to attach the machine photo.
            'photoId' => $photo->id,
        ]);
    }

    /** POST /gym — log a machine set. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'machine' => ['required', 'string', 'max:120'],
            'muscles' => ['sometimes', 'nullable', 'array', 'max:8'],
            'muscles.*' => ['string', 'max:40'],
            'sets' => ['required', 'integer', 'min:1', 'max:99'],
            'reps' => ['required', 'integer', 'min:1', 'max:999'],
            'kcal' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'kcalPerSet' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'photoId' => ['sometimes', 'nullable', 'string'],
        ]);

        $kcal = $data['kcal']
            ?? $this->recognizer->kcalFor((int) ($data['kcalPerSet'] ?? 8), $data['sets'], $data['reps']);

        $log = $request->user()->gymLogs()->create([
            'date' => $data['date'],
            'time' => $data['time'],
            'machine' => $data['machine'],
            'muscles' => $data['muscles'] ?? null,
            'sets' => $data['sets'],
            'reps' => $data['reps'],
            'kcal' => $kcal,
            'photo_id' => $this->validPhotoId($request, $data['photoId'] ?? null),
        ]);

        return response()->json(['id' => $log->id], Response::HTTP_CREATED);
    }

    /** DELETE /gym/{id} */
    public function destroy(Request $request, string $gym): Response
    {
        $request->user()->gymLogs()->findOrFail($gym)->delete();

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
