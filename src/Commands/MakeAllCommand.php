<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeAllCommand extends Command
{
    protected $signature = 'fast-api:make-all
        {name : Comma-separated model name(s), e.g. Post or Post,Tag,User}
        {--without-controller : Skip generating the BaseController-based controller}';

    protected $description = 'Generate model, migration, factory, seeder, controller (extending BaseController), resource, and requests for one or more models';

    public function handle(): void
    {
        $name = $this->argument('name');
        if (empty($name) || ! is_string($name)) {
            $this->error('Please provide a valid model name.');

            return;
        }

        $models = array_filter(array_map('trim', explode(',', $name)));

        foreach ($models as $modelName) {
            $this->generateScaffold($modelName);
        }
    }

    private function generateScaffold(string $modelName): void
    {
        $this->info("Generating scaffold for: {$modelName}");
        $table = Str::snake(Str::pluralStudly(class_basename($modelName)));

        $this->call('make:model', ['name' => $modelName]);
        $this->call('make:migration', ['name' => "create_{$table}_table", '--create' => $table]);
        $this->call('make:factory', ['name' => "{$modelName}Factory"]);
        $this->call('make:seeder', ['name' => "{$modelName}Seeder"]);
        $this->call('make:resource', ['name' => "{$modelName}/{$modelName}Resource"]);
        $this->call('make:request', ['name' => "{$modelName}/Store{$modelName}Request"]);
        $this->call('make:request', ['name' => "{$modelName}/Update{$modelName}Request"]);

        if (! $this->option('without-controller')) {
            $this->generateController($modelName);
        }

        $this->info("Scaffold for {$modelName} generated successfully.");
        $this->newLine();
    }

    private function generateController(string $modelName): void
    {
        $controllerPath = app_path("Http/Controllers/{$modelName}Controller.php");

        if (file_exists($controllerPath)) {
            $this->warn("Controller already exists: {$controllerPath}");

            return;
        }

        $directory = dirname($controllerPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $stub = $this->buildControllerStub($modelName);
        file_put_contents($controllerPath, $stub);

        $this->info("Controller created: {$controllerPath}");
    }

    private function buildControllerStub(string $modelName): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\Http\Controllers;

        use Anil\FastApiCrud\Http\Controllers\BaseController;
        use App\Http\Requests\\{$modelName}\Store{$modelName}Request;
        use App\Http\Requests\\{$modelName}\Update{$modelName}Request;
        use App\Http\Resources\\{$modelName}\\{$modelName}Resource;
        use App\Models\\{$modelName};

        class {$modelName}Controller extends BaseController
        {
            public function __construct()
            {
                parent::__construct(
                    model: {$modelName}::class,
                    storeRequest: Store{$modelName}Request::class,
                    updateRequest: Update{$modelName}Request::class,
                    resource: {$modelName}Resource::class,
                );
            }
        }

        PHP;
    }
}
