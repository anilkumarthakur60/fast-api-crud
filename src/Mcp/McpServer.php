<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Mcp;

use Anil\FastApiCrud\Support\QueryParams;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;
use JsonException;
use stdClass;
use Throwable;

/**
 * Minimal, dependency-free Model Context Protocol server (stdio transport,
 * newline-delimited JSON-RPC 2.0).
 *
 * Exposes the host application's fast-api-crud setup to AI coding agents:
 *
 *   tools      — list_controllers, describe_controller, list_routes, model_info,
 *                get_config, query_reference, scaffold
 *   resources  — the bundled skill / agent guidance and the effective config
 *   prompts    — build-resource, debug-endpoint
 */
final class McpServer
{
    public const PROTOCOL_VERSION = '2025-06-18';

    public const NAME = 'fast-api-crud';

    public const VERSION = '3.1.0';

    /**
     * Reference topics shipped with the skill, exposed as resources
     * (fast-api://skill/{topic}) and through the read_reference / search_reference tools.
     *
     * @var array<string, array{0: string, 1: string}> topic => [relative file, description]
     */
    public const REFERENCES = [
        'skill'                  => ['skills/fast-api-crud/SKILL.md', 'Overview, golden rules, workflow, cheat-sheets, debugging checklist'],
        'controllers'            => ['skills/fast-api-crud/references/controllers.md', 'BaseController & BaseWebController: constructors, every property, query pipeline, perform* steps, return values, web redirects, override points'],
        'routes'                 => ['skills/fast-api-crud/references/routes.md', 'Route::fastApiResource options and all 11 routes'],
        'scaffolding'            => ['skills/fast-api-crud/references/scaffolding.md', 'fast-api:make-all generated files, stubs, naming, gotchas'],
        'query-and-responses'    => ['skills/fast-api-crud/references/query-and-responses.md', 'Every request key, bulk delete, status codes and envelopes'],
        'hooks-contracts-traits' => ['skills/fast-api-crud/references/hooks-contracts-traits.md', 'Lifecycle hooks, Searchable/Sortable/HasPermissionSlug, HasDateScopes, AnonymizesOnDelete, ReplicatesWithRelations'],
        'api-response'           => ['skills/fast-api-crud/references/api-response.md', 'HasApiResponse: success/error and all status shortcuts; ApiException'],
        'macros'                 => ['skills/fast-api-crud/references/macros.md', 'Builder & Collection macros, Pagination and QueryParams support classes'],
        'helpers'                => ['skills/fast-api-crud/references/helpers.md', 'All global helper functions'],
        'config'                 => ['skills/fast-api-crud/references/config.md', 'Every fast-api config key, enums, permissionMiddleware matrix, service provider'],
        'permissions'            => ['skills/fast-api-crud/references/permissions.md', 'spatie/laravel-permission integration: install, middleware alias, seeding, guards, caching, teams, super-admin, testing, 403 checklist'],
        'testing'                => ['skills/fast-api-crud/references/testing.md', 'Pest recipes per endpoint'],
        'agents'                 => ['AGENTS.md', 'Short instructions block for AGENTS.md / CLAUDE.md'],
    ];

    public function __construct(
        private readonly Inspector $inspector,
        private readonly Repository $config,
        private readonly Kernel $artisan,
        private readonly string $resourcesPath,
    ) {}

    /**
     * Serve until the input stream closes.
     *
     * @param resource $input
     * @param resource $output
     */
    public function serve($input, $output): void
    {
        while (($line = fgets($input)) !== false) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $response = $this->handleRaw($line);

            if ($response !== null) {
                fwrite($output, $response . "\n");
                fflush($output);
            }
        }
    }

    /**
     * Handle one raw JSON-RPC message. Returns the encoded response, or null
     * for notifications (which must not be answered).
     */
    public function handleRaw(string $json): ?string
    {
        try {
            $message = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return $this->encode(self::error(null, -32700, 'Parse error: ' . $e->getMessage()));
        }

        if (! is_array($message)) {
            return $this->encode(self::error(null, -32600, 'Invalid Request'));
        }

        $response = $this->handle($message);

        return $response === null ? null : $this->encode($response);
    }

    /**
     * @param array<array-key, mixed> $message
     *
     * @return array<string, mixed>|null
     */
    public function handle(array $message): ?array
    {
        $id = $message['id'] ?? null;
        $method = $message['method'] ?? null;
        $params = is_array($message['params'] ?? null) ? $message['params'] : [];
        $isNotification = ! array_key_exists('id', $message);

        if (! is_string($method)) {
            return $isNotification ? null : self::error($id, -32600, 'Invalid Request: missing method');
        }

        if (str_starts_with($method, 'notifications/')) {
            return null;
        }

        try {
            $result = match ($method) {
                'initialize'     => $this->initialize(),
                'ping'           => [],
                'tools/list'     => ['tools' => $this->toolDefinitions()],
                'tools/call'     => $this->callTool($params),
                'resources/list' => ['resources' => $this->resourceDefinitions()],
                'resources/read' => $this->readResource($params),
                'prompts/list'   => ['prompts' => $this->promptDefinitions()],
                'prompts/get'    => $this->getPrompt($params),
                default          => throw new McpException("Method not found: {$method}", -32601),
            };
        } catch (McpException $e) {
            return $isNotification ? null : self::error($id, $e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            return $isNotification ? null : self::error($id, -32603, $e->getMessage());
        }

        if ($isNotification) {
            return null;
        }

        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    // -------------------------------------------------------------------------
    // Tools
    // -------------------------------------------------------------------------

    /**
     * @return list<array<string, mixed>>
     */
    public function toolDefinitions(): array
    {
        $readOnly = ['readOnlyHint' => true, 'destructiveHint' => false, 'idempotentHint' => true, 'openWorldHint' => false];

        return [
            [
                'name'        => 'list_controllers',
                'description' => 'List every controller in app/Http/Controllers that extends BaseController or BaseWebController, with its model, resource and route count.',
                'inputSchema' => ['type' => 'object', 'properties' => new stdClass, 'additionalProperties' => false],
                'annotations' => $readOnly + ['title' => 'List fast-api-crud controllers'],
            ],
            [
                'name'        => 'describe_controller',
                'description' => 'Full picture of one fast-api-crud controller: constructor bindings, configuration properties ($scopes, $with, $allowedIncludes, $updatableColumns, ...), overridden methods and hooks, registered routes with middleware, and the managed model (fillable, scopes, relations, contracts, hooks).',
                'inputSchema' => [
                    'type'                 => 'object',
                    'properties'           => ['class' => ['type' => 'string', 'description' => 'Fully-qualified controller class, e.g. App\Http\Controllers\PostController']],
                    'required'             => ['class'],
                    'additionalProperties' => false,
                ],
                'annotations' => $readOnly + ['title' => 'Describe controller'],
            ],
            [
                'name'        => 'list_routes',
                'description' => 'All registered routes that point at fast-api-crud controllers (methods, URI, name, action, middleware). Optionally filtered to one controller class.',
                'inputSchema' => [
                    'type'                 => 'object',
                    'properties'           => ['class' => ['type' => 'string', 'description' => 'Optional controller class to filter by']],
                    'additionalProperties' => false,
                ],
                'annotations' => $readOnly + ['title' => 'List routes'],
            ],
            [
                'name'        => 'model_info',
                'description' => 'Describe an Eloquent model as fast-api-crud sees it: table, fillable/guarded, casts, columns, scopes (usable as filters keys), relations (candidates for $with/$allowedIncludes), lifecycle hooks, Searchable/Sortable/HasPermissionSlug implementation, soft deletes.',
                'inputSchema' => [
                    'type'                 => 'object',
                    'properties'           => ['class' => ['type' => 'string', 'description' => 'Fully-qualified model class, e.g. App\Models\Post']],
                    'required'             => ['class'],
                    'additionalProperties' => false,
                ],
                'annotations' => $readOnly + ['title' => 'Model info'],
            ],
            [
                'name'        => 'get_config',
                'description' => 'The effective fast-api config (pagination limits, bulk delete field/cap, renamed query parameter keys, default sort, response envelope keys, permissions toggle, web flash keys).',
                'inputSchema' => ['type' => 'object', 'properties' => new stdClass, 'additionalProperties' => false],
                'annotations' => $readOnly + ['title' => 'Get fast-api config'],
            ],
            [
                'name'        => 'query_reference',
                'description' => 'Cheat-sheet of the index/show request parameters using THIS project\'s configured key names, with an example URL for a given resource. Use before writing URLs in tests or frontend code.',
                'inputSchema' => [
                    'type'                 => 'object',
                    'properties'           => ['resource' => ['type' => 'string', 'description' => 'URI segment, e.g. "posts". Default "posts".']],
                    'additionalProperties' => false,
                ],
                'annotations' => $readOnly + ['title' => 'Query parameter reference'],
            ],
            [
                'name'        => 'read_reference',
                'description' => 'Read one topic of the bundled fast-api-crud reference (derived from the package source; more accurate than the README). Topics: ' . implode(', ', array_keys(self::REFERENCES)) . '.',
                'inputSchema' => [
                    'type'                 => 'object',
                    'properties'           => ['topic' => ['type' => 'string', 'enum' => array_keys(self::REFERENCES), 'description' => 'Reference topic']],
                    'required'             => ['topic'],
                    'additionalProperties' => false,
                ],
                'annotations' => $readOnly + ['title' => 'Read package reference'],
            ],
            [
                'name'        => 'search_reference',
                'description' => 'Full-text search across the bundled fast-api-crud reference (all topics). Returns matching lines with topic and line number plus surrounding context. Use to look up a helper, macro, property, config key, hook or status method.',
                'inputSchema' => [
                    'type'       => 'object',
                    'properties' => [
                        'query'   => ['type' => 'string', 'minLength' => 2, 'description' => 'Case-insensitive substring, e.g. "formatDuration", "$updatableColumns", "allow_all"'],
                        'context' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 10, 'default' => 2, 'description' => 'Lines of context around each hit'],
                    ],
                    'required'             => ['query'],
                    'additionalProperties' => false,
                ],
                'annotations' => $readOnly + ['title' => 'Search package reference'],
            ],
            [
                'name'        => 'scaffold',
                'description' => 'Run `php artisan fast-api:make-all` to generate model, migration, factory, seeder, resource, Store/Update requests and a controller (plus Blade views with web=true) for one or more models. Creates files; never overwrites existing ones.',
                'inputSchema' => [
                    'type'       => 'object',
                    'properties' => [
                        'models' => ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 1, 'description' => 'StudlyCase model names, e.g. ["Post", "Tag"]'],
                        'web'    => ['type' => 'boolean', 'description' => 'Generate a BaseWebController + Blade views instead of an API controller', 'default' => false],
                    ],
                    'required'             => ['models'],
                    'additionalProperties' => false,
                ],
                'annotations' => ['title' => 'Scaffold resource(s)', 'readOnlyHint' => false, 'destructiveHint' => false, 'idempotentHint' => true, 'openWorldHint' => false],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Resources
    // -------------------------------------------------------------------------

    /**
     * @return list<array<string, string>>
     */
    public function resourceDefinitions(): array
    {
        $resources = [];

        foreach (self::REFERENCES as $topic => [, $description]) {
            $resources[] = ['uri' => self::referenceUri($topic), 'name' => "fast-api-crud: {$topic}", 'description' => $description, 'mimeType' => 'text/markdown'];
        }

        $resources[] = ['uri' => 'fast-api://config', 'name' => 'Effective fast-api config', 'description' => 'config(\'fast-api\') as JSON', 'mimeType' => 'application/json'];

        return $resources;
    }

    // -------------------------------------------------------------------------
    // Prompts
    // -------------------------------------------------------------------------

    /**
     * @return list<array<string, mixed>>
     */
    public function promptDefinitions(): array
    {
        return [
            [
                'name'        => 'build-resource',
                'description' => 'Step-by-step plan to add a complete CRUD resource for a model with fast-api-crud.',
                'arguments'   => [
                    ['name' => 'model', 'description' => 'StudlyCase model name, e.g. Post', 'required' => true],
                    ['name' => 'web', 'description' => '"true" for a Blade (BaseWebController) resource', 'required' => false],
                ],
            ],
            [
                'name'        => 'debug-endpoint',
                'description' => 'Diagnose why a fast-api-crud endpoint misbehaves (filter ignored, 403 on updateColumn, pagination, search, includes).',
                'arguments'   => [
                    ['name' => 'symptom', 'description' => 'What is happening, including the request URL/body', 'required' => true],
                ],
            ],
        ];
    }

    public static function referenceUri(string $topic): string
    {
        return match ($topic) {
            'skill'  => 'fast-api://skill',
            'agents' => 'fast-api://agents',
            default  => "fast-api://skill/{$topic}",
        };
    }

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function initialize(): array
    {
        return [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities'    => [
                'tools'     => ['listChanged' => false],
                'resources' => ['subscribe' => false, 'listChanged' => false],
                'prompts'   => ['listChanged' => false],
            ],
            'serverInfo'   => ['name' => self::NAME, 'version' => self::VERSION],
            'instructions' => 'Introspection and scaffolding for a Laravel app using anil/fast-api-crud. '
                . 'Call get_config and query_reference before writing request URLs; call describe_controller '
                . 'before changing a controller; use scaffold to create a new resource.',
        ];
    }

    /**
     * @param array<array-key, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function callTool(array $params): array
    {
        $name = $params['name'] ?? null;
        $arguments = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

        if (! is_string($name)) {
            throw new McpException('tools/call requires a "name"', -32602);
        }

        try {
            $result = match ($name) {
                'list_controllers'    => $this->inspector->listControllers(),
                'describe_controller' => $this->inspector->describeController($this->requireClass($arguments)),
                'list_routes'         => $this->inspector->routesFor(isset($arguments['class']) && is_string($arguments['class']) ? $arguments['class'] : null),
                'model_info'          => $this->inspector->describeModel($this->requireClass($arguments)),
                'get_config'          => $this->config->get('fast-api', []),
                'query_reference'     => $this->queryReference(is_string($arguments['resource'] ?? null) ? $arguments['resource'] : 'posts'),
                'read_reference'      => $this->readReference(is_string($arguments['topic'] ?? null) ? $arguments['topic'] : ''),
                'search_reference'    => $this->searchReference(
                    is_string($arguments['query'] ?? null) ? $arguments['query'] : '',
                    is_int($arguments['context'] ?? null) ? $arguments['context'] : 2,
                ),
                'scaffold' => $this->scaffold($arguments),
                default    => throw new McpException("Unknown tool: {$name}", -32602),
            };
        } catch (McpException $e) {
            throw $e;
        } catch (Throwable $e) {
            return self::toolText($e::class . ': ' . $e->getMessage(), isError: true);
        }

        return self::toolText(is_string($result) ? $result : $this->encode($result, pretty: true));
    }

    /**
     * @param array<array-key, mixed> $arguments
     *
     * @return class-string
     */
    private function requireClass(array $arguments): string
    {
        $class = $arguments['class'] ?? null;

        if (! is_string($class) || $class === '') {
            throw new McpException('Argument "class" is required', -32602);
        }

        $class = ltrim($class, '\\');

        if (! class_exists($class)) {
            throw new McpException("Class not found: {$class}", -32602);
        }

        return $class;
    }

    /**
     * @return array<string, mixed>
     */
    private function queryReference(string $resource): array
    {
        $resource = trim($resource, '/');
        $filters = QueryParams::filters();

        $example = http_build_query([
            $filters                  => json_encode(['active' => 1, QueryParams::includes() => 'author,tags', QueryParams::trashed() => 'with']),
            QueryParams::search()     => 'laravel',
            QueryParams::sortBy()     => 'created_at',
            QueryParams::descending() => 'true',
            QueryParams::perPage()    => 25,
            QueryParams::page()       => 2,
        ]);

        $bulkField = $this->config->get('fast-api.bulk.field', 'delete_rows');
        $allowAll = (bool) $this->config->get('fast-api.pagination.allow_all', false);

        return [
            'exampleIndexUrl' => "/{$resource}?{$example}",
            'parameters'      => [
                $filters                                 => 'JSON object. Each key calls scope{StudlyKey}($query, $value) on the model; unknown keys are ignored.',
                $filters . '.' . QueryParams::includes() => 'Inside the filters JSON: "a,b" or ["a","b"]; only relations in the controller\'s $allowedIncludes are loaded (index + show).',
                $filters . '.' . QueryParams::trashed()  => 'Inside the filters JSON: "with" | "only"; requires $allowTrashedFilter = true and SoftDeletes.',
                QueryParams::search()                    => 'LIKE search over Searchable::searchableColumns() (supports "relation:col1,col2").',
                QueryParams::sortBy()                    => 'Column to sort by. Default: Sortable::sortByDefaults() or fast-api.sorting.default_column (' . $this->stringConfig('fast-api.sorting.default_column', 'id') . ').',
                QueryParams::descending()                => 'true|false. Default: ' . ($this->config->get('fast-api.sorting.default_descending', true) ? 'true' : 'false') . '.',
                QueryParams::perPage()                   => sprintf(
                    'Rows per page. Default %s, max %s. 0 = all rows: %s.',
                    $this->stringConfig('fast-api.pagination.default_per_page', '15'),
                    $this->stringConfig('fast-api.pagination.max_per_page', '100'),
                    $allowAll ? 'allowed (capped at ' . $this->stringConfig('fast-api.pagination.max_all', '1000') . ')' : 'NOT allowed (falls back to default)',
                ),
                QueryParams::page()   => 'Page number (length-aware / simple pagination).',
                QueryParams::cursor() => 'Cursor token (PaginationType::Cursor).',
            ],
            'bulkDelete' => [
                'request' => "DELETE /{$resource}",
                'body'    => [(is_string($bulkField) ? $bulkField : 'delete_rows') => [1, 2, 3]],
                'maxRows' => $this->config->get('fast-api.bulk.max_rows', 1000),
            ],
            'responseEnvelope' => $this->config->get('fast-api.response', []),
        ];
    }

    /**
     * @param array<array-key, mixed> $arguments
     */
    private function scaffold(array $arguments): string
    {
        $models = $arguments['models'] ?? null;

        if (! is_array($models) || $models === []) {
            throw new McpException('Argument "models" must be a non-empty array of model names', -32602);
        }

        $names = [];
        foreach ($models as $model) {
            if (! is_string($model) || ! preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $model)) {
                throw new McpException('Model names must be StudlyCase identifiers (letters and digits only)', -32602);
            }
            $names[] = Str::studly($model);
        }

        $parameters = ['name' => implode(',', $names)];

        if (($arguments['web'] ?? false) === true) {
            $parameters['--web'] = true;
        }

        $exitCode = $this->artisan->call('fast-api:make-all', $parameters);
        $output = trim($this->artisan->output());

        return ($exitCode === 0 ? 'OK' : "FAILED (exit {$exitCode})") . "\n" . $output;
    }

    /**
     * @param array<array-key, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function readResource(array $params): array
    {
        $uri = $params['uri'] ?? null;

        if (! is_string($uri)) {
            throw new McpException('resources/read requires a "uri"', -32602);
        }

        if ($uri === 'fast-api://config') {
            return ['contents' => [['uri' => $uri, 'mimeType' => 'application/json', 'text' => $this->encode($this->config->get('fast-api', []), pretty: true)]]];
        }

        $topic = array_search($uri, array_map(self::referenceUri(...), array_keys(self::REFERENCES)), true);

        if ($topic === false) {
            throw new McpException("Unknown resource: {$uri}", -32002);
        }

        return ['contents' => [['uri' => $uri, 'mimeType' => 'text/markdown', 'text' => $this->readReference(array_keys(self::REFERENCES)[$topic])]]];
    }

    private function readReference(string $topic): string
    {
        if (! isset(self::REFERENCES[$topic])) {
            throw new McpException("Unknown reference topic: {$topic}. Available: " . implode(', ', array_keys(self::REFERENCES)), -32602);
        }

        $path = $this->resourcesPath . '/' . self::REFERENCES[$topic][0];
        $text = is_file($path) ? file_get_contents($path) : false;

        if ($text === false) {
            throw new McpException("Reference file missing: {$path}", -32603);
        }

        return $text;
    }

    /**
     * @return array<string, mixed>
     */
    private function searchReference(string $query, int $context): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            throw new McpException('Argument "query" must be at least 2 characters', -32602);
        }

        $context = max(0, min(10, $context));
        $needle = mb_strtolower($query);
        $hits = [];

        foreach (array_keys(self::REFERENCES) as $topic) {
            $lines = preg_split('/\r?\n/', $this->readReference($topic)) ?: [];
            $total = count($lines);

            foreach ($lines as $index => $line) {
                if (! str_contains(mb_strtolower($line), $needle)) {
                    continue;
                }

                $from = max(0, $index - $context);
                $to = min($total - 1, $index + $context);

                $hits[] = [
                    'topic'   => $topic,
                    'line'    => $index + 1,
                    'match'   => trim($line),
                    'context' => implode("\n", array_slice($lines, $from, $to - $from + 1)),
                ];

                if (count($hits) >= 50) {
                    break 2;
                }
            }
        }

        return ['query' => $query, 'hits' => count($hits), 'truncated' => count($hits) >= 50, 'results' => $hits];
    }

    /**
     * @param array<array-key, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function getPrompt(array $params): array
    {
        $name = $params['name'] ?? null;
        $args = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

        $text = match ($name) {
            'build-resource' => $this->buildResourcePrompt(
                is_string($args['model'] ?? null) ? Str::studly($args['model']) : 'Post',
                in_array($args['web'] ?? null, ['true', true, '1', 1], true),
            ),
            'debug-endpoint' => $this->debugEndpointPrompt(is_string($args['symptom'] ?? null) ? $args['symptom'] : ''),
            default          => throw new McpException('Unknown prompt: ' . (is_string($name) ? $name : '(none)'), -32602),
        };

        return [
            'description' => $name,
            'messages'    => [['role' => 'user', 'content' => ['type' => 'text', 'text' => $text]]],
        ];
    }

    private function buildResourcePrompt(string $model, bool $web): string
    {
        $kebab = Str::kebab(Str::pluralStudly($model));
        $flag = $web ? ' --web' : '';

        return <<<MD
        Add a complete {$model} resource to this Laravel app using anil/fast-api-crud.

        1. Read the `fast-api://skill` resource (and `read_reference` topics `controllers`, `routes`, `scaffolding` as needed), then call `get_config` + `query_reference` so URLs and limits match this project.
        2. Call `model_info` for `App\\Models\\{$model}` if it exists; otherwise run the `scaffold` tool (models: ["{$model}"], web: {$this->boolString($web)}) — equivalent to `php artisan fast-api:make-all {$model}{$flag}`.
        3. Fill the migration and `\$fillable`, then write validation rules in Store{$model}Request / Update{$model}Request and shape {$model}Resource.
        4. Add model scopes for every filter the client needs, implement `Searchable` (and `Sortable` if a default sort other than id desc is wanted).
        5. Configure the controller through properties only (`\$with`, `\$allowedIncludes`, `\$updatableColumns`, `\$scopes`, …). Do not hand-write CRUD methods.
        6. Register routes: `Route::fastApiResource('{$kebab}', {$model}Controller::class)` in routes/api.php (or Route::resource + extra routes for web).
        7. If the project uses Spatie permissions, read `read_reference` topic `permissions`, add `public static function middleware(): array { return [...static::permissionMiddleware('{$kebab}'), 'auth:sanctum']; }`, make sure the `permission` middleware alias is registered, and seed the six `*-{$kebab}` permissions for the right guard.
        8. Write Pest feature tests (see `fast-api://skill/testing`) and run them plus `vendor/bin/pint --dirty`.
        9. Finish with `describe_controller` to confirm the routes and properties, and summarise what was created.
        MD;
    }

    private function debugEndpointPrompt(string $symptom): string
    {
        return <<<MD
        Diagnose this fast-api-crud problem:

        {$symptom}

        Procedure:
        1. `list_controllers` → `describe_controller` for the affected controller; note `properties`, `routes`, `model.scopes`, `model.fillable`.
        2. `get_config` — check `query` key names, `pagination.allow_all/max_per_page`, `bulk`, `permissions.enabled`.
        3. Match the symptom against the checklist in `fast-api://skill` (filter ignored → missing scope; include ignored → not in \$allowedIncludes; 403 on every write → FormRequest authorize() false or missing permission; 403 on PATCH /{id}/status/{column} → not in \$updatableColumns; field not saved → missing from \$fillable or FormRequest rules; rowsPerPage=0 → allow_all false). Use `search_reference` for any symbol you are unsure about.
        4. Propose the smallest fix (property, scope, contract, or FormRequest rule) and a Pest test that reproduces the issue before fixing it.
        MD;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private static function toolText(string $text, bool $isError = false): array
    {
        $result = ['content' => [['type' => 'text', 'text' => $text]]];

        if ($isError) {
            $result['isError'] = true;
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private static function error(mixed $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }

    private function encode(mixed $value, bool $pretty = false): string
    {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | ($pretty ? JSON_PRETTY_PRINT : 0);

        $json = json_encode($value, $flags);

        return $json === false ? '{"jsonrpc":"2.0","id":null,"error":{"code":-32603,"message":"Failed to encode response"}}' : $json;
    }

    private function stringConfig(string $key, string $default): string
    {
        $value = $this->config->get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    private function boolString(bool $value): string
    {
        return $value ? 'true' : 'false';
    }
}
