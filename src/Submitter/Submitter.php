<?php

namespace Stayallive\Lighthouse\GraphQLHive\Submitter;

interface Submitter
{
    /**
     * Submits the usage to Hive.
     *
     * @param array $usage
     *
     * @return void
     */
    public function submitUsage(array $usage): void;
}
