<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScanResource;
use App\Http\Resources\ScanSummaryResource;
use App\Models\Photo;
use App\Models\Scan;
use App\Models\ScanParseJob;
use App\Services\ScanParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ScanController extends Controller
{
    /** camelCase request field => scans column. */
    private const FIELD_MAP = [
        'date' => 'date', 'label' => 'label',
        'weight' => 'weight', 'muscle' => 'muscle', 'fat' => 'fat',
        'fatPct' => 'fat_pct', 'bmi' => 'bmi', 'health' => 'health',
        'bmr' => 'bmr', 'water' => 'water', 'visceral' => 'visceral',
        'waistHip' => 'waist_hip', 'protein' => 'protein', 'salt' => 'salt',
        'leanMass' => 'lean_mass', 'intraWater' => 'intra_water',
        'extraWater' => 'extra_water', 'abdominalFat' => 'abdominal_fat',
        'subcutFat' => 'subcut_fat', 'burnRec' => 'burn_rec',
        'ranges' => 'ranges', 'segments' => 'segments', 'nutrition' => 'nutrition',
    ];

    public function __construct(private readonly ScanParserService $parser) {}

    /** GET /scans — history list (cursor-paginated). */
    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 20);
        $limit = max(1, min($limit, 100));

        $paginator = $request->user()->scans()
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->cursorPaginate($limit, cursorName: 'cursor');

        return response()->json([
            'data' => ScanSummaryResource::collection($paginator->getCollection())->resolve(),
            'nextCursor' => $paginator->nextCursor()?->encode(),
        ]);
    }

    /** GET /scans/{id} — full detail. */
    public function show(Request $request, string $scan): ScanResource
    {
        return new ScanResource($this->findScan($request, $scan));
    }

    /** POST /scans/upload — accept a photo/PDF, kick off an OCR parse job. */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,heic,pdf', 'max:20480'],
        ]);

        $user = $request->user();
        $file = $request->file('file');
        $path = $file->store('scans', 'public');

        $photo = Photo::create([
            'user_id' => $user->id,
            'kind' => 'scan',
            'disk' => 'public',
            'path' => $path,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        $job = $this->parser->createJob($user, $photo);

        return response()->json([
            'jobId' => $job->id,
            'status' => $job->status,
        ], Response::HTTP_ACCEPTED);
    }

    /** GET /scans/parse/{jobId} — poll the parse job. */
    public function parseStatus(Request $request, string $jobId): JsonResponse
    {
        /** @var ScanParseJob $job */
        $job = ScanParseJob::where('user_id', $request->user()->id)
            ->findOrFail($jobId);

        $job = $this->parser->settle($job);

        if ($job->status !== 'done') {
            return response()->json(['status' => $job->status]);
        }

        return response()->json([
            'status' => $job->status,
            'confidence' => (float) $job->confidence,
            'draft' => $job->draft,
            'insight' => $job->insight,
            // The stored original report — pass back to POST /scans to attach it.
            'photoId' => $job->photo_id,
        ]);
    }

    /** POST /scans — persist a reviewed draft. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'label' => ['sometimes', 'nullable', 'string'],
            'weight' => ['required', 'numeric'],
            'muscle' => ['required', 'numeric'],
            'fat' => ['required', 'numeric'],
            'fatPct' => ['required', 'numeric'],
            'bmi' => ['required', 'numeric'],
            'health' => ['required', 'numeric'],
            'bmr' => ['sometimes', 'nullable', 'numeric'],
            'water' => ['sometimes', 'nullable', 'numeric'],
            'visceral' => ['sometimes', 'nullable', 'integer'],
            'waistHip' => ['sometimes', 'nullable', 'numeric'],
            'protein' => ['sometimes', 'nullable', 'numeric'],
            'salt' => ['sometimes', 'nullable', 'numeric'],
            'leanMass' => ['sometimes', 'nullable', 'numeric'],
            'intraWater' => ['sometimes', 'nullable', 'numeric'],
            'extraWater' => ['sometimes', 'nullable', 'numeric'],
            'abdominalFat' => ['sometimes', 'nullable', 'integer'],
            'subcutFat' => ['sometimes', 'nullable', 'integer'],
            'burnRec' => ['sometimes', 'nullable', 'integer'],
            'ranges' => ['sometimes', 'nullable', 'array'],
            'segments' => ['sometimes', 'nullable', 'array'],
            'nutrition' => ['sometimes', 'nullable', 'array'],
            'photoId' => ['sometimes', 'nullable', 'string'],
        ]);

        $attributes = ['user_id' => $request->user()->id];

        foreach (self::FIELD_MAP as $input => $column) {
            if (array_key_exists($input, $validated)) {
                $attributes[$column] = $validated[$input];
            }
        }

        // Attach the original report photo if it belongs to this user.
        if (! empty($validated['photoId'])) {
            $owns = Photo::where('user_id', $request->user()->id)
                ->whereKey($validated['photoId'])
                ->exists();
            if ($owns) {
                $attributes['photo_id'] = $validated['photoId'];
            }
        }

        $scan = Scan::create($attributes);

        return response()->json(['id' => $scan->id], Response::HTTP_CREATED);
    }

    /** DELETE /scans/{id} */
    public function destroy(Request $request, string $scan): Response
    {
        $this->findScan($request, $scan)->delete();

        return response()->noContent();
    }

    private function findScan(Request $request, string $id): Scan
    {
        return $request->user()->scans()->findOrFail($id);
    }
}
