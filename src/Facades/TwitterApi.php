<?php

namespace samibettayeb\TwitterApi\Facades;

use Illuminate\Support\Facades\Facade;

class TwitterApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'twitter-api';
    }
}
