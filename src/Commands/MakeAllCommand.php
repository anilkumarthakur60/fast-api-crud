<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeAllCommand extends Command
{
    protected $signature = 'fast-api:make-all
        {name : Comma-separated model name(s), e.g. Post or Post,Tag,User}
        {--web : Generate a web controller extending BaseWebController instead of BaseController}';

    protected $description = 'Generate model, migration, factory, seeder, controller (extending BaseController or BaseWebController), resource, and requests for one or more models';

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
        $this->generateController($modelName);

        if ($this->option('web')) {
            $this->generateBladeViews($modelName);
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

        $stub = $this->option('web')
            ? $this->buildWebControllerStub($modelName)
            : $this->buildControllerStub($modelName);

        file_put_contents($controllerPath, $stub);

        $this->info("Controller created: {$controllerPath}");
    }

    private function buildControllerStub(string $modelName): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\Http\\Controllers;

        use Anil\\FastApiCrud\\Http\\Controllers\\BaseController;
        use App\\Http\\Requests\\{$modelName}\\Store{$modelName}Request;
        use App\\Http\\Requests\\{$modelName}\\Update{$modelName}Request;
        use App\\Http\\Resources\\{$modelName}\\{$modelName}Resource;
        use App\\Models\\{$modelName};

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

    private function buildWebControllerStub(string $modelName): string
    {
        $slug = Str::kebab(Str::pluralStudly(class_basename($modelName)));
        $resourceName = Str::camel(class_basename($modelName));
        $collectionName = Str::camel(Str::pluralStudly(class_basename($modelName)));

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\Http\\Controllers;

        use Anil\\FastApiCrud\\Http\\Controllers\\BaseWebController;
        use App\\Http\\Requests\\{$modelName}\\Store{$modelName}Request;
        use App\\Http\\Requests\\{$modelName}\\Update{$modelName}Request;
        use App\\Models\\{$modelName};

        class {$modelName}Controller extends BaseWebController
        {
            public function __construct()
            {
                parent::__construct(
                    model: {$modelName}::class,
                    storeRequest: Store{$modelName}Request::class,
                    updateRequest: Update{$modelName}Request::class,
                    viewPrefix: '{$slug}',
                    routePrefix: '{$slug}',
                    resourceName: '{$resourceName}',
                    collectionName: '{$collectionName}',
                );
            }
        }

        PHP;
    }

    private function generateBladeViews(string $modelName): void
    {
        $slug = Str::kebab(Str::pluralStudly(class_basename($modelName)));
        $resourceName = Str::camel(class_basename($modelName));
        $collectionName = Str::camel(Str::pluralStudly(class_basename($modelName)));
        $viewDir = resource_path("views/{$slug}");

        if (! is_dir($viewDir)) {
            mkdir($viewDir, 0755, true);
        }

        $views = [
            'index'  => $this->buildIndexViewStub($modelName, $collectionName, $slug),
            'create' => $this->buildCreateViewStub($modelName, $slug),
            'edit'   => $this->buildEditViewStub($modelName, $resourceName, $slug),
            'show'   => $this->buildShowViewStub($modelName, $resourceName, $slug),
        ];

        foreach ($views as $name => $content) {
            $viewPath = "{$viewDir}/{$name}.blade.php";

            if (file_exists($viewPath)) {
                $this->warn("View already exists: {$viewPath}");

                continue;
            }

            file_put_contents($viewPath, $content);
            $this->info("View created: {$viewPath}");
        }
    }

    private function buildIndexViewStub(string $modelName, string $collectionName, string $slug): string
    {
        return <<<BLADE
        @extends('layouts.app')

        @section('content')
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1>{$modelName} List</h1>
                <a href="{{ route('{$slug}.create') }}" class="btn btn-primary">Create {$modelName}</a>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(\${$collectionName} as \$item)
                        <tr>
                            <td>{{ \$item->id }}</td>
                            <td>
                                <a href="{{ route('{$slug}.show', \$item) }}">View</a>
                                <a href="{{ route('{$slug}.edit', \$item) }}">Edit</a>
                                <form action="{{ route('{$slug}.destroy', \$item) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ \${$collectionName}->links() }}
        </div>
        @endsection
        BLADE;
    }

    private function buildCreateViewStub(string $modelName, string $slug): string
    {
        return <<<BLADE
        @extends('layouts.app')

        @section('content')
        <div class="container">
            <h1>Create {$modelName}</h1>

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form action="{{ route('{$slug}.store') }}" method="POST">
                @csrf

                {{-- Add your form fields here --}}

                <button type="submit" class="btn btn-primary">Create</button>
                <a href="{{ route('{$slug}.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
        @endsection
        BLADE;
    }

    private function buildEditViewStub(string $modelName, string $resourceName, string $slug): string
    {
        return <<<BLADE
        @extends('layouts.app')

        @section('content')
        <div class="container">
            <h1>Edit {$modelName}</h1>

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form action="{{ route('{$slug}.update', \${$resourceName}) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Add your form fields here --}}

                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('{$slug}.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
        @endsection
        BLADE;
    }

    private function buildShowViewStub(string $modelName, string $resourceName, string $slug): string
    {
        return <<<BLADE
        @extends('layouts.app')

        @section('content')
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1>{$modelName} Details</h1>
                <div>
                    <a href="{{ route('{$slug}.edit', \${$resourceName}) }}" class="btn btn-primary">Edit</a>
                    <a href="{{ route('{$slug}.index') }}" class="btn btn-secondary">Back</a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <p><strong>ID:</strong> {{ \${$resourceName}->id }}</p>

                    {{-- Add more fields here --}}
                </div>
            </div>
        </div>
        @endsection
        BLADE;
    }
}
