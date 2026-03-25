<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiException extends Exception
{
    public function render(): JsonResponse
    {
        $statusCode = $this->getCode();

        if ($statusCode < 100 || $statusCode >= 600) {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        $payload = [
            'error' => [
                'message' => $this->getMessage(),
            ],
        ];

        if (config('app.debug', false)) {
            $payload['error']['file'] = $this->getFile();
            $payload['error']['line'] = $this->getLine();
        }

        return response()->json($payload, $statusCode);
    }
}
