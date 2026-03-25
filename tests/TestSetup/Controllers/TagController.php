<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Controllers;

use Anil\FastApiCrud\Http\Controllers\BaseController;
use Anil\FastApiCrud\Tests\TestSetup\Models\TagModel;
use Anil\FastApiCrud\Tests\TestSetup\Requests\Tag\StoreTagRequest;
use Anil\FastApiCrud\Tests\TestSetup\Requests\Tag\UpdateTagRequest;
use Anil\FastApiCrud\Tests\TestSetup\Resources\TagResource;
use Exception;

class TagController extends BaseController
{
    /**
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct(
            model: TagModel::class,
            storeRequest: StoreTagRequest::class,
            updateRequest: UpdateTagRequest::class,
            resource: TagResource::class
        );
    }
}
