<?php

namespace App\Services;

use Anthropic\Client;
use App\Models\Photo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Food calorie/macro analysis behind the "snap a dish" and "add manually" flows.
 *
 * When an ANTHROPIC_API_KEY is configured, dish photos are sent to Claude
 * (vision + structured output) for an identification + calorie/macro estimate,
 * and manual meal descriptions are sent to Claude (text) for the same. Without
 * a key — or if the call fails — it falls back to a deterministic catalog pick
 * (photos) or a simple portion-based heuristic (text) so both flows always work.
 */
class FoodRecognitionService
{
    /** Image MIME types Claude vision accepts. */
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /** Deterministic fallbacks for the photo flow, keyed off the file hash. */
    private const CATALOG = [
        [
            'name' => 'Grilled salmon bowl',
            'kcal' => 610,
            'macros' => ['protein' => 46, 'carbs' => 58, 'fat' => 22],
            'items' => [
                ['label' => 'Salmon', 'confidence' => 0.94, 'box' => [0.20, 0.30, 0.45, 0.55]],
                ['label' => 'Rice', 'confidence' => 0.88, 'box' => [0.55, 0.55, 0.78, 0.78]],
            ],
            'confidence' => 0.92,
        ],
        [
            'name' => 'Chicken Caesar salad',
            'kcal' => 430,
            'macros' => ['protein' => 38, 'carbs' => 18, 'fat' => 24],
            'items' => [
                ['label' => 'Grilled chicken', 'confidence' => 0.91, 'box' => [0.25, 0.28, 0.52, 0.6]],
                ['label' => 'Romaine', 'confidence' => 0.83, 'box' => [0.5, 0.45, 0.82, 0.8]],
            ],
            'confidence' => 0.89,
        ],
        [
            'name' => 'Greek yogurt bowl',
            'kcal' => 340,
            'macros' => ['protein' => 28, 'carbs' => 32, 'fat' => 10],
            'items' => [
                ['label' => 'Greek yogurt', 'confidence' => 0.93, 'box' => [0.22, 0.25, 0.6, 0.7]],
                ['label' => 'Berries', 'confidence' => 0.86, 'box' => [0.55, 0.3, 0.8, 0.55]],
            ],
            'confidence' => 0.9,
        ],
    ];

    /**
     * Identify a dish photo and estimate its calories + macros.
     *
     * @param  Photo|null  $photo  the stored dish photo (drives the Claude call)
     * @param  string|null  $fingerprint  e.g. the file hash, for deterministic fallback variety
     */
    public function recognize(?Photo $photo = null, ?string $fingerprint = null): array
    {
        if ($photo && config('services.anthropic.key')) {
            try {
                return $this->recognizeWithClaude($photo);
            } catch (Throwable $e) {
                Log::warning('Dish Claude recognition failed; using fallback.', ['error' => $e->getMessage()]);
            }
        }

        return $this->catalogPick($fingerprint ?? $photo?->id);
    }

    /**
     * Estimate calories + macros for a manually-described meal.
     *
     * @param  string  $description  the dish name / description the user typed
     * @param  int|null  $portionGrams  optional portion size in grams
     */
    public function estimateFromText(string $description, ?int $portionGrams = null): array
    {
        if (config('services.anthropic.key')) {
            try {
                return $this->estimateWithClaude($description, $portionGrams);
            } catch (Throwable $e) {
                Log::warning('Meal Claude estimate failed; using fallback.', ['error' => $e->getMessage()]);
            }
        }

        return $this->heuristicEstimate($description, $portionGrams);
    }

    /** Deterministic catalog choice for the photo fallback. */
    private function catalogPick(?string $fingerprint): array
    {
        $index = $fingerprint
            ? hexdec(substr(md5($fingerprint), 0, 8)) % count(self::CATALOG)
            : 0;

        return self::CATALOG[$index];
    }

    /** Send the dish photo to Claude vision and map its structured output. */
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
            maxTokens: 3000,
            thinking: ['type' => 'adaptive'],
            system: 'You are a nutrition vision assistant. Identify the dish in the photo and estimate its '
                .'calories and macronutrients for the full portion shown, judging quantity from visible '
                .'portion size and typical preparation. Return whole-number kcal and whole-number grams for '
                .'protein/carbs/fat. List the main visible components in "items" with a per-item confidence. '
                .'Set "confidence" between 0 and 1 to reflect how sure you are of the identification and '
                .'portion estimate.',
            messages: [[
                'role' => 'user',
                'content' => [
                    ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => $b64]],
                    ['type' => 'text', 'text' => 'Identify this dish and estimate its nutrition.'],
                ],
            ]],
            outputConfig: [
                'effort' => 'low',
                'format' => ['type' => 'json_schema', 'schema' => $this->imageSchema()],
            ],
        );

        return $this->fromImagePayload($this->decode($message));
    }

    /** Send the meal description to Claude (text) and map its structured output. */
    private function estimateWithClaude(string $description, ?int $portionGrams): array
    {
        $client = new Client(apiKey: config('services.anthropic.key'));

        $prompt = $portionGrams && $portionGrams > 0
            ? sprintf('Estimate the nutrition for: %s (about %d g).', $description, $portionGrams)
            : sprintf('Estimate the nutrition for one typical serving of: %s.', $description);

        $message = $client->messages->create(
            model: config('services.anthropic.model', 'claude-opus-4-7'),
            maxTokens: 2000,
            thinking: ['type' => 'adaptive'],
            system: 'You are a nutrition database assistant. Given a meal description, estimate the calories '
                .'and macronutrients for the stated portion (or one typical serving if none is given). Return '
                .'whole-number kcal and whole-number grams for protein/carbs/fat. Normalize "name" to a clean, '
                .'capitalized dish name. Set "confidence" between 0 and 1.',
            messages: [[
                'role' => 'user',
                'content' => [['type' => 'text', 'text' => $prompt]],
            ]],
            outputConfig: [
                'effort' => 'low',
                'format' => ['type' => 'json_schema', 'schema' => $this->textSchema()],
            ],
        );

        $data = $this->decode($message);

        return [
            'name' => $this->name($data['name'] ?? null, $description),
            'kcal' => $this->int($data['kcal'] ?? null),
            'macros' => $this->macros($data['macros'] ?? null),
            'items' => [],
            'confidence' => $this->confidence($data['confidence'] ?? null),
        ];
    }

    /** Portion-based heuristic for the manual-entry fallback (~1.6 kcal/g). */
    private function heuristicEstimate(string $description, ?int $portionGrams): array
    {
        $grams = $portionGrams && $portionGrams > 0 ? $portionGrams : 300;
        $kcal = (int) round($grams * 1.6);

        return [
            'name' => $this->name(null, $description),
            'kcal' => $kcal,
            'macros' => [
                'protein' => (int) round($kcal * 0.25 / 4),
                'carbs' => (int) round($kcal * 0.45 / 4),
                'fat' => (int) round($kcal * 0.30 / 9),
            ],
            'items' => [],
            'confidence' => 0.4,
        ];
    }

    /** Map Claude's photo payload onto the recognize() shape. */
    private function fromImagePayload(array $d): array
    {
        return [
            'name' => $this->name($d['name'] ?? null, 'Meal'),
            'kcal' => $this->int($d['kcal'] ?? null),
            'macros' => $this->macros($d['macros'] ?? null),
            'items' => $this->items($d['items'] ?? null),
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

    private function macros($macros): array
    {
        $macros = is_array($macros) ? $macros : [];

        return [
            'protein' => $this->int($macros['protein'] ?? null),
            'carbs' => $this->int($macros['carbs'] ?? null),
            'fat' => $this->int($macros['fat'] ?? null),
        ];
    }

    private function items($items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $out = [];
        foreach ($items as $item) {
            if (! is_array($item) || ! is_string($item['label'] ?? null) || $item['label'] === '') {
                continue;
            }
            $out[] = [
                'label' => $item['label'],
                'confidence' => $this->confidence($item['confidence'] ?? null),
                'box' => [],
            ];
        }

        return $out;
    }

    private function int($v): int
    {
        return is_numeric($v) ? max(0, (int) round((float) $v)) : 0;
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

        return ucfirst(trim($fallback)) ?: 'Meal';
    }

    /** JSON schema for the photo (dish recognition) response. */
    private function imageSchema(): array
    {
        $num = ['type' => 'number'];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'name' => ['type' => 'string'],
                'kcal' => $num,
                'macros' => $this->macroSchema($num),
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'confidence' => $num,
                        ],
                        'required' => ['label', 'confidence'],
                    ],
                ],
                'confidence' => $num,
            ],
            'required' => ['name', 'kcal', 'macros', 'items', 'confidence'],
        ];
    }

    /** JSON schema for the text (manual entry) response. */
    private function textSchema(): array
    {
        $num = ['type' => 'number'];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'name' => ['type' => 'string'],
                'kcal' => $num,
                'macros' => $this->macroSchema($num),
                'confidence' => $num,
            ],
            'required' => ['name', 'kcal', 'macros', 'confidence'],
        ];
    }

    private function macroSchema(array $num): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => ['protein' => $num, 'carbs' => $num, 'fat' => $num],
            'required' => ['protein', 'carbs', 'fat'],
        ];
    }
}
