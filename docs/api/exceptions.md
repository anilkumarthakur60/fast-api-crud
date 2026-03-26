# Exceptions

## ApiException

`Anil\FastApiCrud\Exceptions\ApiException`

Custom exception that renders as a JSON response. Extends `Exception`.

### Usage

```php
use Anil\FastApiCrud\Exceptions\ApiException;

throw new ApiException('Resource not found', 404);
throw new ApiException('Validation failed', 422);
throw new ApiException('Server error');  // Defaults to 500 if code < 100 or >= 600
```

### Response Format

**Production** (`APP_DEBUG=false`):

```json
{
  "error": {
    "message": "Resource not found"
  }
}
```

**Debug** (`APP_DEBUG=true`):

```json
{
  "error": {
    "message": "Resource not found",
    "file": "/app/Http/Controllers/PostController.php",
    "line": 42
  }
}
```

### Status Code Validation

If the exception code is outside the valid HTTP range (100-599), it defaults to `500 Internal Server Error`.

### render Method

```php
public function render(): JsonResponse
```

Automatically called by Laravel's exception handler. Returns the JSON response with the appropriate status code.
