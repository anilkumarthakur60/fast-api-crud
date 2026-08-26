# Permissions — fast-api-crud × spatie/laravel-permission (v6 / v7)

`spatie/laravel-permission` is a **suggested** dependency (not required). It is needed only if a
controller calls `static::permissionMiddleware()`; `fast-api.permissions.enabled = false` turns
that helper into a no-op without uninstalling anything.

## 1. Install & wire Spatie (once per app)

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate            # roles, permissions, model_has_*, role_has_permissions
```

```php
// app/Models/User.php
use Spatie\Permission\Traits\HasRoles;
class User extends Authenticatable { use HasRoles; }
```

**Register the middleware aliases — Spatie does NOT do this automatically:**

```php
// bootstrap/app.php (Laravel 11+/12/13)
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);
})
```

`permissionMiddleware()` emits `permission:view-posts` strings, so the `permission` alias is
mandatory. Missing alias → `Target class [permission] does not exist` (500) on every request.

## 2. Wire a controller

```php
class PostController extends BaseController
{
    public static function middleware(): array
    {
        return [
            ...static::permissionMiddleware('posts'),
            'auth:sanctum',            // authenticate FIRST — Spatie throws 403 "User is not logged in." otherwise
        ];
    }
}
```

Middleware order: Laravel runs controller middleware in array order — put `auth:*` **before**
the spread so an unauthenticated request gets 401 from the guard instead of Spatie's 403.

`permissionMiddleware(string $slug)` returns six `Illuminate\Routing\Controllers\Middleware`
objects (empty array when `fast-api.permissions.enabled` is false; `RuntimeException` if the
Spatie provider class is missing):

| Permission name | `only:` actions | `CrudAction` |
|---|---|---|
| `view-{slug}` | index, show | `View` |
| `store-{slug}` | store | `Store` |
| `update-{slug}` | update, updateColumn | `Update` |
| `delete-{slug}` | destroy, delete, permanentDelete | `Delete` |
| `change-status-{slug}` | changeStatus | `ChangeStatus` |
| `restore-{slug}` | restore, restoreAll | `Restore` |

Not covered: web `create`/`edit`. Add
`new Middleware('permission:store-posts', only: ['create'])` and
`new Middleware('permission:update-posts', only: ['edit'])` yourself.

Slug convention: the kebab plural resource name (`posts`, `blog-posts`). Keep it in one place
by implementing `HasPermissionSlug` on the model and calling
`static::permissionMiddleware((new Post)->getPermissionSlug())`.

## 3. Seed the permissions

```php
// database/seeders/PermissionSeeder.php
use Anil\FastApiCrud\Enums\CrudAction;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

public function run(): void
{
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $guard = 'web';                       // or 'sanctum' / 'api' — MUST match the guard that authenticates the request
    $slugs = ['posts', 'tags', 'categories'];

    foreach ($slugs as $slug) {
        foreach (CrudAction::cases() as $action) {
            Permission::findOrCreate("{$action->value}-{$slug}", $guard);
        }
    }

    Role::findOrCreate('admin', $guard)->syncPermissions(Permission::where('guard_name', $guard)->get());
    Role::findOrCreate('editor', $guard)->syncPermissions(["view-posts", "store-posts", "update-posts", "change-status-posts"]);
}
```

Or via artisan: `php artisan permission:create-permission view-posts web`,
`permission:create-role admin web "view-posts|store-posts"`, `permission:show`, `permission:cache-reset`.

Assign: `$user->assignRole('editor')`, `$user->givePermissionTo('delete-posts')`,
`$user->syncRoles([...])`, `$user->syncPermissions([...])`, `$user->revokePermissionTo(...)`,
`$user->removeRole(...)`. Check: `$user->can('view-posts')`, `hasPermissionTo()`, `hasAnyPermission([...])`,
`hasRole()`, `getAllPermissions()`. Query scopes: `User::permission('view-posts')`, `User::role('admin')`.

## 4. Guards (the #1 source of 403s)

Every permission/role row has a `guard_name`. Spatie's middleware checks the user resolved by
the request's guard (`Auth::guard($guard)->user()`) and looks up permissions **for that guard**
(a model's default guard = first guard in `auth.guards` whose provider's model is that class).
API apps using Sanctum usually authenticate via `auth:sanctum` while the `User` model's default
guard is `web` — Sanctum resolves the user through the `web` guard's provider, so `web` permissions
work. If you use a dedicated `api` guard/provider, seed permissions with `guard_name = 'api'`
or pass the guard explicitly: `permission:view-posts,api` (override `middleware()` and build the
`Middleware` objects yourself, or use `PermissionMiddleware::using('view-posts', 'api')`).

Symptoms: `There is no permission named 'view-posts' for guard 'web'` (`PermissionDoesNotExist`)
or `The given role or permission should use guard 'api' instead of 'web'` (`GuardDoesNotMatch`).

## 5. Responses

Spatie throws `Spatie\Permission\Exceptions\UnauthorizedException` (extends `HttpException`, 403):
- not authenticated → `User is not logged in.`
- authenticated, missing permission → `User does not have the right permissions.`
  (set `permission.display_permission_in_exception = true` to list the required names — dev only).
- user model lacks `HasRoles` → `Authorizable class ... must use Spatie\Permission\Traits\HasRoles trait.`

Render as JSON automatically when the request has `Accept: application/json`.

## 6. Super admin

```php
// AppServiceProvider::boot()
Gate::before(fn ($user, $ability) => $user->hasRole('super-admin') ? true : null);
```
`PermissionMiddleware` uses `$user->canAny()`, which goes through the Gate, so `Gate::before`
short-circuits every fast-api permission for that role.

## 7. Caching

Permissions are cached for 24 h (`permission.cache.expiration_time`, key `spatie.permission.cache`).
Creating/deleting permissions through the Eloquent models flushes it automatically; raw inserts,
seeders that run before the app boots, or DB restores do not — run
`php artisan permission:cache-reset` or `app()[PermissionRegistrar::class]->forgetCachedPermissions()`.
Role ↔ user assignments are **not** cached (only the permission/role definitions are).

## 8. Teams / multi-tenant

`permission.teams = true` + `permission:setup-teams` migration adds `team_id`. Set the tenant
before controller middleware runs (a global middleware calling
`setPermissionsTeamId($tenantId)`), otherwise every check runs against team `null`. Combine with
fast-api `*Scopes` properties (`$updateScopes = ['forTenant' => ...]`) so rows are also filtered.

## 9. Wildcards & enums

`permission.enable_wildcard_permission = true` allows `posts.*` style names, but the names
fast-api-crud emits are `action-slug` (dash-separated), not dotted — wildcards do not apply to
them unless you build the middleware yourself. `CrudAction` is a backed enum, usable directly in
Spatie ≥ 6.10 APIs that accept `BackedEnum` (e.g. `Permission::findOrCreate(CrudAction::View->value . '-posts')`).

## 10. Testing

```php
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->user = User::factory()->create();
});

it('denies index without view permission', function () {
    $this->actingAs($this->user)->getJson('/api/posts')->assertForbidden();
});

it('allows index with view permission', function () {
    Permission::findOrCreate('view-posts', 'web');
    $this->user->givePermissionTo('view-posts');

    $this->actingAs($this->user)->getJson('/api/posts')->assertOk();
});

// Skip permissions entirely in a test:
config(['fast-api.permissions.enabled' => false]);   // must be set BEFORE the controller is resolved
```

`permissionMiddleware()` is evaluated when the route's controller middleware is gathered
(per request), so toggling `fast-api.permissions.enabled` inside a test works as long as it
happens before the HTTP call.

## Checklist when a fast-api endpoint returns 403

1. `auth:*` middleware present and *before* the permission middleware? (else Spatie's 403 masks a 401)
2. `permission` alias registered in `bootstrap/app.php`?
3. `User` uses `HasRoles`?
4. Permission row exists with the **same guard** as the authenticated guard? (`permission:show`)
5. User actually has it (`$user->getAllPermissions()->pluck('name')`)?
6. Cache stale? `permission:cache-reset`.
7. Still 403 on writes only → the FormRequest's `authorize()` returns `false` (not Spatie at all).
