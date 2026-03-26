# Pagination Utility

`Anil\FastApiCrud\Support\Pagination`

Static helpers for resolving pagination configuration from config and request. Used internally by the builder macros; can also be used directly.

## Methods

### resolvePerPage

```php
public static function resolvePerPage(): int
```

Resolves the effective per-page value from request + config:
- Returns `0` when `rowsPerPage=0` and `allow_all` is true (fetch all records)
- Returns `default_per_page` when `rowsPerPage` is negative
- Clamps to `max_per_page` when exceeded

### defaultPerPage

```php
public static function defaultPerPage(): int
```

Returns the `fast-api.pagination.default_per_page` config value (default: `15`).

### maxPerPage

```php
public static function maxPerPage(): int
```

Returns the `fast-api.pagination.max_per_page` config value (default: `100`).

### requestedPerPage

```php
public static function requestedPerPage(int $default): int
```

Returns the raw `rowsPerPage` query parameter value, or `$default` if not present or not numeric.

### configInt

```php
public static function configInt(string $key, int $default): int
```

Get a config value as an integer with a fallback default. Returns `$default` if the config value isn't numeric.

### configBool

```php
public static function configBool(string $key, bool $default): bool
```

Get a config value as a boolean with a fallback default.

## Usage

```php
use Anil\FastApiCrud\Support\Pagination;

// In a controller or service
$perPage = Pagination::resolvePerPage();       // Effective per-page value
$default = Pagination::defaultPerPage();       // 15
$max = Pagination::maxPerPage();               // 100
$requested = Pagination::requestedPerPage(15); // Raw request value

// Generic config helpers
$value = Pagination::configInt('fast-api.pagination.default_per_page', 15);
$flag = Pagination::configBool('fast-api.pagination.allow_all', true);
```
