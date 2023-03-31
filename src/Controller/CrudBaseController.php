<?php

namespace Anil\FastApiCrud\Controller;

use Exception;
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
    protected array $withAll = [];
    protected array $withCount = [];
    protected array $withAggregate = [];
    protected array $loadAll = [];
    protected array $loadCount = [];
    protected array $loadAggregate = [];
    protected bool $isApi = TRUE;
    protected bool $forceDelete = FALSE;


    public function __construct(public $model, public $storeRequest, public $updateRequest, public $resource) {}


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
        $model = $this->model::findOrFail($id);
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
            $model = $this->model::find($item);
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


    public function changeStatus($id)
    {
        $model = $this->model::findOrFail($id);
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
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage());
        }

        return $this->resource::make($model);
    }


    private function checkFillable($model, $columns): bool
    {
        $fillableColumns = $this->fillableColumn($model);

        $diff = array_diff($columns, $fillableColumns);

        return count($diff) > 0 ? FALSE : TRUE;
    }


    public function update($id)
    {
        $data = resolve($this->updateRequest)->safe()->only((new $this->model())->getFillable());

        $model = $this->model::findOrFail($id);

        try {
            DB::beginTransaction();
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


    private function fillableColumn($model): array
    {
        return Schema::getColumnListing($this->tableName($model));
    }


    private function tableName($model): string
    {
        return $model->getTable();
    }

}