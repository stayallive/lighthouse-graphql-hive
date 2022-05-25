<?php

namespace Tests;

use Orchestra\Testbench\TestCase as LaravelTestCase;
use Stayallive\Lighthouse\GraphQLHive\ServiceProvider;

class TestCase extends LaravelTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ServiceProvider::class,
        ];
    }
}
