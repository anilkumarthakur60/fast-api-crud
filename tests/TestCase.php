<?php

namespace Anil\FastApiCrud\Tests;

use Anil\FastApiCrud\Providers\ApiCrudServiceProvider;
use Anil\FastApiCrud\Tests\TestSetup\Controllers\PostController;
use Anil\FastApiCrud\Tests\TestSetup\Controllers\TagController;
use Anil\FastApiCrud\Tests\TestSetup\Controllers\UserController;
use Anil\FastApiCrud\Tests\TestSetup\Middleware\PermissionMiddleware;
use Anil\FastApiCrud\Tests\TestSetup\Models\CountryModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\OwnerModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\PermissionModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\PostModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\TagModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\UserModel;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    /**
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Anil\FastApiCrud\\Tests\\TestSetup\\Factories\\'.class_basename($modelName)
        );

        $app = $this->app;

        // Configure authentication to use UserModel
        $app['config']->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => UserModel::class,
        ]);
        $app['config']->set('auth.defaults.guard', 'web');
        $app['config']->set('permission.guard_name', 'web');

        // Create all necessary tables before running tests
        $this->runMigrations();

        // Register "permission" middleware alias
        $this->registerMiddleware();
    }

    /**
     * Create all tables required by tests.
     */
    protected function runMigrations(): void
    {
        $schema = $this->app['db']->connection()->getSchemaBuilder();

        if (! $schema->hasTable('countries')) {
            $this->createCountriesTable();
        }
        if (! $schema->hasTable('users')) {
            $this->createUsersTable();
        }
        if (! $schema->hasTable('profiles')) {
            $this->createProfilesTable();
        }
        if (! $schema->hasTable('comments')) {
            $this->createCommentsTable();
        }
        if (! $schema->hasTable('owners')) {
            $this->createOwnersTable();
        }
        if (! $schema->hasTable('pets')) {
            $this->createPetsTable();
        }
        if (! $schema->hasTable('photos')) {
            $this->createPhotosTable();
        }
        if (! $schema->hasTable('tags')) {
            $this->createTagsTable();
        }
        if (! $schema->hasTable('posts')) {
            $this->createPostsTable();
        }
        if (! $schema->hasTable('post_tag')) {
            $this->createPostTagTable();
        }
        if (! $schema->hasTable('parents')) {
            $this->createParentsTable();
        }
        if (! $schema->hasTable('children')) {
            $this->createChildrenTable();
        }
        if (! $schema->hasTable('permissions')) {
            $this->createPermissionsTable();
        }
        if (! $schema->hasTable('user_permission')) {
            $this->createUserPermissionTable();
        }
    }

    private function createCountriesTable(): void
    {
        $this->app['db']->connection()
            ->getSchemaBuilder()
            ->create('countries', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
    }

    private function createUsersTable(): void
    {
        $this->app['db']->connection()
            ->getSchemaBuilder()
            ->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('username')->unique();
                $table->string('email')->unique();
                $table->string('password')->nullable();
                $table->boolean('active')->default(true);
                $table->boolean('status')->default(true);
                $table->foreignIdFor(CountryModel::class, 'country_id')
                    ->nullable()
                    ->constrained('countries')
                    ->cascadeOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
    }

    private function createProfilesTable(): void
    {
        $this->app['db']->connection()
            ->getSchemaBuilder()
            ->create('profiles', function (Blueprint $table) {
                $table->id();
                $table->morphs('profilable');
                $table->text('bio')->nullable();
                $table->timestamps();
            });
    }

    private function createCommentsTable(): void
    {
        $this->app['db']->connection()
            ->getSchemaBuilder()
            ->create('comments', function (Blueprint $table) {
                $table->id();
                $table->morphs('commentable');
                $table->text('body')->nullable();
                $table->timestamps();
            });
    }

    private function createOwnersTable(): void
    {
        $this->app['db']->connection()
            ->getSchemaBuilder()
            ->create('owners', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
    }

    private function createPetsTable(): void
    {
        $this->app['db']->connection()
            ->getSchemaBuilder()
            ->create('pets', function (Blueprint $table) {
                $table->id();
                $table->string('pet_name');
                $table->foreignIdFor(OwnerModel::class, 'owner_id')
                    ->nullable()
                    ->constrained('owners')
                    ->cascadeOnDelete();
                $table->timestamps();
            });
    }

    private function createPhotosTable(): void
    {
        $this->app['db']->connection()
            ->getSchemaBuilder()
            ->create('photos', function (Blueprint $table) {
                $table->id();
                $table->morphs('imageable');
                $table->string('path')->nullable();
                $table->timestamps();
            });
    }

    private function createTagsTable(): void
    {
        $this->app['db']->connection()
            ->getSchemaBuilder()
            ->create('tags', function (Blueprint $table) {
                $table->id();
                $table->string('tag_name');
                $table->longText('desc')->nullable();
                $table->boolean('status')->default(true);
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
    }

    private function createPostsTable(): void
    {
        $this->app['db']->connection()
            ->getSchemaBuilder()
            ->create('posts', function (Blueprint $table) {
                $table->id();
                $table->string('post_title');
                $table->longText('desc')->nullable();
                $table->boolean('status')->default(true);
                $table->boolean('active')->default(true);
                $table->foreignIdFor(UserModel::class, 'user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
    }

    private function createPostTagTable(): void
    {
        $this->app['db']->connection()
            ->getSchemaBuilder()
            ->create('post_tag', function (Blueprint $table) {
                $table->id();
                $table->foreignIdFor(PostModel::class, 'post_id')
                    ->constrained('posts')
                    ->cascadeOnDelete();
                $table->foreignIdFor(TagModel::class, 'tag_id')
                    ->constrained('tags')
                    ->cascadeOnDelete();
            });
    }

    private function createParentsTable(): void
    {
        $this->app['db']->connection()
            ->getSchemaBuilder()
            ->create('parents', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
    }

    private function createChildrenTable(): void
    {
        $this->app['db']->connection()
            ->getSchemaBuilder()
            ->create('children', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_id')
                    ->nullable()
                    ->constrained('parents')
                    ->cascadeOnDelete();
                $table->string('title')->nullable();
                $table->timestamps();
            });
    }

    private function createPermissionsTable(): void
    {
        $this->app['db']->connection()
            ->getSchemaBuilder()
            ->create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
    }

    private function createUserPermissionTable(): void
    {
        $this->app['db']->connection()
            ->getSchemaBuilder()
            ->create('user_permission', function (Blueprint $table) {
                $table->id();
                $table->foreignIdFor(UserModel::class, 'user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
                $table->foreignIdFor(PermissionModel::class, 'permission_id')
                    ->constrained('permissions')
                    ->cascadeOnDelete();
            });
    }

    /**
     * Register “permission” middleware alias.
     *
     * @throws BindingResolutionException
     */
    private function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('permission', PermissionMiddleware::class);
    }

    /**
     * Register package service providers.
     *
     * @param  Application  $app
     * @return array<int,string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ApiCrudServiceProvider::class,
            PermissionServiceProvider::class,
        ];
    }

    /**
     * Define application routes for tests.
     *
     * @param  Router  $router
     */
    protected function defineRoutes($router): void
    {
        $this->registerPostRoutes($router);
        $this->registerTagRoutes($router);
        $this->registerUserRoutes($router);
    }

    private function registerPostRoutes(Router $router): void
    {
        $router->get('posts', [PostController::class, 'index'])->name('posts.index');
        $router->post('posts', [PostController::class, 'store'])->name('posts.store');
        $router->post('posts/delete', [PostController::class, 'delete'])->name('posts.delete');
        $router->post('posts/restore-all-trashed', [PostController::class, 'restoreAllTrashed'])
            ->name('posts.restore-all-trashed');
        $router->post('posts/force-delete-trashed', [PostController::class, 'forceDeleteTrashed'])
            ->name('posts.force-delete-trashed');
        $router->get('posts/{id}', [PostController::class, 'show'])->name('posts.show');
        $router->put('posts/{id}', [PostController::class, 'update'])->name('posts.update');
        $router->put('posts/{id}/status-change/{column}', [PostController::class, 'changeStatusOtherColumn'])
            ->name('posts.changeStatusOtherColumn');
        $router->put('posts/{id}/status-change', [PostController::class, 'changeStatus'])
            ->name('posts.changeStatus');
        $router->put('posts/{id}/restore-trashed', [PostController::class, 'restoreTrashed'])
            ->name('posts.restoreTrashed');
        $router->delete('posts/{id}', [PostController::class, 'destroy'])->name('posts.destroy');
    }

    private function registerTagRoutes(Router $router): void
    {
        $router->get('tags', [TagController::class, 'index'])->name('tags.index');
        $router->post('tags', [TagController::class, 'store'])->name('tags.store');
        $router->post('tags/delete', [TagController::class, 'delete'])->name('tags.delete');
        $router->post('tags/restore-all-trashed', [TagController::class, 'restoreAllTrashed'])
            ->name('tags.restore-all-trashed');
        $router->delete('tags/force-delete-trashed/{id}', [TagController::class, 'forceDeleteTrashed'])
            ->name('tags.force-delete-trashed');
        $router->get('tags/{id}', [TagController::class, 'show'])->name('tags.show');
        $router->put('tags/{id}', [TagController::class, 'update'])->name('tags.update');
        $router->put('tags/{id}/status-change/{column}', [TagController::class, 'changeStatusOtherColumn'])
            ->name('tags.changeStatusOtherColumn');
        $router->put('tags/{id}/status-change', [TagController::class, 'changeStatus'])
            ->name('tags.changeStatus');
        $router->put('tags/{id}/restore-trashed', [TagController::class, 'restoreTrashed'])
            ->name('tags.restoreTrashed');
        $router->delete('tags/{id}', [TagController::class, 'destroy'])->name('tags.destroy');
    }

    private function registerUserRoutes(Router $router): void
    {
        $router->get('users', [UserController::class, 'index'])->name('users.index');
        $router->post('users', [UserController::class, 'store'])->name('users.store');
        $router->post('users/delete', [UserController::class, 'delete'])->name('users.delete');
        $router->post('users/restore-all-trashed', [UserController::class, 'restoreAllTrashed'])
            ->name('users.restore-all-trashed');
        $router->post('users/force-delete-trashed', [UserController::class, 'forceDeleteTrashed'])
            ->name('users.force-delete-trashed');
        $router->get('users/{id}', [UserController::class, 'show'])->name('users.show');
        $router->put('users/{id}', [UserController::class, 'update'])->name('users.update');
        $router->put('users/{id}/status-change/{column}', [UserController::class, 'changeStatusOtherColumn'])
            ->name('users.changeStatusOtherColumn');
        $router->put('users/{id}/status-change', [UserController::class, 'changeStatus'])
            ->name('users.changeStatus');
        $router->put('users/{id}/restore-trashed', [UserController::class, 'restoreTrashed'])
            ->name('users.restoreTrashed');
        $router->delete('users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    }
}
