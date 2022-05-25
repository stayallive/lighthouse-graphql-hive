<?php

namespace Stayallive\Lighthouse\GraphQLHive\Collector;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;

class InternalHiveCollectDirective extends BaseDirective implements FieldMiddleware
{
    public function __construct(
        private Collector $collector
    ) {
    }

    public function handleField(FieldValue $fieldValue, Closure $next): FieldValue
    {
        $resolver = $fieldValue->getResolver();

        $fieldValue->setResolver(function ($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo) use ($resolver) {
            $this->collector->collect($resolveInfo);

            return $resolver($root, $args, $context, $resolveInfo);
        });

        return $next($fieldValue);
    }

    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'GRAPHQL'
        """
        Do not use this directive directly, it is automatically added to the schema when using Hive usage tracking.
        """
        directive @internalHiveCollect on FIELD_DEFINITION
        GRAPHQL;
    }
}
