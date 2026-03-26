# Controller Properties

Both `BaseController` and `BaseWebController` share these properties via the `CrudQueries` trait.

## All Properties

```php
class PostController extends BaseController
{
    // ── Pagination ─────────────────────────────────────────────
    protected PaginationType $paginationType = PaginationType::LengthAware;
    // Options: LengthAware, Simple, Cursor, None

    // ── Index query ────────────────────────────────────────────
    protected array $scopes = [];          // Scopes applied to index
    protected array $with = [];            // Eager load relationships in index
    protected array $withCount = [];       // Count relationships in index
    protected array $withAggregate = [];   // Aggregate functions in index

    // ── Show query ─────────────────────────────────────────────
    protected array $loadScopes = [];      // Scopes applied to show
    protected array $load = [];            // Eager load relationships in show
    protected array $loadCount = [];       // Count relationships in show
    protected array $loadAggregate = [];   // Aggregate functions in show

    // ── Operation scopes ───────────────────────────────────────
    protected array $updateScopes = [];    // Scopes when finding for update
    protected array $deleteScopes = [];    // Scopes when finding for delete
    protected array $columnScopes = [];    // Scopes for changeStatus / updateColumn
    protected array $restoreScopes = [];   // Scopes when finding for restore

    // ── Delete behavior ────────────────────────────────────────
    protected bool $forceDelete = false;   // true = permanent, false = soft delete
}
```

## Scope Syntax

All scope properties (`$scopes`, `$loadScopes`, `$deleteScopes`, `$updateScopes`, `$columnScopes`, `$restoreScopes`) support the same syntax:

```php
// Simple scopes — no parameters
protected array $scopes = ['active', 'published'];
// Calls: $query->active() and $query->published()

// Parameterized scopes
protected array $scopes = [
    'status' => 1,
    'active' => 1,
    'type'   => 'article',
];
// Calls: $query->status(1), $query->active(1), $query->type('article')

// Array parameters
protected array $scopes = [
    'statusIn' => [1, 2, 3],
];
// Calls: $query->statusIn(1, 2, 3)  (spread as arguments)

// Closure parameters
protected array $scopes = [
    'custom' => function ($query) {
        $query->where('featured', true);
    },
];

// Mixed
protected array $scopes = [
    'active',
    'status' => 1,
];
```

Scopes are matched by checking if the model has a `scope{Name}` or `{name}` method. Unmatched scopes are silently ignored.

## Eager Loading

```php
// String relations
protected array $with = ['user', 'tags'];

// Nested relations
protected array $with = ['user.profile', 'tags.posts'];

// Constrained eager loads
protected array $with = [
    'comments' => function ($query) {
        $query->where('approved', true)->latest();
    },
];
```

## Aggregates

```php
// Simple: withAggregate('relation', 'column')
protected array $withAggregate = [
    'comments' => 'id',
];

// With function: withAggregate('relation', 'column', 'function')
protected array $withAggregate = [
    'ratings' => ['score', 'avg'],
];
```

## Pagination Types

```php
use Anil\FastApiCrud\Enums\PaginationType;

protected PaginationType $paginationType = PaginationType::LengthAware; // Default
protected PaginationType $paginationType = PaginationType::Simple;      // No total count
protected PaginationType $paginationType = PaginationType::Cursor;      // Cursor-based
protected PaginationType $paginationType = PaginationType::None;        // No pagination (all records)
```

## Force Delete

```php
// Soft delete (default) — uses $model->delete()
protected bool $forceDelete = false;

// Permanent delete — uses $model->forceDelete()
protected bool $forceDelete = true;
```

This affects both `destroy()` (single) and `delete()` (bulk) methods.

## Example: Fully Configured Controller

```php
class PostController extends BaseController
{
    protected PaginationType $paginationType = PaginationType::LengthAware;

    protected array $with = ['user', 'tags'];
    protected array $withCount = ['comments'];
    protected array $scopes = ['active'];

    protected array $load = ['user', 'tags', 'comments.user'];
    protected array $loadCount = ['comments', 'tags'];

    protected array $deleteScopes = ['active'];
    protected array $updateScopes = ['active'];

    protected bool $forceDelete = false;

    public function __construct()
    {
        parent::__construct(
            model: Post::class,
            storeRequest: StorePostRequest::class,
            updateRequest: UpdatePostRequest::class,
            resource: PostResource::class,
        );
    }
}
```
