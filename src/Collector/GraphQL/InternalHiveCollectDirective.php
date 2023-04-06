<?php

namespace Stayallive\Lighthouse\GraphQLHive\Collector\GraphQL;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Stayallive\Lighthouse\GraphQLHive\Collector\Collector;

class InternalHiveCollectDirective extends BaseDirective implements FieldMiddleware
{
    public function __construct(
        private Collector $collector,
    ) {
    }

    public function handleField(FieldValue $fieldValue): void
    {
        $fieldValue->wrapResolver(fn (callable $resolver) => function ($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo) use ($resolver) {
            $this->collector->collect($resolveInfo);

            return $resolver($root, $args, $context, $resolveInfo);
        });
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
