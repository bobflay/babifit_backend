<?php

namespace App\Services;

use Anthropic\Client;
use App\Models\Photo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Gym-machine analysis behind the "snap a machine" flow.
 *
 * When an ANTHROPIC_API_KEY is configured, a photo of a gym machine is sent to
 * Claude (vision + structured output) to identify the machine, its target
 * muscles, and an estimated calorie burn per working set. Without a key — or if
 * the call fails — it falls back to a deterministic catalog pick so the flow
 * always works. Total calories for a logged session are then sized from the
 * per-set estimate and the sets/reps the user enters (see kcalFor()).
 */
class GymMachineRecognitionService
{
    /** Image MIME types Claude vision accepts. */
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /** Reps a kcalPerSet estimate is referenced against (kcal scales from here). */
    private const BASELINE_REPS = 10;

    /** Deterministic fallbacks for the photo flow, keyed off the file hash. */
    private const CATALOG = [
        [
            'name' => 'Chest Press',
            'muscles' => ['Chest', 'Shoulders', 'Triceps'],
            'kcalPerSet' => 9,
            'confidence' => 0.82,
        ],
        [
            'name' => 'Lat Pulldown',
            'muscles' => ['Back', 'Biceps'],
            'kcalPerSet' => 8,
            'confidence' => 0.8,
        ],
        [
            'name' => 'Leg Press',
            'muscles' => ['Quads', 'Glutes', 'Hamstrings'],
            'kcalPerSet' => 12,
            'confidence' => 0.84,
        ],
        [
            'name' => 'Seated Row',
            'muscles' => ['Back', 'Biceps'],
            'kcalPerSet' => 8,
            'confidence' => 0.81,
        ],
    ];

    /**
     * Identify a gym-machine photo and estimate its per-set calorie burn.
     *
     * @param  Photo|null  $photo  the stored machine photo (drives the Claude call)
     * @param  string|null  $fingerprint  e.g. the file hash, for deterministic fallback variety
     */
    public function recognize(?Photo $photo = null, ?string $fingerprint = null): array
    {
        if ($photo && config('services.anthropic.key')) {
            try {
                return $this->recognizeWithClaude($photo);
            } catch (Throwable $e) {
                Log::warning('Gym machine Claude recognition failed; using fallback.', ['error' => $e->getMessage()]);
            }
        }

        return $this->catalogPick($fingerprint ?? $photo?->id);
    }

    /**
     * Total calories for a session, scaled from the per-set estimate by the
     * sets performed and how the rep count compares to the baseline.
     */
    public function kcalFor(int $kcalPerSet, int $sets, int $reps): int
    {
        $sets = max(1, $sets);
        $reps = max(1, $reps);

        return (int) max(1, round($kcalPerSet * $sets * ($reps / self::BASELINE_REPS)));
    }

    /** Deterministic catalog choice for the photo fallback. */
    private function catalogPick(?string $fingerprint): array
    {
        $index = $fingerprint
            ? hexdec(substr(md5($fingerprint), 0, 8)) % count(self::CATALOG)
            : 0;

        return self::CATALOG[$index];
    }

    /** Send the machine photo to Claude vision and map its structured output. */
    private function recognizeWithClaude(Photo $photo): array
    {
        $mime = $photo->mime ?: 'image/jpeg';
        if (! in_array($mime, self::IMAGE_MIMES, true)) {
            return $this->catalogPick($photo->id);
        }

        $bytes = Storage::disk($photo->disk ?? 'public')->get($photo->path);
        $b64 = base64_encode($bytes);

        $client = new Client(apiKey: config('services.anthropic.key'));

        $message = $client->messages->create(
            model: config('services.anthropic.model', 'claude-opus-4-7'),
            maxTokens: 2000,
            thinking: ['type' => 'adaptive'],
            system: 'You are a strength-training vision assistant. Identify the gym machine in the photo '
                .'(use the common name shown on the machine if visible, e.g. "Chest Press", "Lat Pulldown"). '
                .'List the primary target muscle groups it trains. Estimate "kcalPerSet": the whole-number '
                .'calories an average adult burns performing one working set of about 10 reps on this machine '
                .'at a moderate load (typically 6-15). Set "confidence" between 0 and 1 to reflect how sure '
                .'you are of the machine identification.',
            messages: [[
                'role' => 'user',
                'content' => [
                    ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => $b64]],
                    ['type' => 'text', 'text' => 'Identify this gym machine and estimate its calorie burn per set.'],
                ],
            ]],
            outputConfig: [
                'effort' => 'low',
                'format' => ['type' => 'json_schema', 'schema' => $this->imageSchema()],
            ],
        );

        return $this->fromImagePayload($this->decode($message));
    }

    /** Map Claude's payload onto the recognize() shape. */
    private function fromImagePayload(array $d): array
    {
        return [
            'name' => $this->name($d['name'] ?? null, 'Gym machine'),
            'muscles' => $this->muscles($d['muscles'] ?? null),
            'kcalPerSet' => $this->kcalPerSet($d['kcalPerSet'] ?? null),
            'confidence' => $this->confidence($d['confidence'] ?? null),
        ];
    }

    /** Pull the first text block out of a Claude message and JSON-decode it. */
    private function decode($message): array
    {
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

        return $data;
    }

    private function muscles($muscles): array
    {
        if (! is_array($muscles)) {
            return [];
        }

        $out = [];
        foreach ($muscles as $m) {
            if (is_string($m) && trim($m) !== '') {
                $out[] = trim($m);
            }
        }

        return array_slice($out, 0, 4);
    }

    /** Clamp the per-set estimate to a sane working range. */
    private function kcalPerSet($v): int
    {
        $n = is_numeric($v) ? (int) round((float) $v) : 8;

        return max(1, min(40, $n));
    }

    private function confidence($v): float
    {
        return is_numeric($v) ? max(0.0, min(1.0, (float) $v)) : 0.5;
    }

    private function name($value, string $fallback): string
    {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        return ucfirst(trim($fallback)) ?: 'Gym machine';
    }

    /** JSON schema for the machine recognition response. */
    private function imageSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'name' => ['type' => 'string'],
                'muscles' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'kcalPerSet' => ['type' => 'number'],
                'confidence' => ['type' => 'number'],
            ],
            'required' => ['name', 'muscles', 'kcalPerSet', 'confidence'],
        ];
    }
}
