<?php

namespace Stayallive\Lighthouse\GraphQLHive\Submitter\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Stayallive\Lighthouse\GraphQLHive\Client;
use Stayallive\Lighthouse\GraphQLHive\ServiceProvider;

class Job implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private array $payload,
    ) {}

    public function handle(Client $client): void
    {
        if (!ServiceProvider::enabled() || ServiceProvider::driver() !== Submitter::class) {
            return;
        }

        rescue(fn () => $client->submitUsage($this->payload));
    }
}
