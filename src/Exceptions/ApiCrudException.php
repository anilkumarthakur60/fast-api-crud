<?php

namespace Anil\FastApiCrud\Exceptions;

use Exception;
use Throwable;

class ApiCrudException extends Exception
{

    public function render($request)
    {
        return response()->json([
            'error' => [
                'message' => $this->getMessage(),
                'file'=> $this->getFile(),
                'line'=> $this->getLine(),

            ]
        ], $this->getCode());
    }



}
