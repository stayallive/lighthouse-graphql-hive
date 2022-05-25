<?php

namespace Stayallive\Lighthouse\GraphQLHive\Submitter\Queue;

use Stayallive\Lighthouse\GraphQLHive\Submitter\Submitter as SubmitterInterface;

class Submitter implements SubmitterInterface
{
    private array $buffer = [];

    private bool $terminateHooked = false;

    public function submitUsage(array $usage): void
    {
        $this->buffer[] = $usage;

        $this->dispatchWhenTerminating();
    }

    private function dispatchJobs(): void
    {
        (new Job($this->buffer))
            ->onQueue(config('services.graphqlhive.queue'));
    }

    private function dispatchWhenTerminating(): void
    {
        if ($this->terminateHooked) {
            return;
        }

        $this->terminateHooked = true;

        app()->terminating(fn () => $this->dispatchJobs());
    }
}
