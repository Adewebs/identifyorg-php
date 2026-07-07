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

## Requirements

PHP 8.0+, the `curl` and `json` extensions (bundled with virtually every PHP
install). No Composer dependencies beyond that — same "thin wrapper"
philosophy as the Node and Go SDKs.
