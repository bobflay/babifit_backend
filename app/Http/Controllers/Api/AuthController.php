<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly TokenService $tokens) {}

    /** POST /auth/register — create an account and start a session. */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // hashed by the model 'password' cast
        ]);
        $user->initials = $user->resolveInitials();
        $user->save();

        return response()->json($this->tokens->issue($user), 201);
    }

    /** POST /auth/login — exchange credentials for a session. */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        return response()->json($this->tokens->issue($user));
    }

    /** POST /auth/refresh — mint a new access token from a refresh token. */
    public function refresh(Request $request): JsonResponse
    {
        $data = $request->validate([
            'refreshToken' => ['required', 'string'],
        ]);

        $token = $this->tokens->resolveRefreshToken($data['refreshToken']);

        if (! $token) {
            throw ValidationException::withMessages([
                'refreshToken' => ['The refresh token is invalid or has expired.'],
            ]);
        }

        /** @var User $user */
        $user = $token->tokenable;

        return response()->json($this->tokens->issueAccess($user));
    }

    /** POST /auth/logout — revoke the access token used for this request. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
