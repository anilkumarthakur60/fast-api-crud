<?php

namespace Anil\FastApiCrud\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

trait HasApiResponse
{
    /**
     * Return a success response with given data and HTTP status code.
     *
     * @param  array<string, mixed>  $data  The data to include in the response.
     * @param  int  $code  The HTTP status code (default 200).
     */
    public function success(array $data = [], int $code = ResponseAlias::HTTP_OK): JsonResponse
    {
        return response()->json(['data' => $data], $code);
    }

    /**
     * Return an error response with a message, errors data, and HTTP status code.
     *
     * @param  string  $message  The error message.
     * @param  array<string, mixed>  $data  Additional error details.
     * @param  int  $status  The HTTP status code (default 400).
     */
    public function error(string $message = 'Something went wrong', array $data = [], int $status = ResponseAlias::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json(['errors' => $data, 'message' => $message], $status);
    }

    // Informational responses (1xx)

    /**
     * Return a 100 Continue response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function continue(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_CONTINUE);
    }

    /**
     * Return a 101 Switching Protocols response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function switchingProtocols(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_SWITCHING_PROTOCOLS);
    }

    /**
     * Return a 102 Processing response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function processing(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_PROCESSING);
    }

    /**
     * Return a 103 Early Hints response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function earlyHints(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_EARLY_HINTS);
    }

    // Successful responses (2xx)

    /**
     * Return a 200 OK response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function ok(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_OK);
    }

    /**
     * Return a 201 Created response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function created(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_CREATED);
    }

    /**
     * Return a 202 Accepted response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function accepted(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_ACCEPTED);
    }

    /**
     * Return a 203 Non-Authoritative Information response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function nonAuthoritativeInformation(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_NON_AUTHORITATIVE_INFORMATION);
    }

    /**
     * Return a 204 No Content response with no body.
     */
    public function noContent(): JsonResponse
    {
        return response()->json(null, ResponseAlias::HTTP_NO_CONTENT);
    }

    /**
     * Return a 205 Reset Content response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function resetContent(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_RESET_CONTENT);
    }

    /**
     * Return a 206 Partial Content response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function partialContent(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_PARTIAL_CONTENT);
    }

    /**
     * Return a 207 Multi-Status response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function multiStatus(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_MULTI_STATUS);
    }

    /**
     * Return a 208 Already Reported response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function alreadyReported(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_ALREADY_REPORTED);
    }

    /**
     * Return a 226 IM Used response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function imUsed(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_IM_USED);
    }

    // Redirection responses (3xx)

    /**
     * Return a 300 Multiple Choices response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function multipleChoices(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_MULTIPLE_CHOICES);
    }

    /**
     * Return a 301 Moved Permanently response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function movedPermanently(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * Return a 302 Found response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function found(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_FOUND);
    }

    /**
     * Return a 303 See Other response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function seeOther(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_SEE_OTHER);
    }

    /**
     * Return a 304 Not Modified response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function notModified(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_NOT_MODIFIED);
    }

    /**
     * Return a 305 Use Proxy response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function useProxy(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_USE_PROXY);
    }

    /**
     * Return a 307 Temporary Redirect response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function temporaryRedirect(array $data = []): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_TEMPORARY_REDIRECT);
    }

    /**
     * Return a 308 Permanent Redirect response with optional data.
     *
     * @param  array<string, mixed>  $data
     */
    public function permanentRedirect(array $data = ['message' => 'Permanent Redirect']): JsonResponse
    {
        return $this->success($data, ResponseAlias::HTTP_PERMANENTLY_REDIRECT);
    }

    // Client error responses (4xx)

    /**
     * Return a 400 Bad Request error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function badRequest(string $message = 'Bad Request', array $data = [], int $status = ResponseAlias::HTTP_BAD_REQUEST): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 401 Unauthorized error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function unauthorized(string $message = 'Unauthorized', array $data = [], int $status = ResponseAlias::HTTP_UNAUTHORIZED): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 402 Payment Required error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function paymentRequired(string $message = 'Payment Required', array $data = [], int $status = ResponseAlias::HTTP_PAYMENT_REQUIRED): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 403 Forbidden error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function forbidden(string $message = 'Forbidden', array $data = [], int $status = ResponseAlias::HTTP_FORBIDDEN): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 404 Not Found error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function notFound(string $message = 'Not Found', array $data = [], int $status = ResponseAlias::HTTP_NOT_FOUND): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 405 Method Not Allowed error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function methodNotAllowed(string $message = 'Method Not Allowed', array $data = [], int $status = ResponseAlias::HTTP_METHOD_NOT_ALLOWED): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 406 Not Acceptable error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function notAcceptable(string $message = 'Not Acceptable', array $data = [], int $status = ResponseAlias::HTTP_NOT_ACCEPTABLE): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 407 Proxy Authentication Required error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function proxyAuthenticationRequired(string $message = 'Proxy Authentication Required', array $data = [], int $status = ResponseAlias::HTTP_PROXY_AUTHENTICATION_REQUIRED): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 408 Request Timeout error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function requestTimeout(string $message = 'Request Timeout', array $data = [], int $status = ResponseAlias::HTTP_REQUEST_TIMEOUT): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 409 Conflict error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function conflict(string $message = 'Conflict', array $data = [], int $status = ResponseAlias::HTTP_CONFLICT): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 410 Gone error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function gone(string $message = 'Gone', array $data = [], int $status = ResponseAlias::HTTP_GONE): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 411 Length Required error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function lengthRequired(string $message = 'Length Required', array $data = [], int $status = ResponseAlias::HTTP_LENGTH_REQUIRED): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 412 Precondition Failed error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function preconditionFailed(string $message = 'Precondition Failed', array $data = [], int $status = ResponseAlias::HTTP_PRECONDITION_FAILED): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 413 Content Too Large error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function contentTooLarge(string $message = 'Content Too Large', array $data = [], int $status = ResponseAlias::HTTP_REQUEST_ENTITY_TOO_LARGE): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 414 URI Too Long error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function uriTooLong(string $message = 'URI Too Long', array $data = [], int $status = ResponseAlias::HTTP_REQUEST_URI_TOO_LONG): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 415 Unsupported Media Type error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function unsupportedMediaType(string $message = 'Unsupported Media Type', array $data = [], int $status = ResponseAlias::HTTP_UNSUPPORTED_MEDIA_TYPE): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 416 Range Not Satisfiable error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function rangeNotSatisfiable(string $message = 'Range Not Satisfiable', array $data = [], int $status = ResponseAlias::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 417 Expectation Failed error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function expectationFailed(string $message = 'Expectation Failed', array $data = [], int $status = ResponseAlias::HTTP_EXPECTATION_FAILED): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 418 I'm a teapot error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function imATeapot(string $message = 'I\'m a teapot', array $data = [], int $status = ResponseAlias::HTTP_I_AM_A_TEAPOT): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 421 Misdirected Request error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function misdirectedRequest(string $message = 'Misdirected Request', array $data = [], int $status = ResponseAlias::HTTP_MISDIRECTED_REQUEST): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 422 Unprocessable Content error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function unprocessableContent(string $message = 'Unprocessable Content', array $data = [], int $status = ResponseAlias::HTTP_UNPROCESSABLE_ENTITY): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 423 Locked error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function locked(string $message = 'Locked', array $data = [], int $status = ResponseAlias::HTTP_LOCKED): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 424 Failed Dependency error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function failedDependency(string $message = 'Failed Dependency', array $data = [], int $status = ResponseAlias::HTTP_FAILED_DEPENDENCY): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 425 Too Early error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function tooEarly(string $message = 'Too Early', array $data = [], int $status = ResponseAlias::HTTP_TOO_EARLY): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 426 Upgrade Required error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function upgradeRequired(string $message = 'Upgrade Required', array $data = [], int $status = ResponseAlias::HTTP_UPGRADE_REQUIRED): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 428 Precondition Required error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function preconditionRequired(string $message = 'Precondition Required', array $data = [], int $status = ResponseAlias::HTTP_PRECONDITION_REQUIRED): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 429 Too Many Requests error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function tooManyRequests(string $message = 'Too Many Requests', array $data = [], int $status = ResponseAlias::HTTP_TOO_MANY_REQUESTS): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 431 Request Header Fields Too Large error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function requestHeaderFieldsTooLarge(string $message = 'Request Header Fields Too Large', array $data = [], int $status = ResponseAlias::HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 451 Unavailable For Legal Reasons error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function unavailableForLegalReasons(string $message = 'Unavailable For Legal Reasons', array $data = [], int $status = ResponseAlias::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    // Server error responses (5xx)

    /**
     * Return a 500 Internal Server Error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function internalServerError(string $message = 'Internal Server Error', array $data = [], int $status = ResponseAlias::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 501 Not Implemented error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function notImplemented(string $message = 'Not Implemented', array $data = [], int $status = ResponseAlias::HTTP_NOT_IMPLEMENTED): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 502 Bad Gateway error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function badGateway(string $message = 'Bad Gateway', array $data = [], int $status = ResponseAlias::HTTP_BAD_GATEWAY): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 503 Service Unavailable error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function serviceUnavailable(string $message = 'Service Unavailable', array $data = [], int $status = ResponseAlias::HTTP_SERVICE_UNAVAILABLE): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 504 Gateway Timeout error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function gatewayTimeout(string $message = 'Gateway Timeout', array $data = [], int $status = ResponseAlias::HTTP_GATEWAY_TIMEOUT): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 505 HTTP Version Not Supported error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function httpVersionNotSupported(string $message = 'HTTP Version Not Supported', array $data = [], int $status = ResponseAlias::HTTP_VERSION_NOT_SUPPORTED): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 506 Variant Also Negotiates error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function variantAlsoNegotiates(string $message = 'Variant Also Negotiates', array $data = [], int $status = ResponseAlias::HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 507 Insufficient Storage error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function insufficientStorage(string $message = 'Insufficient Storage', array $data = [], int $status = ResponseAlias::HTTP_INSUFFICIENT_STORAGE): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 508 Loop Detected error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function loopDetected(string $message = 'Loop Detected', array $data = [], int $status = ResponseAlias::HTTP_LOOP_DETECTED): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 510 Not Extended error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function notExtended(string $message = 'Not Extended', array $data = [], int $status = ResponseAlias::HTTP_NOT_EXTENDED): JsonResponse
    {
        return $this->error($message, $data, $status);
    }

    /**
     * Return a 511 Network Authentication Required error response.
     *
     * @param  array<string, mixed>  $data
     */
    public function networkAuthenticationRequired(string $message = 'Network Authentication Required', array $data = [], int $status = ResponseAlias::HTTP_NETWORK_AUTHENTICATION_REQUIRED): JsonResponse
    {
        return $this->error($message, $data, $status);
    }
}
