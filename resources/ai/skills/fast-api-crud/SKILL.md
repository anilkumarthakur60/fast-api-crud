---
name: fast-api-crud
description: Complete guide to the anil/fast-api-crud Laravel package — BaseController / BaseWebController, fast-api:make-all scaffolding, Route::fastApiResource, filters/sort/search/pagination query params, lifecycle hooks, Searchable/Sortable/HasPermissionSlug contracts, HasDateScopes/AnonymizesOnDelete/ReplicatesWithRelations traits, Builder & Collection macros, HasApiResponse responders, global helpers, ApiException, config keys, Spatie permissions. Use whenever a task touches any of these, a controller extending BaseController/BaseWebController, a `fast-api.*` config key, or asks for a new REST/Blade resource in a project that has this package installed.
---

# fast-api-crud

Laravel package that turns a model + two FormRequests + a Resource into a complete CRUD API
(or Blade UI) with filtering, sorting, search, pagination, soft-delete handling and permission
middleware — without writing controller methods. It also ships reusable query macros, HTTP
responders, model traits and helpers usable anywhere in the app.

Everything below is derived from the package source (v3.x). Where the README disagrees with
this skill, trust the skill. Live project facts (controllers, routes, effective config) come
from the `fast-api-crud` MCP server when connected — prefer its tools over guessing.

## Reference map (read the file for the area you are touching)

| Area | File | Covers |
|---|---|---|
| Controllers | `references/controllers.md` | constructors, every property, query pipeline, every `perform*` step, transactions, API return values, web redirects/flash/messages, safe override points |
| Routes | `references/routes.md` | `fastApiResource` options, all 11 routes with verbs/URIs/names, ordering rules |
| Scaffolding | `references/scaffolding.md` | `fast-api:make-all` outputs, stubs, naming, the `authorize() → false` trap |
| Query params & responses | `references/query-and-responses.md` | every request key, bulk delete body, status codes & envelopes per action |
| Hooks, contracts, traits | `references/hooks-contracts-traits.md` | hook order & transaction scope, `Searchable`/`Sortable`/`HasPermissionSlug`, `HasDateScopes` (all 14 scopes), `AnonymizesOnDelete`, `ReplicatesWithRelations` |
| Responders | `references/api-response.md` | `success()`/`error()` + all 62 status shortcuts, `ApiException` |
| Macros | `references/macros.md` | `initializer`, `likeWhere`, `paginates`, `simplePaginates`, `cursorPaginates`, `withAggregates`, `withCountWhereHas`, `Collection::paginate`, `Pagination`, `QueryParams` |
| Helpers | `references/helpers.md` | all 25 global functions with real semantics and gotchas |
| Config & enums | `references/config.md` | every `fast-api.*` key, `PaginationType`, `CrudAction`, `permissionMiddleware()` matrix, provider |
| Permissions (Spatie) | `references/permissions.md` | installing spatie/laravel-permission, registering the `permission` alias, wiring `middleware()`, seeding the six permissions per slug, guards, caching, teams, super-admin, testing, 403 checklist |
| Testing | `references/testing.md` | Pest recipes per endpoint |

## Golden rules

1. **Never hand-write index/show/store/update/destroy** in a `BaseController`/`BaseWebController`
   subclass. Customise via properties (`$scopes`, `$with`, `$load`, `$allowedIncludes`,
   `$allowTrashedFilter`, `$updatableColumns`, `$paginationType`, `$forceDelete`, `*Scopes`),
   model scopes, contracts and hooks; override `protected` internals (`buildIndexQuery()`,
   `findModel()`, `perform*()`) before public actions.
2. **Validation lives in the FormRequests**; persisted data = `validated()` ∩ `$fillable`
   (or ∩ table columns when `$fillable` is empty). Request-only keys are read in `after*` hooks.
   The `make:request` stub's `authorize()` returns `false` — flip it or every write is 403.
3. **Filters are model scopes.** `?filters={"active":1}` → `scopeActive($q, 1)` only if the scope
   is declared; unknown keys are ignored. `include`/`trashed` are reserved keys.
4. **`updateColumn` is allow-listed** by `$updatableColumns` (default `['status']`) and writes
   `request()->input($column)` unvalidated. **`changeStatus` is not allow-listed** — it toggles
   any existing fillable column named by the route (`/{id}/status` uses `status`).
5. Query-param names come from `config('fast-api.query.*')` — read it (or MCP `get_config` /
   `query_reference`) before writing URLs. `rowsPerPage=0` returns all rows only when
   `pagination.allow_all` is true (default false), capped by `max_all`.
6. Every write runs in a DB transaction with hooks inside; throw to abort. Exceptions propagate
   to Laravel's handler (API) or become a flash + `back()` (web).
7. Run `vendor/bin/pint --dirty` on generated PHP; the package uses `declare(strict_types=1)`.

## Workflow: new resource

```bash
php artisan fast-api:make-all Post          # API; --web for Blade; Post,Tag,Category for several
```
1. Migration + `$fillable` → 2. `rules()` and `authorize()` in both FormRequests → 3. `Resource::toArray()`
→ 4. `Route::fastApiResource('posts', PostController::class)` → 5. scopes / `Searchable` /
`Sortable` on the model → 6. controller properties → 7. `middleware()` with
`static::permissionMiddleware('posts')` if Spatie is used (seed the six permissions)
→ 8. Pest tests (`references/testing.md`).

## Controller cheat-sheet

```php
class PostController extends BaseController
{
    protected PaginationType $paginationType = PaginationType::LengthAware; // Simple | Cursor | None(all rows!)
    protected array $scopes = ['published', 'status' => 1, 'statusIn' => [[1, 2]]]; // arrays are spread → wrap
    protected array $with = ['author'], $withCount = ['comments'], $withAggregate = ['ratings' => 'score'];
    protected array $load = ['author', 'tags'], $loadCount = [], $loadAggregate = [], $loadScopes = [];
    protected array $allowedIncludes = ['author', 'tags'];   // filters.include allowlist
    protected bool  $allowTrashedFilter = true;              // filters.trashed with|only (index only)
    protected array $updatableColumns = ['status', 'featured'];
    protected array $updateScopes = [], $deleteScopes = [], $columnScopes = [], $restoreScopes = [];
    protected bool  $forceDelete = false;

    public function __construct() { parent::__construct(Post::class, StorePostRequest::class, UpdatePostRequest::class, PostResource::class); }
    public static function middleware(): array { return static::permissionMiddleware('posts'); }
    protected function afterCreate(Model $model): void { parent::afterCreate($model); $model->tags()->sync(request()->input('tag_ids', [])); }
}
```

Web variant adds `viewPrefix`, `routePrefix`, `resourceName`, `collectionName` (+ optional
`resource`); views `{prefix}.index|create|edit|show`; flash keys from `fast-api.web.*`; override
`*SuccessMessage()` / `genericErrorMessage()` for copy.

## Routes registered by `Route::fastApiResource('posts', …)`

GET `/posts` index · POST `/posts` store · DELETE `/posts` delete(bulk, body `delete_rows[]`) ·
POST `/posts/restore` restoreAll · PATCH `/posts/{id}/status/{column}` updateColumn ·
PATCH `/posts/{id}/status` changeStatus · PATCH `/posts/{id}/restore` restore ·
DELETE `/posts/{id}/force` permanentDelete · GET `/posts/{id}` show · PUT|PATCH `/posts/{id}` update ·
DELETE `/posts/{id}` destroy. Options `only`, `except`, `parameter`, `names`. No `create`/`edit`.

## Request parameters (defaults)

`?filters={"scope":value,"include":"a,b","trashed":"with"}&search=&sortBy=&descending=&rowsPerPage=&page=&cursor=`
— details, limits and response codes in `references/query-and-responses.md`.

## Hooks

`beforeCreate/afterCreate · beforeUpdate/afterUpdate · beforeDelete/afterDelete ·
beforeStatusChange/afterStatusChange · beforeColumnUpdate/afterColumnUpdate ·
beforeRestore/afterRestore · beforeForceDelete/afterForceDelete`. Controller form:
`protected function afterCreate(Model $m): void`; model form: `public function afterCreate(): void`
(called by the controller default). `restoreAll` fires no hooks.

## Reusable outside controllers

- Model: `Searchable`, `Sortable`, `HasPermissionSlug`; traits `HasDateScopes`
  (`today yesterday thisWeek lastWeek monthToDate thisMonth lastMonth quarterToDate lastQuarter
  yearToDate lastYear last7Days last30Days date('from to to')`, all `($column = 'created_at')`),
  `AnonymizesOnDelete`, `ReplicatesWithRelations::replicateWithRelations($relations = [], $except = [])`.
- Query: `Model::query()->initializer()->likeWhere([...], $term)->withAggregates([...])
  ->withCountWhereHas('comments')->paginates()`; `collect($rows)->paginate(10)`.
- HTTP: `use HasApiResponse;` → `success()`, `error()`, `created()`, `noContent()`, `notFound()`,
  `unprocessableContent()`, … (62 shortcuts); `throw new ApiException('msg', 404)`.
- Helpers: dates (`ymdDate`, `dateForReports`, …), `parseTimeToSeconds`, `formatDuration`,
  `filterValue`, `arrayFilters`, `flattenArray`, `tableColumns`, `fillableCsv`, `columnsCsv`,
  `scopeMethods`, `appClasses`, `databaseClasses`, `uuid`, `slug`, `relativePath`,
  `classShortName`, `_dd` — semantics and gotchas in `references/helpers.md`.

## Debugging checklist

| Symptom | Check |
|---|---|
| Filter ignored | `scope{StudlyKey}` declared on the model? Reserved key (`include`/`trashed`)? |
| `include` ignored | Relation in `$allowedIncludes`? |
| 403 on every store/update | FormRequest `authorize()` still `false`? Spatie permission missing? |
| 403 on `PATCH /{id}/status/{column}` | Column in `$updatableColumns`? |
| 500 "Column … is not fillable" on changeStatus/updateColumn | Add the column to `$fillable`. |
| `rowsPerPage=0` still paginates | `pagination.allow_all` false (default). Cursor pagination never returns all. |
| Field not saved | In `$fillable` **and** in `rules()`? (`$guarded` models: is it a real column?) |
| Trashed rows missing on index | `$allowTrashedFilter = true` and model uses `SoftDeletes`? Show never returns trashed. |
| `RuntimeException: spatie/laravel-permission is required` | Install it or drop `permissionMiddleware()`. |
| `Target class [permission] does not exist` | Register Spatie's middleware aliases in `bootstrap/app.php` (see `references/permissions.md`). |
| 403 `User does not have the right permissions` / `no permission named … for guard` | Permission not seeded for the authenticating guard, or cache stale — `permissions.md` checklist. |
| Search does nothing | Model implements `Searchable` with non-empty `searchableColumns()`? Index only. |
| Sorting by relation column fails | `sortBy` is applied verbatim via `latest()/oldest()` — only own columns. |
| 404 on update/delete of an existing row | A `*Scopes` property excludes it. |
| Unique constraint on re-create after soft delete | Add `AnonymizesOnDelete` (needs `SoftDeletes`). |
