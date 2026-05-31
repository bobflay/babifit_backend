<?php

namespace App\Services;

use Anthropic\Client;
use App\Models\User;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * The in-app AI coach. Answers free-form questions ("can I eat this?", "should I
 * skip the gym today?") grounded in the user's own recent history — the last 7
 * days of meals, activities and gym sessions, plus their targets and latest body
 * scan — so advice is personalised rather than generic.
 *
 * Conversation state lives on the client; each request carries the running
 * transcript and we re-attach a freshly-built context summary to the system
 * prompt so the model always sees up-to-date numbers.
 */
class CoachService
{
    /** How many days of history to summarise into the coach's context. */
    private const HISTORY_DAYS = 7;

    /**
     * Answer the latest user message given the prior transcript.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function reply(User $user, array $messages, ?string $screen = null): string
    {
        if (! config('services.anthropic.key')) {
            return $this->fallback();
        }

        try {
            $client = new Client(apiKey: config('services.anthropic.key'));

            $message = $client->messages->create(
                model: config('services.anthropic.model', 'claude-opus-4-7'),
                maxTokens: 1200,
                thinking: ['type' => 'adaptive'],
                system: $this->systemPrompt($user, $screen),
                messages: $this->normalize($messages),
                outputConfig: ['effort' => 'low'],
            );

            return $this->text($message);
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Coach chat failed; using fallback.', ['error' => $e->getMessage()]);

            return $this->fallback();
        }
    }

    /** Coerce the client transcript into the SDK's user/assistant message shape. */
    private function normalize(array $messages): array
    {
        $out = [];
        foreach ($messages as $m) {
            $role = ($m['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user';
            $content = trim((string) ($m['content'] ?? ''));
            if ($content === '') {
                continue;
            }
            $out[] = [
                'role' => $role,
                'content' => [['type' => 'text', 'text' => $content]],
            ];
        }

        // The API requires the conversation to start with a user turn.
        while ($out !== [] && $out[0]['role'] !== 'user') {
            array_shift($out);
        }

        if ($out === []) {
            $out[] = ['role' => 'user', 'content' => [['type' => 'text', 'text' => 'Hi']]];
        }

        return $out;
    }

    /** Build the grounding system prompt: coaching brief + the user's data. */
    private function systemPrompt(User $user, ?string $screen): string
    {
        $persona = 'You are babifit Coach, a friendly, concise personal fitness and nutrition coach inside a '
            ."fitness app. The user's real recent data is provided below — always ground your answer in it. "
            .'When they ask whether they can eat or do something, give a clear verdict (yes / yes in moderation / '
            .'better not) with a one-line reason that references their numbers (calories remaining, protein left, '
            .'how much they have burned, recent training). Keep replies short — 2-4 sentences, no markdown headings. '
            .'Use the same units shown in the data (kcal, g, kg). Never invent data you were not given; if something '
            ."isn't in the context, say so briefly. You are not a doctor — avoid medical claims.";

        if ($screen) {
            $persona .= ' The user opened the coach from the "'.$screen.'" screen, so bias toward that topic.';
        }

        return $persona."\n\n=== USER CONTEXT ===\n".$this->contextSummary($user);
    }

    /** A compact, model-readable digest of the user's targets + 7-day history. */
    private function contextSummary(User $user): string
    {
        $today = Carbon::today();
        $start = $today->copy()->subDays(self::HISTORY_DAYS - 1);
        $startStr = $start->toDateString();
        $endStr = $today->toDateString();

        $lines = [];
        $lines[] = 'Name: '.$user->name;
        $lines[] = 'Today: '.$endStr;

        // Goals / targets.
        $target = $user->target;
        if ($target) {
            $lines[] = sprintf(
                'Daily targets — calories: %d kcal, protein: %dg, carbs: %dg, fat: %dg, burn goal: %d kcal.',
                (int) $target->calories,
                (int) $target->protein,
                (int) $target->carbs,
                (int) $target->fat,
                (int) $target->burn,
            );
            if ($target->weight !== null) {
                $lines[] = 'Goal weight: '.$target->weight.' kg, goal body fat: '.($target->fat_pct ?? '—').'%.';
            }
        } else {
            $lines[] = 'Daily targets: not set.';
        }

        // Latest body scan.
        $scan = $user->scans()->orderByDesc('date')->orderByDesc('created_at')->first();
        if ($scan) {
            $lines[] = sprintf(
                'Latest scan (%s) — weight: %s kg, body fat: %s%%, muscle: %s kg, health score: %s/100.',
                Carbon::parse($scan->date)->toDateString(),
                $scan->weight,
                $scan->fat_pct,
                $scan->muscle,
                $scan->health,
            );
        }

        // Today's running totals.
        $eatenToday = (int) $user->meals()->whereDate('date', $endStr)->sum('kcal');
        $proteinToday = (int) $user->meals()->whereDate('date', $endStr)->sum('protein');
        $burnedToday = (int) $user->activities()->whereDate('date', $endStr)->sum('kcal')
            + (int) $user->gymLogs()->whereDate('date', $endStr)->sum('kcal');
        $calTarget = (int) ($target->calories ?? 0);
        $remaining = $calTarget > 0 ? $calTarget - $eatenToday + $burnedToday : null;
        $lines[] = sprintf(
            'TODAY so far — eaten: %d kcal (protein %dg), burned: %d kcal%s.',
            $eatenToday,
            $proteinToday,
            $burnedToday,
            $remaining !== null ? ', remaining budget: '.$remaining.' kcal' : '',
        );

        // 7-day meals.
        $meals = $user->meals()
            ->whereBetween('date', [$startStr, $endStr])
            ->orderBy('date')->orderBy('time')->get();
        $lines[] = "\nMEALS — last ".self::HISTORY_DAYS.' days ('.$meals->count().' logged):';
        if ($meals->isEmpty()) {
            $lines[] = '  (none logged)';
        } else {
            foreach ($meals->groupBy(fn ($m) => Carbon::parse($m->date)->toDateString()) as $day => $rows) {
                $names = $rows->map(fn ($m) => $m->name.' ('.(int) $m->kcal.' kcal)')->implode(', ');
                $lines[] = sprintf('  %s [%d kcal, %dg protein]: %s', $day, (int) $rows->sum('kcal'), (int) $rows->sum('protein'), $names);
            }
        }

        // 7-day activities.
        $acts = $user->activities()
            ->whereBetween('date', [$startStr, $endStr])
            ->orderBy('date')->get();
        $lines[] = "\nCARDIO/ACTIVITIES — last ".self::HISTORY_DAYS.' days ('.$acts->count().' sessions):';
        if ($acts->isEmpty()) {
            $lines[] = '  (none logged)';
        } else {
            foreach ($acts as $a) {
                $lines[] = sprintf(
                    '  %s: %s, %d min, %d kcal%s',
                    Carbon::parse($a->date)->toDateString(),
                    $a->type,
                    (int) $a->mins,
                    (int) $a->kcal,
                    $a->distance ? ' ('.$a->distance.')' : '',
                );
            }
        }

        // 7-day gym.
        $gym = $user->gymLogs()
            ->whereBetween('date', [$startStr, $endStr])
            ->orderBy('date')->orderBy('time')->get();
        $lines[] = "\nGYM (machine sets) — last ".self::HISTORY_DAYS.' days ('.$gym->count().' entries):';
        if ($gym->isEmpty()) {
            $lines[] = '  (none logged)';
        } else {
            foreach ($gym->groupBy(fn ($g) => Carbon::parse($g->date)->toDateString()) as $day => $rows) {
                $machines = $rows->map(fn ($g) => $g->machine.' '.(int) $g->sets.'x'.(int) $g->reps)->implode(', ');
                $lines[] = sprintf('  %s [%d kcal]: %s', $day, (int) $rows->sum('kcal'), $machines);
            }
        }

        return implode("\n", $lines);
    }

    /** Pull the assistant's reply text out of a Claude message. */
    private function text($message): string
    {
        $out = '';
        foreach ($message->content as $block) {
            if (($block->type ?? null) === 'text') {
                $out .= $block->text;
            }
        }

        $out = trim($out);

        return $out !== '' ? $out : $this->fallback();
    }

    /** Shown when the AI is unavailable (no key / call failed). */
    private function fallback(): string
    {
        return "I can't reach the coach right now. As a rule of thumb: stay near your daily calorie target, "
            .'keep protein high, and balance heavier meals with a bit more movement. Try again in a moment.';
    }
}
