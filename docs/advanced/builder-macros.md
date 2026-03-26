# Builder Macros

Registered on `Illuminate\Database\Eloquent\Builder` by the service provider. Available on all Eloquent queries.

## initializer

```php
->initializer(bool $orderBy = true): Builder
```

Apply request-based filters, sorting, and scopes.

**How it works:**

1. Reads `?filters={"scope":"value"}` — decodes JSON, converts keys to StudlyCase, calls matching `scope{Name}` methods
2. Reads `?sortBy=column&descending=true` — applies ordering
3. If model implements `Sortable` and no sort params given, uses `sortByDefaults()`
4. Default sort: `id` descending

```php
// Standard usage
$query = Post::query()->initializer();

// Disable sorting
$query = Post::query()->initializer(orderBy: false);
```

**Filter examples:**

```
?filters={"active":1}             → $query->active(1)
?filters={"queryFilter":"test"}   → $query->queryFilter("test")
?filters={"my_scope":1}           → $query->myScope(1)  (StudlyCase conversion)
```

Invalid JSON in `filters` is silently ignored. Filters with `null` values are skipped. Unknown scope names are skipped.

## likeWhere

```php
->likeWhere(array $attributes, ?string $searchTerm = null): Builder
```

Multi-column LIKE search with relation support.

```php
// Simple columns
Post::query()->likeWhere(['name', 'desc'], 'laravel');
// WHERE (name LIKE '%laravel%' OR desc LIKE '%laravel%')

// Relation columns (colon syntax: 'relation:col1,col2')
Post::query()->likeWhere(['name', 'user:name,email'], 'test');
// WHERE (name LIKE '%test%' OR EXISTS (
//   SELECT * FROM users WHERE ... AND (name LIKE '%test%' OR email LIKE '%test%')
// ))

// Null/empty search returns query unchanged
Post::query()->likeWhere(['name'], null);   // No-op
Post::query()->likeWhere(['name'], '');     // No-op
```

## paginates

```php
->paginates(array $columns = ['*'], string $pageName = 'page', ?int $page = null): Paginator
```

Length-aware pagination using `rowsPerPage` request parameter.

```php
Post::query()->paginates();                          // Default: 15 per page
Post::query()->paginates(['id', 'name']);             // Specific columns
Post::query()->paginates(['*'], 'p', 2);             // Custom page name & page
```

**Behavior:**
- Reads `rowsPerPage` from request query string
- Respects `fast-api.pagination.max_per_page` (clamps to max)
- When `rowsPerPage=0` and `fast-api.pagination.allow_all=true`: counts all records and uses that as per-page (effectively returns all)
- Negative values fall back to `default_per_page`

## simplePaginates

```php
->simplePaginates(array $columns = ['*'], string $pageName = 'page', ?int $page = null): Paginator
```

Simple pagination (no total count query). Same parameters and behavior as `paginates()` but uses `simplePaginate()` internally.

## cursorPaginates

```php
->cursorPaginates(array $columns = ['*'], ?string $cursorName = null, ?Cursor $cursor = null): CursorPaginator
```

Cursor-based pagination. Best for large datasets and infinite scroll.

```php
Post::query()->cursorPaginates();
Post::query()->cursorPaginates(['id', 'name'], 'cursor');
```

Uses `rowsPerPage` request parameter. Negative values fall back to default. Values above max are clamped.

## withAggregates

```php
->withAggregates(array $aggregates): Builder
```

Apply multiple aggregate functions in a single call.

```php
Post::query()->withAggregates([
    'comments' => 'id',                  // withAggregate('comments', 'id')
    'ratings'  => ['score', 'avg'],      // withAggregate('ratings', 'score', 'avg')
    'views'    => ['count', 'sum'],      // withAggregate('views', 'count', 'sum')
]);
```

**Array format:**
- `'relation' => 'column'` — calls `withAggregate($relation, $column)`
- `'relation' => ['column', 'function']` — calls `withAggregate($relation, $column, $function)`

Empty array is a no-op.

## withCountWhereHas

```php
->withCountWhereHas(
    string $relation,
    ?Closure $callback = null,
    string $operator = '>=',
    int $count = 1
): Builder
```

Adds `withCount` AND `whereHas` in one call — counts the relation and filters to only include models that have matching related records.

```php
// Posts with at least 1 comment, including comment count
Post::query()->withCountWhereHas('comments');

// Posts with approved comments
Post::query()->withCountWhereHas('comments', function ($q) {
    $q->where('approved', true);
});

// Posts with 5+ comments
Post::query()->withCountWhereHas('comments', null, '>=', 5);

// Posts with exactly 3 tags
Post::query()->withCountWhereHas('tags', null, '=', 3);
```

Supports colon syntax for relation with constraining: `'comments:approved'`.

## orWithCountWhereHas

```php
->orWithCountWhereHas(
    string $relation,
    ?Closure $callback = null,
    string $operator = '>=',
    int $count = 1
): Builder
```

OR variant — uses `orWhereHas` instead of `whereHas`.

```php
// Posts that have comments OR tags
Post::query()
    ->withCountWhereHas('comments')
    ->orWithCountWhereHas('tags');
```
