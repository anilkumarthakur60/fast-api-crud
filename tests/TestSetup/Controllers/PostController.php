<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Controllers;

use Anil\FastApiCrud\Controller\BaseController;
use Anil\FastApiCrud\Tests\TestSetup\Models\PostModel;
use Anil\FastApiCrud\Tests\TestSetup\Requests\Post\StorePostRequest;
use Anil\FastApiCrud\Tests\TestSetup\Requests\Post\UpdatePostRequest;
use Anil\FastApiCrud\Tests\TestSetup\Resources\PostResource;
use Exception;

class PostController extends BaseController
{
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
