<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CoachService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoachController extends Controller
{
    public function __construct(private readonly CoachService $coach) {}

    /**
     * POST /coach/chat — ask the AI coach a question.
     *
     * The client owns the conversation, so it posts the running transcript;
     * the server re-grounds each turn in the user's latest 7-day history.
     */
    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'messages' => ['required', 'array', 'min:1', 'max:40'],
            'messages.*.role' => ['required', 'string', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string', 'max:4000'],
            'screen' => ['sometimes', 'nullable', 'string', 'max:40'],
        ]);

        $reply = $this->coach->reply(
            $request->user(),
            $data['messages'],
            $data['screen'] ?? null,
        );

        return response()->json(['reply' => $reply]);
    }
}
