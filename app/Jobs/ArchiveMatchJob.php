<?php

namespace App\Jobs;

use App\Models\ArchivedMatch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ArchiveMatchJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(public int $archivedMatchId)
    {
    }

    public function handle(): void
    {
        $match = ArchivedMatch::findOrFail($this->archivedMatchId);

        if ($match->status === 'archived') {
            return;
        }

        $match->update([
            'status'   => 'processing',
            'attempts' => $match->attempts + 1,
        ]);

        $payload = $match->payload;

        if (($payload['force_fail'] ?? false) === true) {
            throw new RuntimeException('Forced archive failure for testing.');
        }

        $payload['archived_at'] = now()->toISOString();

        $match->update([
            'payload' => $payload,
            'status'  => 'archived',
        ]);
    }

    public function failed(Throwable $e): void
    {
        $match = ArchivedMatch::find($this->archivedMatchId);

        if ($match) {
            $match->update([
                'status' => 'failed',
            ]);
        }

        Log::channel('match-failures')->error('Match archive job failed', [
            'archived_match_id' => $this->archivedMatchId,
            'message'           => $e->getMessage(),
        ]);
    }
}
