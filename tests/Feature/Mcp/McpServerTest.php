<?php

declare(strict_types=1);

use Anil\FastApiCrud\Mcp\McpServer;
use Anil\FastApiCrud\Tests\TestSetup\Controllers\PostController;
use Anil\FastApiCrud\Tests\TestSetup\Models\PostModel;

/**
 * @param array<string, mixed> $params
 *
 * @return array<string, mixed>
 */
function mcp(string $method, array $params = [], int|string|null $id = 1): array
{
    $server = app(McpServer::class);
    $message = ['jsonrpc' => '2.0', 'id' => $id, 'method' => $method, 'params' => $params];

    $response = $server->handle($message);

    expect($response)->toBeArray();

    /** @var array<string, mixed> $response */
    return $response;
}

/**
 * @param array<string, mixed> $arguments
 */
function mcpTool(string $name, array $arguments = []): mixed
{
    $response = mcp('tools/call', ['name' => $name, 'arguments' => $arguments]);

    expect($response)->toHaveKey('result');
    $result = $response['result'];
    expect($result)->toBeArray()->toHaveKey('content');
    expect($result['isError'] ?? false)->toBeFalse();

    $text = $result['content'][0]['text'];
    $decoded = json_decode($text, true);

    return $decoded ?? $text;
}

it('answers initialize with protocol version and capabilities', function () {
    $response = mcp('initialize', ['protocolVersion' => '2025-06-18', 'capabilities' => [], 'clientInfo' => ['name' => 'pest', 'version' => '1']]);

    expect($response['jsonrpc'])->toBe('2.0')
        ->and($response['id'])->toBe(1)
        ->and($response['result']['protocolVersion'])->toBe(McpServer::PROTOCOL_VERSION)
        ->and($response['result']['serverInfo']['name'])->toBe('fast-api-crud')
        ->and($response['result']['capabilities'])->toHaveKeys(['tools', 'resources', 'prompts']);
});

it('ignores notifications', function () {
    $server = app(McpServer::class);

    expect($server->handle(['jsonrpc' => '2.0', 'method' => 'notifications/initialized']))->toBeNull();
});

it('returns JSON-RPC errors for bad input', function () {
    $server = app(McpServer::class);

    $parse = json_decode((string) $server->handleRaw('{not json'), true);
    expect($parse['error']['code'])->toBe(-32700);

    $missing = mcp('no/such/method');
    expect($missing['error']['code'])->toBe(-32601);

    $badTool = mcp('tools/call', ['name' => 'nope']);
    expect($badTool['error']['code'])->toBe(-32602);

    $badClass = mcp('tools/call', ['name' => 'describe_controller', 'arguments' => ['class' => 'App\\Nope']]);
    expect($badClass['error']['code'])->toBe(-32602);
});

it('lists tools, resources and prompts', function () {
    $tools = array_column(mcp('tools/list')['result']['tools'], 'name');
    expect($tools)->toBe(['list_controllers', 'describe_controller', 'list_routes', 'model_info', 'get_config', 'query_reference', 'read_reference', 'search_reference', 'scaffold']);

    $resources = array_column(mcp('resources/list')['result']['resources'], 'uri');
    expect($resources)->toContain('fast-api://skill', 'fast-api://skill/helpers', 'fast-api://skill/controllers', 'fast-api://agents', 'fast-api://config')
        ->and($resources)->toHaveCount(count(McpServer::REFERENCES) + 1);

    $prompts = array_column(mcp('prompts/list')['result']['prompts'], 'name');
    expect($prompts)->toBe(['build-resource', 'debug-endpoint']);
});

it('reads the bundled skill and the live config as resources', function () {
    $skill = mcp('resources/read', ['uri' => 'fast-api://skill'])['result']['contents'][0];
    expect($skill['mimeType'])->toBe('text/markdown')
        ->and($skill['text'])->toContain('name: fast-api-crud')
        ->and($skill['text'])->toContain('Route::fastApiResource');

    config(['fast-api.pagination.max_per_page' => 42]);
    $config = json_decode(mcp('resources/read', ['uri' => 'fast-api://config'])['result']['contents'][0]['text'], true);
    expect($config['pagination']['max_per_page'])->toBe(42);

    expect(mcp('resources/read', ['uri' => 'fast-api://missing'])['error']['code'])->toBe(-32002);
});

it('ships a reference file for every topic and every topic is readable', function () {
    foreach (array_keys(McpServer::REFERENCES) as $topic) {
        $text = mcpTool('read_reference', ['topic' => $topic]);
        expect($text)->toBeString()->not->toBeEmpty();

        $viaResource = mcp('resources/read', ['uri' => McpServer::referenceUri($topic)])['result']['contents'][0]['text'];
        expect($viaResource)->toBe($text);
    }

    expect(mcp('tools/call', ['name' => 'read_reference', 'arguments' => ['topic' => 'nope']])['error']['code'])->toBe(-32602);
});

it('documents every public symbol of the package in the reference', function () {
    $all = implode("\n", array_map(fn (string $t) => mcpTool('read_reference', ['topic' => $t]), array_keys(McpServer::REFERENCES)));

    // Every global helper function.
    preg_match_all('/^function\s+(\w+)\s*\(/m', file_get_contents(dirname(__DIR__, 3) . '/src/Support/Helpers.php'), $helpers);
    // Every HasApiResponse method.
    preg_match_all('/public function (\w+)\(/', file_get_contents(dirname(__DIR__, 3) . '/src/Concerns/HasApiResponse.php'), $responders);
    // Every HasDateScopes scope.
    preg_match_all('/public function scope(\w+)\(/', file_get_contents(dirname(__DIR__, 3) . '/src/Concerns/HasDateScopes.php'), $scopes);
    // Every Builder macro.
    preg_match_all('/Builder::macro\(\'(\w+)\'/', file_get_contents(dirname(__DIR__, 3) . '/src/Macros/BuilderMacros.php'), $macros);
    // Every config key (top-level.sub).
    $config = require dirname(__DIR__, 3) . '/config/fast-api.php';
    $configKeys = [];
    foreach ($config as $section => $values) {
        foreach (array_keys($values) as $key) {
            $configKeys[] = "{$section}.{$key}";
        }
    }
    // Every controller property.
    preg_match_all('/protected (?:array|bool|PaginationType) \$(\w+)/', file_get_contents(dirname(__DIR__, 3) . '/src/Concerns/HasCrudOperations.php'), $properties);

    $symbols = array_merge(
        $helpers[1],
        $responders[1],
        array_map(fn (string $s) => lcfirst($s), $scopes[1]),
        $macros[1],
        $configKeys,
        array_map(fn (string $p) => '$' . $p, $properties[1]),
        ['likeWhere', 'fastApiResource', 'replicateWithRelations', 'ApiException', 'CrudAction', 'PaginationType', 'permissionMiddleware'],
    );

    $missing = array_values(array_filter(array_unique($symbols), fn (string $symbol) => ! str_contains($all, $symbol)));

    expect($missing)->toBe([]);
});

it('searches the reference', function () {
    $result = mcpTool('search_reference', ['query' => 'formatDuration', 'context' => 1]);

    expect($result['hits'])->toBeGreaterThan(0)
        ->and($result['results'][0])->toHaveKeys(['topic', 'line', 'match', 'context'])
        ->and(array_column($result['results'], 'topic'))->toContain('helpers');

    expect(mcp('tools/call', ['name' => 'search_reference', 'arguments' => ['query' => 'x']])['error']['code'])->toBe(-32602);
});

it('describes a controller with bindings, properties, routes and model', function () {
    $description = mcpTool('describe_controller', ['class' => PostController::class]);

    expect($description['class'])->toBe(PostController::class)
        ->and($description['kind'])->toBe('api')
        ->and($description['bindings']['model'])->toBe(PostModel::class)
        ->and($description['properties'])->toHaveKeys(['scopes', 'with', 'allowedIncludes', 'updatableColumns', 'paginationType'])
        ->and($description['properties']['paginationType'])->toBe('length-aware')
        ->and($description['routes'])->not->toBeEmpty()
        ->and(array_column($description['routes'], 'action'))->toContain('index', 'store', 'show')
        ->and($description['model']['class'])->toBe(PostModel::class);
});

it('describes a model with scopes, hooks, contracts and columns', function () {
    $info = mcpTool('model_info', ['class' => PostModel::class]);

    expect($info['table'])->toBe((new PostModel)->getTable())
        ->and($info['fillable'])->toBe((new PostModel)->getFillable())
        ->and($info['columns'])->toContain('id')
        ->and($info['scopes'])->toBeArray()
        ->and($info)->toHaveKeys(['softDeletes', 'searchable', 'sortDefaults', 'permissionSlug', 'hooks', 'relations']);
});

it('lists routes pointing at fast-api controllers', function () {
    $routes = mcpTool('list_routes', ['class' => PostController::class]);

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        expect($route['controller'])->toBe(PostController::class)
            ->and($route['methods'])->not->toContain('HEAD');
    }
});

it('builds the query reference from the configured key names', function () {
    config(['fast-api.query.per_page' => 'limit', 'fast-api.query.search' => 'q', 'fast-api.pagination.allow_all' => false]);

    $reference = mcpTool('query_reference', ['resource' => 'articles']);

    expect($reference['exampleIndexUrl'])->toStartWith('/articles?')
        ->and($reference['exampleIndexUrl'])->toContain('limit=25')
        ->and($reference['exampleIndexUrl'])->toContain('q=laravel')
        ->and($reference['parameters'])->toHaveKeys(['limit', 'q', 'filters'])
        ->and($reference['parameters']['limit'])->toContain('NOT allowed')
        ->and($reference['bulkDelete']['body'])->toHaveKey('delete_rows');
});

it('returns the effective config', function () {
    config(['fast-api.bulk.max_rows' => 7]);

    expect(mcpTool('get_config')['bulk']['max_rows'])->toBe(7);
});

it('renders prompts with arguments', function () {
    $prompt = mcp('prompts/get', ['name' => 'build-resource', 'arguments' => ['model' => 'blog post', 'web' => 'true']])['result'];

    expect($prompt['messages'][0]['content']['text'])->toContain('BlogPost')
        ->and($prompt['messages'][0]['content']['text'])->toContain('--web');

    expect(mcp('prompts/get', ['name' => 'nope'])['error']['code'])->toBe(-32602);
});

it('validates scaffold arguments before running artisan', function () {
    $response = mcp('tools/call', ['name' => 'scaffold', 'arguments' => ['models' => ['../evil']]]);

    expect($response['error']['code'])->toBe(-32602);

    $response = mcp('tools/call', ['name' => 'scaffold', 'arguments' => []]);

    expect($response['error']['code'])->toBe(-32602);
});

it('serves newline-delimited JSON-RPC over streams', function () {
    $input = fopen('php://memory', 'w+b');
    $output = fopen('php://memory', 'w+b');
    fwrite($input, json_encode(['jsonrpc' => '2.0', 'id' => 'a', 'method' => 'ping']) . "\n");
    fwrite($input, json_encode(['jsonrpc' => '2.0', 'method' => 'notifications/initialized']) . "\n");
    fwrite($input, "\n" . json_encode(['jsonrpc' => '2.0', 'id' => 'b', 'method' => 'tools/list']) . "\n");
    rewind($input);

    app(McpServer::class)->serve($input, $output);

    rewind($output);
    $lines = array_values(array_filter(explode("\n", stream_get_contents($output))));

    expect($lines)->toHaveCount(2);
    expect(json_decode($lines[0], true))->toBe(['jsonrpc' => '2.0', 'id' => 'a', 'result' => []]);
    expect(json_decode($lines[1], true)['id'])->toBe('b');
});
