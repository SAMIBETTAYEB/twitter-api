<?php

return [
    'TWITTER_APP_KEY' => env('TWITTER_APP_KEY', ''),
    'TWITTER_APP_SECRET' => env('TWITTER_APP_SECRET', ''),
    'TWITTER_OAUTH_TOKEN' => env('TWITTER_OAUTH_TOKEN', ''),
    'TWITTER_OAUTH_VERSION' => env('TWITTER_OAUTH_VERSION', 1),
    'REQUEST_CACHE_DIR' => env('REQUEST_CACHE_DIR', storage_path('framework/cache/data')),
    'REQUEST_CACHE_EXIPRE_AT' => env('REQUEST_CACHE_EXIPRE_AT', '15 seconds'),
];
