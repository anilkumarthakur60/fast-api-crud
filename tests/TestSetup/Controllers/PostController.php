<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Controllers;

use Anil\FastApiCrud\Http\Controllers\BaseController;
use Anil\FastApiCrud\Tests\TestSetup\Models\PostModel;
use Anil\FastApiCrud\Tests\TestSetup\Requests\Post\StorePostRequest;
use Anil\FastApiCrud\Tests\TestSetup\Requests\Post\UpdatePostRequest;
use Anil\FastApiCrud\Tests\TestSetup\Resources\PostResource;
use Exception;

class PostController extends BaseController
{
    /** @var array<int, string> */
    protected array $allowedIncludes = ['user', 'tags'];

    protected bool $allowTrashedFilter = true;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct(
            model: PostModel::class,
            storeRequest: StorePostRequest::class,
            updateRequest: UpdatePostRequest::class,
            resource: PostResource::class
        );
    }
}
