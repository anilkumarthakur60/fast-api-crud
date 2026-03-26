<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Http\Controllers;

use Anil\FastApiCrud\Concerns\ApiResponder;
use Anil\FastApiCrud\Concerns\CrudQueries;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract base controller providing standard CRUD operations for Eloquent models.
 *
 * Supports: index, show, store, update, destroy, bulk delete, changeStatus,
 * updateColumn, restore, restoreAll, permanentDelete.
 *
 * Each operation supports lifecycle hooks (override in child controller or define on model).
 */
abstract class BaseController extends Controller
{
    use ApiResponder;
    use AuthorizesRequests;
    use CrudQueries;

    /** @var class-string<JsonResource> */
    protected readonly string $resource;

    /**
     * @param  class-string<Model>  $model
     * @param  class-string<FormRequest>  $storeRequest
     * @param  class-string<FormRequest>  $updateRequest
     * @param  class-string<JsonResource>  $resource
     *
     * @throws Exception
     */
    public function __construct(
        string $model,
        string $storeRequest,
        string $updateRequest,
        string $resource,
    ) {
        $this->model = $this->resolveModel($model);
        $this->storeRequest = $this->resolveFormRequest($storeRequest, 'storeRequest');
        $this->updateRequest = $this->resolveFormRequest($updateRequest, 'updateRequest');
        $this->resource = $this->resolveResource($resource);
        $this->registerPermissionMiddleware();
    }

    // -------------------------------------------------------------------------
    // CRUD operations
    // -------------------------------------------------------------------------

    /**
     * List all records, applying scopes, eager loads, counts, aggregates, search and pagination.
     */
    public function index(): AnonymousResourceCollection
    {
        $query = $this->buildIndexQuery();

        return $this->resource::collection($this->paginateQuery($query));
    }

    /**
     * Display the specified resource.
     */
    public function show(int|string $id): JsonResource
    {
        $query = $this->buildShowQuery();

        return new $this->resource($query->findOrFail($id));
    }

    /**
     * Store a newly created resource. Returns 201 Created on success.
     *
     * @throws ValidationException
     */
    public function store(): JsonResponse
    {
        try {
            $model = $this->performStore();
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return (new $this->resource($model))
            ->toResponse(request())
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update the specified resource.
     *
     * @throws ValidationException
     */
    public function update(int|string $id): JsonResource|JsonResponse
    {
        try {
            $model = $this->performUpdate($id);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return new $this->resource($model);
    }

    /**
     * Remove the specified resource (soft delete or force delete).
     *
     * @throws ValidationException
     */
    public function destroy(int|string $id): JsonResponse
    {
        try {
            $this->performDestroy($id);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(code: Response::HTTP_NO_CONTENT);
    }

    /**
     * Bulk delete records by passing an array of IDs in 'delete_rows'.
     *
     * @throws ValidationException
     */
    public function delete(): JsonResponse
    {
        try {
            $this->performBulkDelete();
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(code: Response::HTTP_NO_CONTENT);
    }

    // -------------------------------------------------------------------------
    // Extended operations
    // -------------------------------------------------------------------------

    /**
     * Toggle a boolean status column between 0 and 1.
     *
     * @throws ValidationException
     */
    public function changeStatus(int|string $id, string $column = 'status'): JsonResource|JsonResponse
    {
        try {
            $model = $this->performChangeStatus($id, $column);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return new $this->resource($model);
    }

    /**
     * Update a specific fillable column with the request value.
     *
     * @throws ValidationException
     */
    public function updateColumn(int|string $id, string $column = 'status'): JsonResource|JsonResponse
    {
        try {
            $model = $this->performUpdateColumn($id, $column);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return new $this->resource($model);
    }

    /**
     * Restore a single soft-deleted resource.
     *
     * @throws ValidationException
     */
    public function restore(int|string $id): JsonResource|JsonResponse
    {
        try {
            $model = $this->performRestore($id);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return new $this->resource($model);
    }

    /**
     * Restore all soft-deleted records.
     *
     * @throws ValidationException
     */
    public function restoreAll(): JsonResponse
    {
        try {
            $this->performRestoreAll();
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(code: Response::HTTP_NO_CONTENT);
    }

    /**
     * Permanently delete a soft-deleted resource.
     *
     * @throws ValidationException
     */
    public function permanentDelete(int|string $id): JsonResponse
    {
        try {
            $this->performPermanentDelete($id);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(code: Response::HTTP_NO_CONTENT);
    }
}
