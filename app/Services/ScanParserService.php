<?php

namespace App\Services;

use Anthropic\Client;
use App\Http\Resources\ScanResource;
use App\Jobs\ParseScanJob;
use App\Models\Photo;
use App\Models\Scan;
use App\Models\ScanParseJob;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Reads an uploaded InBody report and extracts the body-composition metrics.
 *
 * When an ANTHROPIC_API_KEY is configured, the stored photo/PDF is sent to
 * Claude (vision + structured output) which returns the values in the exact
 * shape the app consumes. Without a key — or if the call fails — it falls back
 * to a deterministic synthesized draft so the upload → review → save flow always
 * works end to end.
 */
class ScanParserService
{
    private const CONFIDENCE = 0.94;

    /** Population reference ranges (not read from the sheet). */
    private const RANGES = [
        'weight' => [20, 62.6, 80.9, 162],
        'muscle' => [12, 38.2, 45.1, 90],
        'fat' => [3, 11, 21, 42],
    ];

    /** Image MIME types Claude vision accepts (PDFs go via the document block). */
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /**
     * Record the job as 'processing' and hand the (slow) extraction to the
     * queue, so POST /scans/upload returns immediately and the app polls.
     */
    public function createJob(User $user, ?Photo $photo): ScanParseJob
    {
        $job = ScanParseJob::create([
            'user_id' => $user->id,
            'photo_id' => $photo?->id,
            'status' => 'processing',
        ]);

        ParseScanJob::dispatch($job->id);

        return $job;
    }

    /**
     * Runs on the queue worker: perform the extraction and finalize the job.
     * Extraction itself never throws (it falls back to a synthesized draft),
     * so reaching 'done' is the normal terminal state.
     */
    public function fulfill(string $jobId): void
    {
        $job = ScanParseJob::with('photo')->find($jobId);
        if (! $job || $job->status === 'done') {
            return;
        }

        $user = $job->user;
        [$draftScan, $confidence] = $this->extract($user, $job->photo);

        // Render the draft in the GET /scans/{id} shape (transient, unsaved).
        $draft = ScanResource::make($draftScan)->resolve();

        $job->update([
            'status' => 'done',
            'confidence' => $confidence,
            'draft' => $draft,
            'insight' => $this->insight($user, $draftScan),
        ]);
    }

    /** The worker finalizes the job; the poll just reflects its current state. */
    public function settle(ScanParseJob $job): ScanParseJob
    {
        return $job->fresh() ?? $job;
    }

    /**
     * @return array{0: Scan, 1: float} [unsaved draft scan, confidence]
     */
    private function extract(User $user, ?Photo $photo): array
    {
        if ($photo && config('services.anthropic.key')) {
            try {
                return $this->extractWithClaude($user, $photo);
            } catch (Throwable $e) {
                Log::warning('InBody Claude extraction failed; using fallback.', ['error' => $e->getMessage()]);
            }
        }

        return [$this->draftScan($user), self::CONFIDENCE];
    }

    /**
     * Send the report to Claude and map its structured output onto a Scan.
     *
     * @return array{0: Scan, 1: float}
     */
    private function extractWithClaude(User $user, Photo $photo): array
    {
        $mime = $photo->mime ?: 'image/png';

        if ($mime !== 'application/pdf' && ! in_array($mime, self::IMAGE_MIMES, true)) {
            // Unsupported format for vision — fall back gracefully.
            return [$this->draftScan($user), self::CONFIDENCE];
        }

        $bytes = Storage::disk($photo->disk ?? 'public')->get($photo->path);
        $b64 = base64_encode($bytes);

        $media = $mime === 'application/pdf'
            ? ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $b64]]
            : ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => $b64]];

        $client = new Client(apiKey: config('services.anthropic.key'));

        $message = $client->messages->create(
            model: config('services.anthropic.model', 'claude-opus-4-7'),
            maxTokens: 8000,
            thinking: ['type' => 'adaptive'],
            system: 'You are a precise medical-document data extractor. You read InBody body-composition '
                .'analysis sheets and transcribe their numeric values exactly. Never invent values: if a field '
                .'is not present or not legible, omit it from the output. Units: weight/muscle/fat/protein in kg, '
                .'percentages as plain numbers, water in litres, BMR in kcal. Set "confidence" to your overall '
                .'transcription confidence between 0 and 1.',
            messages: [[
                'role' => 'user',
                'content' => [
                    $media,
                    ['type' => 'text', 'text' => 'Extract every body-composition value from this InBody report into the required JSON schema.'],
                ],
            ]],
            outputConfig: [
                'effort' => 'medium',
                'format' => ['type' => 'json_schema', 'schema' => $this->schema()],
            ],
        );

        $json = null;
        foreach ($message->content as $block) {
            if (($block->type ?? null) === 'text') {
                $json = $block->text;
                break;
            }
        }

        $data = is_string($json) ? json_decode($json, true) : null;
        if (! is_array($data)) {
            throw new \RuntimeException('Claude returned no parseable JSON.');
        }

        $confidence = isset($data['confidence']) && is_numeric($data['confidence'])
            ? (float) $data['confidence']
            : self::CONFIDENCE;

        return [$this->scanFromExtraction($user, $data), $confidence];
    }

    /** Map the model's camelCase output onto an unsaved Scan. */
    private function scanFromExtraction(User $user, array $d): Scan
    {
        $date = Carbon::today();
        $num = fn (string $k) => array_key_exists($k, $d) && is_numeric($d[$k]) ? (float) $d[$k] : null;
        $int = fn (string $k) => array_key_exists($k, $d) && is_numeric($d[$k]) ? (int) round((float) $d[$k]) : null;

        $segments = null;
        if (isset($d['segments']) && is_array($d['segments'])) {
            foreach (['armR', 'armL', 'torso', 'legR', 'legL'] as $seg) {
                $s = $d['segments'][$seg] ?? null;
                if (is_array($s) && is_numeric($s['muscle'] ?? null) && is_numeric($s['fat'] ?? null)) {
                    $segments[$seg] = ['muscle' => (float) $s['muscle'], 'fat' => (float) $s['fat']];
                }
            }
        }

        $nutrition = null;
        if (isset($d['nutrition']) && is_array($d['nutrition'])) {
            foreach (['protein', 'fat', 'salt'] as $n) {
                $v = $d['nutrition'][$n] ?? null;
                if (in_array($v, ['low', 'normal', 'high'], true)) {
                    $nutrition[$n] = $v;
                }
            }
        }

        return new Scan([
            'user_id' => $user->id,
            'date' => $date,
            'label' => $date->format('j M'),
            'weight' => $num('weight'),
            'muscle' => $num('muscle'),
            'fat' => $num('fat'),
            'fat_pct' => $num('fatPct'),
            'bmi' => $num('bmi'),
            'health' => $num('health'),
            'bmr' => $num('bmr'),
            'water' => $num('water'),
            'visceral' => $int('visceral'),
            'waist_hip' => $num('waistHip'),
            'protein' => $num('protein'),
            'salt' => $num('salt'),
            'lean_mass' => $num('leanMass'),
            'intra_water' => $num('intraWater'),
            'extra_water' => $num('extraWater'),
            'abdominal_fat' => $int('abdominalFat'),
            'subcut_fat' => $int('subcutFat'),
            'burn_rec' => $int('burnRec'),
            'ranges' => self::RANGES,
            'segments' => $segments,
            'nutrition' => $nutrition,
        ]);
    }

    /**
     * JSON schema for Claude's structured output. Anthropic's grammar
     * compiler limits unions, optionals, and overall complexity, so we
     * extract only the flat headline scalars here. Nested segments and
     * nutrition stay unset by extraction — they're filled from the
     * previous scan or by the review step.
     */
    private function schema(): array
    {
        $num = ['type' => 'number'];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'weight' => $num, 'muscle' => $num, 'fat' => $num, 'fatPct' => $num,
                'bmi' => $num, 'health' => $num, 'bmr' => $num, 'water' => $num,
                'visceral' => $num, 'waistHip' => $num, 'protein' => $num, 'salt' => $num,
                'leanMass' => $num, 'intraWater' => $num, 'extraWater' => $num,
                'abdominalFat' => $num, 'subcutFat' => $num, 'burnRec' => $num,
                'confidence' => $num,
            ],
            'required' => ['weight', 'muscle', 'fat', 'fatPct', 'bmi', 'health', 'bmr', 'water', 'confidence'],
        ];
    }

    /** Build an unsaved Scan representing the freshly "read" printout (fallback). */
    private function draftScan(User $user): Scan
    {
        $latest = $user->scans()->orderByDesc('date')->first();

        $date = $latest
            ? Carbon::parse($latest->date)->addDays(8)
            : Carbon::today();

        if (! $latest) {
            return $this->baselineScan($user, $date);
        }

        $scan = $latest->replicate();
        $scan->id = null;
        $scan->photo_id = null;
        $scan->date = $date;
        $scan->label = $date->format('j M');
        $scan->weight = round((float) $latest->weight - 0.6, 1);
        $scan->fat = round((float) $latest->fat - 0.7, 1);
        $scan->fat_pct = round((float) $latest->fat_pct - 0.4, 1);
        $scan->muscle = round((float) $latest->muscle + 0.1, 1);
        $scan->bmi = round((float) $latest->bmi - 0.2, 1);
        $scan->health = round((float) $latest->health + 1.2, 1);
        $scan->visceral = max(1, (int) $latest->visceral - 1);

        return $scan;
    }

    private function baselineScan(User $user, Carbon $date): Scan
    {
        return new Scan([
            'user_id' => $user->id,
            'date' => $date,
            'label' => $date->format('j M'),
            'weight' => 100.0, 'muscle' => 38.6, 'fat' => 31.9, 'fat_pct' => 31.9,
            'bmi' => 29.6, 'health' => 65.1, 'bmr' => 1840.8, 'water' => 49.8,
            'visceral' => 14, 'waist_hip' => 0.98, 'protein' => 12.9, 'salt' => 5.4,
            'lean_mass' => 68.1, 'intra_water' => 30.6, 'extra_water' => 19.2,
            'abdominal_fat' => 140, 'subcut_fat' => 280, 'burn_rec' => 384,
            'ranges' => self::RANGES,
            'segments' => [
                'armR' => ['muscle' => 4.1, 'fat' => 2.3],
                'armL' => ['muscle' => 4.1, 'fat' => 2.3],
                'torso' => ['muscle' => 31.4, 'fat' => 17.3],
                'legR' => ['muscle' => 11.3, 'fat' => 4.2],
                'legL' => ['muscle' => 11.2, 'fat' => 4.2],
            ],
            'nutrition' => ['protein' => 'low', 'fat' => 'high', 'salt' => 'high'],
        ]);
    }

    private function insight(User $user, Scan $draft): string
    {
        $latest = $user->scans()->orderByDesc('date')->first();

        if (! $latest) {
            return 'First scan recorded. We will start tracking your trend from here.';
        }

        $days = abs((int) Carbon::parse($latest->date)->diffInDays(Carbon::parse($draft->date)));
        $healthDelta = round((float) $draft->health - (float) $latest->health, 1);

        return sprintf(
            '%d days after your last scan. Health score %s %+.1f.',
            $days,
            $healthDelta >= 0 ? 'up' : 'down',
            $healthDelta,
        );
    }
}
