# Fast API CRUD for Laravel

A powerful Laravel package that provides full-featured CRUD operations for your API with minimal boilerplate. Supports pagination (length-aware, simple, cursor), filtering, sorting, search, soft deletes, Spatie permissions, lifecycle hooks, and much more.

**Supports:** Laravel 11, 12, 13 | PHP 8.2+

## Installation

```bash
composer require anil/fast-api-crud
```

Publish the config file (optional):

```bash
php artisan vendor:publish --provider="Anil\FastApiCrud\FastApiCrudServiceProvider" --tag=config
```

### Spatie Permission Setup

If you want automatic permission middleware, install and configure [spatie/laravel-permission](https://github.com/spatie/laravel-permission) and register middleware aliases:

```php
// bootstrap/app.php (Laravel 11+)
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);
})
```

## Quick Start

### 1. Generate Scaffold

```bash
php artisan fast-api:make-all Post
# Or multiple: php artisan fast-api:make-all Post,Tag,User
```

This generates: Model, Migration, Factory, Seeder, Controller (extending BaseController), Resource, Store & Update Requests.

### 2. Create Your Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Anil\FastApiCrud\Http\Controllers\BaseController;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\Post\PostResource;
use App\Models\Post;

class PostController extends BaseController
{
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

### 3. Register Routes

```php
use App\Http\Controllers\PostController;

Route::controller(PostController::class)->prefix('posts')->group(function () {
    Route::get('', 'index');
    Route::post('', 'store');
    Route::get('{id}', 'show');
    Route::put('{id}', 'update');
    Route::delete('{id}', 'destroy');

    // Bulk delete
    Route::post('delete', 'delete');

    // Status & column operations
    Route::put('{id}/status-change', 'changeStatus');
    Route::put('{id}/status-change/{column}', 'changeStatus');
    Route::put('{id}/update-column/{column}', 'updateColumn');

    // Soft delete operations
    Route::put('{id}/restore', 'restore');
    Route::post('restore-all', 'restoreAll');
    Route::delete('{id}/permanent-delete', 'permanentDelete');
});
```

### 4. API Usage

```
GET    /posts?rowsPerPage=10&sortBy=created_at&descending=true
GET    /posts?filters={"status":1}&search=hello
GET    /posts?rowsPerPage=0              # All records (if allow_all is enabled)
POST   /posts                            # Create
GET    /posts/{id}                       # Show
PUT    /posts/{id}                       # Update
DELETE /posts/{id}                       # Delete (soft or hard)
POST   /posts/delete                     # Bulk delete: { "delete_rows": [1, 2, 3] }
PUT    /posts/{id}/status-change         # Toggle status column (0/1)
PUT    /posts/{id}/restore               # Restore soft-deleted
DELETE /posts/{id}/permanent-delete      # Force delete soft-deleted
```

## BaseController Properties

Override these in your child controller to customize behavior:

```php
class PostController extends BaseController
{
    // Pagination strategy: LengthAware, Simple, Cursor, None
    protected PaginationType $paginationType = PaginationType::LengthAware;

    // Scopes for index query
    protected array $scopes = ['active', 'published' => true];

    // Scopes for show query
    protected array $loadScopes = [];

    // Eager load relationships (index)
    protected array $with = ['author', 'tags'];

    // Count relationships (index)
    protected array $withCount = ['comments'];

    // Aggregate functions (index)
    protected array $withAggregate = ['comments' => 'id'];

    // Eager load relationships (show)
    protected array $load = ['author', 'comments.user'];

    // Count relationships (show)
    protected array $loadCount = ['comments'];

    // Aggregate functions (show)
    protected array $loadAggregate = [];

    // Force permanent deletion
    protected bool $forceDelete = false;

    // Scopes for specific operations
    protected array $deleteScopes = [];
    protected array $updateScopes = [];
    protected array $columnScopes = [];
    protected array $restoreScopes = [];
}
```

## Contracts (Interfaces)

### HasPermissionSlug

Implement on your model to enable automatic Spatie permission middleware:

```php
use Anil\FastApiCrud\Contracts\HasPermissionSlug;

class Post extends Model implements HasPermissionSlug
{
    public function getPermissionSlug(): string
    {
        return 'posts';
    }
}
```

This auto-registers middleware: `view-posts`, `store-posts`, `update-posts`, `delete-posts`, `change-status-posts`, `restore-posts`.

### Searchable

Implement on your model to enable automatic search via `?search=term`:

```php
use Anil\FastApiCrud\Contracts\Searchable;

class Post extends Model implements Searchable
{
    public function searchableColumns(): array
    {
        return ['title', 'content', 'author:name,email'];
    }
}
```

Supports relation columns using colon syntax: `'relation:column1,column2'`.

### Sortable

Implement on your model to provide default sorting:

```php
use Anil\FastApiCrud\Contracts\Sortable;

class Post extends Model implements Sortable
{
    public function sortByDefaults(): array
    {
        return ['sortBy' => 'created_at', 'sortByDesc' => true];
    }
}
```

## Pagination Types

```php
use Anil\FastApiCrud\Enums\PaginationType;

class PostController extends BaseController
{
    protected PaginationType $paginationType = PaginationType::LengthAware; // default
    // PaginationType::Simple       - Simple pagination (no total count)
    // PaginationType::Cursor       - Cursor-based pagination (best for large datasets)
    // PaginationType::None         - No pagination, returns all records
}
```

## Lifecycle Hooks

Override in your controller or define on the model:

```php
class PostController extends BaseController
{
    protected function beforeCreate(Model $model): void
    {
        // Runs before model is saved (model is filled but not yet persisted)
    }

    protected function afterCreate(Model $model): void
    {
        // Runs after model is saved
        $model->tags()->sync(request()->input('tags', []));
    }

    protected function beforeUpdate(Model $model): void { }
    protected function afterUpdate(Model $model): void { }
    protected function beforeDelete(Model $model): void { }
    protected function afterDelete(Model $model): void { }
    protected function beforeStatusChange(Model $model): void { }
    protected function afterStatusChange(Model $model): void { }
    protected function beforeColumnUpdate(Model $model): void { }
    protected function afterColumnUpdate(Model $model): void { }
    protected function beforeRestore(Model $model): void { }
    protected function afterRestore(Model $model): void { }
    protected function beforeForceDelete(Model $model): void { }
    protected function afterForceDelete(Model $model): void { }
}
```

Or define hooks directly on the model:

```php
class Post extends Model
{
    public function afterCreate(): void
    {
        // Will be called automatically by BaseController
    }
}
```

## Model Traits

### HasDateScopes

Pre-built date range filtering scopes:

```php
use Anil\FastApiCrud\Concerns\HasDateScopes;

class Post extends Model
{
    use HasDateScopes;
}

// Available scopes:
Post::query()->today();              // Records from today
Post::query()->yesterday();          // Records from yesterday
Post::query()->thisWeek();           // Records from this week
Post::query()->lastWeek();           // Records from last week
Post::query()->monthToDate();        // First of month to now
Post::query()->thisMonth();          // Entire current month
Post::query()->lastMonth();          // Entire previous month
Post::query()->quarterToDate();      // First of quarter to now
Post::query()->lastQuarter();        // Previous quarter
Post::query()->yearToDate();         // First of year to now
Post::query()->last7Days();          // Last 7 days
Post::query()->last30Days();         // Last 30 days
Post::query()->lastYear();           // Last 12 months
Post::query()->date('2025-01-01 to 2025-01-31'); // Custom range

// All scopes accept a custom column:
Post::query()->today('published_at');
```

### HasUuid

Automatic UUID v4 primary keys:

```php
use Anil\FastApiCrud\Concerns\HasUuid;

class Post extends Model
{
    use HasUuid;
}
```

### HandlesDeleteEvents

Anonymizes unique column values on soft delete to prevent constraint violations:

```php
use Anil\FastApiCrud\Concerns\HandlesDeleteEvents;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use SoftDeletes, HandlesDeleteEvents;
}
```

### HasReplicatesWithRelation

Deep clone models with all loaded relationships:

```php
use Anil\FastApiCrud\Concerns\HasReplicatesWithRelation;

class Post extends Model
{
    use HasReplicatesWithRelation;
}

$post = Post::with(['comments', 'tags'])->find(1);
$clone = $post->replicateWithRelations();
```

Supports: BelongsTo, HasOne, HasMany, BelongsToMany, MorphOne, MorphMany, MorphTo, MorphToMany.

## Builder Macros

These are registered automatically and available on all Eloquent queries:

### likeWhere

```php
Post::query()->likeWhere(['title', 'content', 'author:name'], 'search term');
```

### initializer

Applies request-based filters and sorting:

```php
Post::query()->initializer();
// Reads: ?filters={"status":1,"category":"tech"}&sortBy=created_at&descending=true
```

### withAggregates

```php
Post::query()->withAggregates([
    'comments' => 'id',                    // COUNT by default
    'orders' => ['amount', 'sum'],         // SUM
]);
```

### withCountWhereHas / orWithCountWhereHas

```php
Post::query()->withCountWhereHas('comments', fn ($q) => $q->where('approved', true));
```

### paginates / simplePaginates / cursorPaginates

```php
Post::query()->paginates();         // Length-aware pagination
Post::query()->simplePaginates();   // Simple pagination
Post::query()->cursorPaginates();   // Cursor pagination
```

All read `rowsPerPage` from the request and respect config limits.

## Collection Macro

### paginate

Paginate an in-memory collection:

```php
$collection = collect($items);
$paginated = $collection->paginate(perPage: 15);
```

## ApiResponder Trait

Use standalone in any controller:

```php
use Anil\FastApiCrud\Concerns\ApiResponder;

class MyController extends Controller
{
    use ApiResponder;

    public function myAction()
    {
        return $this->ok(['key' => 'value']);        // 200
        return $this->created(['id' => 1]);          // 201
        return $this->noContent();                   // 204
        return $this->badRequest('Invalid input');   // 400
        return $this->unauthorized();                // 401
        return $this->forbidden();                   // 403
        return $this->notFound('Post not found');    // 404
        return $this->unprocessableContent();        // 422
        return $this->tooManyRequests();             // 429
        return $this->internalServerError();         // 500

        // Generic
        return $this->success($data, 200);
        return $this->error('message', $errors, 400);
    }
}
```

## Helper Functions

Available globally after installation:

| Function | Description |
|----------|-------------|
| `parseTimeToSeconds('01:30:00')` | Parse time string to seconds |
| `formatDuration(3661)` | Format seconds: "1h 1m 1s" |
| `diffForHumans($date)` | "2 hours ago" |
| `ymdDate($date)` | Format as Y-m-d |
| `dateForReports($date)` | Format as Y-m-d H:i |
| `toFormattedDateString($date)` | "January 1, 2025" |
| `toDateString($date)` | Y-m-d |
| `toDateTimeString($date)` | Y-m-d H:i:s |
| `toTimeString($date)` | H:i:s |
| `filterValue('key')` | Get value from ?filters JSON |
| `arrayFilters($data)` | Decode/filter JSON or array |
| `flattenArray($data)` | Flatten nested array |
| `sortDirection()` | ASC/DESC from ?descending |
| `sortBy()` | Sort key(s) from ?sort |
| `tableColumns('users')` | Ordered column list |
| `fillableCsv(Model::class)` | Fillable as CSV |
| `columnsCsv(Model::class)` | Columns as CSV |
| `scopeMethods($model)` | List scope methods |
| `databaseClasses()` | Find classes in database/ |
| `appClasses()` | Find classes in app/ |
| `uuid()` | Generate UUID v4 |
| `slug('Hello World')` | URL slug |
| `classShortName(Class::class)` | Short class name |
| `relativePath($path)` | Relative from project root |

## Configuration

```php
// config/fast-api.php

return [
    'pagination' => [
        'default_per_page' => 15,        // Default items per page
        'max_per_page' => 100,           // Maximum allowed per page
        'allow_all' => true,             // Allow rowsPerPage=0 for all records
    ],

    'soft_delete' => [
        'anonymize_unique_columns' => true,  // Append _{timestamp} on soft delete
    ],

    'response' => [
        'success_key' => 'data',         // JSON key for success data
        'error_key' => 'errors',         // JSON key for error details
        'message_key' => 'message',      // JSON key for error message
    ],

    'permissions' => [
        'enabled' => true,               // Auto-register Spatie middleware
    ],
];
```

## Artisan Commands

```bash
# Generate complete scaffold for one or more models
php artisan fast-api:make-all Post
php artisan fast-api:make-all Post,Tag,User
```

## License

MIT
