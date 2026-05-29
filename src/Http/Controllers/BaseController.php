<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Http\Controllers;

use Anil\FastApiCrud\Concerns\HasApiResponse;
use Anil\FastApiCrud\Concerns\HasCrudOperations;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controllers\HasMiddleware;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Abstract base controller providing standard CRUD operations for Eloquent models.
 *
 * Supports: index, show, store, update, destroy, bulk delete, changeStatus,
 * updateColumn, restore, restoreAll, permanentDelete.
 *
 * Each operation supports lifecycle hooks (override in child controller or define on model).
 */
abstract class BaseController implements HasMiddleware
{
    use AuthorizesRequests;
    use HasApiResponse;
    use HasCrudOperations;

    /** @var class-string<JsonResource> */
    protected readonly string $resource;

    /**
     * @param class-string<Model> $model
     * @param class-string<FormRequest> $storeRequest
     * @param class-string<FormRequest> $updateRequest
     * @param class-string<JsonResource> $resource
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
    }

    /**
     * Get the middleware that should be assigned to the controller.
     *
     * Override in child controllers to add permission middleware:
     *
     *   public static function middleware(): array
     *   {
     *       return static::permissionMiddleware('posts');
     *   }
     *
     * @return array<int, \Illuminate\Routing\Controllers\Middleware|string>
     */
    public static function middleware(): array
    {
        return [];
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
     * Validation, authorization, and database errors propagate to the framework's
     * exception handler so they render with correct status codes (422/403/500)
     * and debug-aware messages rather than being flattened into a generic 400.
     *
     * @throws Throwable
     */
    public function store(): JsonResponse
    {
        $model = $this->performStore();

        return (new $this->resource($model))
            ->toResponse(request())
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update the specified resource.
     *
     * @throws Throwable
     */
    public function update(int|string $id): JsonResource
    {
        return new $this->resource($this->performUpdate($id));
    }

    /**
     * Remove the specified resource (soft delete or force delete).
     *
     * @throws Throwable
     */
    public function destroy(int|string $id): JsonResponse
    {
        $this->performDestroy($id);

        return $this->noContent();
    }

    /**
     * Bulk delete records by passing an array of IDs in 'delete_rows'.
     *
     * @throws Throwable
     */
    public function delete(): JsonResponse
    {
        $this->performBulkDelete();

        return $this->noContent();
    }

    // -------------------------------------------------------------------------
    // Extended operations
    // -------------------------------------------------------------------------

    /**
     * Toggle a boolean status column between 0 and 1.
     *
     * @throws Throwable
     */
    public function changeStatus(int|string $id, string $column = 'status'): JsonResource
    {
        return new $this->resource($this->performChangeStatus($id, $column));
    }

    /**
     * Update a specific fillable column with the request value.
     *
     * @throws Throwable
     */
    public function updateColumn(int|string $id, string $column = 'status'): JsonResource
    {
        return new $this->resource($this->performUpdateColumn($id, $column));
    }

    /**
     * Restore a single soft-deleted resource.
     *
     * @throws Throwable
     */
    public function restore(int|string $id): JsonResource
    {
        return new $this->resource($this->performRestore($id));
    }

    /**
     * Restore all soft-deleted records.
     *
     * @throws Throwable
     */
    public function restoreAll(): JsonResponse
    {
        $this->performRestoreAll();

        return $this->noContent();
    }

    /**
     * Permanently delete a soft-deleted resource.
     *
     * @throws Throwable
     */
    public function permanentDelete(int|string $id): JsonResponse
    {
        $this->performPermanentDelete($id);

        return $this->noContent();
    }
}
