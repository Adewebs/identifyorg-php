# identifyorg/sdk-php (PHP server SDK)

```bash
composer require identifyorg/sdk-php
```

```php
<?php
require 'vendor/autoload.php';

use IdentifyOrg\IdentifyOrgClient;
use IdentifyOrg\IdentifyOrgApiError;

$identifyorg = new IdentifyOrgClient(getenv('IDENTIFYORG_SECRET_KEY'));

try {
    // Identity verification
    $result = $identifyorg->verifyBvn('22212345678', 'Ada', 'Okafor');

    // Top up credit
    $topup = $identifyorg->topup(5000);
    echo $topup['checkout_url'];

    // Mint a realtime token for YOUR user, then hand it to the browser/mobile
    // client (IdentifyOrg JS/React Native/Flutter/Kotlin SDK) — never send
    // your secret key to a client.
    $call = $identifyorg->streamingToken('video', $userId, $userName);
    return ['token' => $call['token'], 'url' => $call['url'], 'room_name' => $call['room_name']];

    $chat = $identifyorg->chatToken($visitorId, $visitorName);

    // Website Audit — first standard-tier audit free per account, then
    // billed. 'deep' runs in the background: returns immediately with
    // status 'pending', poll getAudit() until it's 'ready'/'failed'.
    $audit = $identifyorg->runAudit('https://example.com', 'standard');
    $audit = $identifyorg->getAudit($audit['id']);
    echo $audit['score'] . ' ' . $audit['grade'];
    file_put_contents('report.pdf', $identifyorg->getAuditPdf($audit['id']));

    // AI media detection — self-hosted, no third-party AI-detection API.
    // Image/text: first scan free per account, then billed. Video: always billed.
    $imgScan = $identifyorg->scanImage('photo.jpg');
    $textScan = $identifyorg->scanText('... at least ~800 characters ...');
    $videoScan = $identifyorg->scanVideo('clip.mp4', 12);
    echo $textScan['verdict'];
} catch (IdentifyOrgApiError $e) {
    // $e->status, $e->apiCode, $e->getMessage()
}
```

## Laravel example: minting a call token endpoint

```php
Route::post('/api/call-token', function (Request $request) {
    $identifyorg = new IdentifyOrg\IdentifyOrgClient(config('services.identifyorg.secret_key'));
    return $identifyorg->streamingToken('video', auth()->id(), auth()->user()->name);
});
```

Your frontend calls this endpoint (never IdentifyOrg directly with a secret
key), then passes the returned `token`/`url` straight into the IdentifyOrg JS
SDK's `joinCall()`.

## What's new in 0.2.0

```php
$started = $client->phoneVerifyStart('08031234567', null, 'signup-42');
$result  = $client->phoneVerifyCheck($started['id'], '418209'); // ['verified' => true, ...]

file_put_contents('out.docx', $client->pdfToDocx('report.pdf'));
file_put_contents('merged.pdf', $client->pdfMerge(['a.pdf', 'b.pdf']));

$client->sendSms('08031234567', 'Your order has shipped');
$client->createWebhook('https://example.com/hooks/identifyorg', ['verification.completed']);
$client->accountOverview(30);
$client->call('GET', '/v1/tools/access'); // any endpoint not wrapped yet
```

`topup()` and `payments()` were removed: they need a dashboard login, so they always
returned 401 with an API key.

## Requirements

PHP 8.0+, the `curl` and `json` extensions (bundled with virtually every PHP
install). No Composer dependencies beyond that — same "thin wrapper"
philosophy as the Node and Go SDKs.

## Address verification and bill prices

```php
$check = $client->verifyAddress('12 Adeola Odeku Street, Victoria Island, Lagos', 'Ada Obi');
echo $check['confirm_url']; // send this to the person
$result = $client->getAddressVerification($check['id']);

$plans = $client->billDataPlans('mtn', 'monthly');
$price = $client->billQuote('airtime', 500.0, 'mtn');
```

## Take payments

```php
$client->saveBusinessProfile('Ada Stores', 'individual', '08031234567');
$acct = $client->createVirtualAccount('Tunde Bello', 'tunde@example.com', 'order-1001');
$charge = $client->createCardCharge(5000, 'tunde@example.com', 'Tunde Bello'); // send him the checkout link
print_r($client->receivedPayments(null, 'success'));
```

## WhatsApp for platforms

```php
$ws = $client->createWhatsAppWorkspace('Ada Stores', 'client_8841', true, ['business_name' => 'Ada Stores', 'about' => 'Phones and accessories in Yaba']);
$client->setWhatsAppBrand('YourPlatform', null, '#0a7f3f');   // once, for every connect page
$link = $client->connectWhatsApp($ws['id']);
echo $link['connect_url'];                                    // send your client here

// with a test key: rehearse with no real number and no billing
$client->testWhatsAppScan($ws['id']);
print_r($client->testWhatsAppInbound($ws['id'], 'How much is a charger?')['replies']);
$client->useWhatsAppPayPerReply();
print_r($client->whatsAppUsage(30));
```
