# `config/fast-api.php` — every key

Publish: `php artisan vendor:publish --provider="Anil\FastApiCrud\FastApiCrudServiceProvider" --tag=config`.
Merged via `mergeConfigFrom`, so partial overrides are fine.

| Key | Default | Read by | Effect |
|---|---|---|---|
| `pagination.default_per_page` | `15` | `Pagination` | Used when `rowsPerPage` missing / non-numeric / ≤ 0 (unless allow_all) |
| `pagination.max_per_page` | `100` | `Pagination`, `cursorPaginates` | Upper bound for requested per-page |
| `pagination.allow_all` | `false` | `Pagination::resolvePerPage` | `rowsPerPage=0` → all rows (offset paginators only) |
| `pagination.max_all` | `1000` | `resolveEffectivePerPage` | Cap for the all-rows path; `0` = unbounded |
| `bulk.max_rows` | `1000` | `performBulkDelete` | `max:N` rule on the id array; `0` disables |
| `bulk.field` | `delete_rows` | `performBulkDelete` | Request key with the ids |
| `query.filters` | `filters` | `QueryParams` | top-level key |
| `query.search` | `search` | | |
| `query.sort_by` | `sortBy` | | |
| `query.descending` | `descending` | | |
| `query.per_page` | `rowsPerPage` | | |
| `query.page` | `page` | | |
| `query.cursor` | `cursor` | | |
| `query.include` | `include` | | key *inside* the filters JSON |
| `query.trashed` | `trashed` | | key *inside* the filters JSON |
| `sorting.default_column` | `id` | `initializer` | when no sort key and not `Sortable` |
| `sorting.default_descending` | `true` | `initializer` | |
| `soft_delete.anonymize_unique_columns` | `true` | `AnonymizesOnDelete` | |
| `response.success_key` | `data` | `HasApiResponse::success` | |
| `response.error_key` | `errors` | `HasApiResponse::error` | |
| `response.message_key` | `message` | `HasApiResponse::error` | |
| `permissions.enabled` | `true` | `permissionMiddleware()` | `false` → returns `[]` (no middleware) |
| `web.flash_key_success` | `success` | `BaseWebController` | |
| `web.flash_key_error` | `error` | `BaseWebController` | |

The global helpers `filterValue()`, `sortBy()`, `sortDirection()` do **not** honour `query.*`
renames — they read the literal `filters`, `sort`, `descending` keys.

## Enums (`Anil\FastApiCrud\Enums`)

`PaginationType: string` — `LengthAware = 'length-aware'`, `Simple = 'simple'`, `Cursor = 'cursor'`, `None = 'none'`.

`CrudAction: string` — `View = 'view'`, `Store = 'store'`, `Update = 'update'`, `Delete = 'delete'`,
`ChangeStatus = 'change-status'`, `Restore = 'restore'` — the prefixes of the permission names.

## Permissions (`permissionMiddleware()`)

```php
protected static function permissionMiddleware(string $slug): array   // on HasCrudOperations
```
Returns `[]` when `permissions.enabled` is false; throws `RuntimeException` if
`spatie/laravel-permission` is not installed; otherwise six `Illuminate\Routing\Controllers\Middleware`
objects using the `permission:` alias:

| Permission | Actions |
|---|---|
| `view-{slug}` | index, show |
| `store-{slug}` | store |
| `update-{slug}` | update, updateColumn |
| `delete-{slug}` | destroy, delete, permanentDelete |
| `change-status-{slug}` | changeStatus |
| `restore-{slug}` | restore, restoreAll |

`create`/`edit` (web) are not covered — add `new Middleware('permission:store-posts', only: ['create'])` etc.
Requires the `permission` middleware alias to be registered in `bootstrap/app.php` — Spatie v6/v7 do **not** register it for you. Full integration guide: `permissions.md`.

## Service provider

`Anil\FastApiCrud\FastApiCrudServiceProvider` (auto-discovered): merges config, registers the
three macro sets, and the console commands `fast-api:make-all`, `fast-api:mcp`, `fast-api:install-ai`.
Publish tags: `config`, `ai`.
