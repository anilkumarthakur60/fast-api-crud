# Getting Started

## Requirements

- **PHP:** 8.2+
- **Laravel:** 11, 12, or 13
- **[spatie/laravel-permission](https://github.com/spatie/laravel-permission):** ^6.0 or ^7.0

## Installation

```bash
composer require anil/fast-api-crud
```

Publish the config file (optional):

```bash
php artisan vendor:publish --provider="Anil\FastApiCrud\FastApiCrudServiceProvider" --tag=config
```

This publishes `config/fast-api.php`. See [Configuration](./configuration) for all options.

## Quick Start

### 1. Scaffold everything

```bash
php artisan fast-api:make-all Post
```

This generates in one shot:

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

### 2. The generated controller

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

That's it. You now have index, show, store, update, destroy, bulk delete, status toggle, column update, restore, restore all, and permanent delete — all working.

### 3. Register routes

```php
// routes/api.php
use App\Http\Controllers\PostController;

Route::get('posts', [PostController::class, 'index']);
Route::post('posts', [PostController::class, 'store']);
Route::get('posts/{id}', [PostController::class, 'show']);
Route::put('posts/{id}', [PostController::class, 'update']);
Route::delete('posts/{id}', [PostController::class, 'destroy']);
```

See [Routes](./routes) for the full route setup including extended operations.

### 4. Try it

```
GET    /posts                → Paginated list with filtering & sorting
GET    /posts/1              → Single resource with eager loads
POST   /posts                → Create (validates via StorePostRequest) → 201
PUT    /posts/1              → Update (validates via UpdatePostRequest)
DELETE /posts/1              → Soft delete → 204
```

## What's Next?

- [Configuration](./configuration) — Customize pagination, response keys, soft deletes
- [API Controller](./api-controller) — Full method reference and response examples
- [Web Controller](./web-controller) — Blade views and redirects
- [Controller Properties](./controller-properties) — Scopes, eager loading, aggregates
- [Lifecycle Hooks](./lifecycle-hooks) — beforeCreate, afterUpdate, etc.
- [Contracts](./contracts) — Searchable, Sortable, HasPermissionSlug
