# HasCrudOperations Trait

`Anil\FastApiCrud\Concerns\HasCrudOperations`

Shared CRUD query building, data operations, and lifecycle hooks. Used by both `BaseController` and `BaseWebController`.

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$paginationType` | `PaginationType` | `LengthAware` | Pagination strategy |
| `$scopes` | `array` | `[]` | Scopes for index query |
| `$loadScopes` | `array` | `[]` | Scopes for show query |
| `$with` | `array` | `[]` | Eager loads for index |
| `$withCount` | `array` | `[]` | Count relations for index |
| `$withAggregate` | `array` | `[]` | Aggregates for index |
| `$load` | `array` | `[]` | Eager loads for show |
| `$loadCount` | `array` | `[]` | Count relations for show |
| `$loadAggregate` | `array` | `[]` | Aggregates for show |
| `$forceDelete` | `bool` | `false` | Force permanent deletion |
| `$deleteScopes` | `array` | `[]` | Scopes for delete operations |
| `$columnScopes` | `array` | `[]` | Scopes for status/column ops |
| `$restoreScopes` | `array` | `[]` | Scopes for restore operations |
| `$updateScopes` | `array` | `[]` | Scopes for update operations |
| `$model` | `Model` | — | Resolved model instance |
| `$storeRequest` | `string` | — | Store FormRequest class |
| `$updateRequest` | `string` | — | Update FormRequest class |

## Query Builders

### buildIndexQuery

```php
protected function buildIndexQuery(): Builder
```

Builds the index query: `initializer()` → eager loads → counts → aggregates → scopes → search.

### buildShowQuery

```php
protected function buildShowQuery(): Builder
```

Builds the show query: `initializer()` → eager loads → counts → aggregates → scopes.

## Perform Methods

All throw exceptions on failure (caught by the calling controller). Operations run inside database transactions.

| Method | Returns | Description |
|--------|---------|-------------|
| `performStore()` | `Model` | Validate, create, hooks |
| `performUpdate($id)` | `Model` | Validate, update, hooks |
| `performDestroy($id)` | `void` | Delete (soft/force), hooks |
| `performBulkDelete()` | `void` | Validate IDs, delete each, hooks |
| `performChangeStatus($id, $column)` | `Model` | Toggle 0/1, hooks |
| `performUpdateColumn($id, $column)` | `Model` | Update from request, hooks |
| `performRestore($id)` | `Model` | Restore trashed, hooks |
| `performRestoreAll()` | `void` | Restore all trashed |
| `performPermanentDelete($id)` | `void` | Force delete trashed, hooks |

## Lifecycle Hooks

See [Lifecycle Hooks](/guide/lifecycle-hooks) guide.

| Hook | Called By |
|------|-----------|
| `beforeCreate(Model) / afterCreate(Model)` | `performStore()` |
| `beforeUpdate(Model) / afterUpdate(Model)` | `performUpdate()` |
| `beforeDelete(Model) / afterDelete(Model)` | `performDestroy()`, `performBulkDelete()` |
| `beforeStatusChange(Model) / afterStatusChange(Model)` | `performChangeStatus()` |
| `beforeColumnUpdate(Model) / afterColumnUpdate(Model)` | `performUpdateColumn()` |
| `beforeRestore(Model) / afterRestore(Model)` | `performRestore()` |
| `beforeForceDelete(Model) / afterForceDelete(Model)` | `performPermanentDelete()` |

## Internal Helpers

| Method | Returns | Description |
|--------|---------|-------------|
| `resolveModel($class)` | `Model` | Validate and instantiate model |
| `resolveValidatedData($requestClass)` | `array<string, mixed>` | Resolve FormRequest, return only fillable data |
| `resolveFormRequest($class, $name)` | `string` | Validate FormRequest class |
| `resolveResource($class)` | `string` | Validate JsonResource class |
| `findModel($id, $scopes)` | `Model` | Find by ID with optional scopes |
| `applyScopes($query, $scopes)` | `Builder` | Apply scope array to query |
| `applyAggregates($query, $aggregates)` | `Builder` | Apply aggregate functions |
| `applySearch($query)` | `void` | Apply `?search=` if model is Searchable |
| `paginateQuery($query)` | `mixed` | Paginate based on `$paginationType` |
| `assertFillableColumn($model, $column)` | `void` | Verify column exists and is fillable |
| `registerPermissionMiddleware()` | `void` | Register Spatie permission middleware |
| `permissionMiddleware($slug)` | `array<Middleware>` | Static — generate middleware array |
