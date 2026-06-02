# MAKE-IT-FAIL.md

## 1. Use the database queue driver

Set this in `.env`:

```
QUEUE_CONNECTION=database
```

Run:

```bash
php artisan queue:table
php artisan queue:failed-table
php artisan migrate
```

Clear config:

```bash
php artisan config:clear
```

---

## 2. Start the queue worker

Open a second terminal and run:

```bash
php artisan queue:work
```

---

## 3. Trigger a successful archive

Send this request:

```bash
curl -X POST http://localhost:8000/api/matches \
  -H "Content-Type: application/json" \
  -d '{
    "match_uuid": "success-match-001",
    "game_slug": "demo-game",
    "played_at": "2026-06-02T12:00:00Z",
    "payload": {
      "winner": "player-one",
      "score": 100
    }
  }'
```

Expected result:

- HTTP response returns `202`
- A row appears in `archived_matches`
- A row briefly appears in `jobs`
- The worker processes it
- The match status becomes `archived`

---

## 4. Trigger a failed archive

Send this request:

```bash
curl -X POST http://localhost:8000/api/matches \
  -H "Content-Type: application/json" \
  -d '{
    "match_uuid": "fail-match-001",
    "game_slug": "demo-game",
    "played_at": "2026-06-02T12:00:00Z",
    "payload": {
      "force_fail": true
    }
  }'
```

Expected result:

- HTTP response returns `202`
- The job fails in the queue worker
- Laravel retries the job up to 3 times
- Retry delays follow: 10 seconds, 60 seconds, 300 seconds
- After the final failure, a row appears in `failed_jobs`
- The match status becomes `failed`
- An error is written to:

```
storage/logs/match-archive-errors.log
```

---

## 5. Inspect the jobs table

While a job is waiting or delayed, run:

```bash
php artisan tinker
```

Then:

```php
DB::table('jobs')->get();
```

You should see queued or delayed jobs before they are processed.

---

## 6. Inspect the failed jobs table

After the forced failure finishes all retries, run:

```php
DB::table('failed_jobs')->get();
```

You should see a failed job entry containing the failed job payload and exception details.

---

## 7. Retry failed jobs

List failed jobs:

```bash
php artisan queue:failed
```

Retry all failed jobs:

```bash
php artisan queue:retry all
```

Retry one failed job:

```bash
php artisan queue:retry {failed_job_id}
```

Forget a failed job:

```bash
php artisan queue:forget {failed_job_id}
```

Flush all failed jobs:

```bash
php artisan queue:flush
```

---

## 8. Important note about sync vs database

With:

```
QUEUE_CONNECTION=sync
```

The job runs immediately during the HTTP request. If it throws an exception, the user may see the error directly in the HTTP response.

With:

```
QUEUE_CONNECTION=database
```

The API returns quickly, usually with `202 Accepted`, and the job runs later in the queue worker. If it fails, the error appears in the worker, logs, and `failed_jobs` table instead of directly in the HTTP response.
