# Helper Functions

Global helper functions autoloaded via Composer. Available throughout your application.

## Date & Time

### diffForHumans

```php
diffForHumans(?string $date): ?string
```

```php
diffForHumans('2025-01-15');  // "2 months ago"
diffForHumans(null);          // null
```

### ymdDate

```php
ymdDate(?string $date, string $format = 'Y-m-d'): ?string
```

```php
ymdDate('2025-01-15 14:30:00');          // "2025-01-15"
ymdDate('2025-01-15', 'd/m/Y');          // "15/01/2025"
ymdDate(null);                           // null
```

### dateForReports

```php
dateForReports(?string $date, string $format = 'Y-m-d H:i'): ?string
```

Returns `null` on invalid date (doesn't throw).

```php
dateForReports('2025-01-15 14:30:00');   // "2025-01-15 14:30"
dateForReports('invalid');               // null
```

### toFormattedDateString

```php
toFormattedDateString(?string $date): ?string
```

```php
toFormattedDateString('2025-01-15');     // "January 15, 2025"
```

### toDateString

```php
toDateString(?string $date): ?string
```

```php
toDateString('2025-01-15 14:30:00');     // "2025-01-15"
```

### toDateTimeString

```php
toDateTimeString(?string $date): ?string
```

```php
toDateTimeString('2025-01-15 14:30');    // "2025-01-15 14:30:00"
```

### toTimeString

```php
toTimeString(?string $date): ?string
```

```php
toTimeString('2025-01-15 14:30:00');     // "14:30:00"
```

## Duration

### parseTimeToSeconds

```php
parseTimeToSeconds(string $timeString): int|float
```

Supports `H:i:s`, `i:s`, and plain integer seconds.

```php
parseTimeToSeconds('01:30:00');  // 5400
parseTimeToSeconds('30:45');     // 1845
parseTimeToSeconds('120');       // 120
```

### formatDuration

```php
formatDuration(int|float|null $duration, ?string $format = '%y %mo %d %h %m %s', string $separator = ' '): string
```

Placeholders: `%y` years, `%mo` months, `%d` days, `%h` hours, `%m` minutes, `%s` seconds.

```php
formatDuration(3661);                    // "1h 1m 1s"
formatDuration(90061);                   // "1d 1h 1m 1s"
formatDuration(3661, '%h %m');           // "1h 1m"
formatDuration(3661, '%h %m', '-');      // "1h-1m"
formatDuration(null);                    // "0s"
formatDuration(0);                       // "0s"
formatDuration(-3661);                   // "1h 1m 1s" (absolute)
```

## Filtering & Sorting

### filterValue

```php
filterValue(string $key = 'date'): ?string
```

Reads from `?filters={"key":"value"}` query parameter.

```php
// URL: ?filters={"date":"2025-01-15","status":"1"}
filterValue('date');     // "2025-01-15"
filterValue('status');   // "1"
filterValue('missing');  // null
```

### arrayFilters

```php
arrayFilters(array|string|null $data): array<string, mixed>
```

Converts JSON string or array to filtered array (removes falsy values).

```php
arrayFilters('{"active":1,"name":""}');  // ['active' => 1]
arrayFilters(['a' => 1, 'b' => null]);  // ['a' => 1]
arrayFilters(null);                      // []
```

### flattenArray

```php
flattenArray(array $data, int $depth = 0): array<int|string, mixed>
```

```php
flattenArray(['a' => [1, 2], 'b' => [3]]);    // [1, 2, 3]
flattenArray(['a' => [1, [2]]], 1);            // [1, [2]]
```

### sortDirection

```php
sortDirection(): string
```

Returns sort direction from `?descending` query parameter.

```php
// ?descending=true  → "ASC"
// ?descending=false → "DESC"
```

### sortBy

```php
sortBy(): array<string>|string|null
```

Returns sort key(s) from `?sort` request parameter.

```php
// ?sort=name          → "name"
// ?sort[]=name&sort[]=id → ["name", "id"]
// (not set)           → null
```

## Utility

### uuid

```php
uuid(): UuidInterface
```

Generate UUID v4 via `Str::uuid()`.

### slug

```php
slug(?string $text = null): ?string
```

```php
slug('Hello World');  // "hello-world"
slug(null);           // null
```

### relativePath

```php
relativePath(string $path): string
```

Returns path relative to `base_path()`.

```php
relativePath('/var/www/app/Models/Post.php');  // "app/Models/Post.php"
```

### classShortName

```php
classShortName(string $param): ?string
```

Uses reflection. May throw `ReflectionException`.

```php
classShortName(App\Models\Post::class);  // "Post"
```

## Introspection

### scopeMethods

```php
scopeMethods(object $class): array<string>
```

Returns all public methods starting with `"scope"`.

```php
scopeMethods(new Post);  // ["scopeActive", "scopePublished", ...]
```

### tableColumns

```php
tableColumns(string|Model $table = 'users'): array<string>
```

Returns ordered column list: `[id, ...sorted middle..., created_at, updated_at, deleted_at]`.

```php
tableColumns('posts');          // ["id", "active", "desc", ...]
tableColumns(Post::class);     // Same — accepts class-string
tableColumns(new Post);        // Same — accepts model instance
```

### fillableCsv

```php
fillableCsv(string $model): string
```

```php
fillableCsv(Post::class);  // "name,desc,status,active"
fillableCsv('posts');       // All columns from table (if not a model class)
```

### columnsCsv

```php
columnsCsv(string $model, string $separator = ','): string
```

```php
columnsCsv(Post::class);        // "id,active,desc,name,..."
columnsCsv(Post::class, '|');   // "id|active|desc|name|..."
```

## Class Discovery

### appClasses

```php
appClasses(string $path = 'App', array $excluding = []): array<int, string>
```

Recursively finds PHP classes in app directory.

```php
appClasses('Models');                                   // ["App\Models\Post", ...]
appClasses('Models', [App\Models\User::class]);         // Excludes User
```

### databaseClasses

```php
databaseClasses(?string $directory = null, array $excluding = []): array<int, string>
```

Recursively finds PHP classes in database directory.

```php
databaseClasses('seeders');     // ["Database\Seeders\PostSeeder", ...]
databaseClasses();              // All classes in database/
```
