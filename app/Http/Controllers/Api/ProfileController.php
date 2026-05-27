<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /** GET /me */
    public function show(Request $request): UserResource
    {
        $user = $request->user()->load('target');

        return new UserResource($user);
    }

    /** PATCH /me — partial profile and/or target update. */
    public function update(Request $request): UserResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'initials' => ['sometimes', 'nullable', 'string', 'max:4'],
            'age' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:130'],
            'height' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:300'],
            'gender' => ['sometimes', 'nullable', 'in:M,F,O'],
            'streakDays' => ['sometimes', 'integer', 'min:0'],
            'avatarUrl' => ['sometimes', 'nullable', 'string'],

            'target' => ['sometimes', 'array'],
            'target.calories' => ['sometimes', 'integer', 'min:0'],
            'target.protein' => ['sometimes', 'integer', 'min:0'],
            'target.carbs' => ['sometimes', 'integer', 'min:0'],
            'target.fat' => ['sometimes', 'integer', 'min:0'],
            'target.burn' => ['sometimes', 'integer', 'min:0'],
            'target.weight' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'target.fatPct' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $user = $request->user();

        $profile = collect($data)
            ->only(['name', 'initials', 'age', 'height', 'gender'])
            ->all();

        if (array_key_exists('streakDays', $data)) {
            $profile['streak_days'] = $data['streakDays'];
        }
        if (array_key_exists('avatarUrl', $data)) {
            $profile['avatar_url'] = $data['avatarUrl'];
        }

        if ($profile) {
            $user->fill($profile)->save();
        }

        if (isset($data['target'])) {
            $targetData = collect($data['target'])
                ->mapWithKeys(fn ($value, $key) => [
                    $key === 'fatPct' ? 'fat_pct' : $key => $value,
                ])
                ->all();

            $user->target()->updateOrCreate(['user_id' => $user->id], $targetData);
        }

        return new UserResource($user->fresh('target'));
    }
}
