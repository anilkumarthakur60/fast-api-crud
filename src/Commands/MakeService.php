<?php

namespace Anil\FastApiCrud\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeService extends GeneratorCommand
{
    const STUB_PATH = __DIR__.'/../../stubs/';

    protected $signature = 'make:service {name : Create a service class} {--i : Create a service interface}';

    protected $description = 'Create a new service class and optional interface';

    protected $type = 'Service';

    public function getStub(): string
    {
        return self::STUB_PATH.'service.stub';
    }

    public function getServiceInterfaceStub(): string
    {
        return self::STUB_PATH.'service.interface.stub.stub';
    }

    public function getInterfaceStub(): string
    {
        return self::STUB_PATH.'interface.stub';
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
        $hasInterface = $this->option('i');

        $this->files->put(
            $path,
            $this->sortImports(
                $this->buildServiceClass($name, $hasInterface)
            )
        );
        $message = $this->type;

        if ($hasInterface) {
            $interfaceName = $this->getNameInput().'Interface.php';
            $interfacePath = str_replace($this->getNameInput().'.php', 'Interfaces/', $path);

            $this->makeDirectory($interfacePath.$interfaceName);

            $this->files->put(
                $interfacePath.$interfaceName,
                $this->sortImports(
                    $this->buildServiceInterface($this->getNameInput())
                )
            );

            $message .= ' and Interface';
        }

        $this->info($message.' created successfully.');
    }

    public function buildServiceClass(string $name, $hasInterface): string
    {
        $stub = $this->files->get(
            $hasInterface ? $this->getServiceInterfaceStub() : $this->getStub()
        );

        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    public function buildServiceInterface(string $name): string
    {
        $stub = $this->files->get($this->getInterfaceStub());

        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    public function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Services';
    }
}
