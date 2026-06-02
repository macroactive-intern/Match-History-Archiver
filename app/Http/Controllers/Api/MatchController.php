<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ArchiveMatchJob;
use App\Models\ArchivedMatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'match_uuid' => ['required', 'string'],
            'game_slug'  => ['required', 'string'],
            'played_at'  => ['required', 'date'],
            'payload'    => ['required', 'array'],
        ]);

        $match = ArchivedMatch::firstOrCreate(
            ['match_uuid' => $validated['match_uuid']],
            [
                'game_slug' => $validated['game_slug'],
                'played_at' => $validated['played_at'],
                'payload'   => $validated['payload'],
                'status'    => 'pending',
            ]
        );

        if ($match->wasRecentlyCreated) {
            ArchiveMatchJob::dispatch($match);

            return response()->json($match, 202);
        }

        return response()->json($match, 200);
    }

    public function status(string $uuid): JsonResponse
    {
        $match = ArchivedMatch::where('match_uuid', $uuid)->first();

        if (! $match) {
            return response()->json(['message' => 'Match not found.'], 404);
        }

        return response()->json([
            'match_uuid' => $match->match_uuid,
            'status'     => $match->status,
            'attempts'   => $match->attempts,
            'created_at' => $match->created_at,
            'updated_at' => $match->updated_at,
        ]);
    }
}
