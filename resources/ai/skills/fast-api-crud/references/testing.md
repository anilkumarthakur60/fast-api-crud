# Testing a fast-api-crud resource (Pest)

```php
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists posts filtered by scope and searched', function () {
    Post::factory()->count(3)->create(['active' => 1]);
    Post::factory()->create(['active' => 0, 'title' => 'hidden']);

    $this->getJson('/api/posts?' . http_build_query([
        'filters'     => json_encode(['active' => 1, 'include' => 'author']),
        'search'      => 'laravel',
        'sortBy'      => 'created_at',
        'descending'  => 'true',
        'rowsPerPage' => 10,
    ]))
        ->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta']);
});

it('creates a post', function () {
    $this->postJson('/api/posts', ['title' => 'Hello', 'body' => '...'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Hello');
});

it('rejects invalid payloads', function () {
    $this->postJson('/api/posts', [])->assertUnprocessable()->assertJsonValidationErrors(['title']);
});

it('toggles status and refuses non-allowlisted columns', function () {
    $post = Post::factory()->create(['status' => 0]);

    $this->patchJson("/api/posts/{$post->id}/status")->assertOk()->assertJsonPath('data.status', 1);
    $this->patchJson("/api/posts/{$post->id}/status/is_admin", ['is_admin' => 1])->assertForbidden();
});

it('soft deletes, restores, bulk deletes', function () {
    $posts = Post::factory()->count(2)->create();

    $this->deleteJson("/api/posts/{$posts[0]->id}")->assertNoContent();
    $this->assertSoftDeleted($posts[0]);

    $this->patchJson("/api/posts/{$posts[0]->id}/restore")->assertOk();

    $this->deleteJson('/api/posts', ['delete_rows' => $posts->pluck('id')->all()])->assertNoContent();
});
```

With Spatie permissions enabled, act as a user holding `view-posts`, `store-posts`, … or
set `config(['fast-api.permissions.enabled' => false])` in the test.

If the project renamed query keys (`fast-api.query.*`), build URLs with
`Anil\FastApiCrud\Support\QueryParams::perPage()` etc. instead of literals.
