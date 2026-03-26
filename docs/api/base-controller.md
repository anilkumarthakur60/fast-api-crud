# BaseController

`Anil\FastApiCrud\Http\Controllers\BaseController`

Abstract controller for JSON API endpoints. Uses `HasApiResponse`, `AuthorizesRequests`, and `HasCrudOperations` traits.

## Constructor

```php
public function __construct(
    string $model,         // class-string<Model>
    string $storeRequest,  // class-string<FormRequest>
    string $updateRequest, // class-string<FormRequest>
    string $resource,      // class-string<JsonResource>
)
```

All parameters are validated. Throws `Exception` if any class doesn't extend the expected base class.

## Properties

Inherits all properties from [`HasCrudOperations`](./has-crud-operations). Additionally:

| Property | Type | Description |
|----------|------|-------------|
| `$resource` | `class-string<JsonResource>` | Required. API resource class for response formatting. |

## Public Methods

### index

```php
public function index(): AnonymousResourceCollection
```

Lists all records with pagination, filtering, sorting, search, eager loads, counts, and aggregates.

### show

```php
public function show(int|string $id): JsonResource
```

Returns a single resource with eager loads, counts, aggregates, and scopes.

### store

```php
public function store(): JsonResponse
```

Creates a new resource. Returns 201 Created. Validates via `$storeRequest`. Re-throws `ValidationException` for proper 422 responses.

### update

```php
public function update(int|string $id): JsonResource|JsonResponse
```

Updates the specified resource. Validates via `$updateRequest`.

### destroy

```php
public function destroy(int|string $id): JsonResponse
```

Deletes a single resource (soft or force based on `$forceDelete`). Returns 204.

### delete

```php
public function delete(): JsonResponse
```

Bulk deletes records. Expects `delete_rows` array in request body. Returns 204.

### changeStatus

```php
public function changeStatus(int|string $id, string $column = 'status'): JsonResource|JsonResponse
```

Toggles a boolean column between `0` and `1`.

### updateColumn

```php
public function updateColumn(int|string $id, string $column = 'status'): JsonResource|JsonResponse
```

Updates a specific fillable column with the request value.

### restore

```php
public function restore(int|string $id): JsonResource|JsonResponse
```

Restores a single soft-deleted resource.

### restoreAll

```php
public function restoreAll(): JsonResponse
```

Restores all soft-deleted resources. Returns 204.

### permanentDelete

```php
public function permanentDelete(int|string $id): JsonResponse
```

Permanently deletes a soft-deleted resource. Returns 204.
