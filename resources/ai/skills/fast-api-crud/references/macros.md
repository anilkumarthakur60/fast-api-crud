# Builder & Collection macros (complete)

Registered globally by the service provider (`BuilderMacros::register()`, `CollectionMacros::register()`).

## Eloquent `Builder`

### `initializer(bool $orderBy = true): Builder`
1. If `request()->filled(filtersKey)` and the value is a JSON object: for each `key => value`
   with non-null value, skipping the reserved `include`/`trashed` keys, call `$query->{$key}($value)`
   **only if** `$model->hasNamedScope($key)` (covers `scopeX` methods and `#[Scope]` attributes).
   The raw value is passed as the scope's first argument (string, number, array, object→array).
2. If `$orderBy`: `sortBy = request(sortByKey)`, `desc = request()->boolean(descendingKey, config default_descending)`.
   If `sortBy` is null and the model is `Sortable`, use `sortByDefaults()`. Fallback column:
   `fast-api.sorting.default_column` (`id`). Then `latest($col)` or `oldest($col)`.
   ⚠ `sortBy` is not validated against columns — allowlist it yourself if the API is public
   (e.g. override `buildIndexQuery()` and use `initializer(orderBy: false)` + your own `orderBy`).

### `likeWhere(array $attributes, ?string $searchTerm = null): Builder`
No-op for null/empty term. Wraps everything in one `where(function)`: plain columns →
`orWhere(col, 'LIKE', "%term%")`; `'relation:colA,colB'` → `whereHas(relation, fn => orWhereAny([...], 'LIKE', "%term%"))`.
(The relation branch uses `whereHas`, i.e. AND-ed inside the group — combine carefully with plain columns.)

### `paginates(array $columns = ['*'], ?string $pageName = null, ?int $page = null): Paginator`
`paginate(Pagination::resolveEffectivePerPage(fn => count()), $columns, $pageName ?? QueryParams::page(), $page)`.
Length-aware (`total`, `last_page`).

### `simplePaginates(array $columns = ['*'], ?string $pageName = null, ?int $page = null): Paginator`
Same per-page logic, `simplePaginate()` (no total).

### `cursorPaginates(array $columns = ['*'], ?string $cursorName = null, ?Cursor $cursor = null): CursorPaginator`
perPage = requested ≤ 0 ? default : `min(requested, max_per_page)` — "all rows" is never honoured
for cursor pagination. Cursor param name = `QueryParams::cursor()`.

### `withAggregates(array $aggregates): Builder`
`['relation' => 'column']` → `withAggregate(relation, column)`;
`['relation' => ['column', 'avg']]` → `withAggregate(relation, column, 'avg')`.

### `withCountWhereHas(string $relation, ?Closure $callback = null, string $operator = '>=', int $count = 1): Builder`
`whereHas(before(relation, ':'), $callback, $operator, $count)->withCount(...)` — filters rows to
those having matching related rows AND adds `{relation}_count` (callback applied to both).
`orWithCountWhereHas(...)` is the `orWhereHas` variant.

## `Collection`

### `paginate(int $perPage, ?int $total = null, ?int $page = null, string $pageName = 'page'): LengthAwarePaginator`
In-memory pagination via `forPage()`. Page defaults to `LengthAwarePaginator::resolveCurrentPage($pageName)`,
path to the current URL.

## `Pagination` support class (`Anil\FastApiCrud\Support\Pagination`)

| Method | Meaning |
|---|---|
| `configInt($key, $default)`, `configBool($key, $default)` | typed config readers |
| `requestedPerPage(int $default): int` | `request()->query(perPageKey)` if numeric |
| `resolvePerPage(): int` | `0` when `allow_all` && requested `0`; default when requested ≤ 0; else `min(requested, max_per_page)` |
| `defaultPerPage()`, `maxPerPage()` | config readers (15 / 100) |
| `resolveEffectivePerPage(Closure $countFn): int` | if `resolvePerPage()` is 0 → `min(count(), max_all)` (`max_all` 0 = unbounded); COUNT only runs in that branch |

## `QueryParams` (`Anil\FastApiCrud\Support\QueryParams`)
Static: `filters() search() sortBy() descending() perPage() page() cursor() includes() trashed()`
→ the configured key name (`fast-api.query.*`), falling back to the defaults
`filters search sortBy descending rowsPerPage page cursor include trashed`.
