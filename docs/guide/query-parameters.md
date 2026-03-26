# Query Parameters

The package reads these query parameters automatically via the `initializer()` macro.

## Filtering

```
GET /posts?filters={"active":1,"status":1,"queryFilter":"search term"}
```

The `filters` parameter accepts a **JSON-encoded object**. Each key is matched against the model's scopes:

- `"active":1` calls `scopeActive(1)` on the model
- `"queryFilter":"search term"` calls `scopeQueryFilter("search term")`
- Keys are converted to StudlyCase: `"my_scope"` → `scopeMyScope`

If a matching scope method isn't found, the filter is silently skipped. If the JSON is invalid, all filters are skipped.

### Model Scopes for Filtering

```php
class Post extends Model
{
    // Called via ?filters={"active":1}
    public function scopeActive(Builder $query, int $active = 1): Builder
    {
        return $query->where('active', $active);
    }

    // Called via ?filters={"queryFilter":"search term"}
    public function scopeQueryFilter(Builder $query, string $value): Builder
    {
        return $query->likeWhere(['name', 'desc'], $value);
    }

    // Called via ?filters={"hasPosts":1}
    public function scopeHasPosts(Builder $query): Builder
    {
        return $query->whereHas('posts');
    }
}
```

## Sorting

```
GET /posts?sortBy=created_at&descending=true
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `sortBy` | string | `id` | Column name to sort by |
| `descending` | boolean | `true` | `true` for DESC, `false` for ASC |

If the model implements `Sortable`, its `sortByDefaults()` values are used when no sort params are provided:

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

### Disable Sorting

The `initializer()` macro accepts an `orderBy` parameter:

```php
$query = Post::query()->initializer(orderBy: false);
```

When used in a controller, the `buildIndexQuery()` and `buildShowQuery()` methods always call `initializer()` with sorting enabled.

## Pagination

```
GET /posts?rowsPerPage=25
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `rowsPerPage` | integer | 15 | Records per page |

Special cases:
- `rowsPerPage=0` — returns **all records** if `fast-api.pagination.allow_all` is `true`
- Values above `max_per_page` are clamped to the max (default: 100)
- Negative values fall back to `default_per_page`

## Search

```
GET /posts?search=laravel
```

If the model implements `Searchable`, a LIKE search runs across the columns returned by `searchableColumns()`:

```php
use Anil\FastApiCrud\Contracts\Searchable;

class Post extends Model implements Searchable
{
    public function searchableColumns(): array
    {
        return [
            'name',
            'desc',
            'user:name,email',     // Search in related model
        ];
    }
}
```

This generates:

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

Empty or null search terms are ignored.

## Combined Example

```
GET /posts?filters={"active":1,"status":1}&sortBy=name&descending=false&rowsPerPage=20&search=laravel
```

This:
1. Applies `scopeActive(1)` and `scopeStatus(1)` from filters
2. Sorts by `name` ascending
3. Paginates with 20 per page
4. Searches for "laravel" in searchable columns
