# Lifecycle Hooks

Every CRUD operation supports before/after hooks. Define them on your **model** or override them in your **controller**.

## Available Hooks

| Operation | Before Hook | After Hook |
|-----------|-------------|------------|
| `store()` | `beforeCreate(Model)` | `afterCreate(Model)` |
| `update()` | `beforeUpdate(Model)` | `afterUpdate(Model)` |
| `destroy()` / `delete()` | `beforeDelete(Model)` | `afterDelete(Model)` |
| `changeStatus()` | `beforeStatusChange(Model)` | `afterStatusChange(Model)` |
| `updateColumn()` | `beforeColumnUpdate(Model)` | `afterColumnUpdate(Model)` |
| `restore()` | `beforeRestore(Model)` | `afterRestore(Model)` |
| `permanentDelete()` | `beforeForceDelete(Model)` | `afterForceDelete(Model)` |

## Model-Level Hooks

Define the methods directly on your model. The controller checks `method_exists()` before calling:

```php
class Post extends Model
{
    public function beforeCreate(): void
    {
        $this->slug = Str::slug($this->name);
    }

    public function afterCreate(): void
    {
        if (request()->filled('tag_ids')) {
            $this->tags()->sync(request()->input('tag_ids'));
        }
    }

    public function beforeUpdate(): void
    {
        $this->slug = Str::slug($this->name);
    }

    public function afterUpdate(): void
    {
        if (request()->filled('tag_ids')) {
            $this->tags()->sync(request()->input('tag_ids'));
        }
    }

    public function beforeDelete(): void
    {
        // Clean up related data before deletion
    }

    public function afterDelete(): void
    {
        // Log deletion, send notification, etc.
    }

    public function beforeStatusChange(): void { }
    public function afterStatusChange(): void { }

    public function beforeColumnUpdate(): void { }
    public function afterColumnUpdate(): void { }

    public function beforeRestore(): void { }
    public function afterRestore(): void { }

    public function beforeForceDelete(): void { }
    public function afterForceDelete(): void { }
}
```

## Controller-Level Hooks

Override hooks in your controller. **This completely replaces the model-level hook** — the model method is no longer called:

```php
class PostController extends BaseController
{
    protected function afterCreate(Model $model): void
    {
        // This replaces $model->afterCreate()
        Notification::send($admins, new PostCreated($model));
        Cache::forget('posts.count');
    }

    protected function beforeDelete(Model $model): void
    {
        // Called before both soft delete and force delete
        Log::info("Deleting post: {$model->id}");
    }
}
```

## Transaction Safety

All hooks run **inside a database transaction**. If any hook throws an exception, the transaction is rolled back:

```
beginTransaction()
  → beforeCreate($model)
  → $model->save()
  → afterCreate($model)
commit()

// If any step throws:
rollBack()
```

This applies to store, update, destroy, bulk delete, changeStatus, updateColumn, restore, and permanentDelete.
