<?php

namespace Anil\FastApiCrud\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeAllCommand extends Command
{
    protected $signature = 'fast-api:make-all {name : Comma-separated model name(s), e.g. Post or Post,Tag,User}';

    protected $description = 'Generate model, migration, factory, seeder, controller, resource, requests, and test for one or more models';

    public function handle(): void
    {
        $name = $this->argument('name');
        if (empty($name) || ! is_string($name)) {
            $this->error('Please provide a valid model name.');

            return;
        }

        $models = array_filter(array_map('trim', explode(',', $name)));

        foreach ($models as $modelName) {
            $this->info("Generating scaffold for: {$modelName}");
            $table = Str::snake(Str::pluralStudly(class_basename($modelName)));

            $this->call('make:model', ['name' => $modelName]);
            $this->call('make:migration', ['name' => "create_{$table}_table", '--create' => $table]);
            $this->call('make:factory', ['name' => "{$modelName}Factory"]);
            $this->call('make:seeder', ['name' => "{$modelName}Seeder"]);
            $this->call('make:controller', ['name' => "{$modelName}Controller", '--model' => $modelName]);
            $this->call('make:resource', ['name' => "{$modelName}/{$modelName}Resource"]);
            $this->call('make:request', ['name' => "{$modelName}/Store{$modelName}Request"]);
            $this->call('make:request', ['name' => "{$modelName}/Update{$modelName}Request"]);
        }
    }
}
