# `HasApiResponse` — JSON responders (complete)

`use Anil\FastApiCrud\Concerns\HasApiResponse;` — used by `BaseController`, usable in any class
that can call `response()`.

```php
success(array $data = [], int $code = 200): JsonResponse   // {"data": $data}
error(string $message = 'Something went wrong', array $data = [], int $status = 400): JsonResponse
                                                          // {"errors": $data, "message": $message}
```

Envelope keys from config: `fast-api.response.success_key` (`data`), `error_key` (`errors`),
`message_key` (`message`).

Success shortcuts — signature `method(array $data = []): JsonResponse` except `noContent()` (no args, `null` body):

| 1xx | 2xx | 3xx |
|---|---|---|
| `continue` 100 | `ok` 200 | `multipleChoices` 300 |
| `switchingProtocols` 101 | `created` 201 | `movedPermanently` 301 |
| `processing` 102 | `accepted` 202 | `found` 302 |
| `earlyHints` 103 | `nonAuthoritativeInformation` 203 | `seeOther` 303 |
| | `noContent` 204 | `notModified` 304 |
| | `resetContent` 205 | `useProxy` 305 |
| | `partialContent` 206 | `temporaryRedirect` 307 |
| | `multiStatus` 207 | `permanentRedirect` 308 |
| | `alreadyReported` 208 | |
| | `imUsed` 226 | |

Error shortcuts — signature `method(string $message = '<default>', array $data = []): JsonResponse`:

| 4xx | | 5xx |
|---|---|---|
| `badRequest` 400 | `expectationFailed` 417 | `internalServerError` 500 |
| `unauthorized` 401 | `imATeapot` 418 | `notImplemented` 501 |
| `paymentRequired` 402 | `misdirectedRequest` 421 | `badGateway` 502 |
| `forbidden` 403 | `unprocessableContent` 422 | `serviceUnavailable` 503 |
| `notFound` 404 | `locked` 423 | `gatewayTimeout` 504 |
| `methodNotAllowed` 405 | `failedDependency` 424 | `httpVersionNotSupported` 505 |
| `notAcceptable` 406 | `tooEarly` 425 | `variantAlsoNegotiates` 506 |
| `proxyAuthenticationRequired` 407 | `upgradeRequired` 426 | `insufficientStorage` 507 |
| `requestTimeout` 408 | `preconditionRequired` 428 | `loopDetected` 508 |
| `conflict` 409 | `tooManyRequests` 429 | `notExtended` 510 |
| `gone` 410 | `requestHeaderFieldsTooLarge` 431 | `networkAuthenticationRequired` 511 |
| `lengthRequired` 411 | `unavailableForLegalReasons` 451 | |
| `preconditionFailed` 412 | | |
| `contentTooLarge` 413 | | |
| `uriTooLong` 414 | | |
| `unsupportedMediaType` 415 | | |
| `rangeNotSatisfiable` 416 | | |

Default messages are the standard reason phrases ("Not Found", "Unprocessable Content", …).

## `ApiException` (`Anil\FastApiCrud\Exceptions\ApiException`)

`throw new ApiException('Resource not found', 404);` — `render()` returns
`{"error":{"message":...}}` with the exception code as HTTP status (codes outside 100–599 → 500);
adds `file` and `line` when `app.debug` is true. Not used internally by the controllers.
