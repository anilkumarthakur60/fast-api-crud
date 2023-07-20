<?php

namespace Anil\FastApiCrud\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeAction extends GeneratorCommand
{
    const STUB_PATH = __DIR__.'/../../stubs/';

    protected $signature = 'make:action {name : Create an action class}';

    protected $description = 'Create a new action class';

    protected $type = 'Action';

    protected function getStub(): string
    {
        return self::STUB_PATH.'action.stub';

    }

    public function handle()
    {
        if ($this->isReservedName($this->getNameInput())) {
            $this->error('The name "'.$this->getNameInput().'" is reserved by PHP.');

            return false;
        }

        $name = $this->qualifyClass($this->getNameInput());

        $path = $this->getPath($name);

        if ((! $this->hasOption('force') ||
                ! $this->option('force')) &&
            $this->alreadyExists($this->getNameInput())) {
            $this->error($this->type.' already exists!');

            return false;
        }

        $this->makeDirectory($path);

        $this->files->put(
            $path,
            $this->sortImports(
                $this->buildServiceClass($name)
            )
        );
        $message = $this->type;

        $this->info($message.' created successfully.');
    }

    protected function buildServiceClass(string $name): string
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Actions';
    }
}
