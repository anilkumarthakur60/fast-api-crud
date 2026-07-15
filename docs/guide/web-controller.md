# Web Controller

`BaseWebController` provides Blade/web endpoints. Returns `View` and `RedirectResponse` with flash messages.

## Constructor

```php
use Anil\FastApiCrud\Http\Controllers\BaseWebController;

class PostController extends BaseWebController
{
    public function __construct()
    {
        parent::__construct(
            model: Post::class,
            storeRequest: StorePostRequest::class,
            updateRequest: UpdatePostRequest::class,
            viewPrefix: 'admin.posts',     // View name prefix
            routePrefix: 'admin.posts',    // Route name prefix for redirects
            resourceName: 'post',          // Variable name in show/edit views
            collectionName: 'posts',       // Variable name in index view
            resource: PostResource::class, // Optional — for data transformation
        );
    }
}
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `model` | `class-string<Model>` | Yes | Eloquent model class |
| `storeRequest` | `class-string<FormRequest>` | Yes | Store validation |
| `updateRequest` | `class-string<FormRequest>` | Yes | Update validation |
| `viewPrefix` | `string` | Yes | e.g. `'admin.posts'` resolves to `admin.posts.index` |
| `routePrefix` | `string` | Yes | e.g. `'admin.posts'` redirects to `route('admin.posts.index')` |
| `resourceName` | `string` | Yes | Variable name for single model in views |
| `collectionName` | `string` | Yes | Variable name for paginated collection in index |
| `resource` | `class-string<JsonResource>\|null` | No | Optional resource for data transformation |

## Methods

| Method | HTTP | Return | View/Route |
|--------|------|--------|------------|
| `index()` | GET | `View` | `{viewPrefix}.index` |
| `create()` | GET | `View` | `{viewPrefix}.create` |
| `store()` | POST | `RedirectResponse` | Redirect to `{routePrefix}.index` |
| `show($id)` | GET | `View` | `{viewPrefix}.show` |
| `edit($id)` | GET | `View` | `{viewPrefix}.edit` |
| `update($id)` | PUT | `RedirectResponse` | Redirect to `{routePrefix}.index` |
| `destroy($id)` | DELETE | `RedirectResponse` | Redirect to `{routePrefix}.index` |
| `delete()` | POST | `RedirectResponse` | Redirect to `{routePrefix}.index` |
| `changeStatus($id, $column)` | PUT | `RedirectResponse` | Redirect to `{routePrefix}.index` |
| `updateColumn($id, $column)` | PUT | `RedirectResponse` | Redirect to `{routePrefix}.index` |
| `restore($id)` | PUT | `RedirectResponse` | Redirect to `{routePrefix}.index` |
| `restoreAll()` | POST | `RedirectResponse` | Redirect to `{routePrefix}.index` |
| `permanentDelete($id)` | POST | `RedirectResponse` | Redirect to `{routePrefix}.index` |

## View Variables

| View | Variable | Type |
|------|----------|------|
| `index` | `${collectionName}` (e.g. `$posts`) | Paginated collection |
| `show` | `${resourceName}` (e.g. `$post`) | Model instance |
| `edit` | `${resourceName}` (e.g. `$post`) | Model instance |
| `create` | (none by default) | Override to pass data |

## Flash Messages

All write operations redirect with a flash message. Override any message method:

```php
class PostController extends BaseWebController
{
    protected function storeSuccessMessage(): string       { return __('Post created!'); }
    protected function updateSuccessMessage(): string      { return __('Post updated!'); }
    protected function destroySuccessMessage(): string     { return __('Post deleted.'); }
    protected function bulkDeleteSuccessMessage(): string  { return __('Posts deleted.'); }
    protected function statusChangeSuccessMessage(): string    { return __('Status updated.'); }
    protected function columnUpdateSuccessMessage(): string    { return __('Column updated.'); }
    protected function restoreSuccessMessage(): string         { return __('Post restored.'); }
    protected function restoreAllSuccessMessage(): string      { return __('All posts restored.'); }
    protected function permanentDeleteSuccessMessage(): string { return __('Post permanently deleted.'); }
}
```

Flash session keys are configurable via `fast-api.web.flash_key_success` and `fast-api.web.flash_key_error`.

On error, `redirectBackWithError()` redirects back with old input and an error flash message.

## Overriding Views

Override any method to pass extra data:

```php
class PostController extends BaseWebController
{
    public function create(): \Illuminate\View\View
    {
        return view($this->viewName('create'), [
            'categories' => Category::all(),
            'tags' => Tag::all(),
        ]);
    }

    public function edit(int|string $id): \Illuminate\View\View
    {
        $query = $this->buildShowQuery();

        return view($this->viewName('edit'), [
            $this->resourceName => $query->findOrFail($id),
            'categories' => Category::all(),
        ]);
    }
}
```

The `viewName(string $suffix)` helper resolves the full view name from the prefix.

## Blade Example

::: v-pre
```blade
{{-- resources/views/admin/posts/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex justify-content-between mb-3">
        <h1>Posts</h1>
        <a href="{{ route('admin.posts.create') }}" class="btn btn-primary">
            Create Post
        </a>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($posts as $post)
                <tr>
                    <td>{{ $post->id }}</td>
                    <td>{{ $post->name }}</td>
                    <td>{{ $post->status ? 'Active' : 'Inactive' }}</td>
                    <td>
                        <a href="{{ route('admin.posts.show', $post) }}">View</a>
                        <a href="{{ route('admin.posts.edit', $post) }}">Edit</a>
                        <form action="{{ route('admin.posts.destroy', $post) }}"
                              method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Are you sure?')">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $posts->links() }}
</div>
@endsection
```
:::

## Web Routes

```php
// routes/web.php
use App\Http\Controllers\PostController;

// Standard Laravel resource routes
Route::resource('admin/posts', PostController::class);

// Extended operations
Route::patch('admin/posts/{id}/status', [PostController::class, 'changeStatus'])
    ->name('admin.posts.changeStatus');
Route::patch('admin/posts/{id}/restore', [PostController::class, 'restore'])
    ->name('admin.posts.restore');
Route::post('admin/posts/restore', [PostController::class, 'restoreAll'])
    ->name('admin.posts.restoreAll');
Route::delete('admin/posts/{id}/force', [PostController::class, 'permanentDelete'])
    ->name('admin.posts.permanentDelete');
```
