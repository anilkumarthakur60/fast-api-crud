# Contracts

Three interfaces your models can implement to unlock features automatically.

## Searchable

Enables automatic LIKE search when `?search=` query parameter is present.

```php
use Anil\FastApiCrud\Contracts\Searchable;

class Post extends Model implements Searchable
{
    /**
     * @return array<int, string>
     */
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

**Relation syntax:** `'relation:column1,column2'` — uses `whereHas` with `orWhereAny` LIKE.

**Request:** `GET /posts?search=laravel`

**Generated SQL:**
```sql
WHERE (
    name LIKE '%laravel%'
    OR desc LIKE '%laravel%'
    OR EXISTS (
        SELECT * FROM users
        WHERE users.id = posts.user_id
        AND (name LIKE '%laravel%' OR email LIKE '%laravel%')
    )
)
```

If the search term is null or empty, the search is skipped entirely.

## Sortable

Provides default sort configuration when no `?sortBy` query parameter is given.

```php
use Anil\FastApiCrud\Contracts\Sortable;

class Post extends Model implements Sortable
{
    /**
     * @return array{sortBy: string, sortByDesc: bool}
     */
    public function sortByDefaults(): array
    {
        return [
            'sortBy'     => 'created_at',
            'sortByDesc' => true,
        ];
    }
}
```

| Key | Type | Description |
|-----|------|-------------|
| `sortBy` | `string` | Column name to sort by |
| `sortByDesc` | `bool` | `true` for descending, `false` for ascending |

When the request includes `?sortBy=`, the request value takes precedence over the defaults.

Without this interface, the default sort is `id` descending.

## HasPermissionSlug

Enables automatic Spatie permission middleware registration on CRUD actions.

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

Returning `'posts'` registers these permission middleware:

| Permission | Applied To |
|-----------|------------|
| `view-posts` | `index`, `show` |
| `store-posts` | `store` |
| `update-posts` | `update`, `updateColumn` |
| `delete-posts` | `destroy`, `delete`, `permanentDelete` |
| `change-status-posts` | `changeStatus` |
| `restore-posts` | `restore`, `restoreAll` |

Returning an empty string disables permission registration for that model.

Requires `fast-api.permissions.enabled` to be `true` (default). See [Permissions](./permissions) for more details.
