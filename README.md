
# About FastApiCrud #
It provides basic to advanced CRUD (Create, Read, Update, Delete) functionality for a given model.

## Composer ##  
## Installation ##
```apacheconf
composer require anil/fast-api-crud
```
* $model: The name of the model that this controller is responsible for.
* $storeRequest: The name of the request class to use when storing data.
* $updateRequest: The name of the request class to use when updating data.
* $resource: The name of the resource class to use when returning data.


### The class has several protected properties that can be overridden in child classes:###

* ```$withAll```: An array of relationships to eager load when fetching all records.
* ```withCount```: An array of relationships to count when fetching all records.
* ```withAggregate```: An array of aggregate functions to apply when fetching all records.
* ```loadAll```: An array of relationships to eager load when fetching a single record.
* ```loadCount```: An array of relationships to count when fetching a single record.
* ```loadAggregate```: An array of aggregate functions to apply when fetching a single record.
* ```isApi```: A boolean indicating whether the controller is being used as an API or not.
* ```forceDelete```: A boolean indicating whether to perform a soft delete or a hard delete.

### The class has several methods that correspond to basic CRUD operations:###

* ```index()```: Returns a paginated collection of all records.
* ```store()```: Creates a new record.
* ```update($id)```: Updates an existing record with the specified ID.
* ```show($id)```: Returns a single record with the specified ID.
* ```destroy($id)```: Deletes a single record with the specified ID.
* ```delete()```: Deletes multiple records at once.

#### There are also two helper methods, ```error()``` and ```success()```, that return a JSON response with a message and data. These are used to standardize error and success responses across the controller. ####
