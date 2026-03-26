# Scaffolding Command

Generate a complete CRUD scaffold with a single command.

## Usage

```bash
# Single model — API controller
php artisan fast-api:make-all Post

# Single model — Web controller with Blade views
php artisan fast-api:make-all Post --web

# Multiple models
php artisan fast-api:make-all Post,Tag,Category

# Multiple models — Web
php artisan fast-api:make-all Post,Tag,Category --web
```

## Signature

```
fast-api:make-all {name} {--web}
```

| Argument/Option | Description |
|----------------|-------------|
| `name` | Comma-separated model name(s), e.g. `Post` or `Post,Tag,User` |
| `--web` | Generate `BaseWebController` instead of `BaseController`, plus Blade views |

## Generated Files

For `php artisan fast-api:make-all Post`:

| File | Path |
|------|------|
| Model | `app/Models/Post.php` |
| Migration | `database/migrations/create_posts_table.php` |
| Factory | `database/factories/PostFactory.php` |
| Seeder | `database/seeders/PostSeeder.php` |
| Resource | `app/Http/Resources/Post/PostResource.php` |
| Store Request | `app/Http/Requests/Post/StorePostRequest.php` |
| Update Request | `app/Http/Requests/Post/UpdatePostRequest.php` |
| Controller | `app/Http/Controllers/PostController.php` |

With `--web`, also generates:

| File | Path |
|------|------|
| Index View | `resources/views/posts/index.blade.php` |
| Create View | `resources/views/posts/create.blade.php` |
| Edit View | `resources/views/posts/edit.blade.php` |
| Show View | `resources/views/posts/show.blade.php` |

## Generated API Controller

```php
<?php

namespace App\Http\Controllers;

use Anil\FastApiCrud\Http\Controllers\BaseController;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\Post\PostResource;
use App\Models\Post;

class PostController extends BaseController
{
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

## Generated Web Controller

```php
<?php

namespace App\Http\Controllers;

use Anil\FastApiCrud\Http\Controllers\BaseWebController;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Models\Post;

class PostController extends BaseWebController
{
    public function __construct()
    {
        parent::__construct(
            model: Post::class,
            storeRequest: StorePostRequest::class,
            updateRequest: UpdatePostRequest::class,
            viewPrefix: 'posts',
            routePrefix: 'posts',
            resourceName: 'post',
            collectionName: 'posts',
        );
    }
}
```

## Generated Blade Views

The `--web` flag generates starter Blade templates that extend `layouts.app` and include:

- **index.blade.php** — Table listing with pagination, flash messages, edit/delete actions
- **create.blade.php** — Form with CSRF, submit and cancel buttons
- **edit.blade.php** — Form with CSRF and `@method('PUT')`, pre-filled data
- **show.blade.php** — Detail view with edit/back buttons

These are starting points — customize them for your needs.
