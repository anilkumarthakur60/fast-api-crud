# API Controller

`BaseController` provides JSON API endpoints. Returns `JsonResponse`, `JsonResource`, and `AnonymousResourceCollection`.

## Constructor

```php
use Anil\FastApiCrud\Http\Controllers\BaseController;

class PostController extends BaseController
{
    public function __construct()
    {
        parent::__construct(
            model: Post::class,                      // class-string<Model>
            storeRequest: StorePostRequest::class,   // class-string<FormRequest>
            updateRequest: UpdatePostRequest::class,  // class-string<FormRequest>
            resource: PostResource::class,            // class-string<JsonResource>
        );
    }
}
```

All four parameters are required and validated at construction time.

## Methods

| Method | HTTP | Return Type | Status |
|--------|------|-------------|--------|
| `index()` | GET | `AnonymousResourceCollection` | 200 |
| `show(int\|string $id)` | GET | `JsonResource` | 200 |
| `store()` | POST | `JsonResponse` | 201 |
| `update(int\|string $id)` | PUT | `JsonResource\|JsonResponse` | 200 |
| `destroy(int\|string $id)` | DELETE | `JsonResponse` | 204 |
| `delete()` | POST | `JsonResponse` | 204 |
| `changeStatus(int\|string $id, string $column = 'status')` | PUT | `JsonResource\|JsonResponse` | 200 |
| `updateColumn(int\|string $id, string $column = 'status')` | PUT | `JsonResource\|JsonResponse` | 200 |
| `restore(int\|string $id)` | PUT | `JsonResource\|JsonResponse` | 200 |
| `restoreAll()` | POST | `JsonResponse` | 204 |
| `permanentDelete(int\|string $id)` | POST | `JsonResponse` | 204 |

Methods returning `JsonResource|JsonResponse` return `JsonResponse` when an exception occurs. Validation exceptions (`ValidationException`) are re-thrown so Laravel returns a proper 422 response.

## Response Examples

### index — Paginated List

```http
GET /posts?rowsPerPage=2&sortBy=id&descending=false
```

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
    "last": "http://example.com/posts?page=34",
    "prev": null,
    "next": "http://example.com/posts?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 34,
    "per_page": 2,
    "to": 2,
    "total": 68
  }
}
```

### show — Single Resource

```http
GET /posts/1
```

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

### store — Create (201)

```http
POST /posts
Content-Type: application/json

{ "name": "New Post", "desc": "Description", "status": 1, "active": 1 }
```

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

### destroy — Delete (204)

```http
DELETE /posts/1
```

```json
{
  "data": []
}
```

### delete — Bulk Delete (204)

```http
POST /posts/delete
Content-Type: application/json

{ "delete_rows": [1, 2, 3] }
```

The `delete_rows` array is validated: each ID must exist in the model's table.

### changeStatus — Toggle Boolean

```http
PATCH /posts/1/status
```

Toggles the `status` column between `0` and `1`.

### updateColumn — Set an Allowlisted Column

```http
PATCH /posts/1/status/active
```

Sets the `{column}` (here `active`) to the request-body value. The column must be listed in
the controller's `$updatableColumns` allowlist (default `['status']`) or the request returns `403`.

### Validation Error (422)

When `FormRequest` validation fails, Laravel's handler returns:

```json
{
  "message": "The name field is required.",
  "errors": {
    "name": ["The name field is required."]
  }
}
```

### Operation Error (400)

When an exception occurs during a CRUD operation:

```json
{
  "errors": [],
  "message": "Column [invalid] does not exist on table [posts]."
}
```

## Customizing

All controller [properties](./controller-properties) and [lifecycle hooks](./lifecycle-hooks) work with `BaseController`. See those pages for details.
