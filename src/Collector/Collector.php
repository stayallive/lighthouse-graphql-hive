<?php

namespace Stayallive\Lighthouse\GraphQLHive\Collector;

use Illuminate\Support\Collection;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Events\EndExecution;
use Nuwave\Lighthouse\Events\StartRequest;
use Nuwave\Lighthouse\Events\StartExecution;
use GraphQL\Language\AST\OperationDefinitionNode;
use Stayallive\Lighthouse\GraphQLHive\ServiceProvider;
use Stayallive\Lighthouse\GraphQLHive\Submitter\Submitter;

class Collector
{
    private array $executions = [];

    private ?string $clientName    = null;
    private ?string $clientVersion = null;

    private ?OperationDefinitionNode $currentExecution = null;
    private float|int                $currentExecutionStart;
    private array                    $currentExecutionFields;

    public function __construct(
        private Submitter $submitter,
    ) {}

    public function handleStartRequest(StartRequest $startRequest): void
    {
        $request = $startRequest->request;

        $clientHeader = $request->header('graphql-client');

        if (!empty($clientHeader)) {
            [$this->clientName, $this->clientVersion] = explode(':', "{$clientHeader}:");
        } else {
            $this->clientName    = $request->header('x-graphql-client-name');
            $this->clientVersion = $request->header('x-graphql-client-version');
        }
    }

    public function handleStartExecution(StartExecution $startExecution): void
    {
        /** @var \GraphQL\Language\AST\OperationDefinitionNode|null $operationDefinition */
        $operationDefinition = $startExecution->query->definitions[0] ?? null;

        if (!$operationDefinition instanceof OperationDefinitionNode) {
            return;
        }

        if ($operationDefinition->loc->source === null) {
            return;
        }

        $this->currentExecution       = $operationDefinition;
        $this->currentExecutionStart  = $this->currentTime();
        $this->currentExecutionFields = [];
    }

    public function collect(ResolveInfo $resolveInfo): void
    {
        $this->currentExecutionFields[] = [
            'type'  => $resolveInfo->parentType->name,
            'field' => $resolveInfo->fieldName,
        ];
    }

    public function handleEndExecution(EndExecution $endExecution): void
    {
        if ($this->currentExecution === null) {
            return;
        }

        $errorCount = count($endExecution->result->errors);

        $this->executions[] = [
            'timestamp'     => $endExecution->moment->getTimestampMs(),
            'operation'     => $this->currentExecution->loc->source->body,
            'operationName' => $this->currentExecution->name?->value,
            'fields'        => $this->collectFields(),
            'execution'     => [
                'ok'          => $errorCount === 0,
                'duration'    => $this->diffTime($this->currentExecutionStart),
                'errorsTotal' => $errorCount,
            ],
            'metadata'      => [
                'client' => $this->clientName && $this->clientVersion ? [
                    'name'    => $this->clientName,
                    'version' => $this->clientVersion,
                ] : null,
            ],
        ];
    }

    public function handleEndRequest(): void
    {
        if (ServiceProvider::enabled()) {
            foreach ($this->executions as $execution) {
                $this->submitter->submitUsage($execution);
            }
        }

        $this->executions = [];
    }

    private function diffTime(float|int $start): int
    {
        $end = $this->currentTime();

        if ($this->platformSupportsNanoseconds()) {
            return (int)($end - $start);
        }

        // Difference is in seconds (with microsecond precision)
        // * 1000 to get to milliseconds
        // * 1000 to get to microseconds
        // * 1000 to get to nanoseconds
        return (int)(($end - $start) * 1000 * 1000 * 1000);
    }

    private function currentTime(): float|int
    {
        return $this->platformSupportsNanoseconds()
            ? hrtime(true)
            : microtime(true);
    }

    private function collectFields(): array
    {
        return collect($this->currentExecutionFields)
            ->groupBy('type')
            ->map
            ->pluck('field')
            ->flatMap(static fn (Collection $fields, string $type) => $fields->map(static fn (string $field) => "{$type}.{$field}"))
            ->unique()
            ->values()
            ->all();
    }

    private function platformSupportsNanoseconds(): bool
    {
        return function_exists('hrtime');
    }
}
