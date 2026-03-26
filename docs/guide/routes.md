# Routes

## API Routes

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

// Status & column update
Route::put('posts/{id}/status-change', [PostController::class, 'changeStatus'])
    ->name('posts.changeStatus');
Route::put('posts/{id}/status-change/{column}', [PostController::class, 'updateColumn'])
    ->name('posts.updateColumn');

// Soft delete operations
Route::put('posts/{id}/restore', [PostController::class, 'restore'])
    ->name('posts.restore');
Route::post('posts/restore-all', [PostController::class, 'restoreAll'])
    ->name('posts.restoreAll');
Route::post('posts/{id}/force-delete', [PostController::class, 'permanentDelete'])
    ->name('posts.permanentDelete');
```

## Web Routes

```php
use App\Http\Controllers\PostController;

// Standard Laravel resource (index, create, store, show, edit, update, destroy)
Route::resource('posts', PostController::class);

// Extended operations
Route::post('posts/delete', [PostController::class, 'delete'])
    ->name('posts.delete');
Route::put('posts/{id}/status-change', [PostController::class, 'changeStatus'])
    ->name('posts.changeStatus');
Route::put('posts/{id}/status-change/{column}', [PostController::class, 'updateColumn'])
    ->name('posts.updateColumn');
Route::put('posts/{id}/restore', [PostController::class, 'restore'])
    ->name('posts.restore');
Route::post('posts/restore-all', [PostController::class, 'restoreAll'])
    ->name('posts.restoreAll');
Route::post('posts/{id}/force-delete', [PostController::class, 'permanentDelete'])
    ->name('posts.permanentDelete');
```

## Route Summary

| Method | URI | Action | Description |
|--------|-----|--------|-------------|
| GET | `/posts` | `index` | Paginated list |
| POST | `/posts` | `store` | Create resource |
| GET | `/posts/{id}` | `show` | Single resource |
| PUT | `/posts/{id}` | `update` | Update resource |
| DELETE | `/posts/{id}` | `destroy` | Soft/force delete |
| POST | `/posts/delete` | `delete` | Bulk delete via `delete_rows` |
| PUT | `/posts/{id}/status-change` | `changeStatus` | Toggle boolean column |
| PUT | `/posts/{id}/status-change/{column}` | `updateColumn` | Update specific column |
| PUT | `/posts/{id}/restore` | `restore` | Restore soft-deleted |
| POST | `/posts/restore-all` | `restoreAll` | Restore all soft-deleted |
| POST | `/posts/{id}/force-delete` | `permanentDelete` | Permanently delete |

Web controllers also have:

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

Only fields listed in the model's `$fillable` array are accepted (others are silently stripped).

### delete (bulk)

```json
{
  "delete_rows": [1, 2, 3]
}
```

Each ID is validated to exist in the model's table.

### changeStatus

No body needed. Toggles the specified column between `0` and `1`.

### updateColumn

Send the column value in the request body:

```json
{
  "status": 1
}
```

The column must be in the model's `$fillable` array and must exist in the database table.
