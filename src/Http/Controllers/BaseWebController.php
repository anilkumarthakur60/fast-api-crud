<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Http\Controllers;

use Anil\FastApiCrud\Concerns\HasCrudOperations;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

/**
 * Abstract base controller providing standard CRUD operations with Blade view responses.
 *
 * Supports: index, create, show, edit, store, update, destroy, bulk delete, changeStatus,
 * updateColumn, restore, restoreAll, permanentDelete.
 *
 * Each operation supports lifecycle hooks (override in child controller or define on model).
 */
abstract class BaseWebController implements HasMiddleware
{
    use AuthorizesRequests;
    use HasCrudOperations;

    /** @var class-string<JsonResource>|null */
    protected readonly ?string $resource;

    /**
     * View name prefix (e.g., 'admin.posts' resolves to 'admin.posts.index', etc.).
     */
    protected string $viewPrefix;

    /**
     * Route name prefix for redirects (e.g., 'admin.posts' resolves to route('admin.posts.index')).
     */
    protected string $routePrefix;

    /**
     * Variable name passed to views for a single model instance (e.g., 'post').
     */
    protected string $resourceName;

    /**
     * Variable name passed to views for collections (e.g., 'posts').
     */
    protected string $collectionName;

    /**
     * @param class-string<Model> $model
     * @param class-string<FormRequest> $storeRequest
     * @param class-string<FormRequest> $updateRequest
     * @param class-string<JsonResource>|null $resource
     *
     * @throws Exception
     */
    public function __construct(
        string $model,
        string $storeRequest,
        string $updateRequest,
        string $viewPrefix,
        string $routePrefix,
        string $resourceName,
        string $collectionName,
        ?string $resource = null,
    ) {
        $this->model = $this->resolveModel($model);
        $this->storeRequest = $this->resolveFormRequest($storeRequest, 'storeRequest');
        $this->updateRequest = $this->resolveFormRequest($updateRequest, 'updateRequest');
        $this->viewPrefix = $viewPrefix;
        $this->routePrefix = $routePrefix;
        $this->resourceName = $resourceName;
        $this->collectionName = $collectionName;
        $this->resource = $resource !== null ? $this->resolveResource($resource) : null;
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
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $query = $this->buildIndexQuery();

        /** @var view-string $viewName */
        $viewName = $this->viewName('index');

        return view($viewName, [
            $this->collectionName => $this->paginateQuery($query),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        /** @var view-string $viewName */
        $viewName = $this->viewName('create');

        return view($viewName);
    }

    /**
     * Store a newly created resource.
     */
    public function store(): RedirectResponse
    {
        try {
            $this->performStore();
        } catch (Exception $e) {
            return $this->redirectBackWithError($e->getMessage());
        }

        return $this->redirectWithSuccess(
            "{$this->routePrefix}.index",
            $this->storeSuccessMessage(),
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(int|string $id): View
    {
        $query = $this->buildShowQuery();

        /** @var view-string $viewName */
        $viewName = $this->viewName('show');

        return view($viewName, [
            $this->resourceName => $query->findOrFail($id),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int|string $id): View
    {
        $query = $this->buildShowQuery();

        /** @var view-string $viewName */
        $viewName = $this->viewName('edit');

        return view($viewName, [
            $this->resourceName => $query->findOrFail($id),
        ]);
    }

    /**
     * Update the specified resource.
     */
    public function update(int|string $id): RedirectResponse
    {
        try {
            $this->performUpdate($id);
        } catch (Exception $e) {
            return $this->redirectBackWithError($e->getMessage());
        }

        return $this->redirectWithSuccess(
            "{$this->routePrefix}.index",
            $this->updateSuccessMessage(),
        );
    }

    /**
     * Remove the specified resource (soft delete or force delete).
     */
    public function destroy(int|string $id): RedirectResponse
    {
        try {
            $this->performDestroy($id);
        } catch (Exception $e) {
            return $this->redirectBackWithError($e->getMessage());
        }

        return $this->redirectWithSuccess(
            "{$this->routePrefix}.index",
            $this->destroySuccessMessage(),
        );
    }

    /**
     * Bulk delete records by passing an array of IDs in 'delete_rows'.
     */
    public function delete(): RedirectResponse
    {
        try {
            $this->performBulkDelete();
        } catch (Exception $e) {
            return $this->redirectBackWithError($e->getMessage());
        }

        return $this->redirectWithSuccess(
            "{$this->routePrefix}.index",
            $this->bulkDeleteSuccessMessage(),
        );
    }

    // -------------------------------------------------------------------------
    // Extended operations
    // -------------------------------------------------------------------------

    /**
     * Toggle a boolean status column between 0 and 1.
     */
    public function changeStatus(int|string $id, string $column = 'status'): RedirectResponse
    {
        try {
            $this->performChangeStatus($id, $column);
        } catch (Exception $e) {
            return $this->redirectBackWithError($e->getMessage());
        }

        return $this->redirectWithSuccess(
            "{$this->routePrefix}.index",
            $this->statusChangeSuccessMessage(),
        );
    }

    /**
     * Update a specific fillable column with the request value.
     */
    public function updateColumn(int|string $id, string $column = 'status'): RedirectResponse
    {
        try {
            $this->performUpdateColumn($id, $column);
        } catch (Exception $e) {
            return $this->redirectBackWithError($e->getMessage());
        }

        return $this->redirectWithSuccess(
            "{$this->routePrefix}.index",
            $this->columnUpdateSuccessMessage(),
        );
    }

    /**
     * Restore a single soft-deleted resource.
     */
    public function restore(int|string $id): RedirectResponse
    {
        try {
            $this->performRestore($id);
        } catch (Exception $e) {
            return $this->redirectBackWithError($e->getMessage());
        }

        return $this->redirectWithSuccess(
            "{$this->routePrefix}.index",
            $this->restoreSuccessMessage(),
        );
    }

    /**
     * Restore all soft-deleted records.
     */
    public function restoreAll(): RedirectResponse
    {
        try {
            $this->performRestoreAll();
        } catch (Exception $e) {
            return $this->redirectBackWithError($e->getMessage());
        }

        return $this->redirectWithSuccess(
            "{$this->routePrefix}.index",
            $this->restoreAllSuccessMessage(),
        );
    }

    /**
     * Permanently delete a soft-deleted resource.
     */
    public function permanentDelete(int|string $id): RedirectResponse
    {
        try {
            $this->performPermanentDelete($id);
        } catch (Exception $e) {
            return $this->redirectBackWithError($e->getMessage());
        }

        return $this->redirectWithSuccess(
            "{$this->routePrefix}.index",
            $this->permanentDeleteSuccessMessage(),
        );
    }

    // -------------------------------------------------------------------------
    // View / route helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the full view name from a suffix (e.g., 'index' → 'admin.posts.index').
     */
    protected function viewName(string $suffix): string
    {
        return "{$this->viewPrefix}.{$suffix}";
    }

    /**
     * Redirect to a named route with a success flash message.
     *
     * @param array<string, string|int|float|bool|null> $parameters
     */
    protected function redirectWithSuccess(string $route, string $message, array $parameters = []): RedirectResponse
    {
        $key = config('fast-api.web.flash_key_success', 'success');
        $flashKey = is_string($key) ? $key : 'success';

        return redirect()->route($route, $parameters)->with($flashKey, $message);
    }

    /**
     * Redirect back with an error flash message and old input.
     */
    protected function redirectBackWithError(string $message): RedirectResponse
    {
        $key = config('fast-api.web.flash_key_error', 'error');
        $flashKey = is_string($key) ? $key : 'error';

        return back()->withInput()->with($flashKey, $message);
    }

    // -------------------------------------------------------------------------
    // Overridable flash messages — override for localization or customization
    // -------------------------------------------------------------------------

    protected function storeSuccessMessage(): string
    {
        return 'Record created successfully.';
    }

    protected function updateSuccessMessage(): string
    {
        return 'Record updated successfully.';
    }

    protected function destroySuccessMessage(): string
    {
        return 'Record deleted successfully.';
    }

    protected function bulkDeleteSuccessMessage(): string
    {
        return 'Records deleted successfully.';
    }

    protected function statusChangeSuccessMessage(): string
    {
        return 'Status updated successfully.';
    }

    protected function columnUpdateSuccessMessage(): string
    {
        return 'Column updated successfully.';
    }

    protected function restoreSuccessMessage(): string
    {
        return 'Record restored successfully.';
    }

    protected function restoreAllSuccessMessage(): string
    {
        return 'All records restored successfully.';
    }

    protected function permanentDeleteSuccessMessage(): string
    {
        return 'Record permanently deleted.';
    }
}
