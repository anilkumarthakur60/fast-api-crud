# Fast API CRUD for Laravel

A powerful Laravel package that provides full-featured CRUD operations with minimal boilerplate. Works for both **API** (JSON responses) and **Web** (Blade views) controllers out of the box.

Supports pagination (length-aware, simple, cursor), filtering, sorting, search, soft deletes, Spatie permissions, lifecycle hooks, and much more.

**Supports:** Laravel 11, 12, 13 | PHP 8.2+

**Requires:** [spatie/laravel-permission](https://github.com/spatie/laravel-permission) ^6.0 or ^7.0

---

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [API Controller](#api-controller-basecontroller)
- [Web Controller](#web-controller-basewebcontroller)
- [Scaffolding Command](#scaffolding-command)
- [Query Parameters](#query-parameters)
- [Controller Properties](#controller-properties)
- [Lifecycle Hooks](#lifecycle-hooks)
- [Contracts](#contracts)
- [Model Traits](#model-traits)
- [Builder Macros](#builder-macros)
- [Collection Macros](#collection-macros)
- [API Responder](#api-responder)
- [Helper Functions](#helper-functions)
- [Exceptions](#exceptions)
- [Permissions](#permissions)
- [Routes](#routes)
- [Pagination Utility](#pagination-utility)
- [Enums](#enums)
- [Full Example](#full-example)

---

## Installation

```bash
composer require anil/fast-api-crud
```

Publish the config file (optional):

```bash
php artisan vendor:publish --provider="Anil\FastApiCrud\FastApiCrudServiceProvider" --tag=config
```

---

## Quick Start

### 1. Generate everything with one command

```bash
php artisan fast-api:make-all Post
```

This creates: Model, Migration, Factory, Seeder, Controller, Resource, Store/Update Requests.

### 2. The generated controller — zero boilerplate

```php
// app/Http/Controllers/PostController.php

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

### 3. Register routes

```php
// routes/api.php

use App\Http\Controllers\PostController;

Route::get('posts', [PostController::class, 'index']);
Route::post('posts', [PostController::class, 'store']);
Route::get('posts/{id}', [PostController::class, 'show']);
Route::put('posts/{id}', [PostController::class, 'update']);
Route::delete('posts/{id}', [PostController::class, 'destroy']);
```

### 4. That's it! You now have a fully working CRUD API

```
GET    /posts              → Paginated list with filtering, sorting, search
GET    /posts/1            → Single resource
POST   /posts              → Create (validates via StorePostRequest)
PUT    /posts/1            → Update (validates via UpdatePostRequest)
DELETE /posts/1            → Soft delete (or force delete)
```

---

## Configuration

File: `config/fast-api.php`

```php
return [
    // Pagination
    'pagination' => [
        'default_per_page' => 15,    // Default records per page
        'max_per_page'     => 100,   // Maximum allowed per page
        'allow_all'        => true,  // Allow rowsPerPage=0 to fetch all
    ],

    // Soft Delete
    'soft_delete' => [
        'anonymize_unique_columns' => true,  // Append _{timestamp} to unique columns on delete
    ],

    // Response envelope keys
    'response' => [
        'success_key' => 'data',
        'error_key'   => 'errors',
        'message_key' => 'message',
    ],

    // Spatie permissions
    'permissions' => [
        'enabled' => true,
    ],

    // Web (Blade) flash message keys
    'web' => [
        'flash_key_success' => 'success',
        'flash_key_error'   => 'error',
    ],
];
```

---

## API Controller (BaseController)

For JSON API endpoints. Returns `JsonResponse`, `JsonResource`, and `AnonymousResourceCollection`.

### Constructor

```php
use Anil\FastApiCrud\Http\Controllers\BaseController;

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

### Available Methods

| Method | HTTP | Return Type | Description |
|--------|------|-------------|-------------|
| `index()` | GET | `AnonymousResourceCollection` | Paginated list |
| `show($id)` | GET | `JsonResource` | Single resource |
| `store()` | POST | `JsonResponse` (201) | Create resource |
| `update($id)` | PUT | `JsonResource\|JsonResponse` | Update resource |
| `destroy($id)` | DELETE | `JsonResponse` (204) | Delete resource |
| `delete()` | POST | `JsonResponse` (204) | Bulk delete via `delete_rows` array |
| `changeStatus($id, $column = 'status')` | PUT | `JsonResource\|JsonResponse` | Toggle boolean column (0/1) |
| `updateColumn($id, $column = 'status')` | PUT | `JsonResource\|JsonResponse` | Update specific fillable column |
| `restore($id)` | PUT | `JsonResource\|JsonResponse` | Restore soft-deleted |
| `restoreAll()` | POST | `JsonResponse` (204) | Restore all trashed |
| `permanentDelete($id)` | POST | `JsonResponse` (204) | Force delete trashed |

Methods returning `JsonResource|JsonResponse` return `JsonResponse` when an exception occurs during the operation.

### Example Responses

**GET /posts** (index):

```json
{
  "data": [
    {
      "id": 1,
      "name": "First Post",
      "status": 1,
      "created_at": "2025-01-15T10:30:00.000000Z"
    },
    {
      "id": 2,
      "name": "Second Post",
      "status": 1,
      "created_at": "2025-01-16T14:00:00.000000Z"
    }
  ],
  "links": {
    "first": "http://example.com/posts?page=1",
    "last": "http://example.com/posts?page=5",
    "prev": null,
    "next": "http://example.com/posts?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 68
  }
}
```

**GET /posts/1** (show):

```json
{
  "data": {
    "id": 1,
    "name": "First Post",
    "desc": "Post description",
    "status": 1,
    "active": 1,
    "created_at": "2025-01-15T10:30:00.000000Z",
    "updated_at": "2025-01-15T10:30:00.000000Z"
  }
}
```

**POST /posts** (store — 201 Created):

```json
{
  "data": {
    "id": 3,
    "name": "New Post",
    "desc": "Description",
    "status": 1,
    "active": 1,
    "created_at": "2025-01-17T09:00:00.000000Z",
    "updated_at": "2025-01-17T09:00:00.000000Z"
  }
}
```

**DELETE /posts/1** (destroy — 204):

```json
{
  "data": []
}
```

**Error response** (400):

```json
{
  "errors": [],
  "message": "Something went wrong"
}
```

**Validation error** (422 — handled by Laravel):

```json
{
  "message": "The name field is required.",
  "errors": {
    "name": ["The name field is required."]
  }
}
```

---

## Web Controller (BaseWebController)

For Blade/web applications. Returns `View` and `RedirectResponse` with flash messages.

### Constructor

```php
use Anil\FastApiCrud\Http\Controllers\BaseWebController;

class PostController extends BaseWebController
{
    public function __construct()
    {
        parent::__construct(
            model: Post::class,
            storeRequest: StorePostRequest::class,
            updateRequest: UpdatePostRequest::class,
            viewPrefix: 'admin.posts',     // views: admin.posts.index, admin.posts.create, etc.
            routePrefix: 'admin.posts',    // redirects: route('admin.posts.index')
            resourceName: 'post',          // $post variable in views
            collectionName: 'posts',       // $posts variable in index view
            resource: PostResource::class, // Optional — for data transformation before views
        );
    }
}
```

### Available Methods

| Method | HTTP | Return | Description |
|--------|------|--------|-------------|
| `index()` | GET | `View` | List page (`{viewPrefix}.index`) |
| `create()` | GET | `View` | Create form (`{viewPrefix}.create`) |
| `store()` | POST | `RedirectResponse` | Create, redirect with flash |
| `show($id)` | GET | `View` | Detail page (`{viewPrefix}.show`) |
| `edit($id)` | GET | `View` | Edit form (`{viewPrefix}.edit`) |
| `update($id)` | PUT | `RedirectResponse` | Update, redirect with flash |
| `destroy($id)` | DELETE | `RedirectResponse` | Delete, redirect with flash |
| `delete()` | POST | `RedirectResponse` | Bulk delete, redirect |
| `changeStatus($id)` | PUT | `RedirectResponse` | Toggle status, redirect |
| `restore($id)` | PUT | `RedirectResponse` | Restore, redirect |
| `restoreAll()` | POST | `RedirectResponse` | Restore all, redirect |
| `permanentDelete($id)` | POST | `RedirectResponse` | Force delete, redirect |

### View Variables

- **index**: `$posts` (or your `$collectionName`) — paginated collection
- **show/edit**: `$post` (or your `$resourceName`) — single model instance

### Customizing Flash Messages

Override any of the message methods in your controller. All return `string` and work with Laravel's `__()` translation helper:

```php
class PostController extends BaseWebController
{
    protected function storeSuccessMessage(): string       { return __('Post created!'); }
    protected function updateSuccessMessage(): string      { return __('Post updated!'); }
    protected function destroySuccessMessage(): string     { return __('Post deleted.'); }
    protected function bulkDeleteSuccessMessage(): string  { return __('Posts deleted.'); }
    protected function statusChangeSuccessMessage(): string    { return __('Status updated.'); }
    protected function columnUpdateSuccessMessage(): string    { return __('Column updated.'); }
    protected function restoreSuccessMessage(): string         { return __('Post restored.'); }
    protected function restoreAllSuccessMessage(): string      { return __('All posts restored.'); }
    protected function permanentDeleteSuccessMessage(): string { return __('Post permanently deleted.'); }
}
```

Flash messages use session keys from config (`fast-api.web.flash_key_success` and `fast-api.web.flash_key_error`).

### Overriding Views

Override any method to pass extra data to views:

```php
class PostController extends BaseWebController
{
    public function create(): \Illuminate\View\View
    {
        return view($this->viewName('create'), [
            'categories' => Category::all(),
            'tags' => Tag::all(),
        ]);
    }

    public function edit(int|string $id): \Illuminate\View\View
    {
        $query = $this->buildShowQuery();

        return view($this->viewName('edit'), [
            $this->resourceName => $query->findOrFail($id),
            'categories' => Category::all(),
        ]);
    }
}
```

### Blade View Example

```blade
{{-- resources/views/admin/posts/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <a href="{{ route('admin.posts.create') }}">Create Post</a>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($posts as $post)
                <tr>
                    <td>{{ $post->id }}</td>
                    <td>{{ $post->name }}</td>
                    <td>
                        <a href="{{ route('admin.posts.show', $post) }}">View</a>
                        <a href="{{ route('admin.posts.edit', $post) }}">Edit</a>
                        <form action="{{ route('admin.posts.destroy', $post) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $posts->links() }}
</div>
@endsection
```

### Web Routes

```php
// routes/web.php

use App\Http\Controllers\PostController;

Route::resource('admin/posts', PostController::class);

// Additional routes for extended operations
Route::put('admin/posts/{id}/status-change', [PostController::class, 'changeStatus']);
Route::put('admin/posts/{id}/restore', [PostController::class, 'restore']);
Route::post('admin/posts/restore-all', [PostController::class, 'restoreAll']);
Route::post('admin/posts/{id}/permanent-delete', [PostController::class, 'permanentDelete']);
```

---

## Scaffolding Command

```bash
# Generate API controller + resources
php artisan fast-api:make-all Post

# Generate Web controller + Blade views
php artisan fast-api:make-all Post --web

# Multiple models at once
php artisan fast-api:make-all Post,Tag,Category

# Multiple models with web
php artisan fast-api:make-all Post,Tag,Category --web
```

**Generated files per model:**

| File | Path |
|------|------|
| Model | `app/Models/Post.php` |
| Migration | `database/migrations/create_posts_table.php` |
| Factory | `database/factories/PostFactory.php` |
| Seeder | `database/seeders/PostSeeder.php` |
| Resource | `app/Http/Resources/Post/PostResource.php` |
| Store Request | `app/Http/Requests/Post/StorePostRequest.php` |
| Update Request | `app/Http/Requests/Post/UpdatePostRequest.php` |
| Controller | `app/Http/Controllers/PostController.php` |
| Views (--web) | `resources/views/posts/index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php` |

---

## Query Parameters

The package reads these query parameters automatically:

### Filtering

```
GET /posts?filters={"active":1,"status":1,"queryFilter":"search term"}
```

The `filters` parameter accepts a JSON object. Each key is matched against the model's scopes:
- `active` calls `scopeActive(1)` on the model
- `queryFilter` calls `scopeQueryFilter("search term")`

### Sorting

```
GET /posts?sortBy=created_at&descending=true
```

- `sortBy` — column name to sort by (default: `id`)
- `descending` — `true` for DESC, `false` for ASC (default: `true`)

If the model implements `Sortable`, its defaults are used when no sort params are provided.

### Pagination

```
GET /posts?rowsPerPage=25
GET /posts?rowsPerPage=0     # Returns all records (if allow_all is true)
```

- `rowsPerPage` — records per page (default: 15, max: 100)

### Search

```
GET /posts?search=laravel
```

If the model implements the `Searchable` interface, performs a LIKE search across the columns returned by `searchableColumns()`.

### Combined Example

```
GET /posts?filters={"active":1}&sortBy=name&descending=false&rowsPerPage=20&search=laravel
```

---

## Controller Properties

Customize behavior by setting properties in your controller:

```php
class PostController extends BaseController
{
    // Pagination
    protected PaginationType $paginationType = PaginationType::LengthAware;
    // Options: LengthAware, Simple, Cursor, None

    // Index query customization
    protected array $scopes = ['active'];                    // Apply scopes to index
    protected array $with = ['user', 'tags'];                // Eager load in index
    protected array $withCount = ['comments'];               // Count relations in index
    protected array $withAggregate = ['ratings' => 'score']; // Aggregates in index

    // Show query customization
    protected array $loadScopes = [];                        // Scopes for show
    protected array $load = ['user', 'tags', 'comments'];   // Eager load in show
    protected array $loadCount = ['comments'];               // Count in show
    protected array $loadAggregate = [];                     // Aggregates in show

    // Operation scopes
    protected array $deleteScopes = [];     // Scopes when finding record for delete
    protected array $updateScopes = [];     // Scopes when finding record for update
    protected array $columnScopes = [];     // Scopes for changeStatus/updateColumn
    protected array $restoreScopes = [];    // Scopes for restore operations

    // Delete behavior
    protected bool $forceDelete = false;    // true = permanent delete, false = soft delete

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

### Scopes with Parameters

```php
// Simple scopes (no parameters)
protected array $scopes = ['active', 'published'];

// Parameterized scopes
protected array $scopes = [
    'status' => 1,
    'active' => 1,
    'type'   => 'article',
];

// Array parameters
protected array $scopes = [
    'statusIn' => [1, 2, 3],
];

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

All scope properties (`$scopes`, `$loadScopes`, `$deleteScopes`, `$updateScopes`, `$columnScopes`, `$restoreScopes`) support the same syntax.

---

## Lifecycle Hooks

Define methods on your **model** to hook into CRUD operations. These are called automatically by the controller.

```php
class Post extends Model
{
    // Called before/after store()
    public function beforeCreate(): void
    {
        $this->slug = Str::slug($this->name);
    }

    public function afterCreate(): void
    {
        // Sync relations from request
        if (request()->filled('tag_ids')) {
            $this->tags()->sync(request()->input('tag_ids'));
        }
    }

    // Called before/after update()
    public function beforeUpdate(): void { }
    public function afterUpdate(): void { }

    // Called before/after destroy() and delete()
    public function beforeDelete(): void { }
    public function afterDelete(): void { }

    // Called before/after changeStatus()
    public function beforeStatusChange(): void { }
    public function afterStatusChange(): void { }

    // Called before/after updateColumn()
    public function beforeColumnUpdate(): void { }
    public function afterColumnUpdate(): void { }

    // Called before/after restore()
    public function beforeRestore(): void { }
    public function afterRestore(): void { }

    // Called before/after permanentDelete()
    public function beforeForceDelete(): void { }
    public function afterForceDelete(): void { }
}
```

You can also override hooks in the **controller**:

```php
class PostController extends BaseController
{
    protected function afterCreate(Model $model): void
    {
        // Controller-level hook overrides model hook
        Notification::send($admins, new PostCreated($model));
    }
}
```

---

## Contracts

### Searchable

Enables automatic LIKE search on `?search=` query parameter.

```php
use Anil\FastApiCrud\Contracts\Searchable;

class Post extends Model implements Searchable
{
    public function searchableColumns(): array
    {
        return [
            'name',
            'desc',
            'user:name,email',  // Search in related model columns
        ];
    }
}
```

**Request:** `GET /posts?search=laravel`

Generates: `WHERE (name LIKE '%laravel%' OR desc LIKE '%laravel%' OR EXISTS (SELECT ... FROM users WHERE name LIKE '%laravel%' OR email LIKE '%laravel%'))`

### Sortable

Provides default sort configuration when no `sortBy` query parameter is given.

```php
use Anil\FastApiCrud\Contracts\Sortable;

class Post extends Model implements Sortable
{
    public function sortByDefaults(): array
    {
        return [
            'sortBy'     => 'created_at',
            'sortByDesc' => true,
        ];
    }
}
```

### HasPermissionSlug

Enables automatic Spatie permission middleware registration.

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

This automatically registers middleware:

| Action | Permission | Routes |
|--------|-----------|--------|
| View | `view-posts` | index, show |
| Store | `store-posts` | store |
| Update | `update-posts` | update, updateColumn |
| Delete | `delete-posts` | destroy, delete, permanentDelete |
| Change Status | `change-status-posts` | changeStatus |
| Restore | `restore-posts` | restore, restoreAll |

---

## Model Traits

### HasDateScopes

Adds query scopes for common date ranges. All accept an optional `$column` parameter (default: `created_at`).

```php
use Anil\FastApiCrud\Concerns\HasDateScopes;

class Post extends Model
{
    use HasDateScopes;
}
```

**Available scopes:**

```php
Post::query()->today();                // Records from today
Post::query()->yesterday();            // Records from yesterday
Post::query()->thisWeek();             // Monday to now
Post::query()->lastWeek();             // Last Monday to Sunday
Post::query()->monthToDate();          // 1st of month to now
Post::query()->thisMonth();            // Entire current month
Post::query()->lastMonth();            // Entire previous month
Post::query()->quarterToDate();        // Start of quarter to now
Post::query()->lastQuarter();          // Previous quarter
Post::query()->yearToDate();           // January 1 to now
Post::query()->lastYear();             // Last 12 months
Post::query()->last7Days();            // Last 7 days
Post::query()->last30Days();           // Last 30 days
Post::query()->date('2025-01-01 to 2025-01-31');  // Custom range

// Use a different column
Post::query()->today('published_at');
Post::query()->lastMonth('updated_at');
```

### HasUuidPrimaryKey

Automatically assigns UUID v4 as primary key on model creation.

```php
use Anil\FastApiCrud\Concerns\HasUuidPrimaryKey;

class Post extends Model
{
    use HasUuidPrimaryKey;
}
```

- Sets `incrementing` to `false`
- Sets `keyType` to `string`
- Auto-generates UUID on creation if key is empty

### AnonymizesOnDelete

Anonymizes unique column values on soft delete to prevent constraint violations.

```php
use Anil\FastApiCrud\Concerns\AnonymizesOnDelete;

class User extends Model
{
    use SoftDeletes, AnonymizesOnDelete;
}
```

When soft-deleted, unique columns get `_{timestamp}` appended:

```
email: john@example.com → john@example.com_1705312800
```

This prevents conflicts when creating a new user with `john@example.com` while the old record is soft-deleted. Controlled by `fast-api.soft_delete.anonymize_unique_columns` config.

### ReplicatesWithRelations

Replicate a model along with all its loaded relations.

```php
use Anil\FastApiCrud\Concerns\ReplicatesWithRelations;

class Post extends Model
{
    use ReplicatesWithRelations;
}

// Usage
$post = Post::with(['tags', 'comments', 'author'])->find(1);
$clone = $post->replicateWithRelations();
// $clone is a saved copy with all relations duplicated
```

**Supported relations:** BelongsTo, MorphTo, HasOne, MorphOne, HasMany, MorphMany, BelongsToMany, MorphToMany

**Not supported:** HasOneThrough and HasManyThrough relations will throw an `Exception` during replication.

The trait also re-applies castable attributes (numeric, boolean, string, json) to the replicated model to ensure proper type handling.

---

## Builder Macros

These macros are registered on `Illuminate\Database\Eloquent\Builder` and available on all queries.

### initializer

```php
->initializer(bool $orderBy = true): Builder
```

Apply request-based filters, sorting, and scopes automatically.

```php
$query = Post::query()->initializer();

// With orderBy disabled
$query = Post::query()->initializer(orderBy: false);
```

**How it works:**
1. Reads `?filters={"scope":"value"}` — decodes JSON, calls matching model scopes (uses `Str::studly` to find `scope{Name}` methods)
2. Reads `?sortBy=column&descending=true` — applies ordering
3. If model implements `Sortable` and no sort params given, uses `sortByDefaults()`
4. Default sort: `id` descending

### likeWhere

```php
->likeWhere(array $attributes, ?string $searchTerm = null): Builder
```

Multi-column LIKE search with relation support. Returns the query unmodified if `$searchTerm` is null or empty.

```php
// Simple columns
Post::query()->likeWhere(['name', 'desc'], 'laravel');

// With relation columns (colon syntax: 'relation:column1,column2')
Post::query()->likeWhere(['name', 'user:name,email', 'tags:name'], 'search');

// Null search returns query unchanged
Post::query()->likeWhere(['name'], null);  // No-op
```

### paginates

```php
->paginates(array $columns = ['*'], string $pageName = 'page', ?int $page = null): Paginator
```

Length-aware pagination using `rowsPerPage` request parameter.

```php
Post::query()->paginates();                              // Default columns
Post::query()->paginates(['id', 'name']);                 // Specific columns
Post::query()->paginates(['*'], 'p', 2);                 // Custom page name & page
```

- Respects `fast-api.pagination.max_per_page` (default: 100)
- When `rowsPerPage=0` and `fast-api.pagination.allow_all=true`, returns all records

### simplePaginates

```php
->simplePaginates(array $columns = ['*'], string $pageName = 'page', ?int $page = null): Paginator
```

Simple pagination (no total count) using `rowsPerPage` request parameter. Same behavior as `paginates()` but without total count query.

### cursorPaginates

```php
->cursorPaginates(array $columns = ['*'], ?string $cursorName = null, ?Cursor $cursor = null): CursorPaginator
```

Cursor-based pagination using `rowsPerPage` request parameter. Best for infinite scroll or large datasets.

### withAggregates

```php
->withAggregates(array $aggregates): Builder
```

Apply multiple aggregate functions in a single call.

```php
Post::query()->withAggregates([
    'comments' => 'id',                    // withAggregate('comments', 'id')
    'ratings'  => ['score', 'avg'],        // withAggregate('ratings', 'score', 'avg')
]);
```

### withCountWhereHas / orWithCountWhereHas

```php
->withCountWhereHas(string $relation, ?Closure $callback = null, string $operator = '>=', int $count = 1): Builder
->orWithCountWhereHas(string $relation, ?Closure $callback = null, string $operator = '>=', int $count = 1): Builder
```

Adds a conditional `withCount` that also filters results using `whereHas` (or `orWhereHas`).

```php
// Basic — posts that have at least 1 comment, with comment count
Post::query()->withCountWhereHas('comments');

// With callback — posts with approved comments
Post::query()->withCountWhereHas('comments', function ($q) {
    $q->where('approved', true);
});

// With operator/count — posts with 5+ comments
Post::query()->withCountWhereHas('comments', null, '>=', 5);

// OR variant — posts with comments OR tags
Post::query()
    ->withCountWhereHas('comments')
    ->orWithCountWhereHas('tags');
```

---

## Collection Macros

### paginate

```php
->paginate(int $perPage, ?int $total = null, ?int $page = null, string $pageName = 'page'): LengthAwarePaginator
```

Paginate an in-memory collection.

```php
$items = collect([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

$paginated = $items->paginate(perPage: 5);
// Page 1: [1, 2, 3, 4, 5] — auto-resolves current page from request

$paginated = $items->paginate(perPage: 3, page: 2);
// Page 2: [4, 5, 6]

$paginated = $items->paginate(perPage: 5, total: 100);
// Override total count (useful for pre-sliced data)
```

---

## API Responder

The `HasApiResponse` trait (used by `BaseController`) provides response helpers for every HTTP status code. You can also use it in any controller:

```php
use Anil\FastApiCrud\Concerns\HasApiResponse;

class MyController extends Controller
{
    use HasApiResponse;
}
```

### Core Methods

```php
// success(array $data = [], int $code = 200): JsonResponse
return $this->success(['key' => 'value']);          // {"data": {"key": "value"}}
return $this->success(['key' => 'value'], 201);    // Same with 201 status

// error(string $message = 'Something went wrong', array $data = [], int $status = 400): JsonResponse
return $this->error('Something went wrong');        // {"errors": [], "message": "Something went wrong"}
return $this->error('Not found', [], 404);          // Same with 404 status
```

The envelope keys (`data`, `errors`, `message`) are configurable via `fast-api.response.*` config.

### Complete Method Reference

All success methods accept `array $data = []`. All error methods accept `string $message` and `array $data = []`.

**1xx Informational:**

| Method | Status | Default Message |
|--------|--------|----------------|
| `continue()` | 100 | — |
| `switchingProtocols()` | 101 | — |
| `processing()` | 102 | — |
| `earlyHints()` | 103 | — |

**2xx Success:**

| Method | Status | Notes |
|--------|--------|-------|
| `ok()` | 200 | — |
| `created()` | 201 | — |
| `accepted()` | 202 | — |
| `nonAuthoritativeInformation()` | 203 | — |
| `noContent()` | 204 | Returns `null` body |
| `resetContent()` | 205 | — |
| `partialContent()` | 206 | — |
| `multiStatus()` | 207 | — |
| `alreadyReported()` | 208 | — |
| `imUsed()` | 226 | — |

**3xx Redirection:**

| Method | Status |
|--------|--------|
| `multipleChoices()` | 300 |
| `movedPermanently()` | 301 |
| `found()` | 302 |
| `seeOther()` | 303 |
| `notModified()` | 304 |
| `useProxy()` | 305 |
| `temporaryRedirect()` | 307 |
| `permanentRedirect()` | 308 |

**4xx Client Error:**

| Method | Status | Default Message |
|--------|--------|----------------|
| `badRequest()` | 400 | Bad Request |
| `unauthorized()` | 401 | Unauthorized |
| `paymentRequired()` | 402 | Payment Required |
| `forbidden()` | 403 | Forbidden |
| `notFound()` | 404 | Not Found |
| `methodNotAllowed()` | 405 | Method Not Allowed |
| `notAcceptable()` | 406 | Not Acceptable |
| `proxyAuthenticationRequired()` | 407 | Proxy Authentication Required |
| `requestTimeout()` | 408 | Request Timeout |
| `conflict()` | 409 | Conflict |
| `gone()` | 410 | Gone |
| `lengthRequired()` | 411 | Length Required |
| `preconditionFailed()` | 412 | Precondition Failed |
| `contentTooLarge()` | 413 | Content Too Large |
| `uriTooLong()` | 414 | URI Too Long |
| `unsupportedMediaType()` | 415 | Unsupported Media Type |
| `rangeNotSatisfiable()` | 416 | Range Not Satisfiable |
| `expectationFailed()` | 417 | Expectation Failed |
| `imATeapot()` | 418 | I'm a teapot |
| `misdirectedRequest()` | 421 | Misdirected Request |
| `unprocessableContent()` | 422 | Unprocessable Content |
| `locked()` | 423 | Locked |
| `failedDependency()` | 424 | Failed Dependency |
| `tooEarly()` | 425 | Too Early |
| `upgradeRequired()` | 426 | Upgrade Required |
| `preconditionRequired()` | 428 | Precondition Required |
| `tooManyRequests()` | 429 | Too Many Requests |
| `requestHeaderFieldsTooLarge()` | 431 | Request Header Fields Too Large |
| `unavailableForLegalReasons()` | 451 | Unavailable For Legal Reasons |

**5xx Server Error:**

| Method | Status | Default Message |
|--------|--------|----------------|
| `internalServerError()` | 500 | Internal Server Error |
| `notImplemented()` | 501 | Not Implemented |
| `badGateway()` | 502 | Bad Gateway |
| `serviceUnavailable()` | 503 | Service Unavailable |
| `gatewayTimeout()` | 504 | Gateway Timeout |
| `httpVersionNotSupported()` | 505 | HTTP Version Not Supported |
| `variantAlsoNegotiates()` | 506 | Variant Also Negotiates |
| `insufficientStorage()` | 507 | Insufficient Storage |
| `loopDetected()` | 508 | Loop Detected |
| `notExtended()` | 510 | Not Extended |
| `networkAuthenticationRequired()` | 511 | Network Authentication Required |

---

## Helper Functions

Global helper functions available throughout your application (autoloaded via composer).

### Date & Time

```php
diffForHumans(?string $date): ?string
// diffForHumans('2025-01-15')           → "2 months ago"
// diffForHumans(null)                   → null

ymdDate(?string $date, string $format = 'Y-m-d'): ?string
// ymdDate('2025-01-15 14:30:00')        → "2025-01-15"
// ymdDate('2025-01-15', 'd/m/Y')        → "15/01/2025"

dateForReports(?string $date, string $format = 'Y-m-d H:i'): ?string
// dateForReports('2025-01-15 14:30:00') → "2025-01-15 14:30"
// Returns null on invalid date (doesn't throw)

toFormattedDateString(?string $date): ?string
// toFormattedDateString('2025-01-15')   → "January 15, 2025"

toDateString(?string $date): ?string
// toDateString('2025-01-15 14:30:00')   → "2025-01-15"

toDateTimeString(?string $date): ?string
// toDateTimeString('2025-01-15 14:30')  → "2025-01-15 14:30:00"

toTimeString(?string $date): ?string
// toTimeString('2025-01-15 14:30:00')   → "14:30:00"
```

### Duration

```php
parseTimeToSeconds(string $timeString): int|float
// parseTimeToSeconds('01:30:00')        → 5400    (H:i:s format)
// parseTimeToSeconds('30:45')           → 1845    (i:s format)
// parseTimeToSeconds('120')             → 120     (plain seconds)

formatDuration(int|float|null $duration, ?string $format = '%y %mo %d %h %m %s', string $separator = ' '): string
// formatDuration(3661)                  → "1h 1m 1s"
// formatDuration(90061)                 → "1d 1h 1m 1s"
// formatDuration(3661, '%h %m')         → "1h 1m"
// formatDuration(3661, '%h %m', '-')    → "1h-1m"
// formatDuration(null)                  → "0s"
// formatDuration(0)                     → "0s"
// formatDuration(-3661)                 → "1h 1m 1s"  (absolute value)
// Placeholders: %y=years, %mo=months, %d=days, %h=hours, %m=minutes, %s=seconds
```

### Filtering & Sorting

```php
filterValue(string $key = 'date'): ?string
// Reads from ?filters={"date":"2025-01-15"} query parameter
// filterValue('date')                   → "2025-01-15"
// filterValue('missing')                → null

arrayFilters(array|string|null $data): array
// arrayFilters('{"active":1,"name":""}')→ ['active' => 1]  (removes falsy)
// arrayFilters(['a' => 1, 'b' => null]) → ['a' => 1]
// arrayFilters(null)                    → []

flattenArray(array $data, int $depth = 0): array
// flattenArray(['a' => [1, 2], 'b' => [3]])  → [1, 2, 3]
// flattenArray(['a' => [1, [2]]], 1)          → [1, [2]]

sortDirection(): string
// Reads ?descending query parameter
// ?descending=true  → "ASC"
// ?descending=false → "DESC"

sortBy(): array|string|null
// Reads ?sort query parameter
// ?sort=name        → "name"
// ?sort[]=name&sort[]=id → ["name", "id"]
// (not set)         → null
```

### Utility

```php
uuid(): UuidInterface
// uuid()                                → Ramsey\Uuid\UuidInterface (v4)

slug(?string $text = null): ?string
// slug('Hello World')                   → "hello-world"
// slug(null)                            → null

relativePath(string $path): string
// relativePath('/var/www/app/Models/Post.php') → "app/Models/Post.php"
// (relative to base_path())

classShortName(string $param): ?string
// classShortName(App\Models\Post::class) → "Post"
// Uses reflection, throws ReflectionException if unresolvable
```

### Introspection

```php
scopeMethods(object $class): array
// scopeMethods(new Post)                → ["scopeActive", "scopePublished", ...]
// Returns all public methods starting with "scope"

tableColumns(string|Model $table = 'users'): array
// tableColumns('posts')                 → ["id", "active", "desc", "name", ..., "created_at", "updated_at", "deleted_at"]
// tableColumns(Post::class)             → Same (accepts class-string)
// tableColumns(new Post)                → Same (accepts model instance)
// Columns sorted alphabetically, with id first and timestamps last

fillableCsv(string $model): string
// fillableCsv(Post::class)              → "name,desc,status,active"
// fillableCsv('posts')                  → All columns from table (if not a model class)

columnsCsv(string $model, string $separator = ','): string
// columnsCsv(Post::class)               → "id,active,desc,name,status,created_at,updated_at"
// columnsCsv(Post::class, '|')          → "id|active|desc|name|status|created_at|updated_at"
```

### Class Discovery

```php
appClasses(string $path = 'App', array $excluding = []): array
// appClasses('Models')                   → ["App\Models\Post", "App\Models\User", ...]
// appClasses('Models', [App\Models\User::class])  → ["App\Models\Post", ...]

databaseClasses(?string $directory = null, array $excluding = []): array
// databaseClasses('seeders')             → ["Database\Seeders\PostSeeder", ...]
// databaseClasses()                      → All classes in database/ directory
// databaseClasses('factories', ['Database\Factories\UserFactory']) → Filtered list
```

---

## Exceptions

### ApiException

Custom exception that renders as JSON with debug info in development.

```php
use Anil\FastApiCrud\Exceptions\ApiException;

throw new ApiException('Resource not found', 404);
```

**Production response:**

```json
{
  "error": {
    "message": "Resource not found"
  }
}
```

**Debug response** (when `APP_DEBUG=true`):

```json
{
  "error": {
    "message": "Resource not found",
    "file": "/app/Http/Controllers/PostController.php",
    "line": 42
  }
}
```

---

## Permissions

### Automatic Registration

If your model implements `HasPermissionSlug` and `fast-api.permissions.enabled` is `true`, permission middleware is registered automatically in the constructor.

### Static Middleware (Laravel 11+ style)

For the modern `HasMiddleware` interface:

```php
use Illuminate\Routing\Controllers\HasMiddleware;

class PostController extends BaseController implements HasMiddleware
{
    public static function middleware(): array
    {
        return self::permissionMiddleware('posts');
    }

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

---

## Routes

### Full API Route Setup

```php
use App\Http\Controllers\PostController;

// Standard CRUD
Route::get('posts', [PostController::class, 'index'])->name('posts.index');
Route::post('posts', [PostController::class, 'store'])->name('posts.store');
Route::get('posts/{id}', [PostController::class, 'show'])->name('posts.show');
Route::put('posts/{id}', [PostController::class, 'update'])->name('posts.update');
Route::delete('posts/{id}', [PostController::class, 'destroy'])->name('posts.destroy');

// Bulk delete
Route::post('posts/delete', [PostController::class, 'delete'])->name('posts.delete');

// Status & column
Route::put('posts/{id}/status-change', [PostController::class, 'changeStatus'])->name('posts.changeStatus');
Route::put('posts/{id}/status-change/{column}', [PostController::class, 'updateColumn'])->name('posts.updateColumn');

// Soft delete operations
Route::put('posts/{id}/restore', [PostController::class, 'restore'])->name('posts.restore');
Route::post('posts/restore-all', [PostController::class, 'restoreAll'])->name('posts.restoreAll');
Route::post('posts/{id}/force-delete', [PostController::class, 'permanentDelete'])->name('posts.permanentDelete');
```

### Full Web Route Setup

```php
use App\Http\Controllers\PostController;

// Standard resource routes (index, create, store, show, edit, update, destroy)
Route::resource('posts', PostController::class);

// Extended operations
Route::put('posts/{id}/status-change', [PostController::class, 'changeStatus'])->name('posts.changeStatus');
Route::put('posts/{id}/restore', [PostController::class, 'restore'])->name('posts.restore');
Route::post('posts/restore-all', [PostController::class, 'restoreAll'])->name('posts.restoreAll');
Route::post('posts/{id}/force-delete', [PostController::class, 'permanentDelete'])->name('posts.permanentDelete');
```

---

## Pagination Utility

The `Anil\FastApiCrud\Support\Pagination` class provides static helpers used internally by the macros. You can also use them directly:

```php
use Anil\FastApiCrud\Support\Pagination;

Pagination::defaultPerPage();       // 15 (from config)
Pagination::maxPerPage();           // 100 (from config)
Pagination::requestedPerPage(15);   // Value of ?rowsPerPage or default
Pagination::resolvePerPage();       // Effective per-page (respects max, allow_all)

// Generic config helpers
Pagination::configInt('fast-api.pagination.default_per_page', 15);   // int
Pagination::configBool('fast-api.pagination.allow_all', true);       // bool
```

---

## Enums

### PaginationType

```php
use Anil\FastApiCrud\Enums\PaginationType;

PaginationType::LengthAware  // 'length-aware' — Standard pagination with total count
PaginationType::Simple        // 'simple'       — Simple pagination without total
PaginationType::Cursor        // 'cursor'       — Cursor-based pagination
PaginationType::None          // 'none'         — No pagination, returns all records
```

### CrudAction

Used for permission middleware registration.

```php
use Anil\FastApiCrud\Enums\CrudAction;

CrudAction::View          // 'view'
CrudAction::Store         // 'store'
CrudAction::Update        // 'update'
CrudAction::Delete        // 'delete'
CrudAction::ChangeStatus  // 'change-status'
CrudAction::Restore       // 'restore'
```

---

## Full Example

### Model

```php
namespace App\Models;

use Anil\FastApiCrud\Concerns\HasDateScopes;
use Anil\FastApiCrud\Concerns\AnonymizesOnDelete;
use Anil\FastApiCrud\Contracts\HasPermissionSlug;
use Anil\FastApiCrud\Contracts\Searchable;
use Anil\FastApiCrud\Contracts\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model implements Searchable, Sortable, HasPermissionSlug
{
    use SoftDeletes, HasDateScopes, AnonymizesOnDelete;

    protected $fillable = ['name', 'desc', 'status', 'active', 'user_id'];

    // --- Contracts ---

    public function searchableColumns(): array
    {
        return ['name', 'desc', 'user:name,email'];
    }

    public function sortByDefaults(): array
    {
        return ['sortBy' => 'created_at', 'sortByDesc' => true];
    }

    public function getPermissionSlug(): string
    {
        return 'posts';
    }

    // --- Relations ---

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    // --- Scopes (callable via ?filters={"active":1}) ---

    public function scopeActive($query, int $active = 1)
    {
        return $query->where('active', $active);
    }

    // --- Lifecycle Hooks ---

    public function afterCreate(): void
    {
        if (request()->filled('tag_ids')) {
            $this->tags()->sync(request()->input('tag_ids'));
        }
    }

    public function afterUpdate(): void
    {
        if (request()->filled('tag_ids')) {
            $this->tags()->sync(request()->input('tag_ids'));
        }
    }
}
```

### API Controller

```php
namespace App\Http\Controllers\Api;

use Anil\FastApiCrud\Enums\PaginationType;
use Anil\FastApiCrud\Http\Controllers\BaseController;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\Post\PostResource;
use App\Models\Post;

class PostController extends BaseController
{
    protected PaginationType $paginationType = PaginationType::LengthAware;

    protected array $with = ['user', 'tags'];
    protected array $withCount = ['tags'];
    protected array $load = ['user', 'tags', 'tags.posts'];
    protected array $scopes = ['active'];

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

### Web Controller

```php
namespace App\Http\Controllers\Web;

use Anil\FastApiCrud\Http\Controllers\BaseWebController;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Models\Post;

class PostController extends BaseWebController
{
    protected array $with = ['user', 'tags'];
    protected array $load = ['user', 'tags'];

    public function __construct()
    {
        parent::__construct(
            model: Post::class,
            storeRequest: StorePostRequest::class,
            updateRequest: UpdatePostRequest::class,
            viewPrefix: 'posts',
            routePrefix: 'posts',
            resourceName: 'post',
            collectionName: 'posts',
        );
    }

    protected function storeSuccessMessage(): string
    {
        return __('Post created successfully!');
    }
}
```

---

## License

MIT
