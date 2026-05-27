<?php

namespace App\Services;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Issues short-lived access tokens paired with longer-lived refresh tokens.
 * Tokens are distinguished by ability: 'access' for the API, 'refresh' for
 * minting new access tokens.
 */
class TokenService
{
    public const ACCESS_TTL = 3600;          // 1 hour (seconds)

    public const REFRESH_TTL = 60 * 24 * 30; // 30 days (minutes)

    /** @return array{accessToken:string, refreshToken:string, expiresIn:int} */
    public function issue(User $user): array
    {
        $access = $user->createToken(
            'access',
            ['access'],
            now()->addSeconds(self::ACCESS_TTL),
        );

        $refresh = $user->createToken(
            'refresh',
            ['refresh'],
            now()->addMinutes(self::REFRESH_TTL),
        );

        return [
            'accessToken' => $access->plainTextToken,
            'refreshToken' => $refresh->plainTextToken,
            'expiresIn' => self::ACCESS_TTL,
        ];
    }

    /** @return array{accessToken:string, expiresIn:int} */
    public function issueAccess(User $user): array
    {
        $access = $user->createToken(
            'access',
            ['access'],
            now()->addSeconds(self::ACCESS_TTL),
        );

        return [
            'accessToken' => $access->plainTextToken,
            'expiresIn' => self::ACCESS_TTL,
        ];
    }

    /** Resolve a usable refresh token, or null if missing/expired/wrong ability. */
    public function resolveRefreshToken(string $plainText): ?PersonalAccessToken
    {
        $token = PersonalAccessToken::findToken($plainText);

        if (! $token || ! $token->can('refresh')) {
            return null;
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            return null;
        }

        return $token;
    }
}
