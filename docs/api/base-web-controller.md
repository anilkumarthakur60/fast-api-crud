# BaseWebController

`Anil\FastApiCrud\Http\Controllers\BaseWebController`

Abstract controller for Blade/web endpoints. Uses `AuthorizesRequests` and `CrudQueries` traits.

## Constructor

```php
public function __construct(
    string $model,           // class-string<Model>
    string $storeRequest,    // class-string<FormRequest>
    string $updateRequest,   // class-string<FormRequest>
    string $viewPrefix,      // e.g. 'admin.posts'
    string $routePrefix,     // e.g. 'admin.posts'
    string $resourceName,    // e.g. 'post'
    string $collectionName,  // e.g. 'posts'
    ?string $resource = null, // Optional class-string<JsonResource>
)
```

## Properties

Inherits all properties from [`CrudQueries`](./crud-queries). Additionally:

| Property | Type | Description |
|----------|------|-------------|
| `$resource` | `class-string<JsonResource>\|null` | Optional. For data transformation. |
| `$viewPrefix` | `string` | View name prefix. `'admin.posts'` resolves to `admin.posts.index`, etc. |
| `$routePrefix` | `string` | Route name prefix for redirects. |
| `$resourceName` | `string` | Variable name for single model in show/edit views. |
| `$collectionName` | `string` | Variable name for collection in index view. |

## Public Methods

### index

```php
public function index(): View
```

Returns `{viewPrefix}.index` view with `${collectionName}` variable containing paginated results.

### create

```php
public function create(): View
```

Returns `{viewPrefix}.create` view. Override to pass extra data (categories, tags, etc.).

### store

```php
public function store(): RedirectResponse
```

Creates resource, redirects to `{routePrefix}.index` with success flash.

### show

```php
public function show(int|string $id): View
```

Returns `{viewPrefix}.show` view with `${resourceName}` variable.

### edit

```php
public function edit(int|string $id): View
```

Returns `{viewPrefix}.edit` view with `${resourceName}` variable.

### update

```php
public function update(int|string $id): RedirectResponse
```

Updates resource, redirects to `{routePrefix}.index` with success flash.

### destroy

```php
public function destroy(int|string $id): RedirectResponse
```

Deletes resource, redirects with success flash.

### delete

```php
public function delete(): RedirectResponse
```

Bulk deletes, redirects with success flash.

### changeStatus

```php
public function changeStatus(int|string $id, string $column = 'status'): RedirectResponse
```

### updateColumn

```php
public function updateColumn(int|string $id, string $column = 'status'): RedirectResponse
```

### restore

```php
public function restore(int|string $id): RedirectResponse
```

### restoreAll

```php
public function restoreAll(): RedirectResponse
```

### permanentDelete

```php
public function permanentDelete(int|string $id): RedirectResponse
```

## Protected Helpers

### viewName

```php
protected function viewName(string $suffix): string
```

Returns `"{$viewPrefix}.{$suffix}"`. Example: `$this->viewName('create')` → `'admin.posts.create'`.

### redirectWithSuccess

```php
protected function redirectWithSuccess(string $route, string $message, array $parameters = []): RedirectResponse
```

Redirects to the named route with a success flash message (key from config).

### redirectBackWithError

```php
protected function redirectBackWithError(string $message): RedirectResponse
```

Redirects back with old input and an error flash message (key from config).

## Overridable Message Methods

All return `string`. Override in your controller for localization:

| Method | Default Message |
|--------|----------------|
| `storeSuccessMessage()` | `'Record created successfully.'` |
| `updateSuccessMessage()` | `'Record updated successfully.'` |
| `destroySuccessMessage()` | `'Record deleted successfully.'` |
| `bulkDeleteSuccessMessage()` | `'Records deleted successfully.'` |
| `statusChangeSuccessMessage()` | `'Status updated successfully.'` |
| `columnUpdateSuccessMessage()` | `'Column updated successfully.'` |
| `restoreSuccessMessage()` | `'Record restored successfully.'` |
| `restoreAllSuccessMessage()` | `'All records restored successfully.'` |
| `permanentDeleteSuccessMessage()` | `'Record permanently deleted.'` |
