<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Mcp;

use Anil\FastApiCrud\Contracts\HasPermissionSlug;
use Anil\FastApiCrud\Contracts\Searchable;
use Anil\FastApiCrud\Contracts\Sortable;
use Anil\FastApiCrud\Http\Controllers\BaseController;
use Anil\FastApiCrud\Http\Controllers\BaseWebController;
use BackedEnum;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\Finder\Finder;
use Throwable;

/**
 * Read-only introspection of a host application's fast-api-crud usage.
 *
 * Powers the MCP tools: discovers controllers extending BaseController /
 * BaseWebController, reads their configuration properties, maps the routes
 * that point at them, and summarises the models they manage.
 */
final class Inspector
{
    /** @var list<string> */
    public const HOOKS = [
        'beforeCreate', 'afterCreate',
        'beforeUpdate', 'afterUpdate',
        'beforeDelete', 'afterDelete',
        'beforeStatusChange', 'afterStatusChange',
        'beforeColumnUpdate', 'afterColumnUpdate',
        'beforeRestore', 'afterRestore',
        'beforeForceDelete', 'afterForceDelete',
    ];

    /** @var list<string> */
    private const CONTROLLER_PROPERTIES = [
        'paginationType', 'scopes', 'with', 'withCount', 'withAggregate',
        'loadScopes', 'load', 'loadCount', 'loadAggregate',
        'allowedIncludes', 'allowTrashedFilter', 'updatableColumns', 'forceDelete',
        'updateScopes', 'deleteScopes', 'columnScopes', 'restoreScopes',
    ];

    public function __construct(
        private readonly Application $app,
        private readonly Router $router,
    ) {}

    /**
     * Discover every controller class under app/Http/Controllers that extends
     * BaseController or BaseWebController.
     *
     * @return list<class-string>
     */
    public function controllerClasses(): array
    {
        $directory = $this->app->basePath('app/Http/Controllers');

        if (! is_dir($directory)) {
            return [];
        }

        $namespace = $this->app->getNamespace();
        $classes = [];

        foreach (Finder::create()->files()->in($directory)->name('*.php') as $file) {
            $relative = Str::of($file->getRelativePathname())
                ->beforeLast('.php')
                ->replace(['/', '\\'], '\\')
                ->toString();

            $class = $namespace . 'Http\Controllers\\' . $relative;

            if (! class_exists($class)) {
                continue;
            }

            if (self::isCrudController($class)) {
                $classes[] = $class;
            }
        }

        sort($classes);

        return $classes;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listControllers(): array
    {
        return array_map(fn (string $class): array => $this->summariseController($class), $this->controllerClasses());
    }

    /**
     * @param class-string $class
     *
     * @return array<string, mixed>
     */
    public function summariseController(string $class): array
    {
        $bindings = $this->constructorBindings($class);

        return [
            'class'    => $class,
            'kind'     => is_subclass_of($class, BaseWebController::class) ? 'web' : 'api',
            'model'    => $bindings['model'] ?? null,
            'resource' => $bindings['resource'] ?? null,
            'routes'   => count($this->routesFor($class)),
        ];
    }

    /**
     * @param class-string $class
     *
     * @return array<string, mixed>
     */
    public function describeController(string $class): array
    {
        if (! class_exists($class) || ! self::isCrudController($class)) {
            throw new InvalidArgumentException("{$class} is not a fast-api-crud controller (must extend BaseController or BaseWebController).");
        }

        $reflection = new ReflectionClass($class);
        $defaults = $reflection->getDefaultProperties();
        $properties = [];

        foreach (self::CONTROLLER_PROPERTIES as $property) {
            if (array_key_exists($property, $defaults)) {
                $properties[$property] = self::exportValue($defaults[$property]);
            }
        }

        $overridden = [];
        foreach ($reflection->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() === $class && ! $method->isConstructor()) {
                $overridden[] = $method->getName();
            }
        }

        $bindings = $this->constructorBindings($class);
        $modelClass = $bindings['model'] ?? null;

        return [
            'class'                 => $class,
            'kind'                  => is_subclass_of($class, BaseWebController::class) ? 'web' : 'api',
            'file'                  => $reflection->getFileName(),
            'bindings'              => $bindings,
            'properties'            => $properties,
            'overriddenMethods'     => $overridden,
            'controllerHooks'       => array_values(array_intersect($overridden, self::HOOKS)),
            'hasMiddlewareOverride' => in_array('middleware', $overridden, true),
            'routes'                => $this->routesFor($class),
            'model'                 => is_string($modelClass) && class_exists($modelClass) ? $this->describeModel($modelClass) : null,
        ];
    }

    /**
     * Resolve model / request / resource classes by instantiating the controller
     * through the container and reading the readonly properties set by the
     * base constructor. Falls back to an empty array if the controller cannot
     * be built (e.g. a missing model class).
     *
     * @param class-string $class
     *
     * @return array<string, string|null>
     */
    public function constructorBindings(string $class): array
    {
        try {
            $instance = $this->app->make($class);
        } catch (Throwable) {
            return [];
        }

        if (! is_object($instance)) {
            return [];
        }

        $reflection = new ReflectionClass($instance);
        $bindings = [];

        foreach (['model', 'storeRequest', 'updateRequest', 'resource', 'viewPrefix', 'routePrefix', 'resourceName', 'collectionName'] as $name) {
            if (! $reflection->hasProperty($name)) {
                continue;
            }

            $property = $reflection->getProperty($name);

            if (! $property->isInitialized($instance)) {
                continue;
            }

            $value = $property->getValue($instance);

            $bindings[$name] = match (true) {
                $value instanceof Model => $value::class,
                is_string($value)       => $value,
                default                 => null,
            };
        }

        return $bindings;
    }

    /**
     * Routes whose controller is the given class (or any fast-api controller when null).
     *
     * @return list<array{methods: list<string>, uri: string, name: string|null, action: string, controller: string, middleware: list<string>}>
     */
    public function routesFor(?string $class = null): array
    {
        $routes = [];

        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            $controller = $this->routeController($route);

            if ($controller === null) {
                continue;
            }

            if ($class !== null ? $controller !== $class : ! self::isCrudController($controller)) {
                continue;
            }

            $methods = [];
            foreach ($route->methods() as $method) {
                if (is_string($method) && $method !== 'HEAD') {
                    $methods[] = $method;
                }
            }

            $middleware = [];
            foreach ($route->gatherMiddleware() as $item) {
                $middleware[] = is_string($item) ? $item : 'Closure';
            }

            $routes[] = [
                'methods'    => $methods,
                'uri'        => $route->uri(),
                'name'       => $route->getName(),
                'action'     => $route->getActionMethod(),
                'controller' => $controller,
                'middleware' => $middleware,
            ];
        }

        return $routes;
    }

    /**
     * @return array<string, mixed>
     */
    public function describeModel(string $class): array
    {
        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            throw new InvalidArgumentException("{$class} is not an Eloquent model.");
        }

        $model = new $class;
        $reflection = new ReflectionClass($class);

        $scopes = [];
        $hooks = [];
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();

            if (str_starts_with($name, 'scope') && strlen($name) > 5) {
                $scopes[] = Str::camel(substr($name, 5));
            }

            if (in_array($name, self::HOOKS, true) && $method->getDeclaringClass()->getName() === $class) {
                $hooks[] = $name;
            }
        }

        // Laravel 12+ attribute-based scopes (#[Scope]).
        foreach ($reflection->getMethods() as $method) {
            foreach ($method->getAttributes() as $attribute) {
                if (str_ends_with($attribute->getName(), '\Scope')) {
                    $scopes[] = $method->getName();
                }
            }
        }

        $scopes = array_values(array_unique($scopes));
        sort($scopes);

        $columns = [];

        try {
            $columns = Schema::connection($model->getConnectionName())->getColumnListing($model->getTable());
        } catch (Throwable) {
            // No database available — columns stay empty.
        }

        $traits = array_values(class_uses_recursive($class));

        return [
            'class'          => $class,
            'table'          => $model->getTable(),
            'primaryKey'     => $model->getKeyName(),
            'keyType'        => $model->getKeyType(),
            'fillable'       => $model->getFillable(),
            'guarded'        => $model->getGuarded(),
            'casts'          => $model->getCasts(),
            'columns'        => $columns,
            'softDeletes'    => in_array(SoftDeletes::class, $traits, true),
            'searchable'     => $model instanceof Searchable ? $model->searchableColumns() : null,
            'sortDefaults'   => $model instanceof Sortable ? $model->sortByDefaults() : null,
            'permissionSlug' => $model instanceof HasPermissionSlug ? $model->getPermissionSlug() : null,
            'scopes'         => $scopes,
            'hooks'          => $hooks,
            'relations'      => $this->relationMethods($reflection),
            'traits'         => $traits,
        ];
    }

    public static function isCrudController(string $class): bool
    {
        return is_subclass_of($class, BaseController::class) || is_subclass_of($class, BaseWebController::class);
    }

    /**
     * Public methods whose declared return type is an Eloquent Relation.
     *
     * @param ReflectionClass<Model> $reflection
     *
     * @return list<string>
     */
    private function relationMethods(ReflectionClass $reflection): array
    {
        $relations = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $type = $method->getReturnType();

            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $typeName = $type->getName();

                if (class_exists($typeName) && is_subclass_of($typeName, \Illuminate\Database\Eloquent\Relations\Relation::class)) {
                    $relations[] = $method->getName();
                }
            }
        }

        return $relations;
    }

    /**
     * @return class-string|null
     */
    private function routeController(Route $route): ?string
    {
        $action = $route->getAction('controller');

        if (! is_string($action)) {
            return null;
        }

        $class = Str::before($action, '@');

        return class_exists($class) ? $class : null;
    }

    /**
     * Convert a property default into something JSON-serialisable.
     */
    private static function exportValue(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof Closure) {
            return '{closure}';
        }

        if (is_array($value)) {
            return array_map(self::exportValue(...), $value);
        }

        return $value;
    }
}
