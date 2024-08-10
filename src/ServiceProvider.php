<?php

namespace Stayallive\Lighthouse\GraphQLHive;

use GraphQL\Language\Parser;
use Nuwave\Lighthouse\Events\EndRequest;
use Nuwave\Lighthouse\Events\EndExecution;
use Nuwave\Lighthouse\Events\StartRequest;
use Illuminate\Console\Scheduling\Schedule;
use Nuwave\Lighthouse\Events\ManipulateAST;
use Nuwave\Lighthouse\Schema\AST\ASTHelper;
use Nuwave\Lighthouse\Events\StartExecution;
use Nuwave\Lighthouse\Events\RegisterDirectiveNamespaces;
use Stayallive\Lighthouse\GraphQLHive\Collector\Collector;
use Stayallive\Lighthouse\GraphQLHive\Submitter\Submitter;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Stayallive\Lighthouse\GraphQLHive\Submitter\Queue\Submitter as QueueSubmitter;
use Stayallive\Lighthouse\GraphQLHive\Submitter\Redis\Submitter as RedisSubmitter;
use Stayallive\Lighthouse\GraphQLHive\Submitter\Redis\Command as RedisSubmitterCommand;

class ServiceProvider extends LaravelServiceProvider
{
    public function boot(EventsDispatcher $eventsDispatcher): void
    {
        $this->commands([
            RedisSubmitterCommand::class,
        ]);

        if (!self::enabled()) {
            return;
        }

        if (self::driver() === RedisSubmitter::class) {
            $this->app->afterResolving(Schedule::class, static function (Schedule $schedule) {
                $scheduled = $schedule->command(RedisSubmitterCommand::class)
                                      ->everyMinute()
                                      ->onOneServer()->runInBackground()->withoutOverlapping();

                $logFilePath = config('logging.scheduled_commands_file');

                if ($logFilePath !== null) {
                    $scheduled->appendOutputTo($logFilePath);
                }
            });
        }

        $eventsDispatcher->listen(RegisterDirectiveNamespaces::class, static fn () => __NAMESPACE__ . '\\Collector\\GraphQL');

        $eventsDispatcher->listen(ManipulateAST::class, static fn (ManipulateAST $event) => self::handleManipulateAST($event));

        $eventsDispatcher->listen(StartRequest::class, [Collector::class, 'handleStartRequest']);
        $eventsDispatcher->listen(StartExecution::class, [Collector::class, 'handleStartExecution']);
        $eventsDispatcher->listen(EndExecution::class, [Collector::class, 'handleEndExecution']);
        $eventsDispatcher->listen(EndRequest::class, [Collector::class, 'handleEndRequest']);
    }

    public function register(): void
    {
        $this->app->singleton(Client::class, static fn (): Client => new Client(config('services.graphqlhive.token')));
        $this->app->singleton(Submitter::class, fn (): Submitter => $this->app->make(self::driver()));
        $this->app->singleton(Collector::class);
    }

    public static function handleManipulateAST(ManipulateAST $manipulateAST): void
    {
        static $tracingDirective = null;

        if ($tracingDirective === null) {
            $tracingDirective = Parser::constDirective('@internalHiveCollect');
        }

        ASTHelper::attachDirectiveToObjectTypeFields($manipulateAST->documentAST, $tracingDirective);
    }

    public static function driver(): string
    {
        return config('services.graphqlhive.submitter') ?? QueueSubmitter::class;
    }

    public static function enabled(): bool
    {
        return config('services.graphqlhive.enabled') === true && !empty(config('services.graphqlhive.token'));
    }
}
