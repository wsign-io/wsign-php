# wsign PHP Library

> مكتبة PHP الرسمية لواجهة الربط البرمجية في منصة وثائق للتوقيع الإلكتروني. تتيح لتطبيقك إنشاء طلبات التوقيع
> وإرسالها ومتابعة حالتها، واستعمال القوالب ومسارات العمل، وتضمين التوقيع في موقعك، وتنزيل الوثيقة النهائية ومحضر
> الأدلّة، والتحقّق من توقيع إشعارات الأحداث (Webhooks). وتعمل المكتبة في وضع الاختبار مجانًا دون أن تُرسَل أيّ رسالة
> إلى طرف آخر، ثم تنتقل إلى الوضع الحيّ بتغيير المفتاح وحده.

The official PHP library for the [wsign](https://developers.wsign.io) e-signature API. Create and send
signature requests, use templates and workflows, embed signing in your site, download the signed PDF and the
sealed evidence record, and verify webhook signatures.

- Zero required dependencies beyond `ext-curl` and `ext-json` (any PSR-18 client can be plugged in).
- Typed exceptions, automatic retries with backoff, automatic idempotency keys.
- Immutable, typed response objects with auto-pagination.
- PHP 8.1, 8.2, 8.3 and 8.4.

## Contents

- [Installation](#installation)
- [Quick start](#quick-start)
- [Authentication and modes](#authentication-and-modes)
- [Configuration](#configuration)
- [Resources](#resources)
- [Response objects](#response-objects)
- [Pagination](#pagination)
- [Error handling](#error-handling)
- [Retries and idempotency](#retries-and-idempotency)
- [Webhooks](#webhooks)
- [Using your own HTTP client](#using-your-own-http-client)
- [Versioning](#versioning)
- [Support and security](#support-and-security)

## Installation

```bash
composer require wsign/wsign-php
```

Requirements: PHP 8.1 or later with the `curl` and `json` extensions.

## Quick start

```php
require 'vendor/autoload.php';

$wsign = new \Wsign\Client(getenv('WSIGN_API_KEY'));
$request = $wsign->signatureRequests->create([
    'source'  => ['type' => 'template', 'template_id' => 'tpl_123'],
    'signers' => [['name' => 'Noha Hassan', 'email' => 'noha@example.com']],
]);

echo $request->id . ' ' . $request->status; // sr_3n8Kd2Qa1V... sent
```

## Authentication and modes

Create API keys in the developer portal at <https://developers.wsign.io>. Every request is authenticated
with `Authorization: Bearer <key>`; the library adds the header for you. Keep secret keys on your server,
for example in an environment variable.

| Key prefix | Kind | Mode |
|---|---|---|
| `sk_test_` | secret | **test**: free, nothing is charged, no message reaches anyone but you, no legal effect |
| `sk_` | secret | **live**: real sending, billed to your plan and wallet |
| `pk_test_` / `pk_` | publishable | for the browser console only; limited routes and origins |

The mode comes from the key, never from a parameter:

```php
$wsign->isTestMode();       // true for sk_test_ / pk_test_
$wsign->isLiveMode();
$wsign->isPublishableKey(); // true for pk_
```

Test mode rules worth knowing: signers must use your own email address (or a team member's), `cc` is ignored,
WhatsApp is simulated, and there are daily limits (20 requests, 50 emails, 5 signers per request). Live
sending requires a paid plan (`402 plan_required` / `plan_expired`) and a verified company (`403 kyb_required`).

## Configuration

```php
$wsign = new \Wsign\Client('sk_test_...', [
    'base_url'        => 'https://wsign.io/api/v1', // default
    'timeout'         => 60,        // seconds per attempt
    'connect_timeout' => 10,
    'max_retries'     => 2,         // automatic retries of transient failures
    'app_info'        => ['name' => 'AcmeCRM', 'version' => '4.2.0', 'url' => 'https://acme.example'],
    'language'        => 'en',      // error messages in English; default: Arabic
]);

// or a single array
$wsign = new \Wsign\Client(['api_key' => getenv('WSIGN_API_KEY'), 'max_retries' => 3]);
```

| Option | Default | Notes |
|---|---|---|
| `api_key` | required | `sk_…`, `sk_test_…`, `pk_…`, `pk_test_…` |
| `base_url` | `https://wsign.io/api/v1` | |
| `timeout` | `60` | total seconds per attempt |
| `connect_timeout` | `10` | seconds |
| `max_retries` | `2` | `0` disables retries |
| `api_version` | `2026-07-01` | sent as `Wsign-Version` |
| `app_info` | none | `name`, `version`, `url`, appended to the User-Agent |
| `language` | none (Arabic) | language of error messages, sent as `Accept-Language`: `en` or `ar`; error codes never change |
| `http_client` | `CurlClient` | any `Wsign\HttpClient\HttpClientInterface` |
| `retry_policy` | `new RetryPolicy()` | backoff tuning |

Every method also accepts per-call options as its last argument:

```php
$wsign->signatureRequests->create($params, [
    'idempotency_key' => 'order-1024',   // POST only
    'timeout'         => 20,
    'max_retries'     => 0,
    'headers'         => ['X-Correlation-Id' => 'abc'],
    'api_key'         => 'sk_test_other', // use another key for this call
]);
```

## Resources

| Property | Methods |
|---|---|
| `account` | `retrieve()` |
| `usage` | `retrieve()` |
| `balance` | `retrieve()` |
| `templates` | `create`, `retrieve`, `list`, `all` |
| `signatureRequests` | `create`, `createBulk`, `retrieve`, `list`, `all`, `send`, `remind`, `cancel`, `listEvents`, `certificate`, `download`, `downloadPdf`, `downloadPdfTo`, `evidence` |
| `sessions` | `create`, `retrieve`, `list`, `all` |
| `signers` | `retrieve`, `createSigningSession`, `decideIdentityMatch` |
| `documents` | `retrieve` |
| `identityVerifications` | `retrieve` |
| `verifications` | `retrieve` |
| `webhookEndpoints` | `create`, `retrieve`, `update`, `delete`, `list`, `all`, `deliveries`, `test`, `eventTypes` |
| `events` | `retrieve`, `list`, `all`, `redeliver` |
| `flows` | `retrieve`, `list`, `all` |

Request and response fields use the API's own names (`template_id`, `legal_level`, …). The full field
reference is at <https://developers.wsign.io/docs>.

### Account, usage and balance

```php
$account = $wsign->account->retrieve();
echo $account->account->name, ' ', $account->plan->id;      // ap_trial, ap_dev, ap_biz, ap_ent, ap_custom

$usage = $wsign->usage->retrieve();
echo $usage->balance->amount, ' ', $usage->balance->currency; // 0 EGP
echo $usage->plan->sends_left;
```

### Templates

```php
$template = $wsign->templates->create([
    'name'    => 'Service agreement',
    'content' => '<p>I, {{signer_name}}, accept the fee of {{amount}}.</p>',
    'fields'  => [['key' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true]],
]);

$template = $wsign->templates->retrieve('tpl_123', ['include' => 'content']);

foreach ($wsign->templates->all(['scope' => 'mine']) as $template) {   // omit scope for the public catalogue
    echo $template->id, ' ', $template->name, PHP_EOL;
}
```

### Signature requests

```php
$request = $wsign->signatureRequests->create([
    'title'       => 'Employment contract',
    'source'      => ['type' => 'template', 'template_id' => 'tpl_123'],
    'legal_level' => 'aes',                 // ses (default), aes (identity verification), qes (token)
    'ordered'     => true,                  // sign one after another
    'signers'     => [[
        'name'   => 'Ahmed Ali',
        'email'  => 'ahmed@example.com',     // or 'phone' => '201012345678' (WhatsApp)
        'type'   => 'individual',            // or company
        'method' => 'draw',                  // or token
        'fields' => ['job_title' => 'Engineer', 'salary' => '25000'],
    ]],
    'fields'    => ['start_date' => '2026-11-01'],
    'cc'        => [['name' => 'Legal', 'email' => 'legal@example.com']],
    'reminders' => ['enabled' => true, 'interval_hours' => 48, 'max' => 3],
    'metadata'  => ['order_id' => 'A-1024'],
    'send'      => true,                     // false creates a draft
]);

$request = $wsign->signatureRequests->retrieve('sr_...');
$request->status;                // draft, sent, partially_signed, completed, declined, expired, canceled
$request->signers[0]->signing_url;

$wsign->signatureRequests->send('sr_...');       // send a draft
$wsign->signatureRequests->remind('sr_...');     // at most once per hour per signer, 10 per request
$wsign->signatureRequests->cancel('sr_...');

$trail       = $wsign->signatureRequests->listEvents('sr_...');   // audit trail
$certificate = $wsign->signatureRequests->certificate('sr_...');
```

Bulk sending creates one request per recipient:

```php
$result = $wsign->signatureRequests->createBulk([
    'source'     => ['type' => 'template', 'template_id' => 'tpl_123'],
    'recipients' => [
        ['name' => 'Ahmed', 'email' => 'ahmed@example.com'],
        ['name' => 'Mona',  'email' => 'mona@example.com'],
    ],
]);
echo $result->created, ' created, ', $result->failed, ' failed';
```

### Signed document and evidence

```php
$download = $wsign->signatureRequests->download('sr_...');   // completed requests only
if ($download->isPdfReady()) {
    $bytes = $wsign->signatureRequests->downloadPdf('sr_...');            // checked against X-File-Sha256
    $wsign->signatureRequests->downloadPdfTo('sr_...', '/var/contracts/sr.pdf');
}

$evidence = $wsign->signatureRequests->evidence('sr_...');
if ($evidence->isSealed()) {
    // Verify $evidence->seal->signature (Ed25519, base64) over $evidence->record_raw with $evidence->seal->public_key.
}
```

While the PDF is being prepared, `downloadPdf()` throws a `ConflictException` with code `pdf_not_ready`.

### Sessions and workflows

Workflows are configured and published in the developer portal. A session is a signature request created
from a workflow; `default` uses the platform defaults.

```php
$session = $wsign->sessions->create([
    'workflow_id' => 'wf_8c1d2e3f4a',      // or 'default'
    'template_id' => 'tpl_123',            // when the workflow chooses the document per request
    'channel'     => 'link',               // email, whatsapp or link (you deliver sign_url yourself)
    'language'    => 'en',
    'vendor_data' => 'customer-42',        // your own reference
    'return_url'  => 'https://acme.example/signed',
    'signers'     => [['name' => 'Noha Hassan', 'email' => 'noha@example.com']],
]);

echo $session->sign_url;   // with a single signer

foreach ($wsign->sessions->all(['vendor_data' => 'customer-42']) as $session) { /* ... */ }
```

### Signers and embedded signing

```php
$signer = $wsign->signers->retrieve('sgr_...');

// Embedded in an iframe (30-minute link)
$embed = $wsign->signers->createSigningSession('sgr_...', ['return_url' => 'https://acme.example/done']);
echo '<iframe src="' . htmlspecialchars($embed->signing_url) . '"></iframe>';

// Or a full-page redirect
$session = $wsign->signers->createSigningSession('sgr_...', [
    'mode'        => 'redirect',
    'success_url' => 'https://acme.example/signed',
    'cancel_url'  => 'https://acme.example/cancel',
]);
header('Location: ' . $session->signing_url);
```

Return, success and cancel URLs must be on a domain you added and had accepted in the developer portal.

#### Name on the identity document

When the name on a signer's identity document differs from the name in the request, `identity.name_match`
records it. With the account setting "hold signing until a decision" (the default), the
`signer.identity_match_hold` event arrives, the state is `awaiting`, and signing waits for your decision
(secret keys only):

```php
$match = $signer->getNameMatch();   // Wsign\Resource\NameMatch or null
if ($match !== null && $match->isAwaitingDecision()) {
    echo $match->expected_name, ' / ', $match->document_name;
    $signer = $wsign->signers->decideIdentityMatch($signer->id, [
        'decision' => 'approve',              // or 'reject' to stop this signer
        'note'     => 'Confirmed by phone',   // optional, stored with the decision
    ]);
}
```

The decision triggers `signer.identity_match_decided`. In both events `$signer->getIdentityMatch()` returns the
result that triggered the event. When nothing awaits a decision the call throws `ConflictException` with the code
`identity_match_not_pending`. `NameMatch::STATE_*` and `NameMatch::VERDICT_*` list the possible values.

### Documents

The API accepts contracts written as text only (in templates or in the request); PDF upload is not supported.

```php
$document = $wsign->documents->retrieve('doc_...');
```

### Identity verifications and verification references

```php
$verification = $wsign->identityVerifications->retrieve('idv_12');   // see $signer->identity->verification_id
echo $verification->status;   // pending, approved, in_review, resubmission_requested, declined, expired

$lookup = $wsign->verifications->retrieve('WTQ-1A2B3C4D5E6F');
if ($lookup->isFound()) {
    echo $lookup->integrity;  // intact, modified or unknown
}
```

An unknown reference does not throw: `isFound()` returns false. `$verification->getNameMatch()` returns the
identity verification's name-match result, or null.

### Webhook endpoints and events

```php
$endpoint = $wsign->webhookEndpoints->create([
    'url'            => 'https://acme.example/hooks/wsign',
    'enabled_events' => [\Wsign\EventType::SIGNATURE_REQUEST_COMPLETED, \Wsign\EventType::SIGNER_SIGNED],
]);
$secret = $endpoint->secret;   // whsec_..., returned only now: store it

foreach ($wsign->webhookEndpoints->all(['limit' => 100]) as $item) { /* every page */ }
$wsign->webhookEndpoints->update($endpoint->id, ['status' => 'disabled']);
$wsign->webhookEndpoints->deliveries($endpoint->id);
$wsign->webhookEndpoints->test($endpoint->id);
$wsign->webhookEndpoints->delete($endpoint->id);
$wsign->webhookEndpoints->eventTypes();   // every supported type

foreach ($wsign->events->all(['type' => 'signature_request.completed']) as $event) { /* ... */ }
$wsign->events->redeliver('evt_...', ['endpoint' => 'we_...']);
```

### Flows

```php
foreach ($wsign->flows->list() as $flow) {
    echo $flow->id, ' ', $flow->name, PHP_EOL;   // configurations of the embeddable console
}
```

## Response objects

Every method returns an immutable object. Read fields as properties or array keys; nested objects work the
same way, lists stay PHP arrays.

```php
$request->status;
$request['status'];
$request->signers[0]->name;
$request->metadata->order_id;
$request->toArray();            // plain arrays
$request->toJson();
json_encode($request);          // empty objects stay {}
```

Fields added by future API versions are readable right away. Writing a field throws a `LogicException`.

The HTTP response of the call is attached to the returned object:

```php
$response = $request->getLastResponse();
$response->status;                  // 201
$response->getRequestId();          // req_...
$response->isIdempotentReplayed();  // true when the API replayed a stored response
$response->getHeader('X-RateLimit-Remaining');
```

Typed classes (`SignatureRequest`, `Signer`, `Template`, `Session`, `Event`, …) declare their fields with
`@property-read`, so IDEs and static analysers know them. If you use PHPStan, the bundled `extension.neon`
(loaded automatically by `phpstan/extension-installer`) lets you read undocumented or nested fields without errors.

## Pagination

`list()` returns one page; `all()` walks every page lazily.

```php
$page = $wsign->signatureRequests->list(['status' => 'completed', 'limit' => 50]);
foreach ($page as $request) { /* this page only */ }
$page->hasMore();
$next = $page->nextPageParams(['status' => 'completed', 'limit' => 50]); // null on the last page

foreach ($wsign->signatureRequests->all(['status' => 'completed', 'limit' => 100]) as $request) {
    echo $request->id, ' ', $request->reference, PHP_EOL;
}

$firstHundred = $wsign->events->all()->toArray(100);
```

Filters for signature requests: `status`, `created_after` and `created_before` (Unix timestamp or ISO date), `limit` (1–100).

## Error handling

Every exception implements `Wsign\Exception\ExceptionInterface`.

| Exception | When |
|---|---|
| `ValidationException` | 400: a parameter is missing or invalid (`getParam()`, `getFieldErrors()`) |
| `TooManySignersException` | 400 `too_many_signers` (a `ValidationException`): more signers than `getMaxSigners()` (5) in one request |
| `AuthenticationException` | 401: missing, invalid or revoked key |
| `PaymentRequiredException` | 402: `plan_required`, `plan_expired`, `insufficient_balance` (`getUpgradeUrl()`) |
| `PermissionException` | 403: `plan_feature_unavailable` (`getFeature()`, `getRequiredPlan()`, `getUpgradeUrl()`), `kyb_required` (`getVerifyUrl()`), `forbidden`, `ip_not_allowed` |
| `NotFoundException` | 404 |
| `ConflictException` | 409: `request_not_cancelable`, `pdf_not_ready`, `idempotency_key_reused`, … |
| `InvalidRequestException` | other 4xx (parent of the three above) |
| `RateLimitException` | 429 (`getRetryAfter()`, `isTransient()`) |
| `ServerException` | 5xx, e.g. `delivery_failed` |
| `ApiConnectionException` | no response: DNS, TLS, refused connection, timeout |
| `UnexpectedResponseException` | the response could not be understood |
| `SignatureVerificationException` | a webhook failed verification |
| `InvalidArgumentException` | the library was called with invalid arguments (no request sent) |

```php
use Wsign\Exception;

try {
    $wsign->signatureRequests->create($params);
} catch (Exception\TooManySignersException $e) {
    echo 'At most ', $e->getMaxSigners(), ' signers per request: split them over several requests';
} catch (Exception\ValidationException $e) {
    echo 'Invalid field ', $e->getParam(), ': ', $e->getMessage();
} catch (Exception\PaymentRequiredException $e) {
    echo 'Top up or upgrade: ', $e->getUpgradeUrl();
} catch (Exception\PermissionException $e) {
    if ($e->isKybRequired()) {
        echo 'Verify your company first: ', $e->getVerifyUrl();
    } elseif ($e->isPlanFeatureUnavailable()) {
        echo $e->getFeature(), ' needs plan ', $e->getRequiredPlan(), ': ', $e->getUpgradeUrl();
    }
} catch (Exception\ApiException $e) {
    error_log(sprintf('Wsign %d %s (%s)', $e->getHttpStatus(), $e->getErrorCode(), $e->getRequestId()));
} catch (Exception\ApiConnectionException $e) {
    error_log('Wsign unreachable: ' . $e->getMessage());
}
```

Branch on `getErrorCode()`, not on the message: messages are human-readable text (Arabic, or English with the
`language => 'en'` client option) and may change. Quote `getRequestId()` when you contact support.

## Retries and idempotency

The library retries automatically (2 retries by default) when:

- the connection fails or times out;
- the API answers `429 too_many_requests` (honouring `Retry-After`);
- the API answers `409 idempotency_in_progress`;
- the API answers `500`, `502`, `503` or `504` (except `delivery_failed` and `feature_unavailable`).

Quota-style refusals (`daily_cap_reached`, `reminder_limit_reached`, `test_daily_limit`, …) and every other
4xx are never retried. Waits use exponential backoff with jitter (0.5 s, 1 s, 2 s … capped at 8 s), and
`Retry-After` is honoured up to 60 s.

Every `POST` carries an `Idempotency-Key`. The library generates one per call and reuses it across its own
retries, so a retried create never creates twice. Pass your own key to make retries safe across processes:

```php
$request = $wsign->signatureRequests->create($params, ['idempotency_key' => 'order-1024-contract']);
```

The API keeps keys for 24 hours. Reusing a key with a different body or endpoint raises a `ConflictException`
with code `idempotency_key_reused`.

Tune the backoff with a `RetryPolicy`:

```php
new \Wsign\Client($key, [
    'max_retries'  => 4,
    'retry_policy' => new \Wsign\RetryPolicy(initialDelay: 1.0, maxDelay: 20.0, maxRetryAfter: 30.0),
]);
```

## Webhooks

Each delivery carries `Wsign-Signature: t=<timestamp>,v1=<signature>`, an HMAC-SHA256 of
`"<timestamp>.<raw body>"` keyed with your endpoint secret. `Webhook::constructEvent()` checks the signature
(in constant time) and the timestamp (5 minutes by default), then returns a typed `Event`. Always pass the
**raw** request body.

### Plain PHP

```php
require 'vendor/autoload.php';

$payload = file_get_contents('php://input');
$header  = $_SERVER['HTTP_WSIGN_SIGNATURE'] ?? '';

try {
    $event = \Wsign\Webhook::constructEvent($payload, $header, getenv('WSIGN_WEBHOOK_SECRET'));
} catch (\Wsign\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit('invalid signature');
}

switch ($event->type) {
    case \Wsign\EventType::SIGNATURE_REQUEST_COMPLETED:
        $request = $event->data->object;   // Wsign\Resource\SignatureRequest
        // store $request->reference, fetch the PDF, ...
        break;
    case \Wsign\EventType::SIGNER_SIGNED:
        $signer = $event->data->object;    // Wsign\Resource\Signer
        break;
}

http_response_code(200);
```

### Laravel

```php
// routes/api.php
Route::post('/hooks/wsign', \App\Http\Controllers\WsignWebhookController::class);
```

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Wsign\EventType;
use Wsign\Exception\SignatureVerificationException;
use Wsign\Webhook;

class WsignWebhookController
{
    public function __invoke(Request $request)
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),                          // the raw body
                (string) $request->header('Wsign-Signature'),
                config('services.wsign.webhook_secret'),
            );
        } catch (SignatureVerificationException $e) {
            return response('invalid signature', 400);
        }

        if ($event->type === EventType::SIGNATURE_REQUEST_COMPLETED) {
            dispatch(new \App\Jobs\StoreSignedContract($event->data->object->id));
        }

        return response()->json(['received' => true]);
    }
}
```

Routes under `routes/api.php` are not CSRF-protected; if you register the route elsewhere, exclude it from
CSRF verification.

### Good practice

- Respond with a `2xx` within 10 seconds and do slow work in a queue. Failed deliveries are retried after
  1 min, 5 min, 15 min, 1 h, 3 h, 6 h, 12 h and 24 h.
- Deliveries can repeat or arrive out of order: deduplicate on `$event->id` (also sent as `Wsign-Event-Id`).
- Test your handler with a locally signed payload:
  `Webhook::generateTestHeader($payload, $secret)`.
- `Webhook::isValid()` returns a boolean instead of throwing; `Webhook::verifyHeader()` checks without decoding.

## Using your own HTTP client

The default transport is cURL. To use Guzzle, Symfony HttpClient or any PSR-18 client:

```php
$psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();
$wsign = new \Wsign\Client($key, [
    'http_client' => new \Wsign\HttpClient\Psr18Client(new \GuzzleHttp\Client(['timeout' => 30]), $psr17, $psr17),
]);
```

You can also implement `Wsign\HttpClient\HttpClientInterface` yourself (one method), for example to add
logging or a proxy. `CurlClient` accepts extra cURL options: `new CurlClient([CURLOPT_PROXY => 'http://proxy:3128'])`.

## Versioning

This library follows [Semantic Versioning](https://semver.org). Version 1.x targets API version
`2026-07-01` (`Client::API_VERSION`) and sends it in the `Wsign-Version` header. New endpoints and fields
arrive in minor releases; anything that breaks existing code, including a change of the pinned API version or
dropping a PHP version, comes in a new major release. See [CHANGELOG.md](CHANGELOG.md).

## Support and security

- Documentation and developer portal: <https://developers.wsign.io>
- Support: support@wsign.io
- Security issues: please report privately to support@wsign.io (see [SECURITY.md](SECURITY.md)); do not open a public issue.

## License

Released under the MIT License. See [LICENSE](LICENSE).
