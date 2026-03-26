# Model Traits

Traits you can add to your Eloquent models for extra functionality.

## HasDateScopes

Adds 14 query scopes for common date ranges. All accept an optional `$column` parameter (default: `created_at`).

```php
use Anil\FastApiCrud\Concerns\HasDateScopes;

class Post extends Model
{
    use HasDateScopes;
}
```

### Available Scopes

| Scope | Description |
|-------|-------------|
| `today($column)` | Records from today |
| `yesterday($column)` | Records from yesterday |
| `thisWeek($column)` | Monday to now |
| `lastWeek($column)` | Last Monday to Sunday |
| `monthToDate($column)` | 1st of month to now |
| `thisMonth($column)` | Entire current month |
| `lastMonth($column)` | Entire previous month |
| `quarterToDate($column)` | Start of quarter to now |
| `lastQuarter($column)` | Previous quarter |
| `yearToDate($column)` | January 1 to now |
| `lastYear($column)` | Last 12 months |
| `last7Days($column)` | Last 7 days |
| `last30Days($column)` | Last 30 days |
| `date($search, $column)` | Custom range: `"YYYY-MM-DD to YYYY-MM-DD"` |

### Usage

```php
// Default column (created_at)
Post::query()->today()->get();
Post::query()->lastWeek()->get();
Post::query()->last30Days()->get();

// Custom column
Post::query()->today('published_at')->get();
Post::query()->lastMonth('updated_at')->get();

// Custom range
Post::query()->date('2025-01-01 to 2025-01-31')->get();
Post::query()->date('2025-03-15 to 2025-03-15')->get();  // Single day
Post::query()->date('2025-01-01')->get();  // Same day start and end

// Chain with other scopes
Post::query()
    ->thisMonth()
    ->where('active', true)
    ->get();
```

The `date()` scope silently returns the query unmodified if the input is null, empty, or unparseable.

All scopes qualify the column with the table name (`{table}.{column}`) to avoid ambiguity in joins.

### Use with Filters

These scopes work with the `?filters=` query parameter:

```
GET /posts?filters={"today":1}
GET /posts?filters={"lastWeek":1}
GET /posts?filters={"date":"2025-01-01 to 2025-01-31"}
```

## HasUuid

Automatically assigns UUID v4 as the primary key on model creation.

```php
use Anil\FastApiCrud\Concerns\HasUuid;

class Post extends Model
{
    use HasUuid;
}
```

### What It Does

- **`bootHasUuid()`** — On `creating` event, assigns `Str::uuid()` to the primary key if empty
- **`getIncrementing()`** — Returns `false` (UUID is not auto-incrementing)
- **`getKeyType()`** — Returns `'string'`

### Migration

```php
Schema::create('posts', function (Blueprint $table) {
    $table->uuid('id')->primary();  // Use uuid instead of id()
    $table->string('name');
    $table->timestamps();
});
```

### Usage

```php
$post = Post::create(['name' => 'Test']);
$post->id; // "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d"
```

You can also set a UUID manually — the trait only assigns one if the key is empty.

## HandlesDeleteEvents

Anonymizes unique column values on soft delete to prevent constraint violations.

```php
use Anil\FastApiCrud\Concerns\HandlesDeleteEvents;

class User extends Model
{
    use SoftDeletes, HandlesDeleteEvents;
}
```

### What It Does

When a model is soft-deleted, all unique (non-primary) columns get `_{timestamp}` appended:

```
Before delete: email = "john@example.com"
After delete:  email = "john@example.com_1705312800"
```

This prevents unique constraint violations when creating a new record with the same value while the old one is soft-deleted.

### Requirements

- Model must use the `SoftDeletes` trait
- Controlled by `fast-api.soft_delete.anonymize_unique_columns` config (default: `true`)
- Only affects columns that are part of a unique index (not primary key)
- Uses `saveQuietly()` to avoid triggering additional model events

## HasReplicatesWithRelation

Replicate a model along with all its loaded relations.

```php
use Anil\FastApiCrud\Concerns\HasReplicatesWithRelation;

class Post extends Model
{
    use HasReplicatesWithRelation;
}
```

### Usage

```php
// Load the model with relations you want to replicate
$post = Post::with(['tags', 'comments', 'author'])->find(1);

// Replicate everything
$clone = $post->replicateWithRelations();
// Returns a saved copy of the post with all relations duplicated
```

### Supported Relations

| Relation Type | Behavior |
|---------------|----------|
| `BelongsTo` | Replicates the related model recursively |
| `MorphTo` | Replicates the related model recursively |
| `HasOne` | Replicates the related model and saves to new parent |
| `MorphOne` | Replicates the related model and saves to new parent |
| `HasMany` | Replicates each related model and saves to new parent |
| `MorphMany` | Replicates each related model and saves to new parent |
| `BelongsToMany` | Syncs the same IDs to the new model (no duplication) |
| `MorphToMany` | Syncs the same IDs to the new model (no duplication) |

### Not Supported

`HasOneThrough` and `HasManyThrough` relations throw an `Exception` with a descriptive message.

### Castable Attributes

The trait automatically re-applies castable attributes (numeric, boolean, string, json) to the replicated model via `matchingCastableAttributes()` to ensure proper type handling during replication.

### Recursive Replication

If a related model also uses `HasReplicatesWithRelation`, it will be replicated recursively with its own relations. Otherwise, a simple `replicate()` is used.
