<?php

declare(strict_types=1);

namespace Anil\FastApiCrud;

use Anil\FastApiCrud\Commands\InstallAiCommand;
use Anil\FastApiCrud\Commands\MakeAllCommand;
use Anil\FastApiCrud\Commands\McpServerCommand;
use Anil\FastApiCrud\Macros\BuilderMacros;
use Anil\FastApiCrud\Macros\CollectionMacros;
use Anil\FastApiCrud\Macros\RouteMacros;
use Anil\FastApiCrud\Mcp\Inspector;
use Anil\FastApiCrud\Mcp\McpServer;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class FastApiCrudServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/fast-api.php' => config_path('fast-api.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../resources/ai/skills/fast-api-crud'    => $this->app->basePath('.claude/skills/fast-api-crud'),
            __DIR__ . '/../resources/ai/agents/fast-api-crud.md' => $this->app->basePath('.claude/agents/fast-api-crud.md'),
        ], 'ai');

        BuilderMacros::register();
        CollectionMacros::register();
        RouteMacros::register();

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeAllCommand::class,
                McpServerCommand::class,
                InstallAiCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/fast-api.php', 'fast-api');

        $this->app->singleton(Inspector::class, fn (Application $app): Inspector => new Inspector(
            $app,
            $app->make(Router::class),
        ));

        $this->app->singleton(McpServer::class, fn (Application $app): McpServer => new McpServer(
            $app->make(Inspector::class),
            $app->make('config'),
            $app->make(Kernel::class),
            dirname(__DIR__) . '/resources/ai',
        ));
    }
}
