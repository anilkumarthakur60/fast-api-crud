<?php

declare(strict_types=1);

namespace Anil\FastApiCrud;

use Anil\FastApiCrud\Commands\MakeAllCommand;
use Anil\FastApiCrud\Macros\BuilderMacros;
use Anil\FastApiCrud\Macros\CollectionMacros;
use Illuminate\Support\ServiceProvider;

class FastApiCrudServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/fast-api.php' => config_path('fast-api.php'),
        ], 'config');

        BuilderMacros::register();
        CollectionMacros::register();

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeAllCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/fast-api.php', 'fast-api');
    }
}
