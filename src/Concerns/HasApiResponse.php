<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Concerns;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait HasApiResponse
{
    /**
     * Return a success response with given data and HTTP status code.
     *
     * @param  array<string, mixed>  $data
     */
    public function success(array $data = [], int $code = Response::HTTP_OK): JsonResponse
    {
        $cfg = config('fast-api.response.success_key', 'data');
        $key = is_string($cfg) ? $cfg : 'data';

        return response()->json([$key => $data], $code);
    }

    /**
     * Return an error response with a message, errors data, and HTTP status code.
     *
     * @param  array<string, mixed>  $data
     */
    public function error(string $message = 'Something went wrong', array $data = [], int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        $errCfg = config('fast-api.response.error_key', 'errors');
        $msgCfg = config('fast-api.response.message_key', 'message');
        $errorKey = is_string($errCfg) ? $errCfg : 'errors';
        $messageKey = is_string($msgCfg) ? $msgCfg : 'message';

        return response()->json([$errorKey => $data, $messageKey => $message], $status);
    }

    // -------------------------------------------------------------------------
    // Informational responses (1xx)
    // -------------------------------------------------------------------------

    /** @param  array<string, mixed>  $data */
    public function continue(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_CONTINUE);
    }

    /** @param  array<string, mixed>  $data */
    public function switchingProtocols(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_SWITCHING_PROTOCOLS);
    }

    /** @param  array<string, mixed>  $data */
    public function processing(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_PROCESSING);
    }

    /** @param  array<string, mixed>  $data */
    public function earlyHints(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_EARLY_HINTS);
    }

    // -------------------------------------------------------------------------
    // Successful responses (2xx)
    // -------------------------------------------------------------------------

    /** @param  array<string, mixed>  $data */
    public function ok(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_OK);
    }

    /** @param  array<string, mixed>  $data */
    public function created(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_CREATED);
    }

    /** @param  array<string, mixed>  $data */
    public function accepted(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_ACCEPTED);
    }

    /** @param  array<string, mixed>  $data */
    public function nonAuthoritativeInformation(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_NON_AUTHORITATIVE_INFORMATION);
    }

    public function noContent(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /** @param  array<string, mixed>  $data */
    public function resetContent(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_RESET_CONTENT);
    }

    /** @param  array<string, mixed>  $data */
    public function partialContent(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_PARTIAL_CONTENT);
    }

    /** @param  array<string, mixed>  $data */
    public function multiStatus(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_MULTI_STATUS);
    }

    /** @param  array<string, mixed>  $data */
    public function alreadyReported(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_ALREADY_REPORTED);
    }

    /** @param  array<string, mixed>  $data */
    public function imUsed(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_IM_USED);
    }

    // -------------------------------------------------------------------------
    // Redirection responses (3xx)
    // -------------------------------------------------------------------------

    /** @param  array<string, mixed>  $data */
    public function multipleChoices(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_MULTIPLE_CHOICES);
    }

    /** @param  array<string, mixed>  $data */
    public function movedPermanently(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_MOVED_PERMANENTLY);
    }

    /** @param  array<string, mixed>  $data */
    public function found(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_FOUND);
    }

    /** @param  array<string, mixed>  $data */
    public function seeOther(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_SEE_OTHER);
    }

    /** @param  array<string, mixed>  $data */
    public function notModified(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_NOT_MODIFIED);
    }

    /** @param  array<string, mixed>  $data */
    public function useProxy(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_USE_PROXY);
    }

    /** @param  array<string, mixed>  $data */
    public function temporaryRedirect(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_TEMPORARY_REDIRECT);
    }

    /** @param  array<string, mixed>  $data */
    public function permanentRedirect(array $data = []): JsonResponse
    {
        return $this->success($data, Response::HTTP_PERMANENTLY_REDIRECT);
    }

    // -------------------------------------------------------------------------
    // Client error responses (4xx)
    // -------------------------------------------------------------------------

    /** @param  array<string, mixed>  $data */
    public function badRequest(string $message = 'Bad Request', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_BAD_REQUEST);
    }

    /** @param  array<string, mixed>  $data */
    public function unauthorized(string $message = 'Unauthorized', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_UNAUTHORIZED);
    }

    /** @param  array<string, mixed>  $data */
    public function paymentRequired(string $message = 'Payment Required', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_PAYMENT_REQUIRED);
    }

    /** @param  array<string, mixed>  $data */
    public function forbidden(string $message = 'Forbidden', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_FORBIDDEN);
    }

    /** @param  array<string, mixed>  $data */
    public function notFound(string $message = 'Not Found', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_NOT_FOUND);
    }

    /** @param  array<string, mixed>  $data */
    public function methodNotAllowed(string $message = 'Method Not Allowed', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /** @param  array<string, mixed>  $data */
    public function notAcceptable(string $message = 'Not Acceptable', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_NOT_ACCEPTABLE);
    }

    /** @param  array<string, mixed>  $data */
    public function proxyAuthenticationRequired(string $message = 'Proxy Authentication Required', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_PROXY_AUTHENTICATION_REQUIRED);
    }

    /** @param  array<string, mixed>  $data */
    public function requestTimeout(string $message = 'Request Timeout', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_REQUEST_TIMEOUT);
    }

    /** @param  array<string, mixed>  $data */
    public function conflict(string $message = 'Conflict', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_CONFLICT);
    }

    /** @param  array<string, mixed>  $data */
    public function gone(string $message = 'Gone', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_GONE);
    }

    /** @param  array<string, mixed>  $data */
    public function lengthRequired(string $message = 'Length Required', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_LENGTH_REQUIRED);
    }

    /** @param  array<string, mixed>  $data */
    public function preconditionFailed(string $message = 'Precondition Failed', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_PRECONDITION_FAILED);
    }

    /** @param  array<string, mixed>  $data */
    public function contentTooLarge(string $message = 'Content Too Large', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
    }

    /** @param  array<string, mixed>  $data */
    public function uriTooLong(string $message = 'URI Too Long', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_REQUEST_URI_TOO_LONG);
    }

    /** @param  array<string, mixed>  $data */
    public function unsupportedMediaType(string $message = 'Unsupported Media Type', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    }

    /** @param  array<string, mixed>  $data */
    public function rangeNotSatisfiable(string $message = 'Range Not Satisfiable', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE);
    }

    /** @param  array<string, mixed>  $data */
    public function expectationFailed(string $message = 'Expectation Failed', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_EXPECTATION_FAILED);
    }

    /** @param  array<string, mixed>  $data */
    public function imATeapot(string $message = 'I\'m a teapot', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_I_AM_A_TEAPOT);
    }

    /** @param  array<string, mixed>  $data */
    public function misdirectedRequest(string $message = 'Misdirected Request', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_MISDIRECTED_REQUEST);
    }

    /** @param  array<string, mixed>  $data */
    public function unprocessableContent(string $message = 'Unprocessable Content', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @param  array<string, mixed>  $data */
    public function locked(string $message = 'Locked', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_LOCKED);
    }

    /** @param  array<string, mixed>  $data */
    public function failedDependency(string $message = 'Failed Dependency', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_FAILED_DEPENDENCY);
    }

    /** @param  array<string, mixed>  $data */
    public function tooEarly(string $message = 'Too Early', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_TOO_EARLY);
    }

    /** @param  array<string, mixed>  $data */
    public function upgradeRequired(string $message = 'Upgrade Required', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_UPGRADE_REQUIRED);
    }

    /** @param  array<string, mixed>  $data */
    public function preconditionRequired(string $message = 'Precondition Required', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_PRECONDITION_REQUIRED);
    }

    /** @param  array<string, mixed>  $data */
    public function tooManyRequests(string $message = 'Too Many Requests', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_TOO_MANY_REQUESTS);
    }

    /** @param  array<string, mixed>  $data */
    public function requestHeaderFieldsTooLarge(string $message = 'Request Header Fields Too Large', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE);
    }

    /** @param  array<string, mixed>  $data */
    public function unavailableForLegalReasons(string $message = 'Unavailable For Legal Reasons', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS);
    }

    // -------------------------------------------------------------------------
    // Server error responses (5xx)
    // -------------------------------------------------------------------------

    /** @param  array<string, mixed>  $data */
    public function internalServerError(string $message = 'Internal Server Error', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /** @param  array<string, mixed>  $data */
    public function notImplemented(string $message = 'Not Implemented', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_NOT_IMPLEMENTED);
    }

    /** @param  array<string, mixed>  $data */
    public function badGateway(string $message = 'Bad Gateway', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_BAD_GATEWAY);
    }

    /** @param  array<string, mixed>  $data */
    public function serviceUnavailable(string $message = 'Service Unavailable', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_SERVICE_UNAVAILABLE);
    }

    /** @param  array<string, mixed>  $data */
    public function gatewayTimeout(string $message = 'Gateway Timeout', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_GATEWAY_TIMEOUT);
    }

    /** @param  array<string, mixed>  $data */
    public function httpVersionNotSupported(string $message = 'HTTP Version Not Supported', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_VERSION_NOT_SUPPORTED);
    }

    /** @param  array<string, mixed>  $data */
    public function variantAlsoNegotiates(string $message = 'Variant Also Negotiates', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL);
    }

    /** @param  array<string, mixed>  $data */
    public function insufficientStorage(string $message = 'Insufficient Storage', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_INSUFFICIENT_STORAGE);
    }

    /** @param  array<string, mixed>  $data */
    public function loopDetected(string $message = 'Loop Detected', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_LOOP_DETECTED);
    }

    /** @param  array<string, mixed>  $data */
    public function notExtended(string $message = 'Not Extended', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_NOT_EXTENDED);
    }

    /** @param  array<string, mixed>  $data */
    public function networkAuthenticationRequired(string $message = 'Network Authentication Required', array $data = []): JsonResponse
    {
        return $this->error($message, $data, Response::HTTP_NETWORK_AUTHENTICATION_REQUIRED);
    }
}
