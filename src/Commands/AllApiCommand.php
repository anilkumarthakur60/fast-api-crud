<?php

namespace Anil\FastApiCrud\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AllApiCommand extends Command
{
    protected $signature = 'multiple {name}';

    protected $description = 'Create a new model with migration, factory, seeder, controller, resource, request and test';

    public function handle(): void
    {
        $name = $this->argument('name');
        if (empty($name) || ! is_string($name)) {
            $this->error('Please provide a valid name for the model.');

            return;
        }
        $models = explode(',', $name);

        foreach ($models as $name) {
            $this->call('make:model', ['name' => $name]);
            $table = Str::snake(Str::pluralStudly(class_basename($name)));
            $this->call('make:migration', ['name' => "create_{$table}_table", '--create' => $table]);
            $this->call('make:factory', ['name' => "{$name}Factory"]);
            $this->call('make:seeder', ['name' => "{$name}Seeder"]);
            $this->call('make:controller', ['name' => "{$name}Controller", '--model' => $name]);
            $this->call('make:resource', ['name' => "{$name}/{$name}Resource"]);
            $this->call('make:request', ['name' => "{$name}/Store{$name}Request"]);
            $this->call('make:request', ['name' => "{$name}/Update{$name}Request"]);
            $this->call('make:test', ['name' => "{$name}/{$name}Test"]);
        }
    }
}
