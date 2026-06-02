<?php

use App\Jobs\ArchiveMatchJob;
use App\Models\ArchivedMatch;
use Illuminate\Support\Facades\Queue;

it('returns 202 and dispatches the job for a new match', function () {
    Queue::fake();

    $response = $this->postJson('/api/matches', [
        'match_uuid' => 'match-123',
        'game_slug'  => 'my-game',
        'played_at'  => '2026-06-02T12:00:00Z',
        'payload'    => ['score' => 10],
    ]);

    $response->assertAccepted();

    Queue::assertPushed(ArchiveMatchJob::class);

    $this->assertDatabaseHas('archived_matches', [
        'match_uuid' => 'match-123',
        'status'     => 'pending',
    ]);
});

it('does not dispatch a second job for a duplicate match_uuid', function () {
    ArchivedMatch::factory()->create([
        'match_uuid' => 'match-123',
        'status'     => 'archived',
    ]);

    Queue::fake();

    $response = $this->postJson('/api/matches', [
        'match_uuid' => 'match-123',
        'game_slug'  => 'my-game',
        'played_at'  => '2026-06-02T12:00:00Z',
        'payload'    => ['score' => 10],
    ]);

    $response->assertOk();

    Queue::assertNothingPushed();
});

it('sets status to archived and increments attempts on successful handle', function () {
    $match = ArchivedMatch::factory()->create([
        'status'   => 'pending',
        'attempts' => 0,
        'payload'  => ['score' => 10],
    ]);

    (new ArchiveMatchJob($match->id))->handle();

    $match->refresh();

    expect($match->status)->toBe('archived');
    expect($match->attempts)->toBe(1);
    expect($match->payload)->toHaveKey('archived_at');
});

it('returns early without changing attempts if match is already archived', function () {
    $match = ArchivedMatch::factory()->create([
        'status'   => 'archived',
        'attempts' => 1,
    ]);

    (new ArchiveMatchJob($match->id))->handle();

    $match->refresh();

    expect($match->attempts)->toBe(1);
});

it('marks status as failed when the job fails', function () {
    $match = ArchivedMatch::factory()->create([
        'status'  => 'pending',
        'payload' => ['force_fail' => true],
    ]);

    $job = new ArchiveMatchJob($match->id);
    $exception = null;

    try {
        $job->handle();
    } catch (RuntimeException $e) {
        $exception = $e;
    }

    expect($exception)->not->toBeNull();

    $job->failed($exception);

    $match->refresh();

    expect($match->status)->toBe('failed');
});

it('has the correct retry and backoff settings', function () {
    $job = new ArchiveMatchJob(1);

    expect($job->tries)->toBe(3);
    expect($job->backoff)->toBe([10, 60, 300]);
});

it('returns the current status for the status endpoint', function () {
    $match = ArchivedMatch::factory()->create([
        'match_uuid' => 'match-abc',
        'status'     => 'archived',
        'attempts'   => 2,
    ]);

    $response = $this->getJson("/api/matches/{$match->match_uuid}/status");

    $response->assertOk()
        ->assertJsonFragment(['status' => 'archived']);
});
