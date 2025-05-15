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

/**
 * @property class-string<Model> $model
 * @property class-string<JsonResource> $resource
 * @property array<string> $scopes
 * @property array<string, mixed> $scopeWithValue
 * @property array<string> $loadScopes
 * @property array<string, mixed> $loadScopeWithValue
 * @property array<string> $with
 * @property array<string> $withCount
 * @property array<string, string> $withAggregate
 * @property array<string> $load
 * @property array<string> $loadCount
 * @property array<string, string> $loadAggregate
 * @property bool $isApi
 * @property bool $forceDelete
 * @property array<string> $deleteScopes
 * @property array<string, mixed> $deleteScopeWithValue
 * @property array<string> $changeStatusScopes
 * @property array<string, mixed> $changeStatusScopeWithValue
 * @property array<string> $restoreScopes
 * @property array<string, mixed> $restoreScopeWithValue
 * @property array<string> $updateScopes
 * @property array<string, mixed> $updateScopeWithValue
 *
 * @mixin Builder<Model>
 */
class CrudBaseController extends BaseController
{
    use AuthorizesRequests;
    use HasApiResponse;
    use ValidatesRequests;

    /**
     * @var array<string>
     */
    public array $scopes = [];

    /**
     * @var array<string, mixed>
     */
    public array $scopeWithValue = [];

    /**
     * @var array<string>
     */
    public array $loadScopes = [];

    /**
     * @var array<string, mixed>
     */
    public array $loadScopeWithValue = [];

    /**
     * @var array<string>
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
     * @var array<string>
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
     * @throws ReflectionException
     * @throws Exception
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
     * @throws Exception
     */
    protected function validateModel(string $modelClass): void
    {
        if (! is_subclass_of($modelClass, Model::class)) {
            throw new Exception('Model is not instance of Model', ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
        $this->model = resolve($modelClass);
    }

    /**
     * @throws Exception
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
     * @throws Exception
     */
    protected function validateResource(string $resource, string $resourceName): void
    {
        if (! is_subclass_of($resource, JsonResource::class)) {
            throw new Exception("$resourceName is not instance of JsonResource", ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
        $this->resource = $resource;
    }

    /**
     * @throws Exception
     */
    protected function setupPermissions(): void
    {
        $permissionSlug = null;
        /** @var Model $model */
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
     * @return AnonymousResourceCollection<JsonResource>
     *                                                   /
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

        if (! empty($this->scopeWithValue)) {
            $this->applyScopeWithValue($query, $this->scopeWithValue);
        }

        return $this->resource::collection($query->paginates());
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
     * @param  Builder<Model>  $query
     * @param  array<string>  $scopes
     * @return Builder<Model>
     */
    protected function applyScopes(Builder $query, array $scopes): Builder
    {
        foreach ($scopes as $scope) {
            if (method_exists($query->getModel(), $scope)) {
                $query->{$scope}();
            }
        }

        return $query;
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, mixed>  $scopeWithValue
     * @return Builder<Model>
     */
    protected function applyScopeWithValue(Builder $query, array $scopeWithValue): Builder
    {
        foreach ($scopeWithValue as $key => $value) {
            if (method_exists($query->getModel(), $key)) {
                $query->$key($value);
            }
        }

        return $query;
    }

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

    protected function afterCreateProcess(Model $model): Model|string
    {
        if (method_exists($model, 'afterCreateProcess')) {
            $model->afterCreateProcess();
        }

        return $model;
    }

    public function show(int|string $id): JsonResource|JsonResponse
    {
        $model = $this->model::query()->initializer()
            ->when($this->load, fn (Builder $query): Builder => $query->with($this->load))
            ->when($this->loadCount, fn (Builder $query): Builder => $query->withCount($this->loadCount))
            ->when($this->loadAggregate, fn (Builder $query): Builder => $this->applyLoadAggregate($query))
            ->when($this->loadScopes, fn (Builder $query): Builder => $this->applyScopes($query, $this->loadScopes))
            ->when($this->loadScopeWithValue, fn (Builder $query): Builder => $this->applyScopeWithValue($query, $this->loadScopeWithValue))
            ->findOrFail($id);

        return new $this->resource($model);
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function applyLoadAggregate(Builder $query): Builder
    {
        foreach ($this->loadAggregate as $key => $value) {
            $query->withAggregate($key, $value);
        }

        return $query;
    }

    public function destroy(int|string $id): JsonResponse
    {
        $model = $this->findModel($id, $this->deleteScopes, $this->deleteScopeWithValue);
        $this->beforeDeleteProcess($model);
        $this->forceDelete ? $model->forceDelete() : $model->delete();
        $this->afterDeleteProcess($model);

        return $this->success(code: ResponseAlias::HTTP_NO_CONTENT);
    }

    /**
     * Find model by ID with optional scopes.
     *
     * @param  array<string>  $scopes
     * @param  array<string, mixed>  $scopeWithValue
     *
     * @throws Exception
     */
    protected function findModel(int|string $id, array $scopes = [], array $scopeWithValue = []): Model
    {

        $query = $this->model::query()
            ->when(! empty($scopes), fn (Builder $query): Builder => $this->applyScopes($query, $scopes))
            ->when(! empty($scopeWithValue), fn (Builder $query): Builder => $this->applyScopeWithValue($query, $scopeWithValue));

        return $query->findOrFail($id);
    }

    protected function beforeDeleteProcess(Model $model): Model
    {
        if (method_exists($model, 'beforeDeleteProcess')) {
            $model->beforeDeleteProcess();
        }

        return $model;
    }

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

    protected function afterDeleteProcess(Model $model): Model
    {
        if (method_exists($model, 'afterDeleteProcess')) {
            $model->afterDeleteProcess();
        }

        return $model;
    }

    protected function validateColumn(Model $model, string $column): bool
    {
        if (! $this->checkFillable($model, [$column])) {
            throw new Exception("$column column not found in fillable");
        }

        return true;
    }

    /**
     * @param  array<string>  $columns
     */
    protected function checkFillable(Model $model, array $columns): bool
    {
        $fillableColumns = $this->fillableColumn($model);

        return count(array_diff($columns, $fillableColumns)) === 0;
    }

    /**
     * @return array<string>
     */
    protected function fillableColumn(Model $model): array
    {
        return Schema::getColumnListing($this->tableName($model));
    }

    protected function tableName(Model $model): string
    {
        return $model->getTable();
    }

    protected function beforeChangeStatusProcess(Model $model): Model
    {
        if (method_exists($model, 'beforeChangeStatusProcess')) {
            $model->beforeChangeStatusProcess();
        }

        return $model;
    }

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

    protected function beforeUpdateProcess(Model $model): Model
    {
        if (method_exists($model, 'beforeUpdateProcess')) {
            $model->beforeUpdateProcess();
        }

        return $model;
    }

    protected function afterUpdateProcess(Model $model): Model
    {
        if (method_exists($model, 'afterUpdateProcess')) {
            $model->afterUpdateProcess();
        }

        return $model;
    }

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
                throw new Exception('Status column does not exist in the database.');
            }
            $this->afterChangeStatusProcess($model);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage());
        }

        return new $this->resource($model);
    }

    protected function afterChangeStatusProcess(Model $model): Model|string
    {
        if (method_exists($model, 'afterChangeStatusProcess')) {
            $model->afterChangeStatusProcess();
        }

        return $model;
    }

    public function restoreTrashed(int|string $id): JsonResource|JsonResponse
    {
        $model = $this->model::query()->initializer()->onlyTrashed()
            ->when($this->restoreScopes, fn ($query) => $this->applyScopes($query, $this->restoreScopes))
            ->when($this->restoreScopeWithValue, fn ($query) => $this->applyScopeWithValue($query, $this->restoreScopeWithValue))
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

    protected function beforeRestoreProcess(Model $model): Model
    {
        if (method_exists($model, 'beforeRestoreProcess')) {
            $model->beforeRestoreProcess();
        }

        return $model;
    }

    protected function afterRestoreProcess(Model $model): Model
    {
        if (method_exists($model, 'afterRestoreProcess')) {
            $model->afterRestoreProcess();
        }

        return $model;
    }

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
