<?php

namespace Stayallive\Lighthouse\GraphQLHive\Submitter\Redis;

use Illuminate\Redis\RedisManager;
use Stayallive\Lighthouse\GraphQLHive\Submitter\Submitter as SubmitterInterface;

class Submitter implements SubmitterInterface
{
    public function __construct(
        private RedisManager $redis
    ) {
    }

    public function submitUsage(array $usage): void
    {
        $this->redis->rPush('hive:usage', json_encode($usage));
    }
}
