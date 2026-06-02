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
}
