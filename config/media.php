<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media base URL
    |--------------------------------------------------------------------------
    |
    | Course media (videos, subtitles, exercise files) is stored under the
    | `/media/...` prefix by the importer. Locally that prefix is served from
    | public/media (a symlink to the course folder). In production the files
    | may live somewhere else — for example on the owner's own machine, served
    | by tools/media-server.py — in which case set MEDIA_BASE_URL to that
    | server's origin and every `/media/x` URL is rendered as `<base>/x`.
    |
    */

    'base_url' => env('MEDIA_BASE_URL'),

];
