<?php

/*
 * Front controller for shared hosting where the web root (public_html) is not
 * the Laravel public/ folder. tools/build-release.sh copies this file into the
 * bundle's public_html/. It looks for the application in ../skillos (preferred:
 * outside the web root) or ./skillos (everything inside public_html).
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$appDir = is_dir(__DIR__.'/../skillos') ? __DIR__.'/../skillos' : __DIR__.'/skillos';

if (file_exists($maintenance = $appDir.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appDir.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appDir.'/bootstrap/app.php';

$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
