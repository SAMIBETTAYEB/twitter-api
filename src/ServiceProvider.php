<?php

namespace samibettayeb\TwitterApi;

use DG\Twitter\Twitter;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    const CONFIG_PATH = __DIR__ . '/../config/twitter-api.php';

    public function boot()
    {
        $this->publishes([
            self::CONFIG_PATH => config_path('twitter-api.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            self::CONFIG_PATH,
            'twitter-api'
        );

        $this->app->bind(TwitterApi::class, function () {
            Twitter::$cacheDir = config('twitter-api.REQUEST_CACHE_DIR');
            Twitter::$cacheExpire = config('twitter-api.REQUEST_CACHE_EXIPRE_AT');
            return new TwitterApi(config('twitter-api.TWITTER_APP_KEY'), config('twitter-api.TWITTER_APP_SECRET'));
        });
    }
}
