# Routes — `Route::fastApiResource()`

Registered by `Anil\FastApiCrud\Macros\RouteMacros` on the `Router`.

```php
Route::fastApiResource(string $name, string $controller, array $options = []): void
```

Options: `only` (list of actions), `except`, `parameter` (default `id`), `names` (route-name
prefix, default = `$name` with `/` → `.`). Route names are `"{prefix}.{action}"`.

Registration order matters (collection routes first so `{id}` never shadows a static segment):

| # | Action | Methods | URI suffix | Name |
|---|---|---|---|---|
| 1 | index | GET | `` | posts.index |
| 2 | store | POST | `` | posts.store |
| 3 | delete (bulk) | DELETE | `` | posts.delete |
| 4 | restoreAll | POST | `/restore` | posts.restoreAll |
| 5 | updateColumn | PATCH | `/{id}/status/{column}` | posts.updateColumn |
| 6 | changeStatus | PATCH | `/{id}/status` | posts.changeStatus |
| 7 | restore | PATCH | `/{id}/restore` | posts.restore |
| 8 | permanentDelete | DELETE | `/{id}/force` | posts.permanentDelete |
| 9 | show | GET | `/{id}` | posts.show |
| 10 | update | PUT, PATCH | `/{id}` | posts.update |
| 11 | destroy | DELETE | `/{id}` | posts.destroy |

Note `delete` (bulk) and `destroy` share the `DELETE` verb; bulk is on the collection URI with a
body `{"delete_rows":[...]}`. `create` and `edit` (web) are **not** registered by the macro.

Helpers: `RouteMacros::resolveActions($options)` and `RouteMacros::routeDefinition($action)`
(unknown action → `[['GET'], '/' . kebab(action)]`).

Manual registration works too, e.g. `Route::post('posts/delete', [PostController::class, 'delete'])`.

Middleware: return it from the controller's `public static function middleware(): array`, e.g.
`[...static::permissionMiddleware('posts'), 'auth:sanctum']`. Or group routes in `routes/api.php`.
