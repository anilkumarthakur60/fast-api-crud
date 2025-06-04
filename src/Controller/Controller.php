<?php

namespace Anil\FastApiCrud\Controller;

use Anil\FastApiCrud\Traits\HasApiResponse;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Throwable;

/**
 * Class Controller
 *
 * A base controller providing standard CRUD operations (index, show, store, update, delete, restore, etc.)
 * for Eloquent models, with support for scopes, eager loading, aggregates, permissions, and API responses.
 */
class Controller extends BaseController
{
    use AuthorizesRequests;
    use HasApiResponse;
    use ValidatesRequests;

    /**
     * Whether to paginate the results if
     */
    public bool $isPaginate = true;

    /**
     * @var list<string>|array<string, string|number|bool>
     */
    public array $scopes = [];

    /**
     * @var array<string>
     */
    public array $loadScopes = [];

    /**
     * @var array<array-key, string|array<string, string|int|float|bool>|(\Closure(\Illuminate\Database\Eloquent\Relations\Relation<*,*,*>): mixed)>
     */
    public array $with = [];

    /**
     * @var array<string>
     */
    public array $withCount = [];

    /**
     * @var array<string, string>
     */
    public array $withAggregate = [];

    /**
     * @var array<array-key, string|array<string, string|int|float|bool>|(\Closure(\Illuminate\Database\Eloquent\Relations\Relation<*,*,*>): mixed)>
     */
    public array $load = [];

    /**
     * @var array<string>
     */
    public array $loadCount = [];

    /**
     * @var array<string, string>
     */
    public array $loadAggregate = [];

    public bool $isApi = true;

    public bool $forceDelete = false;

    /**
     * @var array<string>
     */
    public array $deleteScopes = [];

    /**
     * @var array<string, mixed>
     */
    public array $deleteScopeWithValue = [];

    /**
     * @var array<string>
     */
    public array $changeStatusScopes = [];

    /**
     * @var array<string, mixed>
     */
    public array $changeStatusScopeWithValue = [];

    /**
     * @var array<string>
     */
    public array $restoreScopes = [];

    /**
     * @var array<string, mixed>
     */
    public array $restoreScopeWithValue = [];

    /**
     * @var array<string>
     */
    public array $updateScopes = [];

    /**
     * @var array<string, mixed>
     */
    public array $updateScopeWithValue = [];

    public Model $model;

    public FormRequest $storeRequest;

    public FormRequest $updateRequest;

    /**
     * @var class-string<JsonResource>
     */
    protected string $resource;

    /**
     * Controller constructor.
     *
     * Validates that the provided model, store request, update request, and resource classes
     * are of the correct type, then sets up permission middleware if the model defines a permission slug.
     *
     * @param  class-string<Model>  $model  Fully qualified class name of the Eloquent model.
     * @param  class-string<FormRequest>  $storeRequest  Fully qualified class name of the FormRequest for store().
     * @param  class-string<FormRequest>  $updateRequest  Fully qualified class name of the FormRequest for update().
     * @param  class-string<JsonResource>  $resource  Fully qualified class name of the API Resource.
     *
     * @throws ReflectionException If reflection on model/request/resource fails.
     * @throws Exception If any class is not of the expected type.
     */
    public function __construct(string $model, string $storeRequest, string $updateRequest, string $resource)
    {
        $this->validateModel($model);
        $this->validateRequest($storeRequest, 'StoreRequest');
        $this->validateRequest($updateRequest, 'UpdateRequest');
        $this->validateResource($resource, 'Resource');
        $this->setupPermissions();
    }

    /**
     * Ensure the given class is a valid Eloquent model and instantiate it.
     *
     * @param  class-string<Model>  $modelClass  Fully qualified class name of the model.
     *
     * @throws Exception If the class is not a subclass of Illuminate\Database\Eloquent\Model.
     */
    protected function validateModel(string $modelClass): void
    {
        if (! is_subclass_of($modelClass, Model::class)) {
            throw new Exception('Model is not instance of Model', ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
        $this->model = resolve($modelClass);
    }

    /**
     * Ensure the given class is a valid FormRequest and instantiate it for store or update.
     *
     * @param  class-string<FormRequest>  $request  Fully qualified class name of the FormRequest.
     * @param  string  $requestName  Either 'StoreRequest' or 'UpdateRequest' (for error messages).
     *
     * @throws Exception If the class is not a subclass of Illuminate\Foundation\Http\FormRequest.
     */
    protected function validateRequest(string $request, string $requestName): void
    {
        if (! is_subclass_of($request, FormRequest::class)) {
            throw new Exception("$requestName is not instance of FormRequest", ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
        if ($requestName === 'StoreRequest') {
            $this->storeRequest = new $request;
        } else {
            $this->updateRequest = new $request;
        }
    }

    /**
     * Ensure the given class is a valid API Resource (JsonResource).
     *
     * @param  class-string<JsonResource>  $resource  Fully qualified class name of the Resource.
     * @param  string  $resourceName  Used in error messages.
     *
     * @throws Exception If the class is not a subclass of Illuminate\Http\Resources\Json\JsonResource.
     */
    protected function validateResource(string $resource, string $resourceName): void
    {
        if (! is_subclass_of($resource, JsonResource::class)) {
            throw new Exception("$resourceName is not instance of JsonResource", ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
        $this->resource = $resource;
    }

    /**
     * Set up permission middleware based on the model's permission slug (if defined).
     *
     * If the model has a getPermissionSlug() method, attaches middleware such as:
     * - view-{slug}   to index()
     * - store-{slug}  to store()
     * - update-{slug} to update()
     * - delete-{slug} to delete()
     * - change-status-{slug} to changeStatus()
     * - restore-{slug} to restoreTrashed()
     *
     * @throws Exception If any permission middleware cannot be attached (rare).
     */
    protected function setupPermissions(): void
    {
        $permissionSlug = null;
        $model = $this->model;
        if (method_exists($model, 'getPermissionSlug')) {
            $permissionSlug = $model->getPermissionSlug();
        }
        if ($permissionSlug) {
            $this->middleware("permission:view-{$permissionSlug}")->only(['index']);
            $this->middleware("permission:store-{$permissionSlug}")->only(['store']);
            $this->middleware("permission:update-{$permissionSlug}")->only(['update']);
            $this->middleware("permission:delete-{$permissionSlug}")->only(['delete']);
            $this->middleware("permission:change-status-{$permissionSlug}")->only(['changeStatus']);
            $this->middleware("permission:restore-{$permissionSlug}")->only(['restore']);
        }
    }

    /**
     * List all records, applying any defined scopes, eager loads, counts, and aggregates.
     *
     * @return AnonymousResourceCollection<JsonResource> Paginated collection of resources.
     */
    public function index(): AnonymousResourceCollection
    {
        /**
         * @var Builder<Model> $query
         */
        $query = $this->model::query()->initializer();

        if (! empty($this->with)) {
            $query->with($this->with);
        }

        if (! empty($this->withCount)) {
            $query->withCount($this->withCount);
        }

        if (! empty($this->withAggregate)) {
            $this->applyWithAggregate($query);
        }

        if (! empty($this->scopes)) {
            $this->applyScopes($query, $this->scopes);
        }

        if ($this->isPaginate) {
            return $this->resource::collection($query->paginates());
        }

        return $this->resource::collection($query->get());
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function applyWithAggregate(Builder $query): Builder
    {
        foreach ($this->withAggregate as $key => $value) {
            $query->withAggregate($key, $value);
        }

        return $query;
    }

    /**
     * Apply both simple and parameterized scopes to the query builder.
     *
     * Example of $scopes:
     *  - ['active'] // calls scopeActive() with no arguments
     *  - ['byUser' => 5] // calls scopeByUser(5)
     *  - ['dateRange' => [$from, $to]] // calls scopeDateRange($from, $to)
     *
     * @param  Builder<Model>  $query  The Eloquent query builder.
     * @param  array<int|string, string>  $scopes  An array of scopes to apply.
     * @return Builder<Model> The modified query builder.
     */
    protected function applyScopes(Builder $query, array $scopes): Builder
    {
        foreach ($scopes as $key => $value) {
            if (is_int($key)) {
                $scope = $value;
                $args = [];
            } else {
                $scope = $key;
                $args = is_array($value) ? $value : [$value];
            }

            $scopeMethod = 'scope'.ucfirst($scope);

            if (method_exists($query->getModel(), $scope)) {
                $query->{$scope}(...$args);
            } elseif (method_exists($query->getModel(), $scopeMethod)) {
                $query->{$scopeMethod}(...$args);
            }
        }

        return $query;
    }

    // /**
    //  * @param  Builder<Model>  $query
    //  * @param  array<string, mixed>  $scopeWithValue
    //  * @return Builder<Model>
    //  */
    // protected function applyScopeWithValue(Builder $query, array $scopeWithValue): Builder
    // {
    //     foreach ($scopeWithValue as $key => $value) {
    //         $upperKey = ucfirst($key);
    //         $scopeMethod = "scope{$upperKey}";
    //         if (method_exists($query->getModel(), $key)) {
    //             $query->$key($value);
    //         } elseif (method_exists($query->getModel(), $scopeMethod)) {
    //             $query->$scopeMethod($value);
    //         }
    //     }

    //     return $query;
    // }

    /**
     * Store a newly created resource in storage.
     *
     * Validates using the storeRequest, then creates the model instance inside a transaction,
     * calls afterCreateProcess if defined, and returns the new resource.
     *
     * @return JsonResponse|JsonResource The created resource or JSON error response.
     *
     * @throws Throwable
     */
    public function store(): JsonResponse|JsonResource
    {
        $data = resolve($this->storeRequest::class)->safe()->only((new $this->model)->getFillable());

        try {
            DB::beginTransaction();
            $model = $this->model::create($data);
            $this->afterCreateProcess($model);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage());
        }

        return new $this->resource($model);
    }

    /**
     * Hook for post-create logic on the model.
     *
     * If the model defines an afterCreateProcess() method, it will be called.
     *
     * @param  Model  $model  The newly created model instance.
     * @return Model The potentially modified model.
     */
    protected function afterCreateProcess(Model $model): Model
    {
        if (method_exists($model, 'afterCreateProcess')) {
            $model->afterCreateProcess();
        }

        return $model;
    }

    /**
     * Display the specified resource.
     *
     * Applies any defined eager loads, counts, aggregates, and scopes, then returns the resource.
     *
     * @param  int|string  $id  The primary key of the resource.
     * @return JsonResource|JsonResponse The found resource or JSON error response.
     */
    public function show(int|string $id): JsonResource|JsonResponse
    {
        $model = $this->model::query()->initializer()
            ->when($this->load, fn (Builder $query): Builder => $query->with($this->load))
            ->when($this->loadCount, fn (Builder $query): Builder => $query->withCount($this->loadCount))
            ->when($this->loadAggregate, fn (Builder $query): Builder => $this->applyLoadAggregate($query))
            ->when($this->loadScopes, fn (Builder $query): Builder => $this->applyScopes($query, $this->loadScopes))
            ->findOrFail($id);

        return new $this->resource($model);
    }

    /**
     * Apply aggregate functions (e.g., withAggregate) when loading a single resource.
     *
     * @param  Builder<Model>  $query  The Eloquent query builder.
     * @return Builder<Model> The modified query builder.
     */
    protected function applyLoadAggregate(Builder $query): Builder
    {
        foreach ($this->loadAggregate as $key => $value) {
            $query->withAggregate($key, $value);
        }

        return $query;
    }

    /**
     * Remove the specified resource from storage (soft-delete or force delete).
     *
     * Applies any deleteScopes, calls beforeDeleteProcess, deletes (soft or force),
     * then calls afterDeleteProcess.
     *
     * @param  int|string  $id  The primary key of the resource.
     * @return JsonResponse HTTP 204 No Content on success, or error response.
     *
     * @throws Exception
     */
    public function destroy(int|string $id): JsonResponse
    {
        $model = $this->findModel($id, $this->deleteScopes, $this->deleteScopeWithValue);
        $this->beforeDeleteProcess($model);
        $this->forceDelete ? $model->forceDelete() : $model->delete();
        $this->afterDeleteProcess($model);

        return $this->success(code: ResponseAlias::HTTP_NO_CONTENT);
    }

    /**
     * Find a model by ID, applying optional scopes and scope-with-value filters.
     *
     * @param  int|string  $id  The primary key of the model.
     * @param  string[]  $scopes  List of scope method names to apply.
     * @param  array<string,mixed>  $scopeWithValue  Key-value pairs for parameterized scopes.
     * @return Model The found model instance.
     *
     * @throws Exception If the model cannot be found with the given scopes.
     */
    protected function findModel(int|string $id, array $scopes = [], array $scopeWithValue = []): Model
    {

        $query = $this->model::query()
            ->when(! empty($scopes), fn (Builder $query): Builder => $this->applyScopes($query, $scopes));

        return $query->findOrFail($id);
    }

    /**
     * Hook for pre-delete logic on the model.
     *
     * If the model defines a beforeDeleteProcess() method, it will be called.
     *
     * @param  Model  $model  The model instance about to be deleted.
     * @return Model The model instance (possibly modified).
     */
    protected function beforeDeleteProcess(Model $model): Model
    {
        if (method_exists($model, 'beforeDeleteProcess')) {
            $model->beforeDeleteProcess();
        }

        return $model;
    }

    /**
     * Delete multiple resources by passing an array of IDs in 'delete_rows'.
     *
     * Validates that 'delete_rows' exists and each entry is a valid ID. Wraps the batch delete
     * in a transaction, calling before/after hooks for each model.
     *
     * @return JsonResponse HTTP 204 No Content on success, or error response.
     *
     * @throws Throwable
     */
    public function delete(): JsonResponse
    {
        request()->validate([
            'delete_rows' => ['required', 'array'],
            'delete_rows.*' => ['required', 'exists:'.(new $this->model)->getTable().',id'],
        ]);

        try {
            DB::beginTransaction();
            foreach ((array) request()->delete_rows as $item) {
                /** @var int $item */
                $model = $this->findModel($item, $this->deleteScopes, $this->deleteScopeWithValue);
                $this->beforeDeleteProcess($model);
                $this->forceDelete ? $model->forceDelete() : $model->delete();
                $this->afterDeleteProcess($model);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage());
        }

        return $this->success(code: ResponseAlias::HTTP_NO_CONTENT);
    }

    /**
     * Hook for post-delete logic on the model.
     *
     * If the model defines an afterDeleteProcess() method, it will be called.
     *
     * @param  Model  $model  The model instance that was deleted.
     * @return Model The model instance (possibly modified).
     */
    protected function afterDeleteProcess(Model $model): Model
    {
        if (method_exists($model, 'afterDeleteProcess')) {
            $model->afterDeleteProcess();
        }

        return $model;
    }

    /**
     * Change the status column (toggle between 0 and 1) for the specified resource.
     *
     * Applies any changeStatusScopes, validates that the column is fillable, calls pre/post hooks,
     * and toggles the status inside a transaction.
     *
     * @param  int|string  $id  The primary key of the resource.
     * @param  string  $column  The name of the status column (default 'status').
     * @return JsonResource|JsonResponse The updated resource or JSON error response.
     *
     * @throws Throwable
     */
    public function changeStatus(int|string $id, string $column = 'status'): JsonResource|JsonResponse
    {
        $model = $this->findModel($id, $this->changeStatusScopes, $this->changeStatusScopeWithValue);
        $this->validateColumn($model, $column);

        try {
            DB::beginTransaction();
            $this->beforeChangeStatusProcess($model);
            if (Schema::hasColumn($model->getTable(), $column)) {
                $model->update([$column => $model->$column === 1 ? 0 : 1]);
            } else {
                throw new Exception("{$column} column does not exist in the database.");
            }
            $this->afterChangeStatusProcess($model);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage());
        }

        return new $this->resource($model);
    }

    /**
     * Validate that a given column is present in the model's fillable attributes.
     *
     * @param  Model  $model  The model instance to inspect.
     * @param  string  $column  The column name to validate.
     * @return bool Always returns true if validation passes.
     *
     * @throws Exception If the column is not listed as fillable.
     */
    protected function validateColumn(Model $model, string $column): bool
    {
        if (! $this->checkFillable($model, [$column])) {
            throw new Exception("$column column not found in fillable");
        }

        return true;
    }

    /**
     * Check whether all specified columns are fillable on the model.
     *
     * @param  Model  $model  The model instance.
     * @param  string[]  $columns  List of column names to check.
     * @return bool True if all columns appear in the model's table listing.
     */
    protected function checkFillable(Model $model, array $columns): bool
    {
        $fillableColumns = $this->fillableColumn($model);

        return count(array_diff($columns, $fillableColumns)) === 0;
    }

    /**
     * Retrieve all column names for the model's underlying database table.
     *
     * @param  Model  $model  The model instance.
     * @return string[] List of column names.
     */
    protected function fillableColumn(Model $model): array
    {
        return Schema::getColumnListing($this->tableName($model));
    }

    /**
     * Get the table name associated with the model.
     *
     * @param  Model  $model  The model instance.
     * @return string The table name.
     */
    protected function tableName(Model $model): string
    {
        return $model->getTable();
    }

    /**
     * Hook for pre-change-status logic on the model.
     *
     * If the model defines a beforeChangeStatusProcess() method, it will be called.
     *
     * @param  Model  $model  The model instance whose status is about to be toggled.
     * @return Model The model instance (possibly modified).
     */
    protected function beforeChangeStatusProcess(Model $model): Model
    {
        if (method_exists($model, 'beforeChangeStatusProcess')) {
            $model->beforeChangeStatusProcess();
        }

        return $model;
    }

    /**
     * Update the specified resource in storage.
     *
     * Validates using the updateRequest, applies any updateScopes, calls pre/post hooks,
     * and returns the updated resource.
     *
     * @param  int|string  $id  The primary key of the resource.
     * @return JsonResource|JsonResponse The updated resource or JSON error response.
     */
    public function update(int|string $id): JsonResource|JsonResponse
    {
        $data = resolve($this->updateRequest::class)->safe()->only((new $this->model)->getFillable());
        $model = $this->findModel($id, $this->updateScopes, $this->updateScopeWithValue);

        try {
            DB::beginTransaction();
            $this->beforeUpdateProcess($model);
            $model->update($data);
            $this->afterUpdateProcess($model);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage());
        }

        return new $this->resource($model);
    }

    /**
     * Hook for pre-update logic on the model.
     *
     * If the model defines a beforeUpdateProcess() method, it will be called.
     *
     * @param  Model  $model  The model instance about to be updated.
     * @return Model The model instance (possibly modified).
     */
    protected function beforeUpdateProcess(Model $model): Model
    {
        if (method_exists($model, 'beforeUpdateProcess')) {
            $model->beforeUpdateProcess();
        }

        return $model;
    }

    /**
     * Hook for post-update logic on the model.
     *
     * If the model defines an afterUpdateProcess() method, it will be called.
     *
     * @param  Model  $model  The model instance that was updated.
     * @return Model The model instance (possibly modified).
     */
    protected function afterUpdateProcess(Model $model): Model
    {
        if (method_exists($model, 'afterUpdateProcess')) {
            $model->afterUpdateProcess();
        }

        return $model;
    }

    /**
     * Hook for post-change-status logic on the model.
     *
     * If the model defines an afterChangeStatusProcess() method, it will be called.
     *
     * @param  Model  $model  The model instance whose status was toggled.
     * @return Model|string The model instance (possibly modified), or a string if the hook returns one.
     */
    protected function afterChangeStatusProcess(Model $model): Model|string
    {
        if (method_exists($model, 'afterChangeStatusProcess')) {
            $model->afterChangeStatusProcess();
        }

        return $model;
    }

    /**
     * Restore a single trashed (soft-deleted) resource.
     *
     * Applies any restoreScopes, calls pre/post hooks, and returns the restoring resource.
     *
     * @param  int|string  $id  The primary key of the resource.
     * @return JsonResource|JsonResponse The restoring resource or JSON error response.
     *
     * @throws Throwable
     */
    public function restoreTrashed(int|string $id): JsonResource|JsonResponse
    {
        $model = $this->model::query()->initializer()->onlyTrashed()
            ->when($this->restoreScopes, fn ($query) => $this->applyScopes($query, $this->restoreScopes))
            ->findOrFail($id);

        try {
            DB::beginTransaction();
            $this->beforeRestoreProcess($model);
            $model->restore();
            $this->afterRestoreProcess($model);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage());
        }

        return new $this->resource($model);
    }

    /**
     * Hook for pre-restore logic on the model.
     *
     * If the model defines a beforeRestoreProcess() method, it will be called.
     *
     * @param  Model  $model  The model instance about to be restored.
     * @return Model The model instance (possibly modified).
     */
    protected function beforeRestoreProcess(Model $model): Model
    {
        if (method_exists($model, 'beforeRestoreProcess')) {
            $model->beforeRestoreProcess();
        }

        return $model;
    }

    /**
     * Hook for post-restore logic on the model.
     *
     * If the model defines an afterRestoreProcess() method, it will be called.
     *
     * @param  Model  $model  The model instance that was restoring.
     * @return Model The model instance (possibly modified).
     */
    protected function afterRestoreProcess(Model $model): Model
    {
        if (method_exists($model, 'afterRestoreProcess')) {
            $model->afterRestoreProcess();
        }

        return $model;
    }

    /**
     * Restore all trashed (soft-deleted) records for this model.
     *
     * Does not return a resource, only HTTP 204 on success.
     *
     * @return JsonResponse HTTP 204 No Content on success, or error response.
     *
     * @throws Throwable
     */
    public function restoreAllTrashed(): JsonResponse
    {
        try {
            DB::beginTransaction();
            $this->model::query()->initializer()->onlyTrashed()->restore();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage());
        }

        return $this->success(code: ResponseAlias::HTTP_NO_CONTENT);
    }

    /**
     * Force delete a single trashed (soft-deleted) resource.
     *
     * Applies any forceDeleteScopes, calls pre/post hooks, and returns the deleted resource.
     *
     * @param  int|string  $id  The primary key of the resource.
     * @return JsonResponse|Model The deleted resource or JSON error response.
     *
     * @throws Throwable
     */
    public function forceDeleteTrashed(int|string $id): JsonResponse|Model
    {
        $model = $this->model::query()->initializer()->onlyTrashed()->findOrFail($id);

        try {
            DB::beginTransaction();
            $this->beforeForceDeleteProcess($model);
            $model->forceDelete();
            $this->afterForceDeleteProcess($model);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage());
        }

        return $this->success(code: ResponseAlias::HTTP_NO_CONTENT);
    }

    protected function beforeForceDeleteProcess(Model $model): Model
    {
        if (method_exists($model, 'beforeForceDeleteProcess')) {
            $model->beforeForceDeleteProcess();
        }

        return $model;
    }

    protected function afterForceDeleteProcess(Model $model): Model
    {
        if (method_exists($model, 'afterForceDeleteProcess')) {
            $model->afterForceDeleteProcess();
        }

        return $model;
    }
}
