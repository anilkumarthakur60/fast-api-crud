<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Macros;

use Illuminate\Routing\Router;
use Illuminate\Support\Str;

/**
 * Registers Router macros provided by the package.
 */
final class RouteMacros
{
    /**
     * The full set of routes a fast-api resource exposes.
     *
     * Each entry: action => [httpMethods, uriSuffix].
     * Collection-level routes are listed before the {id} routes so a bare
     * "{id}" segment can never shadow a static sibling segment.
     *
     * @var array<string, array{0: array<int, string>, 1: string}>
     */
    private const ROUTES = [
        'index'           => [['GET'], ''],
        'store'           => [['POST'], ''],
        'delete'          => [['DELETE'], ''],
        'restoreAll'      => [['POST'], '/restore'],
        'updateColumn'    => [['PATCH'], '/{id}/status/{column}'],
        'changeStatus'    => [['PATCH'], '/{id}/status'],
        'restore'         => [['PATCH'], '/{id}/restore'],
        'permanentDelete' => [['DELETE'], '/{id}/force'],
        'show'            => [['GET'], '/{id}'],
        'update'          => [['PUT', 'PATCH'], '/{id}'],
        'destroy'         => [['DELETE'], '/{id}'],
    ];

    public static function register(): void
    {
        /**
         * Register the full CRUD route set for a BaseController/BaseWebController.
         *
         * Usage:
         *   Route::fastApiResource('posts', PostController::class);
         *   Route::fastApiResource('posts', PostController::class, ['only' => ['index', 'show']]);
         *   Route::fastApiResource('posts', PostController::class, ['except' => ['delete', 'restoreAll']]);
         *
         * Options:
         *   - only:      array<int, string> restrict to these actions
         *   - except:    array<int, string> register all but these actions
         *   - parameter: string the route parameter name (default "id")
         *   - names:     string route-name prefix (default: dotted resource name)
         *
         * @param array{only?: array<int, string>, except?: array<int, string>, parameter?: string, names?: string} $options
         */
        Router::macro('fastApiResource', function (string $name, string $controller, array $options = []): void {
            /** @var Router $this */
            $parameter = is_string($options['parameter'] ?? null) ? $options['parameter'] : 'id';
            $namePrefix = is_string($options['names'] ?? null)
                ? $options['names']
                : str_replace('/', '.', trim($name, '/'));

            $uri = trim($name, '/');

            foreach (array_keys(RouteMacros::resolveActions($options)) as $action) {
                [$methods, $suffix] = RouteMacros::routeDefinition($action);

                $suffix = str_replace('{id}', '{' . $parameter . '}', $suffix);

                $this->addRoute($methods, $uri . $suffix, [$controller, $action])
                    ->name("{$namePrefix}.{$action}");
            }
        });
    }

    /**
     * Resolve which actions to register, honouring only/except options.
     *
     * @param array<array-key, mixed> $options
     *
     * @return array<string, array{0: array<int, string>, 1: string}>
     */
    public static function resolveActions(array $options): array
    {
        $routes = self::ROUTES;

        if (isset($options['only']) && is_array($options['only'])) {
            $only = array_filter($options['only'], 'is_string');
            $routes = array_intersect_key($routes, array_flip($only));
        }

        if (isset($options['except']) && is_array($options['except'])) {
            $except = array_filter($options['except'], 'is_string');
            $routes = array_diff_key($routes, array_flip($except));
        }

        return $routes;
    }

    /**
     * Get the HTTP methods and URI suffix for a single action.
     *
     * @return array{0: array<int, string>, 1: string}
     */
    public static function routeDefinition(string $action): array
    {
        return self::ROUTES[$action] ?? [['GET'], '/' . Str::kebab($action)];
    }
}
