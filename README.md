# About FastApiCrud #

It provides basic to advanced CRUD (Create, Read, Update, Delete) functionality for a given model.

## Composer ##  

## Installation ##

```apacheconf
composer require anil/fast-api-crud
```
### Child Constructor method ####
* $model: The name of the model that this controller is responsible for.
* $storeRequest: The name of the request class to use when storing data.
* $updateRequest: The name of the request class to use when updating data.
* $resource: The name of the resource class to use when returning data.

### This package has auto-discovery for laravel 6 and higher and for below you can add this in your providers array  ###

```

    'providers' => [
        ...
        \Anil\FastApiCrud\Providers\ApiCrudServiceProvider::class
    ]
```


### This package is build on Top of Spatie role and permission Register in Kernel.php if you want to apply permission as well  ###

```angular2html

// Laravel 9 uses $routeMiddleware = [
//protected $routeMiddleware = [
// Laravel 10+ uses $middlewareAliases = [
protected $middlewareAliases = [
// ...
'role' => \Spatie\Permission\Middlewares\RoleMiddleware::class,
'permission' => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
];

```

### Eample  ###

```apacheconf
<?php

namespace App\Http\Controllers;

use Anil\FastApiCrud\Controller\CrudBaseController;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\Post\PostResource;
use App\Models\Post;

class PostControllerCrud extends CrudBaseController
{
    public function __construct()
    {
        parent::__construct(
            model: Post::class,
            storeRequest: StorePostRequest::class,
            updateRequest: UpdatePostRequest::class,
            resource: PostResource::class
        );
    }
}

```
### The class has several protected properties that can be overridden in child classes: ###


* ```$scopes```: An array of scopes to apply when fetching all records.
* ```$scopeWithValue```: An array of scoped values to apply when fetching all records.
* ```$loadScopes```: An array of scopes to apply when fetching a record.
* ```$loadScopeWithValue```: An array of scoped values to apply when fetching a record.
* ```$withAll```: An array of relationships to eager load when fetching all records.
* ```$withCount```: An array of relationships to count when fetching all records.
* ```$withAggregate```: An array of aggregate functions to apply when fetching all records.
* ```$loadAll```: An array of relationships to eager load when fetching a single record.
* ```$loadCount```: An array of relationships to count when fetching a single record.
* ```$loadAggregate```: An array of aggregate functions to apply when fetching a single record.
* ```$isApi```: A boolean indicating whether the controller is being used as an API or not.
* ```$forceDelete```: A boolean indicating whether to perform a soft delete or a hard delete.
* ```$applyPermission```: A boolean indicating whether to apply permission or not.
* ```$deleteScopes```: An array of scopes to apply when deleting a record.
* ```$deleteScopeWithValue```: An array of scoped values to apply when deleting a record.
* ```$changeStatusScopes```: An array of scopes to apply when changing status of a record.
* ```$changeStatusScopeWithValue```: An array of scoped values to apply when changing status of a record.
* ```$restoreScopes```: An array of scopes to apply when restoring a record.
* ```$restoreScopeWithValue```: An array of scoped values to apply when restoring a record.
* ```$updateScopes```: An array of scopes to apply when updating a record.
* ```$updateScopeWithValue```: An array of scoped values to apply when updating a record.

### The class has several methods that correspond to basic CRUD operations: ###

 

* ```index()``` - Return Collection of all records.
* ```store()``` - Create a new record.
* ```show($id)``` - Return a single record.
* ```destroy($id)``` Delete a record.
* ```delete()``` - Bulk delete records.
* ```changeStatusOtherColumn($id,$column)``` - Change specific ```$column``` value between 0 and 1 provided from child class
* ```update($id)``` - Update a record.
* ```changeStatus($id)``` - Change status column value of a record between 0 and 1.
* ```restoreTrashed($id)``` - Restore a soft deleted record.
* ```restoreAllTrashed()``` - Restore all soft deleted records.
* ```forceDeleteTrashed($id)``` - Hard delete a soft deleted record.


#### There are also two helper methods, ```error()``` and ```success()```, that return a JSON response with a message and data. These are used to standardize error and success responses across the controller. ####

### for example  ###
```angular2html
//you 
Route::controller(PostController::class)->prefix('posts')->group(function () {
    Route::get('', 'index');
    Route::post('', 'store');
    Route::post('delete', 'delete');
    Route::post('restore-all-trashed', 'restoreAllTrashed');
    Route::post('force-delete-trashed', 'forceDeleteTrashed');
    Route::get('{id}', 'show');
    Route::put('{id}', 'update');
    Route::put('{id}/status-change/{column}', 'changeStatusOtherColumn'); //specific columns change value from 0 to 1 and vice versa
    Route::put('{id}/status-change', 'changeStatus');//default status column from 0 to 1 and vice versa
    Route::put('{id}/restore-trash', 'restoreTrashed');

    Route::delete('{id}', 'destroy');
});
    
```
    
### This package has also featured for making service ,action,trait file also  ###
After installation, the command `php artisan make:service {name} {--N|noContract}` will be available.

### Create services files

For example, the command `php artisan make:service createUser` will generate a service file called `CreateUserService.php` located in `app/Services/CreateUser`.

It will also generate an interface (contract) called `CreateUserContract.php` located in `app/Services/Contracts`.

### Create services for models

Adding a ```--service``` or ```-S``` option is now available when creating a model.

For example, the command `php artisan make:model Post --service` or `php artisan make:model Post -S` will generate a model with service too.

The command `php artisan make:model Post --all` or `php artisan make:model Post -a` will now generate a model, migration, factory, seeder, policy, controller, form requests and service.

### Contracts

Adding a ```--noContract``` or ```-N``` option will prevent the commands from implementing any contract and will not create any contract file.

If you never need any contracts. Publish the config file and then turn the **with_interface** value to false in the config file.

