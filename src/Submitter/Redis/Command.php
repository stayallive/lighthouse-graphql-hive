<?php

namespace Stayallive\Lighthouse\GraphQLHive\Submitter\Redis;

use Throwable;
use Illuminate\Redis\RedisManager;
use Stayallive\Lighthouse\GraphQLHive\Client;
use Illuminate\Console\Command as LaravelCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Stayallive\Lighthouse\GraphQLHive\ServiceProvider;

class Command extends LaravelCommand
{
    private const REDIS_KEY = 'hive:usage';
    private const PAGE_SIZE = 5;

    protected $hidden      = true;
    protected $signature   = 'hive:submitter:redis';
    protected $description = 'Submit queued usages stored in Redis to Hive.';

    public function handle(RedisManager $redisManager, Client $client): void
    {
        if (!ServiceProvider::enabled() || ServiceProvider::driver() !== Submitter::class) {
            $this->warn('Tried to run Hive Redis Submitter but is disabled or incorrect driver... aborting!', OutputInterface::VERBOSITY_VERBOSE);

            return;
        }

        $this->info('Starting Hive Redis submitter...', OutputInterface::VERBOSITY_VERBOSE);

        $redis = $redisManager->client();

        $iterations = 0;

        while (true) {
            if ($iterations > 10) {
                break;
            }

            $payload = $redis->lRange(self::REDIS_KEY, 0, self::PAGE_SIZE - 1);

            $itemCount = count($payload);

            if ($itemCount === 0) {
                $this->info(' > no data left to submit!', OutputInterface::VERBOSITY_VERBOSE);

                break;
            }

            $this->info(" > submitting batch of {$itemCount} items to Hive...", OutputInterface::VERBOSITY_VERBOSE);

            $redis->lTrim(self::REDIS_KEY, self::PAGE_SIZE, -1);

            try {
                $client->submitUsage(array_map(static fn ($item) => json_decode($item, true), $payload));
            } catch (Throwable $e) {
                report($e);

                break;
            }

            $iterations++;
        }

        $this->info('Done!', OutputInterface::VERBOSITY_VERBOSE);
    }
}
