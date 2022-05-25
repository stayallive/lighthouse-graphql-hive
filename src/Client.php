<?php

namespace Stayallive\Lighthouse\GraphQLHive;

use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\ResponseInterface;

class Client
{
    private HttpClient $http;

    public function __construct(string $token, string $baseUrl = 'https://app.graphql-hive.com')
    {
        $this->http = new HttpClient([
            'base_uri'        => $baseUrl,
            'timeout'         => 10,
            'connect_timeout' => 10,
            'headers'         => [
                'User-Agent'  => 'graphql-hive-php/lighthouse',
                'X-API-Token' => $token,
            ],
        ]);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function submitUsage(array $usage): ResponseInterface
    {
        return $this->http->post('usage', [
            'json' => $usage,
        ]);
    }
}
