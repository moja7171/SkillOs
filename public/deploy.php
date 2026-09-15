<?php

// Triggered automatically by .cpanel.yml's deployment task via `curl` right
// after every file copy, so a deploy needs no manual browser visit to
// /_ops/… afterwards. Safe to leave in place: every command below is
// idempotent (migrate/content:import/cache all no-op cleanly on a second
// run). Bootstraps Laravel directly (no HTTP routing/CSRF involved), same
// approach as a sibling project's deploy.php on this same host.
//
// Two ways in: the internal curl (REMOTE_ADDR === 127.0.0.1) as above, or a
// ?token=... matching DEPLOY_TOKEN in .env — lets this be triggered directly
// by visiting the URL in a browser to see the real output, if the internal
// curl's own success is ever hard to confirm otherwise (no SSH/Terminal on
// this host). Read straight out of .env with a raw parse since this all
// happens before Laravel (and its env() helper) boots.
$deployToken = null;
$envPath = dirname(__DIR__).'/.env';
if (is_file($envPath)) {
    foreach (file($envPath) as $line) {
        if (preg_match('/^DEPLOY_TOKEN=(.*)$/', trim($line), $m)) {
            $deployToken = trim($m[1], " \t\n\r\0\x0B\"'");
            break;
        }
    }
}

$isLocal = ($_SERVER['REMOTE_ADDR'] ?? '') === '127.0.0.1';
$hasValidToken = $deployToken && hash_equals($deployToken, $_GET['token'] ?? '');

if (! $isLocal && ! $hasValidToken) {
    http_response_code(403);
    exit('Forbidden');
}

set_time_limit(0);
ini_set('max_execution_time', '0');
ini_set('memory_limit', '512M');

function chmodRecursive(string $path, int $perm): void
{
    @chmod($path, $perm);

    if (! is_dir($path)) {
        return;
    }

    foreach (scandir($path) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        chmodRecursive($path.'/'.$item, $perm);
    }
}

echo '=== '.date('Y-m-d H:i:s')." ===\n";

$root = dirname(__DIR__);

chmodRecursive($root.'/storage', 0775);
chmodRecursive($root.'/bootstrap/cache', 0775);
echo "chmod'd storage/ and bootstrap/cache/ to 775\n\n";

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// The SQLite file itself isn't in git (gitignored on every branch); create
// it before migrate if this is the very first deploy.
$db = config('database.connections.sqlite.database');
if ($db && ! file_exists($db)) {
    @mkdir(dirname($db), 0775, true);
    touch($db);
    echo "created empty SQLite database at $db\n\n";
}

$commands = [
    ['migrate', ['--force' => true]],
    ['storage:link', []],
];

foreach (glob($root.'/content/*', GLOB_ONLYDIR) as $dir) {
    // sample-course is a test/local-demo fixture, not real content -- same exclusion as
    // OpsController::courseSlugs() on main, duplicated here since this script runs
    // standalone before routing exists and can't reuse that method directly.
    if (is_file($dir.'/course.json') && basename($dir) !== 'sample-course') {
        $commands[] = ['content:import', ['slug' => basename($dir), '--prune' => true]];
    }
}

foreach (['config:cache', 'route:cache', 'view:cache'] as $cmd) {
    $commands[] = [$cmd, []];
}

foreach ($commands as [$name, $params]) {
    $argv = array_map(fn ($k, $v) => $v === true ? $k : "$k=$v", array_keys($params), $params);
    echo '--- php artisan '.$name.' '.implode(' ', $argv)." ---\n";

    try {
        Illuminate\Support\Facades\Artisan::call($name, $params);
        echo Illuminate\Support\Facades\Artisan::output()."\n";
    } catch (Throwable $e) {
        echo 'EXCEPTION: '.get_class($e).': '.$e->getMessage()."\n";
        echo $e->getTraceAsString()."\n\n";
    }
}

echo "=== done ===\n";
