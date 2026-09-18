<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\Ai\GeminiClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Maintenance actions for hosts without shell access (shared hosting):
 * /_ops/{action}?token=… runs the artisan commands a deploy needs.
 * Enabled only when OPS_TOKEN is set; see README "Deploying to shared hosting".
 */
class OpsController extends Controller
{
    public const ACTIONS = ['status', 'migrate', 'import', 'optimize', 'clear', 'update', 'delete-course', 'deploy-log', 'ai-check'];

    public function __invoke(Request $request, string $action): Response
    {
        $token = (string) config('app.ops_token');

        if ($token === '' || ! hash_equals($token, (string) $request->query('token', ''))) {
            abort(404);
        }

        if (! in_array($action, self::ACTIONS, true)) {
            abort(404);
        }

        $out = [];

        try {
            match ($action) {
                'status' => $out = $this->status(),
                'migrate' => $out = $this->migrate(),
                'import' => $out = $this->import($request->query('slug'), $request->boolean('prune')),
                'optimize' => $out = $this->artisan('optimize:clear', 'config:cache', 'route:cache', 'view:cache'),
                'clear' => $out = $this->artisan('optimize:clear'),
                'update' => $out = $this->update($request->query('slug'), $request->boolean('prune', true)),
                'delete-course' => $out = $this->deleteCourse($request->query('slug')),
                'deploy-log' => $out = $this->deployLog(),
                'ai-check' => $out = $this->aiCheck(),
            };
        } catch (\Throwable $e) {
            $out[] = 'ERROR: '.$e->getMessage();

            return response(implode("\n", $out), 500, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        return response(implode("\n", $out), 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    /** @return list<string> */
    protected function status(): array
    {
        $connection = (string) config('database.default');

        return [
            'app: '.config('app.name').' ('.config('app.env').')',
            'php: '.PHP_VERSION,
            'laravel: '.app()->version(),
            'database: '.$this->databaseStatusLine($connection),
            'media base: '.(config('media.base_url') ?: '(same host)'),
            'gemini key: '.(config('services.gemini.api_key') ? 'set' : 'MISSING'),
            'ai proxy: '.(config('services.ai_proxy.url') ?: '(direct — no relay configured)'),
            'writable: '.$this->writableStatusLine($connection),
            'courses on disk: '.implode(', ', $this->courseSlugs()),
        ];
    }

    /** File-based (SQLite path/size) or server-based (connect + report host/db) status line. */
    protected function databaseStatusLine(string $connection): string
    {
        if ($connection === 'sqlite') {
            $db = config('database.connections.sqlite.database');

            return $db.' '.(File::exists($db) ? '('.Str::of((string) round(File::size($db) / 1024))->append(' KB').')' : '(missing)');
        }

        $host = config("database.connections.{$connection}.host");
        $name = config("database.connections.{$connection}.database");

        try {
            DB::connection()->getPdo();

            return "{$connection} {$host}/{$name} (connected)";
        } catch (\Throwable $e) {
            return "{$connection} {$host}/{$name} (NOT REACHABLE: {$e->getMessage()})";
        }
    }

    protected function writableStatusLine(string $connection): string
    {
        $line = 'storage='.(is_writable(storage_path()) ? 'yes' : 'NO').' bootstrap/cache='.(is_writable(base_path('bootstrap/cache')) ? 'yes' : 'NO');

        if ($connection === 'sqlite') {
            $dir = dirname((string) config('database.connections.sqlite.database'));
            $line .= ' database/='.(is_writable($dir) ? 'yes' : 'NO');
        }

        return $line;
    }

    /** @return list<string> */
    protected function migrate(): array
    {
        if (config('database.default') === 'sqlite') {
            $db = config('database.connections.sqlite.database');

            if (! File::exists($db)) {
                File::ensureDirectoryExists(dirname($db));
                File::put($db, '');
            }
        }

        return $this->artisan(['migrate' => ['--force' => true]]);
    }

    /** @return list<string> */
    protected function import(?string $slug, bool $prune): array
    {
        $slugs = $slug ? [$slug] : $this->courseSlugs();
        $out = [];

        foreach ($slugs as $s) {
            $out = array_merge($out, $this->artisan(['content:import' => ['slug' => $s, '--prune' => $prune]]));
        }

        return $out ?: ['nothing to import — no course folders under content/'];
    }

    /**
     * The three manual-path steps (migrate, import, optimize) combined into one request,
     * mirroring what `public/deploy.php` runs automatically on the git-based path. `prune`
     * defaults to true here (unlike the standalone `import` action) since a host update is
     * normally a full re-sync of everything under `content/`, not a one-off partial import.
     *
     * @return list<string>
     */
    protected function update(?string $slug, bool $prune): array
    {
        return [
            ...$this->migrate(),
            '',
            ...$this->import($slug, $prune),
            '',
            ...$this->artisan('optimize:clear', 'config:cache', 'route:cache', 'view:cache'),
        ];
    }

    /**
     * Permanently removes a course and everything under it (lessons, activities,
     * mastery records, attempts, plan items — all cascade via FK constraints). Requires
     * an explicit `slug`; there is no "delete everything" form. This is the only way to
     * remove a course on a host with no shell access — `content:import` only adds/updates.
     *
     * @return list<string>
     */
    protected function deleteCourse(?string $slug): array
    {
        if (! $slug) {
            return ['ERROR: pass ?slug=<course-slug> — refusing to delete without one'];
        }

        $course = Course::where('slug', $slug)->first();

        if (! $course) {
            return ["no course with slug \"{$slug}\" — nothing to delete"];
        }

        $title = $course->title;
        $lessonCount = $course->lessons()->count();
        $course->delete();

        return ["deleted course \"{$title}\" (slug: {$slug}, {$lessonCount} lessons)"];
    }

    /**
     * Tail of `storage/logs/deploy-hook.log`, written by `public/deploy.php` (the deploy
     * branch's `.cpanel.yml` post-pull hook) on every invocation, success or failure —
     * the only way to see whether that hook actually ran on a host with no SSH/Terminal.
     *
     * @return list<string>
     */
    protected function deployLog(): array
    {
        $path = storage_path('logs/deploy-hook.log');

        if (! File::exists($path)) {
            return ["no {$path} yet — the auto-deploy hook (.cpanel.yml → deploy.php) has never run, or storage/logs isn't writable"];
        }

        return [collect(explode("\n", File::get($path)))->slice(-200)->implode("\n")];
    }

    /**
     * Round-trips a real Gemini call (direct, or through the relay when `AI_PROXY_URL`
     * is set — see DECISIONS.md §60) so a config change can be confirmed working without
     * needing a learner to submit a real practice. Iran-hosted production genuinely
     * cannot reach Gemini directly (confirmed 2026-09-18); this is how to check whether
     * the relay is actually wired up correctly after changing `.env`.
     *
     * @return list<string>
     */
    protected function aiCheck(): array
    {
        $proxy = config('services.ai_proxy.url');

        $out = ['ai proxy: '.($proxy ?: '(direct — no relay configured)')];

        try {
            $result = app(GeminiClient::class)->generateJson(
                'Reply with the word OK in the answer field.',
                ['type' => 'OBJECT', 'properties' => ['answer' => ['type' => 'STRING']], 'required' => ['answer']],
            );
            $out[] = 'Gemini relay test SUCCESS: '.json_encode($result);
        } catch (\Throwable $e) {
            $out[] = 'Gemini relay test FAILED: '.$e->getMessage();
        }

        return $out;
    }

    /**
     * Courses swept into a bare `import`/`status` (no explicit `slug`). `sample-course`
     * is deliberately excluded — it's a test/local-demo fixture (README "Local setup"),
     * not something that should show up in the real catalog on every deploy; it's still
     * importable by name (`/_ops/import?slug=sample-course`) if ever wanted locally.
     *
     * @return list<string>
     */
    protected function courseSlugs(): array
    {
        return collect(File::directories(base_path('content')))
            ->filter(fn ($dir) => File::exists($dir.'/course.json'))
            ->map(fn ($dir) => basename($dir))
            ->reject(fn ($slug) => $slug === 'sample-course')
            ->values()
            ->all();
    }

    /**
     * Run artisan commands and collect their output. Each argument is either a command
     * name or [command => parameters].
     *
     * @return list<string>
     */
    protected function artisan(string|array ...$commands): array
    {
        $out = [];

        foreach ($commands as $command) {
            [$name, $params] = is_array($command) ? [array_key_first($command), reset($command)] : [$command, []];
            $code = Artisan::call($name, $params);
            $out[] = "$ artisan {$name} → exit {$code}";
            $out[] = trim(Artisan::output());
        }

        return $out;
    }
}
