<?php

namespace Stayallive\Lighthouse\GraphQLHive;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function boot(Dispatcher $events): void
    {
        // ...
    }

    public function register(): void
    {
        // ...
    }
}
