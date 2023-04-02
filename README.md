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

### Register Provider in config/app.php###

```

    'providers' => [
        ...
        \Anil\FastApiCrud\Providers\ApiCrudServiceProvider::class
    ]
```

### Eample ###

```apacheconf
    public function __construct()
    {
        parent::__construct(
            model: Post::class,
            storeRequest: StorePostRequest::class,
            updateRequest: UpdatePostRequest::class,
            resource: PostResource::class
        );
    }

```

### The class has several protected properties that can be overridden in child classes:###


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

### The class has several methods that correspond to basic CRUD operations:###

 

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

