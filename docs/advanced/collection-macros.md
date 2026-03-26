# Collection Macros

Registered on `Illuminate\Support\Collection` by the service provider.

## paginate

```php
->paginate(int $perPage, ?int $total = null, ?int $page = null, string $pageName = 'page'): LengthAwarePaginator
```

Paginate an in-memory collection.

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$perPage` | `int` | required | Records per page |
| `$total` | `?int` | `null` | Total count override (default: collection count) |
| `$page` | `?int` | `null` | Current page (default: resolved from request) |
| `$pageName` | `string` | `'page'` | Query string parameter name for page |

### Usage

```php
$items = collect([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

// Basic — auto-resolves current page from request
$paginated = $items->paginate(perPage: 5);
// Page 1: items [1, 2, 3, 4, 5]

// Explicit page
$paginated = $items->paginate(perPage: 3, page: 2);
// Page 2: items [4, 5, 6]

// Override total (useful for pre-sliced data)
$paginated = $items->paginate(perPage: 5, total: 100);
// Shows 100 as total even though collection has 10 items

// Custom page parameter name
$paginated = $items->paginate(perPage: 5, pageName: 'p');
// Uses ?p=1, ?p=2 in pagination links
```

### Return Value

Returns an `Illuminate\Pagination\LengthAwarePaginator` instance. Works with Blade's `$items->links()` and API resource pagination.

### Example with Blade

```php
// Controller
public function index()
{
    $items = collect(range(1, 50))->paginate(10);
    return view('items.index', compact('items'));
}
```

In your Blade template, loop over `$items` and call `$items->links()` for pagination links.
