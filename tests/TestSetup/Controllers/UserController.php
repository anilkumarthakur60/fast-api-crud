<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Controllers;

use Anil\FastApiCrud\Controller\Controller;
use Anil\FastApiCrud\Tests\TestSetup\Models\UserModel;
use Anil\FastApiCrud\Tests\TestSetup\Requests\User\StoreUserFormRequest;
use Anil\FastApiCrud\Tests\TestSetup\Requests\User\UpdateUserFormRequest;
use Anil\FastApiCrud\Tests\TestSetup\Resources\UserResource;
use Exception;

class UserController extends Controller
{
    /**
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct(
            model: UserModel::class,
            storeRequest: StoreUserFormRequest::class,
            updateRequest: UpdateUserFormRequest::class,
            resource: UserResource::class
        );
    }
}
