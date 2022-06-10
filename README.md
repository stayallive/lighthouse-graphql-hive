# Lighthouse GraphQL Hive

[![Latest Version](https://img.shields.io/github/release/stayallive/lighthouse-graphql-hive.svg?style=flat-square)](https://github.com/stayallive/lighthouse-graphql-hive/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/github/workflow/status/stayallive/lighthouse-graphql-hive/CI/master.svg?style=flat-square)](https://github.com/stayallive/lighthouse-graphql-hive/actions/workflows/ci.yaml)
[![Total Downloads](https://img.shields.io/packagist/dt/stayallive/lighthouse-graphql-hive.svg?style=flat-square)](https://packagist.org/packages/stayallive/lighthouse-graphql-hive)

[GraphQL Hive](https://graphql-hive.com/) can measure and collect data against all your [GraphQL](https://graphql.org/) operations and generate analytics on them.

This package aims to integrate [GraphQL Hive](https://graphql-hive.com/) with [Lighthouse](https://lighthouse-php.com/), a [GraphQL](https://graphql.org/) server
for [Laravel](https://laravel.com/).

## Installation

```bash
composer require stayallive/lighthouse-graphql-hive
```

Add the following snippet to your `config/services.php` file:

```php
    'graphqlhive' => [
        'enabled'   => env('GRAPHQL_HIVE_ENABLED', false),
        'token'     => env('GRAPHQL_HIVE_TOKEN'),
        'submitter' => env('GRAPHQL_HIVE_SUBMITTER'),
        'queue'     => env('GRAPHQL_HIVE_QUEUE'),
    ],
```

In your `.env` configure at least the following:

```dotenv
GRAPHQL_HIVE_ENABLED=true
GRAPHQL_HIVE_TOKEN=<your GraphQL Hive token>
```

_Read more about how to obtain a GraphQL Hive token [here](https://docs.graphql-hive.com/features/tokens)._

## Submitters

A submitter is the code that runs to aggregate and/or send the operation data collected to the [GraphQL Hive](https://graphql-hive.com/) API.

There are 2 submitters currently supported, we default to using the Queue submitter if none is configured:

### Queue (default)

```dotenv
GRAPHQL_HIVE_SUBMITTER=Stayallive\Lighthouse\GraphQLHive\Submitter\Queue\Submitter
```

This submitter will push every operation to a queued job, the job will send every operation to [GraphQL Hive](https://graphql-hive.com/).

You can configure the queue used to dispatch the jobs too by setting the `GRAPHQL_HIVE_QUEUE` environment variable to the name of the queue.

_Note: On high volumes this might cause issues with many queued jobs being pushed and being rate limited by the GraphQL Hive server._

### Redis

```dotenv
GRAPHQL_HIVE_SUBMITTER=Stayallive\Lighthouse\GraphQLHive\Submitter\Redis\Submitter
```

This submitter will push every operation to a Redis list, a scheduled command will run every minut to batch send the operation to [GraphQL Hive](https://graphql-hive.com/).

_Note: This submitter requires a configured Redis connection._

## Usage

You should only have to install this package to benefit, unless you have disabled package auto discovery, in that case you will need to add the service provider to
your `config/app.php` manually.

You can optionally add the `graphql-client` header to your requests in the format `name:version` (like: `my-app:1.2.3`) to see client stats in
the [GraphQL Hive](https://graphql-hive.com/) dashboard. You can also opt to set `x-graphql-client-name` and `x-graphql-client-version` headers instead.

## Security Vulnerabilities

If you discover a security vulnerability within this package, please send an e-mail to Alex Bouma at `alex+security@bouma.me`. All security vulnerabilities will be swiftly
addressed.

## License

This package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
