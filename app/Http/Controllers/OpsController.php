<?php

namespace App\Http\Controllers;

use App\Models\Course;
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
    public const ACTIONS = ['status', 'migrate', 'import', 'optimize', 'clear', 'delete-course'];

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
                'delete-course' => $out = $this->deleteCourse($request->query('slug')),
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
