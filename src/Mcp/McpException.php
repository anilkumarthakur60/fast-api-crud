<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Mcp;

use RuntimeException;

/**
 * A JSON-RPC level error (carries the JSON-RPC error code).
 */
final class McpException extends RuntimeException {}
