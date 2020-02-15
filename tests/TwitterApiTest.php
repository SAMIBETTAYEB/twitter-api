<?php

namespace samibettayeb\TwitterApi\Tests;

use Orchestra\Testbench\TestCase;
use samibettayeb\TwitterApi\Facades\TwitterApi;
use samibettayeb\TwitterApi\ServiceProvider;

class TwitterApiTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'twitter-api' => TwitterApi::class,
        ];
    }

    public function testExample()
    {
        $this->assertEquals(1, 1);
    }
}
