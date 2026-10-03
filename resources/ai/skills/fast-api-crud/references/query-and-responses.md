# Query parameters & response envelopes

Key names below are the defaults; each is renamable in `config/fast-api.php` under `query`.
Use `QueryParams::filters()`, `::search()`, `::sortBy()`, `::descending()`, `::perPage()`,
`::page()`, `::cursor()`, `::includes()`, `::trashed()` (namespace `Anil\FastApiCrud\Support`)
to get the effective names in PHP.

| Param | Where | Meaning |
|---|---|---|
| `filters` | query string, JSON object | Each key calls `scope{Studly(key)}($query, $value)` on the model. Unknown keys ignored. |
| `filters.include` | inside filters JSON | `"a,b"` or `["a","b"]` — eager loads relations from `$allowedIncludes` (index + show). |
| `filters.trashed` | inside filters JSON | `"with"` or `"only"`; needs `$allowTrashedFilter = true` and `SoftDeletes`. |
| `search` | query string | LIKE across `searchableColumns()`; supports `relation:col1,col2`. |
| `sortBy` | query string | Column. Default: `Sortable::sortByDefaults()` → `fast-api.sorting.default_column` (`id`). |
| `descending` | query string | `true`/`false`. Default `fast-api.sorting.default_descending` (`true`). |
| `rowsPerPage` | query string | Default `pagination.default_per_page` (15), max `max_per_page` (100). `0` → all rows only when `allow_all` is true, capped at `max_all` (1000). |
| `page` | query string | Length-aware / simple pagination page. |
| `cursor` | query string | Cursor pagination token (`PaginationType::Cursor`). |

Bulk delete: `DELETE /posts` with body `{"delete_rows": [1,2,3]}`; field name from
`fast-api.bulk.field`, max IDs from `fast-api.bulk.max_rows` (1000, 0 = unlimited).

## Responses (BaseController)

| Endpoint | Status | Body |
|---|---|---|
| index | 200 | `{"data":[...], "links":{...}, "meta":{...}}` (paginator shape depends on `$paginationType`) |
| show / update / changeStatus / updateColumn / restore | 200 | `{"data":{...}}` via the Resource |
| store | 201 | `{"data":{...}}` |
| destroy / delete / restoreAll / permanentDelete | 204 | empty |
| validation failure (FormRequest, bulk-delete ids) | 422 | Laravel default `{"message":..., "errors":{field:[...]}}` |
| record not found / scoped out | 404 | Laravel `ModelNotFoundException` rendering |
| `updateColumn` outside `$updatableColumns` | 403 | `AuthorizationException` |
| missing Spatie permission | 403 | Spatie `UnauthorizedException` |
| column missing / not fillable (changeStatus, updateColumn) | 500 | plain `Exception` |
| `ApiException` thrown by your code | its code | `{"error":{"message":...}}` (+ `file`, `line` when `APP_DEBUG`) |

The CRUD actions do **not** catch exceptions — nothing produces the `{"errors":[],"message":...}`
400 envelope unless your own code calls `$this->error()`. Envelope keys `data` / `errors` /
`message` come from `fast-api.response.*` and only affect `HasApiResponse` methods; the index/show
`data` key is Laravel's resource wrapper (`JsonResource::$wrap`).

Pagination shapes: `LengthAware` → `data/links/meta` with `total`; `Simple` → no `total`;
`Cursor` → `meta.next_cursor`/`prev_cursor`; `None` → plain `{"data":[...]}` (all rows).
