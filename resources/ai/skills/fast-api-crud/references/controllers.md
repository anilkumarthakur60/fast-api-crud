# Controllers — BaseController & BaseWebController (complete)

Namespace `Anil\FastApiCrud\Http\Controllers`. Both are `abstract`, implement
`Illuminate\Routing\Controllers\HasMiddleware`, use `HasCrudOperations`
(+ `HasApiResponse`, `AuthorizesRequests` on the API one). They do **not** extend
`Illuminate\Routing\Controller`, so `$this->middleware()` in the constructor does not exist —
override `public static function middleware(): array` instead.

## Constructors

```php
// API
parent::__construct(
    model: Post::class,                 // must extend Eloquent Model   (else Exception)
    storeRequest: StorePostRequest::class,   // must extend FormRequest (else Exception)
    updateRequest: UpdatePostRequest::class, // must extend FormRequest
    resource: PostResource::class,      // must extend JsonResource
);

// Web
parent::__construct(
    model: Post::class,
    storeRequest: StorePostRequest::class,
    updateRequest: UpdatePostRequest::class,
    viewPrefix: 'admin.posts',   // view('admin.posts.index'|create|edit|show)
    routePrefix: 'admin.posts',  // redirect()->route('admin.posts.index')
    resourceName: 'post',        // $post in show/edit
    collectionName: 'posts',     // $posts in index
    resource: PostResource::class, // optional, nullable
);
```

Validation failures happen when the FormRequest is *resolved* (inside `store()`/`update()`),
so `authorize()` and `rules()` run exactly like a normal type-hinted FormRequest.

## Configuration properties (all `protected`, declared in `HasCrudOperations`)

| Property | Type / default | Used by | Effect |
|---|---|---|---|
| `$paginationType` | `PaginationType` = `LengthAware` | index | `LengthAware`→`paginates()`, `Simple`→`simplePaginates()`, `Cursor`→`cursorPaginates()`, `None`→`get()` (ALL rows, no cap). |
| `$scopes` | `[]` | index | Scopes applied after `initializer()`. |
| `$with` | `[]` | index | `->with()` |
| `$withCount` | `[]` | index | `->withCount()` |
| `$withAggregate` | `[]` | index | `['relation' => 'column']` → `withAggregate(relation, column)` (no function arg here; sum-less "aggregate" = Laravel default). |
| `$loadScopes` | `[]` | show, edit | Scopes on the single-record query. |
| `$load` | `[]` | show, edit | `->with()` |
| `$loadCount` | `[]` | show, edit | `->withCount()` |
| `$loadAggregate` | `[]` | show, edit | as `$withAggregate` |
| `$allowedIncludes` | `[]` | index, show, edit | Allowlist for `filters.include`. Empty = feature off. |
| `$allowTrashedFilter` | `false` | index only | Enables `filters.trashed` = `with`/`only`; requires model `SoftDeletes`. |
| `$updatableColumns` | `['status']` | updateColumn only | Allowlist for the `{column}` route segment → 403 `AuthorizationException` otherwise. **`changeStatus` is NOT gated by this** — it only requires the column to exist and be fillable. |
| `$forceDelete` | `false` | destroy, delete (bulk) | `true` → `forceDelete()` instead of `delete()`. |
| `$updateScopes` | `[]` | update | Applied when finding the record (`findOrFail` → 404 if scoped out). |
| `$deleteScopes` | `[]` | destroy, bulk delete | idem |
| `$columnScopes` | `[]` | changeStatus, updateColumn | idem |
| `$restoreScopes` | `[]` | restore | idem (query is `initializer()->onlyTrashed()`) |

Scope array syntax (every `*Scopes` property):

```php
['active']                                 // scopeActive($q)
['status' => 1]                            // scopeStatus($q, 1)
['statusIn' => [1, 2, 3]]                  // scopeStatusIn($q, 1, 2, 3)   ← array is SPREAD as args
['custom' => fn ($q) => $q->where(...)]    // scopeCustom($q, Closure)   ← closure passed to the scope
```

Only scopes the model declares (`hasNamedScope`) are called; others are ignored silently.
To pass a single array *value* to a scope, wrap it: `['statusIn' => [[1, 2, 3]]]`.

## Query pipeline

**index:** `Model::query()->initializer()` (filters→scopes, sort) → `with/withCount/withAggregate`
→ `$scopes` → requested includes → trashed filter → search (`likeWhere` on `Searchable`)
→ paginate by `$paginationType`.

**show/edit:** `initializer()` → `load/loadCount/loadAggregate` → `$loadScopes` → requested includes
→ `findOrFail($id)` (404). Because `initializer()` runs, `?filters=` scopes also apply to show.

**restore / restoreAll / permanentDelete:** `initializer()->onlyTrashed()` (+ `$restoreScopes` for restore).

## Write operations (shared `perform*` methods)

Every write is wrapped in `DB::beginTransaction()` … `commit()`, rolled back on any `Throwable`,
with hooks fired inside the transaction:

| Method | Steps |
|---|---|
| `performStore()` | validated data → `newInstance()->fill()` → `beforeCreate` → `save()` → `afterCreate` |
| `performUpdate($id)` | validated data → `findModel($id, $updateScopes)` → `beforeUpdate` → `update($data)` → `afterUpdate` |
| `performDestroy($id)` | `findModel($id, $deleteScopes)` → `beforeDelete` → `delete()`/`forceDelete()` → `afterDelete` |
| `performBulkDelete()` | `request()->validate([field => required|array|max:N, field.* => required|exists:table,key])` (422 on failure) → loop: find + hooks + delete |
| `performChangeStatus($id, $column='status')` | `findModel($id, $columnScopes)` → column must exist & be fillable → `beforeStatusChange` → `update([$column => truthy ? 0 : 1])` → `afterStatusChange` |
| `performUpdateColumn($id, $column='status')` | `findModel($id, $columnScopes)` → `assertColumnUpdatable` (403) → exists & fillable → `beforeColumnUpdate` → `update([$column => request()->input($column)])` → `afterColumnUpdate`. **The value comes from `request()->input($column)` — unvalidated**; keep the allowlist tight. |
| `performRestore($id)` | `initializer()->onlyTrashed()` + `$restoreScopes` → `findOrFail` → `beforeRestore` → `restore()` → `afterRestore` |
| `performRestoreAll()` | `initializer()->onlyTrashed()->restore()` (no hooks) |
| `performPermanentDelete($id)` | `initializer()->onlyTrashed()->findOrFail` → `beforeForceDelete` → `forceDelete()` → `afterForceDelete` |

### Validated data → persisted data (`resolveValidatedData`)

`$request->validated()` is intersected with the model's `$fillable`; if `$fillable` is empty
(`$guarded` models, including `$guarded = []`), it is intersected with the **actual table columns**
(memoised `Schema::getColumnListing`). Request-only keys (`tag_ids`, nested arrays) are therefore
never mass-assigned — read them in `afterCreate`/`afterUpdate` via `request()`.

## BaseController (API) return values

| Method | Returns | Status |
|---|---|---|
| `index()` | `$resource::collection($paginated)` | 200 |
| `show($id)` | `new $resource($model)` | 200 / 404 |
| `store()` | resource `->toResponse()->setStatusCode(201)` | 201 |
| `update($id)`, `changeStatus($id,$col)`, `updateColumn($id,$col)`, `restore($id)` | `JsonResource` | 200 |
| `destroy($id)`, `delete()`, `restoreAll()`, `permanentDelete($id)` | `$this->noContent()` | 204 |

Exceptions are **not** caught — validation (422), authorization (403), `ModelNotFound` (404)
and DB errors (500) propagate to Laravel's handler. The `error()` 400 envelope is only what
*you* return from `HasApiResponse` in custom code.

## BaseWebController (Blade) behaviour

| Method | Returns |
|---|---|
| `index()` | `view("{prefix}.index", [$collectionName => paginated])` |
| `create()` | `view("{prefix}.create")` |
| `show($id)`, `edit($id)` | `view("{prefix}.show|edit", [$resourceName => model])` |
| `store()`, `update()`, `destroy()`, `delete()`, `changeStatus()`, `updateColumn()`, `restore()`, `restoreAll()`, `permanentDelete()` | `RedirectResponse` via `perform()` |

`perform(Closure $op, string $successMessage)`:
- success → `redirect()->route("{$routePrefix}.index")->with(flash_key_success, $message)`
- `ValidationException` / `ModelNotFoundException` → **re-thrown** (Laravel redirects back with errors / renders 404)
- any other `Throwable` → `report($e)`, then `back()->withInput()->with(flash_key_error, APP_DEBUG ? $e->getMessage() : genericErrorMessage())`

Overridable protected helpers: `viewName($suffix)`, `redirectWithSuccess($route, $message, $params = [])`,
`redirectBackWithError($message)`, `genericErrorMessage()`, and the message methods
`storeSuccessMessage() updateSuccessMessage() destroySuccessMessage() bulkDeleteSuccessMessage()
statusChangeSuccessMessage() columnUpdateSuccessMessage() restoreSuccessMessage()
restoreAllSuccessMessage() permanentDeleteSuccessMessage()`.

Web routes: `Route::resource('admin/posts', PostController::class)` covers the 7 RESTful actions;
add `changeStatus`, `updateColumn`, `restore`, `restoreAll`, `permanentDelete`, `delete` manually
or use `Route::fastApiResource()` (no `create`/`edit` in that macro — add those two by hand).

## Overriding safely

Prefer overriding the `protected` hooks/`perform*`/`build*Query()` methods over the public actions:

```php
protected function buildIndexQuery(): Builder
{
    return parent::buildIndexQuery()->where('tenant_id', auth()->user()->tenant_id);
}

protected function findModel(int|string $id, array $scopes = []): Model
{
    return parent::findModel($id, $scopes + ['forTenant' => auth()->id()]);
}
```

Other overridable internals: `resolveValidatedData()`, `applyScopes()`, `applyAggregates()`,
`applySearch()`, `applyRequestedIncludes()`, `applyTrashedFilter()`, `requestFilters()`,
`paginateQuery()`, `assertColumnUpdatable()`, `assertFillableColumn()`.
