<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Commands;

use Anil\FastApiCrud\Mcp\McpServer;
use Illuminate\Console\Command;

class McpServerCommand extends Command
{
    protected $signature = 'fast-api:mcp';

    protected $description = 'Serve the fast-api-crud Model Context Protocol server over stdio (for Claude Code, Cursor, Copilot, Codex and other MCP clients)';

    public function handle(McpServer $server): int
    {
        // Anything written to stdout that is not JSON-RPC would corrupt the
        // stream, so keep Laravel's own console output away from it.
        $input = fopen('php://stdin', 'rb');
        $output = fopen('php://stdout', 'wb');

        if ($input === false || $output === false) {
            $this->error('Unable to open stdio streams.');

            return self::FAILURE;
        }

        $server->serve($input, $output);

        return self::SUCCESS;
    }
}
