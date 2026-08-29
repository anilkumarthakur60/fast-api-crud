# Global helper functions (`src/Support/Helpers.php`, autoloaded)

All wrapped in `function_exists` guards — the app can override any of them by declaring first.

## Dates (Carbon; `null`/empty input → `null`)
| Function | Returns |
|---|---|
| `diffForHumans(?string $date): ?string` | `Carbon::parse($date)->diffForHumans()` |
| `ymdDate(?string $date, string $format = 'Y-m-d'): ?string` | formatted |
| `dateForReports(?string $date, string $format = 'Y-m-d H:i'): ?string` | formatted; **invalid → null** (try/catch); `null` input → *now* formatted (Carbon::parse(null)) |
| `toFormattedDateString(?string)` | "January 15, 2025" |
| `toDateString(?string)` | `Y-m-d` |
| `toDateTimeString(?string)` | `Y-m-d H:i:s` |
| `toTimeString(?string)` | `H:i:s` |

## Durations
| Function | Behaviour |
|---|---|
| `parseTimeToSeconds(string): int` | `"H:i:s"` → seconds; `"i:s"` → seconds; otherwise `(int)` of the string. Never negative. |
| `formatDuration(int\|float\|null $seconds, ?string $format = '%y %mo %d %h %m %s', string $separator = ' '): string` | Uses abs value; units 365d/30d; labels `yr mo d h m s`; zero-value units dropped; non-`%` tokens in `$format` are kept literally; `null`/`0`/all-zero → `'0s'`. `formatDuration(3661)` → `1h 1m 1s`. |

## Request filters / sorting (read the *default* key names, not `fast-api.query.*`)
| Function | Behaviour |
|---|---|
| `filterValue(string $key = 'date'): ?string` | `json_decode(request('filters'))[$key]` when it is a string, else null |
| `arrayFilters(array\|string\|null $data): array` | JSON string or array → `collect()->filter()->all()` (drops falsy: `0`, `''`, `null`, `false`, `[]`) |
| `flattenArray(array $data, int $depth = 0): array` | `collect($data)->flatten($depth)` (`0` = fully flat, like Laravel) |
| `sortDirection(): string` | ⚠ returns `'ASC'` when `?descending=true`, `'DESC'` otherwise — inverted naming; do not rely on it for `initializer()` (which handles ordering itself) |
| `sortBy(): array\|string\|null` | reads `?sort=` (**not** `sortBy`); string, filtered array, or null |

## Schema / model introspection
| Function | Behaviour |
|---|---|
| `scopeMethods(object $model): array` | public method names starting with `scope` |
| `tableColumns(string\|Model $table = 'users'): list<string>` | accepts table name, model class-string, or instance; returns `id`, other columns sorted, then `created_at`, `updated_at`, `deleted_at` (always appended, even if absent) |
| `fillableCsv(string $modelOrTable): string` | model → `implode(',', $fillable)`; table → all columns |
| `columnsCsv(string $modelOrTable, string $separator = ','): string` | `implode($sep, tableColumns(...))` |

## Class discovery
| Function | Behaviour |
|---|---|
| `appClasses(string $path = 'App', array $excluding = []): list<string>` | walks `app_path($path)` recursively, derives `App\...` FQCNs from file paths (no autoload check). ⚠ default `'App'` looks in `app/App` — pass `'Models'`, `'Http/Controllers'`, or `''`. |
| `databaseClasses(?string $directory = null, array $excluding = []): list<string>` | regex-scans `database/{dir}` PHP files for `namespace` + `class` |

## Misc
| Function | Behaviour |
|---|---|
| `uuid(): string` | `(string) Str::uuid()` (v4) |
| `slug(?string $text): ?string` | `Str::slug` or null |
| `relativePath(string $absolute): string` | strips `base_path() . '/'` |
| `classShortName(string $abstract): ?string` | `app($abstract)` then reflection short name; throws if the container cannot resolve it |
| `_dd(mixed ...$vars): never` | `dd()` that first sends `Access-Control-Allow-*: *` headers and HTTP 500 so browsers/API clients show the dump; exits 1 |
