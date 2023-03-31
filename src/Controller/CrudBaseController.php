<?php

namespace Anil\FastApiCrud\Controller;

use Exception;
use ReflectionClass;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;

class CrudBaseController extends Controller
{
    public array $scopes = [];
    public array $scopeWithValue = [];
    public array $loadScopes = [];
    public array $loadScopeWithValue = [];
    public array $withAll = [];
    public array $withCount = [];
    public array $withAggregate = [];
    public array $loadAll = [];
    public array $loadCount = [];
    public array $loadAggregate = [];
    public bool $isApi = TRUE;
    public bool $forceDelete = FALSE;


    public function __construct(public $model, public $storeRequest, public $updateRequest, public $resource)
    {

        $constants = new ReflectionClass($this->model);
        try {
            $permissionSlug = $constants->getConstant('permissionSlug');
        } catch (Exception $e) {
            $permissionSlug = NULL;
        }
        if ($permissionSlug) {
            $this->middleware('permission:view-' . $this->model::permissionSlug)
                ->only(['index', 'show']);
            $this->middleware('permission:alter-' . $this->model::permissionSlug)
                ->only(['store', 'update', 'changeStatus', 'changeStatusOtherColumn', 'restore']);
            $this->middleware('permission:delete-' . $this->model::permissionSlug)
                ->only(['delete']);
        }
    }


    public function index()
    {
        $model = $this->model::initializer()
            ->when(property_exists($this, 'withAll') && count($this->withAll), function ($query) {
                return $query->with($this->withAll);
            })
            ->when(property_exists($this, 'withCount') && count($this->withCount), function ($query) {
                return $query->withCount($this->withCount);
            })
            ->when(property_exists($this, 'withAggregate') && count($this->withAggregate), function ($query) {
                foreach ($this->withAggregate as $key => $value) {
                    $query->withAggregate($key, $value);
                }
            })
            ->when(property_exists($this, 'scopes') && count($this->scopes), function ($query) {
                foreach ($this->scopes as $value) {
                    $query->$value();
                }
            })
            ->when(property_exists($this, 'scopeWithValue') && count($this->scopeWithValue), function ($query) {
                foreach ($this->scopeWithValue as $key => $value) {
                    $query->$key($value);
                }
            });

        return $this->resource::collection($model->paginates());
    }


    public function store()
    {
        $data = resolve($this->storeRequest)->safe()->only((new $this->model())->getFillable());

        try {
            DB::beginTransaction();
            $model = $this->model::create($data);
            if (method_exists(new $this->model(), 'afterCreateProcess')) {
                $model->afterCreateProcess();
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage());
        }

        return $this->resource::make($model);
    }


    public function error(
        $message = 'Something went wrong',
        $data = [],
        $code = Response::HTTP_INTERNAL_SERVER_ERROR,
    )
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $code);
    }


    public function show($id)
    {

        $model = $this->model::initializer()
            ->when(property_exists($this, 'loadAll'), function ($query) {
                return $query->with($this->loadAll);
            })
            ->when(property_exists($this, 'loadCount'), function ($query) {
                return $query->withCount($this->loadCount);
            })
            ->when(property_exists($this, 'loadAggregate'), function ($query) {
                foreach ($this->loadAggregate as $key => $value) {
                    $query->withAggregate($key, $value);
                }
            })
            ->when(property_exists($this, 'loadScopes') && count($this->loadScopes), function ($query) {
                foreach ($this->loadScopes as $value) {
                    $query->$value();
                }
            })
            ->when(property_exists($this, 'loadScopeWithValue') && count($this->loadScopeWithValue), function ($query) {
                foreach ($this->loadScopeWithValue as $key => $value) {
                    $query->$key($value);
                }
            })
            ->findOrFail($id);

        return $this->resource::make($model);
    }


    public function destroy($id)
    {
        $model = $this->model::initializer()->findOrFail($id);
        if (method_exists(new $this->model(), 'afterDeleteProcess')) {
            $model->afterDeleteProcess();
        }
        $this->forceDelete === TRUE ? $model->forceDelete() : $model->delete();

        return $this->success(message: 'Data deleted successfully');
    }


    public function delete()
    {
        request()->validate([
            'delete_rows' => ['required', 'array'],
            'delete_rows.*' => ['required', 'exists:' . (new  $this->model())->getTable() . ',id'],
        ]);

        foreach ((array) request()->input('delete_rows') as $item) {
            $model = $this->model::initializer()->find($item);
            if (method_exists(new $this->model(), 'afterDeleteProcess') && $model) {
                $model->afterDeleteProcess();
            }
            if ($model) {
                $this->forceDelete === TRUE ? $model->forceDelete() : $model->delete();
            }
        }

        return $this->success(message: 'Data deleted successfully');
    }


    public function success(
        $message = 'Data fetched successfully',
        $data = [],
        $code = Response::HTTP_OK,
    )
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $code);
    }


    public function changeStatusOtherColumn($id, $column)
    {
        $model = $this->model::initializer()->findOrFail($id);
        try {
            DB::beginTransaction();
            if (method_exists(new $this->model(), 'beforeChangeStatusProcess')) {
                $model->beforeChangeStatusProcess();
            }
            if (!$this->checkFillable($model, [$column])) {
                DB::rollBack();
                throw new Exception("$column column not found in fillable");
            }
            $model->update([$column => $model->$column === 1 ? 0 : 1]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage());
        }

        return $this->resource::make($model);
    }


    protected function checkFillable($model, $columns): bool
    {
        $fillableColumns = $this->fillableColumn($model);

        $diff = array_diff($columns, $fillableColumns);

        return count($diff) > 0 ? FALSE : TRUE;
    }


    public function update($id)
    {
        $data = resolve($this->updateRequest)->safe()->only((new $this->model())->getFillable());

        $model = $this->model::initializer()->findOrFail($id);

        try {
            DB::beginTransaction();
            if (method_exists(new $this->model(), 'beforeUpdateProcess')) {
                $model->beforeUpdateProcess();
            }
            $model->update($data);
            if (method_exists(new $this->model(), 'afterUpdateProcess')) {
                $model->afterUpdateProcess();
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage());
        }

        return $this->resource::make($model);
    }


    protected function fillableColumn($model): array
    {
        return Schema::getColumnListing($this->tableName($model));
    }


    protected function tableName($model): string
    {
        return $model->getTable();
    }


    public function changeStatus($id)
    {
        $model = $this->model::initializer()->findOrFail($id);
        try {
            DB::beginTransaction();
            if (method_exists(new $this->model(), 'beforeChangeStatusProcess')) {
                $model->beforeChangeStatusProcess();
            }
            if (!$this->checkFillable($model, ['status'])) {
                DB::rollBack();
                throw new Exception('Status column not found in fillable');
            }
            $model->update(['status' => $model->status === 1 ? 0 : 1]);
            if (method_exists(new $this->model(), 'afterChangeStatusProcess')) {
                $model->afterChangeStatusProcess();
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage());
        }

        return $this->resource::make($model);
    }


    public function restore($id)
    {
        $model = $this->model::initializer()->onlyTrashed()->findOrFail($id);

        try {
            DB::beginTransaction();

            if (method_exists(new $this->model(), 'beforeRestoreProcess')) {
                $model->beforeRestoreProcess();
            }

            $model->restore();

            if (method_exists(new $this->model(), 'afterRestoreProcess')) {
                $model->afterRestoreProcess();
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage());
        }

        return $this->resource::make($model);
    }

}
