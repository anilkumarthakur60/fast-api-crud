# Scaffolding — `php artisan fast-api:make-all`

```
fast-api:make-all {name : Comma-separated model name(s), e.g. Post or Post,Tag,User} {--web}
```

Per model `Post` (table = `Str::snake(Str::pluralStudly('Post'))` = `posts`):

| Artisan call | Result |
|---|---|
| `make:model Post` | `app/Models/Post.php` |
| `make:migration create_posts_table --create=posts` | `database/migrations/*_create_posts_table.php` |
| `make:factory PostFactory` | `database/factories/PostFactory.php` |
| `make:seeder PostSeeder` | `database/seeders/PostSeeder.php` |
| `make:resource Post/PostResource` | `app/Http/Resources/Post/PostResource.php` |
| `make:request Post/StorePostRequest` | `app/Http/Requests/Post/StorePostRequest.php` |
| `make:request Post/UpdatePostRequest` | `app/Http/Requests/Post/UpdatePostRequest.php` |
| (stub) | `app/Http/Controllers/PostController.php` — skipped with a warning if it exists |
| `--web` (stub) | `resources/views/{kebab-plural}/index|create|edit|show.blade.php` — existing views skipped |

Generated API controller: extends `BaseController`, constructor passes model/requests/resource.
Generated web controller: extends `BaseWebController` with
`viewPrefix = routePrefix = kebab plural` (`blog-posts`), `resourceName = camel` (`blogPost`),
`collectionName = camel plural` (`blogPosts`); no `resource`.

Blade stubs extend `layouts.app`, use Bootstrap classes, read `session('success'|'error')`, and
contain `{{-- Add your form fields here --}}` placeholders. Routes for `--web` assume
`Route::resource('{kebab}', ...)` names (`{kebab}.index`, `.create`, `.store`, `.show`, `.edit`,
`.update`, `.destroy`).

Not generated: routes, policy, tests, `$fillable`, validation rules, migration columns.

⚠ Laravel's `make:request` stub ships `authorize(): bool { return false; }`. Until you change it
to `true` (or a policy/gate check) **every store/update returns 403**. Fix this first.
