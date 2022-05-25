<?php

namespace Stayallive\Lighthouse\GraphQLHive\Submitter\Redis;

use Throwable;
use Illuminate\Redis\RedisManager;
use Stayallive\Lighthouse\GraphQLHive\Client;
use Illuminate\Console\Command as LaravelCommand;
use Stayallive\Lighthouse\GraphQLHive\ServiceProvider;

class Command extends LaravelCommand
{
    protected $hidden      = true;
    protected $signature   = 'hive:submitter:redis';
    protected $description = 'Submit queued usages stored in Redis to Hive.';

    public function handle(RedisManager $redis, Client $client): void
    {
        if (!ServiceProvider::enabled() || ServiceProvider::driver() !== Submitter::class) {
            return;
        }

        $iterations = 0;

        while (true) {
            if ($iterations > 10) {
                break;
            }

            $payload = $redis->rawCommand('lpop', 'hive:usage', 100);

            if ($payload === false || count($payload) === 0) {
                break;
            }

            try {
                $client->submitUsage(array_map(static fn ($item) => json_decode($item, true), $payload));
            } catch (Throwable $e) {
                report($e);

                break;
            }

            $iterations++;
        }
    }
}
