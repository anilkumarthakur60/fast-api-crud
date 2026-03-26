# Permissions

Integrates with [spatie/laravel-permission](https://github.com/spatie/laravel-permission) for automatic permission middleware.

## Automatic Registration

If your model implements `HasPermissionSlug` and `fast-api.permissions.enabled` is `true`, permission middleware is registered automatically in the controller constructor.

### 1. Implement the interface

```php
use Anil\FastApiCrud\Contracts\HasPermissionSlug;

class Post extends Model implements HasPermissionSlug
{
    public function getPermissionSlug(): string
    {
        return 'posts';
    }
}
```

### 2. That's it

The controller constructor calls `registerPermissionMiddleware()` which generates:

| Permission | Applied To Routes |
|-----------|-------------------|
| `view-posts` | `index`, `show` |
| `store-posts` | `store` |
| `update-posts` | `update`, `updateColumn` |
| `delete-posts` | `destroy`, `delete`, `permanentDelete` |
| `change-status-posts` | `changeStatus` |
| `restore-posts` | `restore`, `restoreAll` |

## Static Middleware (Laravel 11+)

For the modern `HasMiddleware` interface, use the static `permissionMiddleware()` helper:

```php
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PostController extends BaseController implements HasMiddleware
{
    public static function middleware(): array
    {
        return self::permissionMiddleware('posts');
    }

    public function __construct()
    {
        parent::__construct(
            model: Post::class,
            storeRequest: StorePostRequest::class,
            updateRequest: UpdatePostRequest::class,
            resource: PostResource::class,
        );
    }
}
```

The `permissionMiddleware(string $slug)` method returns an `array<Middleware>` with the same permission mapping as above.

## CrudAction Enum

Permission names use the `CrudAction` enum values:

```php
use Anil\FastApiCrud\Enums\CrudAction;

CrudAction::View          // 'view'
CrudAction::Store         // 'store'
CrudAction::Update        // 'update'
CrudAction::Delete        // 'delete'
CrudAction::ChangeStatus  // 'change-status'
CrudAction::Restore       // 'restore'
```

## Disabling Permissions

### Globally

```php
// config/fast-api.php
'permissions' => [
    'enabled' => false,
],
```

### Per Model

Return an empty string from `getPermissionSlug()`:

```php
public function getPermissionSlug(): string
{
    return ''; // No permissions registered
}
```

### Don't Implement the Interface

If your model doesn't implement `HasPermissionSlug`, no permissions are registered for that controller.
