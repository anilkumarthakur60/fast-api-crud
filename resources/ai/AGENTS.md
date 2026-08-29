## Laravel CRUD: anil/fast-api-crud

This project builds REST/Blade resources with the `anil/fast-api-crud` package.

- Controllers extend `Anil\FastApiCrud\Http\Controllers\BaseController` (JSON) or
  `BaseWebController` (Blade) and pass `model`, `storeRequest`, `updateRequest`, `resource`
  to `parent::__construct()`. **Do not hand-write index/show/store/update/destroy methods** —
  customise via properties (`$scopes`, `$with`, `$withCount`, `$load`, `$allowedIncludes`,
  `$allowTrashedFilter`, `$updatableColumns`, `$paginationType`, `$forceDelete`), model scopes,
  the `Searchable`/`Sortable`/`HasPermissionSlug` contracts, and `before*/after*` hooks.
- Scaffold: `php artisan fast-api:make-all Post` (`--web` for Blade views; comma-separate models).
- Routes: `Route::fastApiResource('posts', PostController::class, ['only' => [...], 'except' => [...]])`
  registers index, store, show, update, destroy, bulk `delete`, `restoreAll`, `changeStatus`,
  `updateColumn`, `restore`, `permanentDelete`.
- Query params (renamable in `config/fast-api.php` → `query`): `?filters={"scope":value,"include":"rel","trashed":"with"}`
  `&search=&sortBy=&descending=&rowsPerPage=&page=&cursor=`. Every `filters` key maps to a model
  scope; unknown keys are ignored. `rowsPerPage=0` returns all rows only when `pagination.allow_all` is true.
- Validation belongs in `Store{Model}Request`/`Update{Model}Request`; output shaping in `{Model}Resource`.
- Permissions: `public static function middleware(): array { return static::permissionMiddleware('posts'); }`
  → Spatie permissions `view-|store-|update-|delete-|change-status-|restore-posts`. Spatie's `permission`
  middleware alias must be registered in `bootstrap/app.php` (not automatic); seed permissions for the
  guard that authenticates the request; put `auth:*` before the spread.
- Also provided by the package (see the skill references before re-implementing): Builder macros
  (`initializer`, `likeWhere`, `paginates`, `simplePaginates`, `cursorPaginates`, `withAggregates`,
  `withCountWhereHas`), `Collection::paginate`, `HasApiResponse` (`success`/`error` + 62 status
  shortcuts), `ApiException`, model traits `HasDateScopes`, `AnonymizesOnDelete`,
  `ReplicatesWithRelations`, and ~25 global helpers (`formatDuration`, `tableColumns`, `filterValue`, …).
- Gotchas: generated FormRequests have `authorize()` = `false` (403 until changed); `changeStatus`
  is not gated by `$updatableColumns` but `updateColumn` is; all writes run in a transaction with hooks inside.
- Full guidance: the `fast-api-crud` skill (`.claude/skills/fast-api-crud/SKILL.md` or
  `.agents/skills/fast-api-crud/SKILL.md`). Live project introspection: the `fast-api-crud`
  MCP server (`php artisan fast-api:mcp`) — tools `list_controllers`, `describe_controller`,
  `list_routes`, `model_info`, `get_config`, `query_reference`, `read_reference`, `search_reference`, `scaffold`.
