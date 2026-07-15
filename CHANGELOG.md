# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [3.0.0] — 2026-07-15

### Security & Hardening
_Pre-release hardening pass from a full package audit — all covered by new tests._
- **`initializer()` no longer dispatches arbitrary model methods from client filter keys.** Filter keys are matched only against declared query scopes (`hasNamedScope`). The previous `method_exists()` fallback could invoke any public model method whose studly-cased name matched a filter key — e.g. `?filters={"save":{}}` reached `Model::save()` and attempted a write on every index request. Non-scope keys are now silently ignored.
- **`updateColumn` is restricted to an allowlist.** The client-controlled `{column}` route segment previously accepted any *fillable* column (e.g. `is_admin`, `role_id`, `email_verified_at`), bypassing the update FormRequest entirely. A new `protected array $updatableColumns = ['status']` gates the endpoint and returns 403 otherwise. **Breaking:** to update columns other than `status`, add them to `$updatableColumns` on the controller.
- **`rowsPerPage=0` no longer returns the whole table by default.** `pagination.allow_all` now defaults to `false`, so `?rowsPerPage=0` falls back to `default_per_page` instead of returning an unbounded, unauthenticated result set (a DoS vector). When `allow_all` is enabled, the new `pagination.max_all` (default `1000`; `0` = unbounded) caps the "show all" path. **Breaking:** if you relied on `rowsPerPage=0` returning all rows, set `pagination.allow_all` to `true`.
- **`BaseWebController` no longer leaks raw exception messages.** Unexpected write failures are logged via `report()` and shown as a generic, overridable message (`genericErrorMessage()`) unless `APP_DEBUG` is on — preventing SQL/schema disclosure via flash messages.
- `parseTimeToSeconds()` computed a negative value under Carbon 3 (signed `diffInSeconds`); it now computes arithmetically and always returns a non-negative `int` (return type narrowed from `int|float` to `int`).
- Performance: `resolveValidatedData()` memoises the table column listing (no schema metadata query per write for `$guarded` models), and `modelUsesSoftDeletes()` is memoised per model class.

### Breaking Changes
- **Dropped support for Laravel 11.** The minimum supported framework is now Laravel 12 (`illuminate/* ^12.0||^13.0`, `orchestra/testbench ^10.0||^11.0`). PHP 8.2+ is still supported. Run Laravel 12 or 13.
- `BaseController` and `BaseWebController` no longer extend `Illuminate\Routing\Controller`. They now implement `HasMiddleware` directly. The old `$this->middleware()->only()` constructor registration is removed.
- Automatic permission middleware registration (`registerPermissionMiddleware()`) is removed. Declare permissions explicitly by overriding the static `middleware()` method and calling `static::permissionMiddleware('slug')`.
- `spatie/laravel-permission` is no longer a hard dependency. It has moved to `suggest`. Install it separately if you use permissions: `composer require spatie/laravel-permission`.
- `paginateQuery()` return type narrowed from `mixed` to `Paginator<int, Model>|CursorPaginator<int, Model>|Collection<int, Model>`.
- `uuid()` helper now returns `string` instead of `UuidInterface`.
- The `HasUuidPrimaryKey` trait is removed. It duplicated framework functionality — use Laravel's first-party `Illuminate\Database\Eloquent\Concerns\HasUuids` (UUID v7, recommended) or `HasVersion4Uuids` instead.

### Added
- `Pagination::resolveEffectivePerPage(\Closure $countFn)` — COUNT query is deferred and only fires when "show all" is requested (`rowsPerPage=0`), avoiding an extra query on every normal paginated request.
- Static schema index cache in `AnonymizesOnDelete` — `Schema::getIndexes()` is now called once per table per process instead of on every soft-delete.
- Support for models using `$guarded` (including `$guarded = []`). `resolveValidatedData()` now falls back to the actual table columns when `$fillable` is empty, instead of persisting nothing.
- **`Route::fastApiResource('posts', PostController::class)` macro** — registers the full route set (index, store, show, update, destroy, bulk delete, restoreAll, changeStatus, updateColumn, restore, permanentDelete) in one line, with `only`/`except`/`parameter`/`names` options mirroring Laravel's `apiResource`.
- **Client-driven eager loading** — set `$allowedIncludes` on a controller and clients request relations per call via an `include` key inside the filters JSON: `?filters={"include":"author,tags"}` (string or array form). Restricted to the allowlist (anything else is ignored), applied on both index and show.
- **Soft-delete filtering on index** — set `$allowTrashedFilter = true` and clients pass a `trashed` key inside the filters JSON: `?filters={"trashed":"with"}` or `{"trashed":"only"}`. Opt-in and gated on the model being soft-deletable.
- **Bulk-delete cap** — `fast-api.bulk.max_rows` (default 1000) bounds how many IDs the bulk delete endpoint accepts; set to 0 to disable. The request field name is configurable via `fast-api.bulk.field` (default `delete_rows`).
- **Fully configurable query-parameter keys** — `fast-api.query.*` lets you rename every key the index/show reads (`filters`, `search`, `sortBy`, `descending`, `rowsPerPage`, `page`, `cursor`, and the in-filters `include`/`trashed`). Defaults are unchanged. Centralised in a new `Anil\FastApiCrud\Support\QueryParams` resolver used by the macros, pagination helper, and controllers.
- **Configurable default sort** — `fast-api.sorting.default_column` (default `id`) and `fast-api.sorting.default_descending` (default `true`) control ordering when the request has no sort key and the model isn't `Sortable`.
- `LICENSE` file (MIT).
- `CHANGELOG.md`.

### Fixed
- **`store`/`update`/`destroy`/etc. on a missing record now return 404, not 400.** The API controller no longer wraps `ModelNotFoundException` (and every other exception) into a generic 400 with a leaked message. Validation, authorization, not-found, and unexpected errors now propagate to the framework's exception handler and render with correct, debug-aware status codes (422/403/404/500).
- `changeStatus` now toggles correctly for boolean and string (`"0"`/`"1"`) casts, not just integer `1`/`0`.
- `BaseWebController` no longer flattens `ValidationException` into a flash message — validation errors and old input are preserved on redirect-back, and missing records render a 404.
- Transaction rollback in the `perform*` methods now triggers on any `Throwable` (including `Error`/`TypeError`), not only `Exception`.
- **`ReplicatesWithRelations` is now actually usable for relation graphs** — it had three latent bugs that surfaced the moment it touched the database: (1) `HasMany`/`HasOne` children were saved *before* the parent foreign key was set, breaking on non-nullable FKs; children are now persisted through the parent relation; (2) `reApplyCasts()` copied the primary key and timestamps onto the replica (because `getCasts()` reports `id => int`), undoing `replicate()`'s exclusion; (3) cross-model recursion called a `private` method on a different model class, throwing `BadMethodCallException`. Covered by new tests for `HasMany`/`BelongsTo`/`BelongsToMany`.

### Changed
- API controller actions now have narrowed return types (`JsonResource` instead of `JsonResource|JsonResponse`) since the error-wrapping branch was removed.
- `BaseWebController` write actions share a single `perform()` helper, removing nine duplicated try/catch blocks.
- `MakeAllCommand` uses the `File` facade (`ensureDirectoryExists`/`put`/`exists`) instead of raw `mkdir`/`file_put_contents`/`file_exists`, and `handle()` now returns proper `SUCCESS`/`FAILURE` exit codes.
- `applyScopes()` now uses `Model::hasNamedScope()` instead of manual `method_exists` double-check. This correctly supports scopes defined via the `#[LocalScope]` PHP attribute (Laravel 12+).
- `permissionMiddleware()` now includes the `fast-api.permissions.enabled` config guard and a `class_exists` check for Spatie, making it safe to call unconditionally.
- Lifecycle hook methods (`beforeCreate`, `afterCreate`, `beforeUpdate`, `afterUpdate`, `beforeDelete`, `afterDelete`, `beforeStatusChange`, `afterStatusChange`, `beforeColumnUpdate`, `afterColumnUpdate`, `beforeRestore`, `afterRestore`, `beforeForceDelete`, `afterForceDelete`) consolidated — each is now a one-liner delegating to a private `fireModelHook()` dispatcher.
- `performRestore`, `performRestoreAll`, and `performPermanentDelete` now chain `initializer()->onlyTrashed()` consistently with the rest of the codebase.
- `BaseController` 204 responses now call `noContent()` directly instead of wrapping an empty array in `success()`.
- `Str::studly()` in the `initializer` macro is now cached in a local variable instead of being evaluated twice per filter.
- CI matrix tests PHP 8.2–8.5 × Laravel 12–13 (both `prefer-stable` and `prefer-lowest`).
- `HasPermissionSlug` contract and `config/fast-api.php` comments updated to reflect the new explicit `middleware()` pattern.

---

## [2.0.4.12] — 2024-03-05

### Changed
- General stability updates and code style fixes.

## [2.0.4.11] — 2024-02-19

### Fixed
- `withAggregates` macro on the controller was not being applied correctly.

## [2.0.4.10] — 2024-02-19

### Changed
- General updates and code style fixes.

## [2.0.4.9] — 2024-02-14

### Added
- `withCountWhereHas` and `orWithCountWhereHas` Builder macros — combine `withCount` + `whereHas`/`orWhereHas` in a single call.

## [2.0.4.8] — 2024-02-14

### Fixed
- `equalWhere` macro edge cases.
- `initializer` macro filter handling improvements.
- Code style fixes.

## [2.0.4.7] — 2024-02-06

### Changed
- General updates and code style fixes.

## [2.0.4.6] — 2024-02-06

### Changed
- General updates.

## [2.0.4.5] — 2024-02-06

### Changed
- General updates.

## [2.0.4.4] — 2024-01-30

### Changed
- Code formatting and style cleanup.

## [2.0.4.3] — 2023-12-19

### Fixed
- Namespace fix for the `AnonymizesOnDelete` (delete event) trait.

## [2.0.4.2] — 2023-12-19

### Changed
- General updates.

## [2.0.4.1] — 2023-08-21

### Changed
- `tableColumns()` helper now returns `id` first and timestamp columns (`created_at`, `updated_at`, `deleted_at`) last, with remaining columns sorted alphabetically in between.

## [2.0.4] — 2023-08-21

### Added
- `tableColumns()` / `getColumns()` helper function to retrieve an ordered list of column names for a table or Eloquent model.

## [2.0.3] — 2023-07-21

### Fixed
- `initializer` macro now falls back to `newQuery()` when the model does not define an `initializeModel` method, preventing a fatal error on plain models.

## [2.0.2] — 2023-05-30

### Changed
- Config file updates.

## [2.0.1] — 2023-05-10

### Changed
- Argument types relaxed from `string` to `mixed` in several places to improve compatibility.

## [2.0.0] — 2023-05-06

### Breaking Changes
- Full package rewrite targeting Laravel 10+.
- Introduced `BaseController` and `BaseWebController` as the primary extension points.
- `initializer` Builder macro replaces the old `defaultOrder` and filter helpers.
- `paginates`, `simplePaginates`, and `cursorPaginates` Builder macros added, powered by the `Pagination` support class.
- Config file (`fast-api.php`) introduced with pagination, soft-delete, response, permission, and web sections.

### Added
- `HasCrudOperations` trait — shared CRUD query building, lifecycle hooks, and operation execution for both API and web controllers.
- `HasApiResponse` trait — typed helper methods for every HTTP status code.
- `HasDateScopes` model trait — `today`, `yesterday`, `thisWeek`, `lastWeek`, `monthToDate`, `thisMonth`, `lastMonth`, `quarterToDate`, `yearToDate`, `last7Days`, `last30Days`, `lastQuarter`, `lastYear`, `date` scopes.
- `AnonymizesOnDelete` model trait — appends `_{timestamp}` to unique column values on soft delete to prevent constraint violations.
- `HasUuidPrimaryKey` model trait — auto-assigns UUID v4 on creation.
- `ReplicatesWithRelations` model trait — deep-replicates a model along with its loaded relations to any depth, with circular reference protection.
- `Searchable`, `Sortable`, `HasPermissionSlug` contracts.
- `CrudAction` and `PaginationType` enums.
- `ApiException` — renders a structured JSON error response with optional debug detail.
- `fast-api:make-all` Artisan command — scaffolds model, migration, factory, seeder, controller, resource, and requests in one step. Supports `--web` flag for Blade controller + view stubs.
- `CollectionMacros::paginate` — paginate an in-memory Collection.
- CI matrix covering PHP 8.2/8.3 × Laravel 10/11.
- PHPStan at level 10 via Larastan.

---

## [1.0.3] — 2023-04-23

### Fixed
- Namespace fix for the `HasUuidPrimaryKey` (UUID) trait.

## [1.0.2] — 2023-04-18

### Added
- `getSqlQuery()` and `toRawSql()` helper functions.
- In-memory Collection `paginate()` macro.

## [1.0.1] — 2023-04-18

### Changed
- Package auto-discovery configuration.
- Config file auto-merging via `mergeConfigFrom`.

## [1.0.0] — 2023-04-11

### Added
- Initial release.
- Core helper functions (`parseTimeToSeconds`, `formatDuration`, `diffForHumans`, `ymdDate`, `filterValue`, `sortBy`, `scopeMethods`, `uuid`, and more).
- Date scopes, UUID trait, delete event trait.
- Basic controller scaffolding.
