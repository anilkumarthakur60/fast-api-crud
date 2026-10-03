# Lifecycle hooks, contracts, and model traits

## Lifecycle hooks

Controller-level: `protected function beforeCreate(Model $model): void` etc. — the base
implementation calls the model-level hook `if (method_exists($model, $hook)) $model->{$hook}();`
(no arguments, `$this` is the model). Call `parent::afterCreate($model)` in a controller override
if the model hook should still fire.

| Operation | Hooks (in order) | Inside transaction |
|---|---|---|
| store | `beforeCreate` → save → `afterCreate` | yes |
| update | `beforeUpdate` → update → `afterUpdate` | yes |
| destroy / bulk delete (per row) | `beforeDelete` → delete/forceDelete → `afterDelete` | yes |
| changeStatus | `beforeStatusChange` → update → `afterStatusChange` | yes |
| updateColumn | `beforeColumnUpdate` → update → `afterColumnUpdate` | yes |
| restore | `beforeRestore` → restore → `afterRestore` | yes |
| restoreAll | — (none) | yes |
| permanentDelete | `beforeForceDelete` → forceDelete → `afterForceDelete` | yes |

`beforeCreate` receives the filled, **unsaved** model (no id). `afterCreate` has the id — sync
pivots there. Throwing in any hook rolls the whole operation back.

## Contracts (`Anil\FastApiCrud\Contracts`)

```php
interface Searchable      { /** @return array<int,string> */ public function searchableColumns(): array; }
interface Sortable        { /** @return array{sortBy: string, sortByDesc: bool} */ public function sortByDefaults(): array; }
interface HasPermissionSlug { public function getPermissionSlug(): string; }
```

- `Searchable`: `?search=` → `likeWhere(searchableColumns(), $term)`. Entries may be `'column'` or
  `'relation:colA,colB'` (uses `whereHas` + `orWhereAny`). Search is applied on **index only**.
- `Sortable`: used by `initializer()` when the request has no `sortBy`. Keys must be exactly
  `sortBy` and `sortByDesc`.
- `HasPermissionSlug`: convention only — nothing reads it automatically. Use it in
  `middleware()`: `return static::permissionMiddleware((new Post)->getPermissionSlug());`.

## Traits (`Anil\FastApiCrud\Concerns`)

### HasDateScopes
All scopes take `string $column = 'created_at'` and qualify it with the table name.

| Scope | Range |
|---|---|
| `today()` / `yesterday()` | `whereDate = today / yesterday` |
| `thisWeek()` | start of week (Mon) → now |
| `lastWeek()` | previous Mon → previous Sun |
| `monthToDate()` | 1st of month → now |
| `thisMonth()` | 1st → last day of month |
| `lastMonth()` | previous calendar month (no overflow) |
| `quarterToDate()` | start of quarter → now |
| `lastQuarter()` | previous quarter start → current quarter start |
| `yearToDate()` | Jan 1 → now |
| `lastYear()` | now − 1 year → now (rolling 12 months, NOT last calendar year) |
| `last7Days()` / `last30Days()` | today − 6 / 29 days (start of day) → now |
| `date(?string $range, $column)` | `"YYYY-MM-DD to YYYY-MM-DD"` (or a single date); invalid → unchanged |

As filters: `?filters={"today":"published_at"}` or `{"date":"2025-01-01 to 2025-01-31"}`.
Because `initializer()` passes the filter value as the first scope argument, `{"today":1}`
would set `$column = 1` — pass a column name or use a controller `$scopes` entry instead.

### AnonymizesOnDelete
Boots a `deleting` listener. When `fast-api.soft_delete.anonymize_unique_columns` is true, every
column of every **unique, non-primary** index (from `Schema::getIndexes`, cached per table per
process) that holds a string gets `_{unix timestamp}` appended and the model is `saveQuietly()`-ed
before the soft delete. Fires on hard deletes too if the model isn't soft-deletable — only use it
with `SoftDeletes`.

### ReplicatesWithRelations
```php
$clone = $post->replicateWithRelations();                              // loaded relations only
$clone = $post->replicateWithRelations(['comments.replies', 'tags']);  // loadMissing() first
$clone = $post->replicateWithRelations(except: ['slug', 'published_at']);
```
Returns the **saved** replica. Per relation type: `BelongsTo`/`MorphTo` → associate the same parent;
`HasOne/HasMany/MorphOne/MorphMany` → deep-replicate children through the new parent's relation
(FK set before insert; children using the trait recurse, others are shallow-copied);
`BelongsToMany/MorphToMany` → `sync()` the same related ids with pivot columns preserved;
`HasOneThrough/HasManyThrough` → skipped (derived). Circular references are guarded.
Casts are re-applied to attributes that survive `replicate()`.

### HasApiResponse
See `api-response.md`.
