# ApiResponder Trait

`Anil\FastApiCrud\Concerns\ApiResponder`

Provides JSON response helpers for every HTTP status code. Used by `BaseController`. Can be used standalone in any controller.

## Usage

```php
use Anil\FastApiCrud\Concerns\ApiResponder;

class MyController extends Controller
{
    use ApiResponder;

    public function example()
    {
        return $this->ok(['message' => 'Hello']);
    }
}
```

## Core Methods

### success

```php
public function success(array $data = [], int $code = 200): JsonResponse
```

Returns `{"{success_key}": data}` where `{success_key}` is from `fast-api.response.success_key` config (default: `data`).

### error

```php
public function error(string $message = 'Something went wrong', array $data = [], int $status = 400): JsonResponse
```

Returns `{"{error_key}": data, "{message_key}": message}` where keys are from config.

## 1xx Informational

| Method | Status | Signature |
|--------|--------|-----------|
| `continue` | 100 | `(array $data = []): JsonResponse` |
| `switchingProtocols` | 101 | `(array $data = []): JsonResponse` |
| `processing` | 102 | `(array $data = []): JsonResponse` |
| `earlyHints` | 103 | `(array $data = []): JsonResponse` |

## 2xx Success

| Method | Status | Signature |
|--------|--------|-----------|
| `ok` | 200 | `(array $data = []): JsonResponse` |
| `created` | 201 | `(array $data = []): JsonResponse` |
| `accepted` | 202 | `(array $data = []): JsonResponse` |
| `nonAuthoritativeInformation` | 203 | `(array $data = []): JsonResponse` |
| `noContent` | 204 | `(): JsonResponse` — returns `null` body |
| `resetContent` | 205 | `(array $data = []): JsonResponse` |
| `partialContent` | 206 | `(array $data = []): JsonResponse` |
| `multiStatus` | 207 | `(array $data = []): JsonResponse` |
| `alreadyReported` | 208 | `(array $data = []): JsonResponse` |
| `imUsed` | 226 | `(array $data = []): JsonResponse` |

## 3xx Redirection

| Method | Status | Signature |
|--------|--------|-----------|
| `multipleChoices` | 300 | `(array $data = []): JsonResponse` |
| `movedPermanently` | 301 | `(array $data = []): JsonResponse` |
| `found` | 302 | `(array $data = []): JsonResponse` |
| `seeOther` | 303 | `(array $data = []): JsonResponse` |
| `notModified` | 304 | `(array $data = []): JsonResponse` |
| `useProxy` | 305 | `(array $data = []): JsonResponse` |
| `temporaryRedirect` | 307 | `(array $data = []): JsonResponse` |
| `permanentRedirect` | 308 | `(array $data = []): JsonResponse` |

## 4xx Client Error

| Method | Status | Default Message |
|--------|--------|----------------|
| `badRequest` | 400 | Bad Request |
| `unauthorized` | 401 | Unauthorized |
| `paymentRequired` | 402 | Payment Required |
| `forbidden` | 403 | Forbidden |
| `notFound` | 404 | Not Found |
| `methodNotAllowed` | 405 | Method Not Allowed |
| `notAcceptable` | 406 | Not Acceptable |
| `proxyAuthenticationRequired` | 407 | Proxy Authentication Required |
| `requestTimeout` | 408 | Request Timeout |
| `conflict` | 409 | Conflict |
| `gone` | 410 | Gone |
| `lengthRequired` | 411 | Length Required |
| `preconditionFailed` | 412 | Precondition Failed |
| `contentTooLarge` | 413 | Content Too Large |
| `uriTooLong` | 414 | URI Too Long |
| `unsupportedMediaType` | 415 | Unsupported Media Type |
| `rangeNotSatisfiable` | 416 | Range Not Satisfiable |
| `expectationFailed` | 417 | Expectation Failed |
| `imATeapot` | 418 | I'm a teapot |
| `misdirectedRequest` | 421 | Misdirected Request |
| `unprocessableContent` | 422 | Unprocessable Content |
| `locked` | 423 | Locked |
| `failedDependency` | 424 | Failed Dependency |
| `tooEarly` | 425 | Too Early |
| `upgradeRequired` | 426 | Upgrade Required |
| `preconditionRequired` | 428 | Precondition Required |
| `tooManyRequests` | 429 | Too Many Requests |
| `requestHeaderFieldsTooLarge` | 431 | Request Header Fields Too Large |
| `unavailableForLegalReasons` | 451 | Unavailable For Legal Reasons |

All 4xx methods: `(string $message = '...', array $data = []): JsonResponse`

## 5xx Server Error

| Method | Status | Default Message |
|--------|--------|----------------|
| `internalServerError` | 500 | Internal Server Error |
| `notImplemented` | 501 | Not Implemented |
| `badGateway` | 502 | Bad Gateway |
| `serviceUnavailable` | 503 | Service Unavailable |
| `gatewayTimeout` | 504 | Gateway Timeout |
| `httpVersionNotSupported` | 505 | HTTP Version Not Supported |
| `variantAlsoNegotiates` | 506 | Variant Also Negotiates |
| `insufficientStorage` | 507 | Insufficient Storage |
| `loopDetected` | 508 | Loop Detected |
| `notExtended` | 510 | Not Extended |
| `networkAuthenticationRequired` | 511 | Network Authentication Required |

All 5xx methods: `(string $message = '...', array $data = []): JsonResponse`
