<?php

use Anil\FastApiCrud\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

describe('HasApiResponse Trait', function () {
    // Create an anonymous class to use the trait
    $trait = new class
    {
        use HasApiResponse;
    };

    describe('success responses', function () use ($trait) {
        it('returns a success response with default values', function () use ($trait) {
            $response = $trait->success();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_OK)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => []]);
        });

        it('returns a success response with custom data and code', function () use ($trait) {
            $data = ['key' => 'value'];
            $code = ResponseAlias::HTTP_CREATED;
            $response = $trait->success($data, $code);

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe($code)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => $data]);
        });
    });

    describe('error responses', function () use ($trait) {
        it('returns an error response with default values', function () use ($trait) {
            $response = $trait->error();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_BAD_REQUEST)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Something went wrong',
                ]);
        });

        it('returns an error response with custom values', function () use ($trait) {
            $message = 'Custom error';
            $data = ['field' => 'invalid'];
            $status = ResponseAlias::HTTP_UNAUTHORIZED;
            $response = $trait->error($message, $data, $status);

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe($status)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => $data,
                    'message' => $message,
                ]);
        });
    });

    describe('informational responses (1xx)', function () use ($trait) {
        it('returns a 100 Continue response', function () use ($trait) {
            $data = ['info' => 'continue'];
            $response = $trait->continue($data);

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_CONTINUE)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => $data]);
        });

        it('returns a 101 Switching Protocols response', function () use ($trait) {
            $response = $trait->switchingProtocols();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_SWITCHING_PROTOCOLS)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => []]);
        });

        it('returns a 102 Processing response', function () use ($trait) {
            $response = $trait->processing();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_PROCESSING)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => []]);
        });

        it('returns a 103 Early Hints response', function () use ($trait) {
            $response = $trait->earlyHints();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_EARLY_HINTS)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => []]);
        });
    });

    describe('successful responses (2xx)', function () use ($trait) {
        it('returns a 200 OK response', function () use ($trait) {
            $data = ['result' => 'success'];
            $response = $trait->ok($data);

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_OK)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => $data]);
        });

        it('returns a 201 Created response', function () use ($trait) {
            $response = $trait->created();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_CREATED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => []]);
        });

        it('returns a 202 Accepted response', function () use ($trait) {
            $response = $trait->accepted();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_ACCEPTED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => []]);
        });

        it('returns a 203 Non-Authoritative Information response', function () use ($trait) {
            $response = $trait->nonAuthoritativeInformation();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_NON_AUTHORITATIVE_INFORMATION)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => []]);
        });

        it('returns a 204 No Content response', function () use ($trait) {
            $response = $trait->noContent();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_NO_CONTENT)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([]);
        });

        it('returns a 205 Reset Content response', function () use ($trait) {
            $response = $trait->resetContent();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_RESET_CONTENT)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => []]);
        });

        it('returns a 206 Partial Content response', function () use ($trait) {
            $response = $trait->partialContent();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_PARTIAL_CONTENT)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => []]);
        });

        it('returns a 207 Multi-Status response', function () use ($trait) {
            $response = $trait->multiStatus();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_MULTI_STATUS)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => []]);
        });

        it('returns a 208 Already Reported response', function () use ($trait) {
            $response = $trait->alreadyReported();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_ALREADY_REPORTED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => []]);
        });

        it('returns a 226 IM Used response', function () use ($trait) {
            $response = $trait->imUsed();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_IM_USED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => []]);
        });
    });

    describe('redirection responses (3xx)', function () use ($trait) {
        it('returns a 300 Multiple Choices response', function () use ($trait) {
            $response = $trait->multipleChoices();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_MULTIPLE_CHOICES)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => []]);
        });

        it('returns a 301 Moved Permanently response', function () use ($trait) {
            $response = $trait->movedPermanently();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_MOVED_PERMANENTLY)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => []]);
        });

        it('returns a 302 Found response', function () use ($trait) {
            $response = $trait->found();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_FOUND)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => []]);
        });

        it('returns a 303 See Other response', function () use ($trait) {
            $response = $trait->seeOther();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_SEE_OTHER)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => []]);
        });

        it('returns a 304 Not Modified response', function () use ($trait) {
            $response = $trait->notModified();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_NOT_MODIFIED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => []]);
        });

        it('returns a 305 Use Proxy response', function () use ($trait) {
            $response = $trait->useProxy();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_USE_PROXY)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => []]);
        });

        it('returns a 307 Temporary Redirect response', function () use ($trait) {
            $response = $trait->temporaryRedirect();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_TEMPORARY_REDIRECT)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual(['data' => []]);
        });

        it('returns a 308 Permanent Redirect response', function () use ($trait) {
            $response = $trait->permanentRedirect();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_PERMANENTLY_REDIRECT)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'data' => ['message' => 'Permanent Redirect'],
                ]);
        });
    });

    describe('client error responses (4xx)', function () use ($trait) {
        it('returns a 400 Bad Request response', function () use ($trait) {
            $message = 'Invalid input';
            $data = ['field' => 'required'];
            $response = $trait->badRequest($message, $data);

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_BAD_REQUEST)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => $data,
                    'message' => $message,
                ]);
        });

        it('returns a 401 Unauthorized response', function () use ($trait) {
            $response = $trait->unauthorized();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_UNAUTHORIZED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Unauthorized',
                ]);
        });

        it('returns a 402 Payment Required response', function () use ($trait) {
            $response = $trait->paymentRequired();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_PAYMENT_REQUIRED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Payment Required',
                ]);
        });

        it('returns a 403 Forbidden response', function () use ($trait) {
            $response = $trait->forbidden();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_FORBIDDEN)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Forbidden',
                ]);
        });

        it('returns a 404 Not Found response', function () use ($trait) {
            $response = $trait->notFound();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_NOT_FOUND)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Not Found',
                ]);
        });

        it('returns a 405 Method Not Allowed response', function () use ($trait) {
            $response = $trait->methodNotAllowed();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_METHOD_NOT_ALLOWED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Method Not Allowed',
                ]);
        });

        it('returns a 406 Not Acceptable response', function () use ($trait) {
            $response = $trait->notAcceptable();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_NOT_ACCEPTABLE)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Not Acceptable',
                ]);
        });

        it('returns a 407 Proxy Authentication Required response', function () use ($trait) {
            $response = $trait->proxyAuthenticationRequired();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_PROXY_AUTHENTICATION_REQUIRED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Proxy Authentication Required',
                ]);
        });

        it('returns a 408 Request Timeout response', function () use ($trait) {
            $response = $trait->requestTimeout();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_REQUEST_TIMEOUT)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Request Timeout',
                ]);
        });

        it('returns a 409 Conflict response', function () use ($trait) {
            $response = $trait->conflict();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_CONFLICT)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Conflict',
                ]);
        });

        it('returns a 410 Gone response', function () use ($trait) {
            $response = $trait->gone();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_GONE)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Gone',
                ]);
        });

        it('returns a 411 Length Required response', function () use ($trait) {
            $response = $trait->lengthRequired();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_LENGTH_REQUIRED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Length Required',
                ]);
        });

        it('returns a 412 Precondition Failed response', function () use ($trait) {
            $response = $trait->preconditionFailed();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_PRECONDITION_FAILED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Precondition Failed',
                ]);
        });

        it('returns a 413 Content Too Large response', function () use ($trait) {
            $response = $trait->contentTooLarge();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_REQUEST_ENTITY_TOO_LARGE)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Content Too Large',
                ]);
        });

        it('returns a 414 URI Too Long response', function () use ($trait) {
            $response = $trait->uriTooLong();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_REQUEST_URI_TOO_LONG)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'URI Too Long',
                ]);
        });

        it('returns a 415 Unsupported Media Type response', function () use ($trait) {
            $response = $trait->unsupportedMediaType();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_UNSUPPORTED_MEDIA_TYPE)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Unsupported Media Type',
                ]);
        });

        it('returns a 416 Range Not Satisfiable response', function () use ($trait) {
            $response = $trait->rangeNotSatisfiable();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Range Not Satisfiable',
                ]);
        });

        it('returns a 417 Expectation Failed response', function () use ($trait) {
            $response = $trait->expectationFailed();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_EXPECTATION_FAILED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Expectation Failed',
                ]);
        });

        it('returns a 418 I\'m a Teapot response', function () use ($trait) {
            $response = $trait->imATeapot();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_I_AM_A_TEAPOT)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'I\'m a teapot',
                ]);
        });

        it('returns a 421 Misdirected Request response', function () use ($trait) {
            $response = $trait->misdirectedRequest();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_MISDIRECTED_REQUEST)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Misdirected Request',
                ]);
        });

        it('returns a 422 Unprocessable Content response', function () use ($trait) {
            $response = $trait->unprocessableContent();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_UNPROCESSABLE_ENTITY)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Unprocessable Content',
                ]);
        });

        it('returns a 423 Locked response', function () use ($trait) {
            $response = $trait->locked();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_LOCKED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Locked',
                ]);
        });

        it('returns a 424 Failed Dependency response', function () use ($trait) {
            $response = $trait->failedDependency();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_FAILED_DEPENDENCY)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Failed Dependency',
                ]);
        });

        it('returns a 425 Too Early response', function () use ($trait) {
            $response = $trait->tooEarly();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_TOO_EARLY)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Too Early',
                ]);
        });

        it('returns a 426 Upgrade Required response', function () use ($trait) {
            $response = $trait->upgradeRequired();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_UPGRADE_REQUIRED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Upgrade Required',
                ]);
        });

        it('returns a 428 Precondition Required response', function () use ($trait) {
            $response = $trait->preconditionRequired();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_PRECONDITION_REQUIRED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Precondition Required',
                ]);
        });

        it('returns a 429 Too Many Requests response', function () use ($trait) {
            $response = $trait->tooManyRequests();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_TOO_MANY_REQUESTS)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Too Many Requests',
                ]);
        });

        it('returns a 431 Request Header Fields Too Large response', function () use ($trait) {
            $response = $trait->requestHeaderFieldsTooLarge();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Request Header Fields Too Large',
                ]);
        });

        it('returns a 451 Unavailable For Legal Reasons response', function () use ($trait) {
            $response = $trait->unavailableForLegalReasons();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Unavailable For Legal Reasons',
                ]);
        });
    });

    describe('server error responses (5xx)', function () use ($trait) {
        it('returns a 500 Internal Server Error response', function () use ($trait) {
            $response = $trait->internalServerError();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Internal Server Error',
                ]);
        });

        it('returns a 501 Not Implemented response', function () use ($trait) {
            $response = $trait->notImplemented();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_NOT_IMPLEMENTED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Not Implemented',
                ]);
        });

        it('returns a 502 Bad Gateway response', function () use ($trait) {
            $response = $trait->badGateway();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_BAD_GATEWAY)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Bad Gateway',
                ]);
        });

        it('returns a 503 Service Unavailable response', function () use ($trait) {
            $response = $trait->serviceUnavailable();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_SERVICE_UNAVAILABLE)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Service Unavailable',
                ]);
        });

        it('returns a 504 Gateway Timeout response', function () use ($trait) {
            $response = $trait->gatewayTimeout();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_GATEWAY_TIMEOUT)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Gateway Timeout',
                ]);
        });

        it('returns a 505 HTTP Version Not Supported response', function () use ($trait) {
            $response = $trait->httpVersionNotSupported();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_VERSION_NOT_SUPPORTED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'HTTP Version Not Supported',
                ]);
        });

        it('returns a 506 Variant Also Negotiates response', function () use ($trait) {
            $response = $trait->variantAlsoNegotiates();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Variant Also Negotiates',
                ]);
        });

        it('returns a 507 Insufficient Storage response', function () use ($trait) {
            $response = $trait->insufficientStorage();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_INSUFFICIENT_STORAGE)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Insufficient Storage',
                ]);
        });

        it('returns a 508 Loop Detected response', function () use ($trait) {
            $response = $trait->loopDetected();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_LOOP_DETECTED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Loop Detected',
                ]);
        });

        it('returns a 510 Not Extended response', function () use ($trait) {
            $response = $trait->notExtended();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_NOT_EXTENDED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Not Extended',
                ]);
        });

        it('returns a 511 Network Authentication Required response', function () use ($trait) {
            $response = $trait->networkAuthenticationRequired();

            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(ResponseAlias::HTTP_NETWORK_AUTHENTICATION_REQUIRED)
                ->and($response->getContent())->toBeJson()
                ->and(json_decode($response->getContent(), true))->toEqual([
                    'errors' => [],
                    'message' => 'Network Authentication Required',
                ]);
        });
    });
});
