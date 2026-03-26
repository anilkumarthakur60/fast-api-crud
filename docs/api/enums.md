# Enums

## PaginationType

`Anil\FastApiCrud\Enums\PaginationType`

Controls the pagination strategy for index results.

```php
use Anil\FastApiCrud\Enums\PaginationType;
```

| Case | Value | Description |
|------|-------|-------------|
| `LengthAware` | `'length-aware'` | Standard pagination with total count. Uses `paginates()` macro. |
| `Simple` | `'simple'` | Simple pagination without total count query. Uses `simplePaginates()` macro. |
| `Cursor` | `'cursor'` | Cursor-based pagination. Best for large datasets / infinite scroll. Uses `cursorPaginates()` macro. |
| `None` | `'none'` | No pagination. Returns all records via `get()`. |

### Usage

```php
class PostController extends BaseController
{
    protected PaginationType $paginationType = PaginationType::Cursor;
}
```

## CrudAction

`Anil\FastApiCrud\Enums\CrudAction`

Standard CRUD action names used for Spatie permission middleware registration.

```php
use Anil\FastApiCrud\Enums\CrudAction;
```

| Case | Value | Applied To Routes |
|------|-------|-------------------|
| `View` | `'view'` | `index`, `show` |
| `Store` | `'store'` | `store` |
| `Update` | `'update'` | `update`, `updateColumn` |
| `Delete` | `'delete'` | `destroy`, `delete`, `permanentDelete` |
| `ChangeStatus` | `'change-status'` | `changeStatus` |
| `Restore` | `'restore'` | `restore`, `restoreAll` |

### Permission Format

For a model with slug `'posts'`:

```
{CrudAction::value}-{slug}
```

Examples: `view-posts`, `store-posts`, `update-posts`, `delete-posts`, `change-status-posts`, `restore-posts`
