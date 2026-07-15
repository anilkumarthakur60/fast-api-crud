# Routes

The fastest way to register every CRUD route is the `Route::fastApiResource` macro
the package adds to the router — it registers the full set in one line.

## `Route::fastApiResource` (recommended)

```php
use App\Http\Controllers\PostController;

Route::fastApiResource('posts', PostController::class);
```

Restrict the actions, and customise the route parameter name or route-name prefix:

```php
// Only a subset
Route::fastApiResource('posts', PostController::class, ['only' => ['index', 'show']]);

// Everything except some actions
Route::fastApiResource('posts', PostController::class, ['except' => ['delete', 'restoreAll']]);

// Custom {id} parameter name and route-name prefix
Route::fastApiResource('posts', PostController::class, [
    'parameter' => 'post',
    'names'     => 'admin.posts',
]);
```

## Generated routes

| Method | URI | Action | Route name | Description |
|--------|-----|--------|------------|-------------|
| GET | `/posts` | `index` | `posts.index` | Paginated list |
| POST | `/posts` | `store` | `posts.store` | Create resource |
| DELETE | `/posts` | `delete` | `posts.delete` | Bulk delete via `delete_rows` |
| POST | `/posts/restore` | `restoreAll` | `posts.restoreAll` | Restore all soft-deleted |
| PATCH | `/posts/{id}/status/{column}` | `updateColumn` | `posts.updateColumn` | Update an allowlisted column |
| PATCH | `/posts/{id}/status` | `changeStatus` | `posts.changeStatus` | Toggle a boolean column |
| PATCH | `/posts/{id}/restore` | `restore` | `posts.restore` | Restore one soft-deleted record |
| DELETE | `/posts/{id}/force` | `permanentDelete` | `posts.permanentDelete` | Permanently delete |
| GET | `/posts/{id}` | `show` | `posts.show` | Single resource |
| PUT / PATCH | `/posts/{id}` | `update` | `posts.update` | Update resource |
| DELETE | `/posts/{id}` | `destroy` | `posts.destroy` | Soft (or force) delete |

Collection routes are registered before the `{id}` routes so a bare `{id}` segment
never shadows a static sibling such as `/posts/restore`.

## Registering routes manually

Prefer to wire routes yourself? Register the same set explicitly — keep the collection
routes above the `{id}` routes, and match the verbs/URIs so `permissionMiddleware()` and
your clients line up with the macro:

```php
use App\Http\Controllers\PostController;

Route::get('posts', [PostController::class, 'index'])->name('posts.index');
Route::post('posts', [PostController::class, 'store'])->name('posts.store');
Route::delete('posts', [PostController::class, 'delete'])->name('posts.delete');
Route::post('posts/restore', [PostController::class, 'restoreAll'])->name('posts.restoreAll');
Route::patch('posts/{id}/status/{column}', [PostController::class, 'updateColumn'])->name('posts.updateColumn');
Route::patch('posts/{id}/status', [PostController::class, 'changeStatus'])->name('posts.changeStatus');
Route::patch('posts/{id}/restore', [PostController::class, 'restore'])->name('posts.restore');
Route::delete('posts/{id}/force', [PostController::class, 'permanentDelete'])->name('posts.permanentDelete');
Route::get('posts/{id}', [PostController::class, 'show'])->name('posts.show');
Route::match(['put', 'patch'], 'posts/{id}', [PostController::class, 'update'])->name('posts.update');
Route::delete('posts/{id}', [PostController::class, 'destroy'])->name('posts.destroy');
```

## Web controllers

`fastApiResource` also works with a `BaseWebController`. It registers the same actions,
but web controllers additionally define `create` and `edit` form endpoints, which the
macro does **not** register — add those yourself (or use `Route::resource` for the
standard CRUD verbs and `fastApiResource` with `only` for the extras):

| Method | URI | Action | Description |
|--------|-----|--------|-------------|
| GET | `/posts/create` | `create` | Show create form |
| GET | `/posts/{id}/edit` | `edit` | Show edit form |

## Request Bodies

### store / update

Send validated fields as JSON (API) or form data (Web):

```json
{
  "name": "New Post",
  "desc": "Description",
  "status": 1,
  "active": 1
}
```

Only fields listed in the model's `$fillable` array are persisted (others are silently
stripped). Models using `$guarded` fall back to the actual table columns.

### delete (bulk)

```json
{
  "delete_rows": [1, 2, 3]
}
```

Each ID is validated to exist in the model's table. The field name (`delete_rows`) and
the maximum number of IDs are configurable via `fast-api.bulk`.

### changeStatus

No body needed. Toggles the target column (default `status`) between `0` and `1`.

### updateColumn

Send the column value in the request body:

```json
{
  "status": 1
}
```

The `{column}` segment is checked against the controller's `$updatableColumns` allowlist
(default `['status']`) — a column that is not allowlisted returns `403`, even if it is
fillable. Add columns to `$updatableColumns` to expose them.
