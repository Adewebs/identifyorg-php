<?php

declare(strict_types=1);

namespace IdentifyOrg;

/**
 * IdentifyOrg — PHP server SDK.
 *
 * Server-side only: use your SECRET key here (io_test_.../io_live_...).
 * For browser/mobile clients, mint a realtime token with streamingToken()
 * or chatToken() on your backend and hand the result to the IdentifyOrg
 * JS/React Native/Flutter/Kotlin SDK — never ship this key to a client.
 *
 * Zero dependencies — uses PHP's built-in curl extension directly, same
 * "thin wrapper" philosophy as the Node/Go SDKs.
 */
class IdentifyOrgClient
{
    private const DEFAULT_BASE_URL = 'https://api.identifyorg.com';

    private string $apiKey;
    private string $baseUrl;

    public function __construct(string $apiKey, string $baseUrl = self::DEFAULT_BASE_URL)
    {
        if ($apiKey === '') {
            throw new \InvalidArgumentException('IdentifyOrg: apiKey is required');
        }
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    // --- hosted face check ---

    /** Create a hosted face check; send the person to the returned 'url', then read the verdict with getLiveness(). */
    public function createLiveness(?string $reference = null, ?string $returnUrl = null, ?string $referencePhotoB64 = null, int $expiresInMinutes = 30): array
    {
        return $this->post('/v1/liveness/hosted', [
            'reference' => $reference, 'return_url' => $returnUrl,
            'reference_photo' => $referencePhotoB64, 'expires_in_minutes' => $expiresInMinutes,
        ]);
    }

    public function getLiveness(string $id): array
    {
        return $this->get('/v1/liveness/hosted/' . rawurlencode($id));
    }

    // --- verification ---

    /**
     * $phoneNumber (e.g. "08012345678") is required on live keys — the
     * upstream provider rejects BVN match requests without it; test keys
     * ignore it. $dateOfBirth is ISO format (YYYY-MM-DD).
     */
    public function verifyBvn(string $bvn, ?string $firstName = null, ?string $lastName = null, ?string $dateOfBirth = null, ?string $phoneNumber = null, ?string $idempotencyKey = null): array
    {
        return $this->post('/v1/verify/bvn', [
            'bvn' => $bvn, 'first_name' => $firstName, 'last_name' => $lastName,
            'date_of_birth' => $dateOfBirth, 'phone_number' => $phoneNumber,
        ], $idempotencyKey);
    }

    public function verifyNin(string $nin, ?string $firstName = null, ?string $lastName = null, ?string $idempotencyKey = null): array
    {
        return $this->post('/v1/verify/nin', ['nin' => $nin, 'first_name' => $firstName, 'last_name' => $lastName], $idempotencyKey);
    }

    public function verifyFrsc(string $licenceNumber, ?string $firstName = null, ?string $lastName = null, ?string $idempotencyKey = null): array
    {
        return $this->post('/v1/verify/frsc', ['licence_number' => $licenceNumber, 'first_name' => $firstName, 'last_name' => $lastName], $idempotencyKey);
    }

    public function verifyCreditScore(string $bvn, ?string $firstName = null, ?string $lastName = null, ?string $idempotencyKey = null): array
    {
        return $this->post('/v1/verify/credit-score', ['bvn' => $bvn, 'first_name' => $firstName, 'last_name' => $lastName], $idempotencyKey);
    }

    public function verifyCac(string $rcNumber, ?string $firstName = null, ?string $lastName = null, ?string $idempotencyKey = null): array
    {
        return $this->post('/v1/verify/cac', ['rc_number' => $rcNumber, 'first_name' => $firstName, 'last_name' => $lastName], $idempotencyKey);
    }

    // --- file-upload verification: pass a filesystem path to each ---

    /** OCR-verify an ID document photo (national ID, passport, voter's card, driver's licence). */
    public function verifyDocument(string $filePath, ?string $idempotencyKey = null): array
    {
        return $this->postFiles('/v1/verify/document', ['file' => $filePath], $idempotencyKey);
    }

    /** Check whether a selfie photo is a live person (basic anti-spoofing). */
    public function verifyLiveness(string $filePath, ?string $idempotencyKey = null): array
    {
        return $this->postFiles('/v1/verify/liveness', ['file' => $filePath], $idempotencyKey);
    }

    /** Compare a selfie against an ID photo — same person or not. */
    public function verifyFaceMatch(string $selfiePath, string $idPhotoPath, ?string $idempotencyKey = null): array
    {
        return $this->postFiles('/v1/verify/face-match', ['selfie' => $selfiePath, 'id_photo' => $idPhotoPath], $idempotencyKey);
    }

    // --- realtime: calls, streaming, chat ---

    /** $sessionType: 'video' | 'voice' | 'stream'. Mint this server-side,
     * then hand the result to a client SDK (JS/React Native/Flutter/Kotlin). */
    public function streamingToken(string $sessionType, string $identity, ?string $displayName = null, ?string $roomName = null, string $role = 'publisher'): array
    {
        return $this->post("/v1/streaming/{$sessionType}/token", [
            'identity' => $identity,
            'display_name' => $displayName,
            'room_name' => $roomName,
            'role' => $role,
        ]);
    }

    public function streamingSessions(int $limit = 50): array
    {
        return $this->get('/v1/streaming/sessions', ['limit' => $limit]);
    }

    // --- host moderation — call from your backend in response to your own
    // host UI (mute/kick/end buttons), never directly from a client ---

    public function listParticipants(string $sessionId): array
    {
        return $this->get("/v1/streaming/sessions/{$sessionId}/participants");
    }

    public function muteParticipant(string $sessionId, string $identity): array
    {
        return $this->post("/v1/streaming/sessions/{$sessionId}/participants/{$identity}/mute", []);
    }

    public function unmuteParticipant(string $sessionId, string $identity): array
    {
        return $this->post("/v1/streaming/sessions/{$sessionId}/participants/{$identity}/unmute", []);
    }

    public function cameraOffParticipant(string $sessionId, string $identity): array
    {
        return $this->post("/v1/streaming/sessions/{$sessionId}/participants/{$identity}/camera-off", []);
    }

    public function cameraOnParticipant(string $sessionId, string $identity): array
    {
        return $this->post("/v1/streaming/sessions/{$sessionId}/participants/{$identity}/camera-on", []);
    }

    public function kickParticipant(string $sessionId, string $identity): array
    {
        return $this->post("/v1/streaming/sessions/{$sessionId}/participants/{$identity}/kick", []);
    }

    public function endSession(string $sessionId): array
    {
        return $this->post("/v1/streaming/sessions/{$sessionId}/end", []);
    }

    // --- the gifting economy ---

    public function createGift(string $name, int $priceCoins, string $emoji = '🎁'): array
    {
        return $this->post('/v1/streaming/gifts', ['name' => $name, 'emoji' => $emoji, 'price_coins' => $priceCoins]);
    }

    public function listGifts(bool $includeInactive = false): array
    {
        return $this->get('/v1/streaming/gifts', ['include_inactive' => $includeInactive]);
    }

    public function updateGift(string $giftId, array $fields): array
    {
        return $this->patch("/v1/streaming/gifts/{$giftId}", $fields);
    }

    public function deleteGift(string $giftId): void
    {
        $this->delete("/v1/streaming/gifts/{$giftId}");
    }

    public function creditCoins(string $externalId, int $amount, ?string $displayName = null): array
    {
        return $this->post("/v1/streaming/viewers/{$externalId}/coins/credit", ['amount' => $amount, 'display_name' => $displayName]);
    }

    public function getViewer(string $externalId): array
    {
        return $this->get("/v1/streaming/viewers/{$externalId}");
    }

    public function sendGift(string $fromExternalId, string $toIdentity, string $giftId, string $roomName): array
    {
        return $this->post('/v1/streaming/gifts/send', [
            'from_external_id' => $fromExternalId,
            'to_identity' => $toIdentity,
            'gift_id' => $giftId,
            'room_name' => $roomName,
        ]);
    }

    public function chatToken(string $visitorId, ?string $visitorName = null, ?string $channelId = null): array
    {
        return $this->post('/v1/chat/token', ['visitor_id' => $visitorId, 'visitor_name' => $visitorName, 'channel_id' => $channelId]);
    }

    // --- website audit ---
    // The anonymous/claim pair (homepage lead-gen flow) is web-only, not
    // part of the API surface — runAudit is the same billed path the
    // dashboard uses once a customer is logged in, just with a secret key.

    /**
     * $tier: 'standard' | 'ai' | 'deep'. First standard-tier audit is free
     * per account; ai/deep are always billed. 'deep' runs in the
     * background — this returns immediately with status 'pending'; poll
     * getAudit() until 'ready'/'failed'.
     */
    public function runAudit(string $url, string $tier = 'standard', ?string $githubRepoUrl = null): array
    {
        return $this->post('/v1/audits', ['url' => $url, 'tier' => $tier, 'github_repo_url' => $githubRepoUrl]);
    }

    public function listAudits(int $limit = 50): array
    {
        return $this->get('/v1/audits', ['limit' => $limit]);
    }

    public function getAudit(string $auditId): array
    {
        return $this->get("/v1/audits/{$auditId}");
    }

    /** Returns the raw PDF bytes for a ready audit — write them to a file yourself. */
    public function getAuditPdf(string $auditId): string
    {
        $ch = curl_init("{$this->baseUrl}/v1/audits/{$auditId}/pdf");
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => $this->headers(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
        ]);
        $raw = curl_exec($ch);
        if ($raw === false) {
            throw new IdentifyOrgApiError(0, 'connection_error', curl_error($ch));
        }
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($status >= 300) {
            $data = json_decode($raw, true) ?? [];
            $err = $data['error'] ?? [];
            throw new IdentifyOrgApiError($status, $err['code'] ?? null, $err['message'] ?? "HTTP {$status}");
        }
        return $raw;
    }

    // --- AI media detection (image / video / text) ---
    // Self-hosted, no third-party AI-detection API. Same anonymous/claim
    // exclusion as audits above — these are the billed paths.

    /** $filePath: filesystem path to the image. First image scan free per account, then billed. */
    public function scanImage(string $filePath, ?string $idempotencyKey = null): array
    {
        return $this->postFiles('/v1/media-scan/image', ['file' => $filePath], $idempotencyKey);
    }

    /** Minimum ~800 characters — shorter text has no reliable signal. First text scan free per account, then billed. */
    public function scanText(string $text): array
    {
        return $this->post('/v1/media-scan/text', ['text' => $text]);
    }

    /** $filePath: filesystem path to the video. $frames: 4-30 sample frames. Always billed, no free trial. */
    public function scanVideo(string $filePath, int $frames = 12, ?string $idempotencyKey = null): array
    {
        return $this->postFiles("/v1/media-scan/video?frames={$frames}", ['file' => $filePath], $idempotencyKey);
    }

    /** $mediaType: 'image' | 'video' | 'text' | null for all. */
    public function listScans(?string $mediaType = null, int $limit = 50): array
    {
        return $this->get('/v1/media-scan', ['media_type' => $mediaType, 'limit' => $limit]);
    }

    public function getScan(string $scanId): array
    {
        return $this->get("/v1/media-scan/{$scanId}");
    }

    // --- phone verification (OTP by SMS) ---

    /**
     * Sends a code to $phone (local 0803... or +234... both work). $template must
     * contain {code}; {minutes} is also substituted. Test keys return the same
     * shape without sending a message.
     */
    public function phoneVerifyStart(string $phone, ?string $template = null, ?string $reference = null): array
    {
        return $this->post('/v1/phone/verify/start', ['phone' => $phone, 'template' => $template, 'reference' => $reference]);
    }

    /** Free. Returns ['verified' => bool, ...]. */
    public function phoneVerifyCheck(string $id, string $code): array
    {
        return $this->post('/v1/phone/verify/check', ['id' => $id, 'code' => $code]);
    }

    public function phoneVerifications(int $limit = 50): array
    {
        return $this->get('/v1/phone/verifications', ['limit' => $limit]);
    }

    // --- SMS ---

    public function sendSms(string $to, string $message): array
    {
        return $this->post('/v1/sms/send', ['to' => $to, 'message' => $message]);
    }

    /** Billed per delivered recipient, not per attempt. @param string[] $to */
    public function sendBulkSms(array $to, string $message): array
    {
        return $this->post('/v1/sms/send-bulk', ['to' => $to, 'message' => $message]);
    }

    // --- bill payments ---

    public function buyAirtime(string $network, string $phone, float $amount, ?string $idempotencyKey = null): array
    {
        return $this->post('/v1/bills/airtime', ['network' => $network, 'phone' => $phone, 'amount' => $amount], $idempotencyKey);
    }

    public function buyData(string $network, string $phone, string $variationCode, ?string $idempotencyKey = null): array
    {
        return $this->post('/v1/bills/data', ['network' => $network, 'phone' => $phone, 'variation_code' => $variationCode], $idempotencyKey);
    }

    public function payElectricity(string $disco, string $meterNumber, float $amount, string $meterType = 'prepaid', ?string $idempotencyKey = null): array
    {
        return $this->post('/v1/bills/electricity', ['disco' => $disco, 'meter_number' => $meterNumber, 'meter_type' => $meterType, 'amount' => $amount], $idempotencyKey);
    }

    public function payTv(string $provider, string $smartcardNumber, string $variationCode, ?string $idempotencyKey = null): array
    {
        return $this->post('/v1/bills/tv', ['provider' => $provider, 'smartcard_number' => $smartcardNumber, 'variation_code' => $variationCode], $idempotencyKey);
    }

    public function billNetworks(): array
    {
        return $this->get('/v1/bills/networks');
    }

    /** $duration: daily, weekly, 14-days, monthly, 2-months, yearly or other. */
    public function billDataPlans(string $network, ?string $duration = null): array
    {
        return $this->get('/v1/bills/data-plans', array_filter(['network' => $network, 'duration' => $duration], fn ($v) => $v !== null));
    }

    public function billDataDurations(string $network): array
    {
        return $this->get('/v1/bills/data-durations', ['network' => $network]);
    }

    public function billDiscos(): array
    {
        return $this->get('/v1/bills/discos');
    }

    public function billTvProviders(): array
    {
        return $this->get('/v1/bills/tv-providers');
    }

    public function billTvPlans(string $provider): array
    {
        return $this->get('/v1/bills/tv-plans', ['provider' => $provider]);
    }

    /** What you will pay. $type is "airtime" or "electricity". */
    public function billQuote(string $type, float $amount, ?string $network = null, ?string $disco = null): array
    {
        return $this->get('/v1/bills/quote', array_filter(['type' => $type, 'amount' => $amount, 'network' => $network, 'disco' => $disco], fn ($v) => $v !== null));
    }

    /** Offers running now, for example a percentage off airtime. */
    public function billOffers(): array
    {
        return $this->get('/v1/bills/offers');
    }

    public function verifyMeter(string $disco, string $meterNumber, string $meterType = 'prepaid'): array
    {
        return $this->post('/v1/bills/verify-meter', ['disco' => $disco, 'meter_number' => $meterNumber, 'meter_type' => $meterType]);
    }

    public function verifySmartcard(string $provider, string $smartcardNumber): array
    {
        return $this->post('/v1/bills/verify-smartcard', ['provider' => $provider, 'smartcard_number' => $smartcardNumber]);
    }

    // --- webhooks ---

    public function listWebhooks(): array
    {
        return $this->get('/v1/webhooks');
    }

    /** @return string[] the event names you can subscribe to */
    public function webhookEvents(): array
    {
        return $this->get('/v1/webhooks/events')['events'] ?? [];
    }

    /** The signing secret is returned once, in this response. @param string[]|null $events */
    public function createWebhook(string $url, ?array $events = null, ?string $description = null): array
    {
        return $this->post('/v1/webhooks', ['url' => $url, 'events' => $events, 'description' => $description]);
    }

    public function updateWebhook(string $endpointId, array $fields): array
    {
        return $this->patch("/v1/webhooks/{$endpointId}", $fields);
    }

    public function deleteWebhook(string $endpointId): void
    {
        $this->delete("/v1/webhooks/{$endpointId}");
    }

    public function rotateWebhookSecret(string $endpointId): array
    {
        return $this->post("/v1/webhooks/{$endpointId}/rotate-secret", []);
    }

    public function testWebhook(string $endpointId): array
    {
        return $this->post("/v1/webhooks/{$endpointId}/test", []);
    }

    public function webhookDeliveries(string $endpointId, int $limit = 50): array
    {
        return $this->get("/v1/webhooks/{$endpointId}/deliveries", ['limit' => $limit]);
    }

    /** Every event we sent you, newest first. $filters: event, status, endpoint_id, limit, offset. */
    public function allWebhookDeliveries(array $filters = []): array
    {
        return $this->get('/v1/webhooks/deliveries', $filters);
    }

    public function resendWebhookDelivery(string $deliveryId): array
    {
        return $this->post("/v1/webhooks/deliveries/{$deliveryId}/resend", []);
    }

    public function retryWebhookDelivery(string $deliveryId): array
    {
        return $this->post("/v1/webhooks/deliveries/{$deliveryId}/retry", []);
    }

    // --- verification records ---

    public function getVerification(string $id): array
    {
        return $this->get("/v1/verify/{$id}");
    }

    public function verifyDocumentTamper(string $filePath, ?string $idempotencyKey = null): array
    {
        return $this->postFiles('/v1/verify/document-tamper', ['file' => $filePath], $idempotencyKey);
    }

    // --- take payments (account numbers for your customers, card links, withdrawals, refunds) ---
    // A test key works in test mode with pretend money; a live key moves real money.

    public function paymentsOverview(): array
    {
        return $this->get('/v1/business/overview');
    }

    /** $businessType is "registered" or "individual". */
    public function saveBusinessProfile(string $businessName, string $businessType, string $contactPhone): array
    {
        return $this->request('PUT', '/v1/business/profile', array_filter(['business_name' => $businessName, 'business_type' => $businessType, 'contact_phone' => $contactPhone], fn ($v) => $v !== null));
    }

    public function paymentBanks(): array
    {
        return $this->get('/v1/business/banks');
    }

    public function savePayoutAccount(string $bankCode, string $accountNumber): array
    {
        return $this->request('PUT', '/v1/business/payout-account', array_filter(['bank_code' => $bankCode, 'account_number' => $accountNumber], fn ($v) => $v !== null));
    }

    public function requestPayout(float $amount): array
    {
        return $this->post('/v1/business/payouts', ['amount' => $amount]);
    }

    public function payouts(): array
    {
        return $this->get('/v1/business/payouts');
    }

    /** Leave $amount null to refund all of it. */
    public function requestRefund(string $paymentId, ?float $amount = null, string $reason = ''): array
    {
        return $this->post('/v1/business/refunds', ['payment_id' => $paymentId, 'amount' => $amount, 'reason' => $reason]);
    }

    public function refunds(): array
    {
        return $this->get('/v1/business/refunds');
    }

    public function paymentEvents(int $limit = 60): array
    {
        return $this->get('/v1/business/events', ['limit' => $limit]);
    }

    /** A bank account number for one of your customers. The banks need a BVN or NIN for a permanent one; it is never stored. */
    public function createVirtualAccount(string $customerName, string $customerEmail, ?string $reference = null, ?string $bvn = null, ?string $nin = null): array
    {
        return $this->post('/v1/business/virtual-accounts', ['customer_name' => $customerName, 'customer_email' => $customerEmail, 'reference' => $reference, 'bvn' => $bvn, 'nin' => $nin]);
    }

    public function virtualAccounts(int $limit = 100): array
    {
        return $this->get('/v1/business/virtual-accounts', ['limit' => $limit]);
    }

    public function receivedPayments(?string $virtualAccountId = null, ?string $status = null, int $limit = 100): array
    {
        return $this->get('/v1/business/payments', ['virtual_account_id' => $virtualAccountId, 'status' => $status, 'limit' => $limit]);
    }

    /** Returns a checkout link to send your customer. */
    public function createCardCharge(float $amount, string $customerEmail, string $customerName, string $description = ''): array
    {
        return $this->post('/v1/business/charges', ['amount' => $amount, 'customer_email' => $customerEmail, 'customer_name' => $customerName, 'description' => $description]);
    }

    /** Test keys only. */
    public function simulatePayment(string $virtualAccountId, float $amount): array
    {
        return $this->post('/v1/business/test/simulate-payment', ['virtual_account_id' => $virtualAccountId, 'amount' => $amount]);
    }

    public function simulateCardPayment(string $reference): array
    {
        return $this->post('/v1/business/test/simulate-card-payment', ['reference' => $reference]);
    }

    // --- WhatsApp for platforms: a workspace (one number) for every client you serve ---
    // A test key works on test workspaces: pretend linking, no real number, never billed.

    /** One workspace per client business. $externalId is your own id for that client. */
    public function createWhatsAppWorkspace(string $label, ?string $externalId = null, bool $aiEnabled = true, ?array $profile = null, ?array $brand = null, string $mode = 'ai'): array
    {
        return $this->post('/v1/wa/channels', ['label' => $label, 'external_id' => $externalId, 'ai_enabled' => $aiEnabled, 'profile' => $profile, 'brand' => $brand, 'mode' => $mode]);
    }

    public function whatsAppWorkspaces(?string $externalId = null): array
    {
        return $this->get('/v1/wa/channels', ['external_id' => $externalId]);
    }

    /** Live status, plus `qr` (text to draw as a QR code) while the workspace is waiting to be scanned. */
    public function getWhatsAppWorkspace(string $id): array
    {
        return $this->get("/v1/wa/channels/{$id}");
    }

    /** label, ai_enabled, mode or brand. */
    public function updateWhatsAppWorkspace(string $id, array $fields): array
    {
        return $this->patch("/v1/wa/channels/{$id}", $fields);
    }

    public function deleteWhatsAppWorkspace(string $id): void
    {
        $this->request('DELETE', "/v1/wa/channels/{$id}");
    }

    /** Start linking. Returns `connect_url`, the hosted page to send your client to. */
    public function connectWhatsApp(string $id): array
    {
        return $this->post("/v1/wa/channels/{$id}/connect", []);
    }

    public function disconnectWhatsApp(string $id): array
    {
        return $this->post("/v1/wa/channels/{$id}/disconnect", []);
    }

    public function setWhatsAppProfile(string $id, array $profile): array
    {
        return $this->request('PUT', "/v1/wa/channels/{$id}/profile", $profile);
    }

    public function setWhatsAppRules(string $id, array $rules): array
    {
        return $this->request('PUT', "/v1/wa/channels/{$id}/rules", ['rules' => $rules]);
    }

    /** Where customer messages are sent, signed. Returns the signing secret. */
    public function setWhatsAppWebsite(string $id, string $url, ?string $fallbackMessage = null): array
    {
        return $this->request('PUT', "/v1/wa/channels/{$id}/website", array_filter(['url' => $url, 'fallback_message' => $fallbackMessage], fn ($v) => $v !== null));
    }

    /** Replies only, to people who messaged this number. $session is "start" or "end". */
    public function sendWhatsApp(string $id, string $to, ?string $text = null, ?array $media = null, ?string $session = null): array
    {
        return $this->post("/v1/wa/channels/{$id}/send", ['to' => $to, 'text' => $text, 'media' => $media, 'session' => $session]);
    }

    public function whatsAppConversations(string $id): array
    {
        return $this->get("/v1/wa/channels/{$id}/conversations");
    }

    public function replyToWhatsAppConversation(string $conversationId, string $body): array
    {
        return $this->post("/v1/wa/conversations/{$conversationId}/reply", ['body' => $body]);
    }

    /** Ask the assistant a question. Nothing is sent or billed. */
    public function testWhatsAppAssistant(string $id, string $message, array $history = []): array
    {
        return $this->post("/v1/wa/channels/{$id}/test", ['message' => $message, 'history' => $history]);
    }

    /** Test workspaces only: pretend the QR code was scanned. */
    public function testWhatsAppScan(string $id): array
    {
        return $this->post("/v1/wa/channels/{$id}/test/scan", []);
    }

    /** Test workspaces only: a pretend customer message through your rules, your website and the assistant. */
    public function testWhatsAppInbound(string $id, string $text, string $fromPhone = '2348000000001', ?string $name = null): array
    {
        return $this->post("/v1/wa/channels/{$id}/test/inbound", ['text' => $text, 'from_phone' => $fromPhone, 'name' => $name]);
    }

    public function whatsAppPlans(): array
    {
        return $this->get('/v1/wa/plans');
    }

    public function whatsAppSubscription(): array
    {
        return $this->get('/v1/wa/subscription');
    }

    public function useWhatsAppPayPerReply(): array
    {
        return $this->post('/v1/wa/subscription', ['tier' => 'payg']);
    }

    public function whatsAppUsage(int $days = 30, ?string $workspaceId = null): array
    {
        return $this->get('/v1/wa/usage', ['days' => $days, 'channel_id' => $workspaceId]);
    }

    public function getWhatsAppBrand(): array
    {
        return $this->get('/v1/wa/brand');
    }

    /** Your default look for every connect page. Empty fields are cleared. */
    public function setWhatsAppBrand(?string $name = null, ?string $logoUrl = null, ?string $color = null): array
    {
        return $this->request('PUT', '/v1/wa/brand', ['name' => $name ?? '', 'logo_url' => $logoUrl ?? '', 'color' => $color]);
    }

    /** PNG, JPEG, WebP or SVG up to 5 MB. Pass a workspace id for one workspace only. */
    public function uploadWhatsAppLogo(string $filePath, ?string $workspaceId = null): array
    {
        $path = $workspaceId ? "/v1/wa/channels/{$workspaceId}/brand/logo" : '/v1/wa/brand/logo';
        return $this->postFiles($path, ['file' => $filePath]);
    }

    // --- address verification ---

    /** Start a check. Send the person `confirm_url` from the result so they can share their location. */
    public function verifyAddress(string $address, ?string $fullName = null, string $country = 'NG', int $radiusM = 150, ?string $reference = null, ?string $idempotencyKey = null): array
    {
        return $this->post('/v1/verify/address', ['address' => $address, 'full_name' => $fullName, 'country' => $country, 'radius_m' => $radiusM, 'reference' => $reference], $idempotencyKey);
    }

    public function getAddressVerification(string $id): array
    {
        return $this->get("/v1/verify/address/{$id}");
    }

    public function submitAddressLocation(string $id, float $latitude, float $longitude, ?float $accuracyM = null): array
    {
        return $this->post("/v1/verify/address/{$id}/location", ['latitude' => $latitude, 'longitude' => $longitude, 'accuracy_m' => $accuracyM]);
    }

    /** A utility bill or bank statement showing the address. */
    public function uploadAddressDocument(string $id, string $filePath): array
    {
        return $this->postFiles("/v1/verify/address/{$id}/document", ['file' => $filePath]);
    }

    // --- account ---

    /** Spend, top-ups, API calls and success rate for the last $days, compared with the period before. */
    public function accountOverview(int $days = 30): array
    {
        return $this->get('/v1/account/overview', ['days' => $days]);
    }

    /** Escape hatch for any endpoint not wrapped above. $path starts with /v1/. */
    public function call(string $method, string $path, ?array $body = null, array $query = []): array
    {
        $method = strtoupper($method);
        if ($method === 'GET') {
            return $this->get($path, $query);
        }
        return $this->request($method, $path, $body);
    }

    // --- file & image tools ---
    // Each returns the converted file as a binary string (PDF, DOCX, PNG, ZIP...) —
    // write it with file_put_contents(). ocr() returns the decoded JSON array.

    public function pdfToDocx(string $path): string { return $this->tool('pdf-to-docx', [['file', $path]]); }

    public function docxToPdf(string $path): string { return $this->tool('docx-to-pdf', [['file', $path]]); }

    /** Merges the PDFs in the order given. @param string[] $paths */
    public function pdfMerge(array $paths): string
    {
        return $this->tool('pdf-merge', array_map(fn ($p) => ['files', $p], $paths));
    }

    /** $pages e.g. "1-3,7,9-" */
    public function pdfSplit(string $path, string $pages, ?string $password = null): string
    {
        return $this->tool('pdf-split', [['file', $path]], ['pages' => $pages, 'password' => $password]);
    }

    public function pdfCompress(string $path, ?string $password = null): string
    {
        return $this->tool('pdf-compress', [['file', $path]], ['password' => $password]);
    }

    public function pdfWatermark(string $path, string $text, ?float $opacity = null, ?string $password = null): string
    {
        return $this->tool('pdf-watermark', [['file', $path]], ['text' => $text, 'opacity' => $opacity, 'password' => $password]);
    }

    public function pdfProtect(string $path, string $newPassword, ?string $password = null): string
    {
        return $this->tool('pdf-protect', [['file', $path]], ['new_password' => $newPassword, 'password' => $password]);
    }

    public function pdfUnlock(string $path, string $password): string
    {
        return $this->tool('pdf-unlock', [['file', $path]], ['password' => $password]);
    }

    public function ocr(string $path, ?string $password = null): array
    {
        return json_decode($this->tool('ocr', [['file', $path]], ['password' => $password]), true) ?? [];
    }

    /** Returns a transparent PNG. */
    public function removeBackground(string $path): string { return $this->tool('remove-background', [['file', $path]]); }

    public function imageResize(string $path, ?int $width = null, ?int $height = null, ?string $mode = null, ?string $outputFormat = null): string
    {
        return $this->tool('image-resize', [['file', $path]], ['width' => $width, 'height' => $height, 'mode' => $mode, 'output_format' => $outputFormat]);
    }

    public function imageConvert(string $path, string $targetFormat): string
    {
        return $this->tool('image-convert', [['file', $path]], ['target_format' => $targetFormat]);
    }

    public function imageCompress(string $path, ?int $quality = null): string
    {
        return $this->tool('image-compress', [['file', $path]], ['quality' => $quality]);
    }

    public function audioConvert(string $path, string $targetFormat): string
    {
        return $this->tool('audio-convert', [['file', $path]], ['target_format' => $targetFormat]);
    }

    /** One square image in, a ZIP of every iOS, Android and web icon size out. */
    public function appIcons(string $path): string { return $this->tool('app-icons', [['file', $path]]); }

    /** One image in, a ZIP of every Play Store / App Store size out. */
    public function storeAssets(string $path, ?string $suite = null): string
    {
        return $this->tool('store-assets', [['file', $path]], ['suite' => $suite]);
    }

    /**
     * Starts a full-submission batch. $slots maps a slot name (ios_iphone_6_3,
     * ios_ipad_13, ios_watch, android_phone, android_icon, ...) to a path or list
     * of paths. Poll storeAssetsBatchStatus(), then storeAssetsBatchDownload().
     *
     * @param array<string, string|string[]> $slots
     */
    public function storeAssetsBatchStart(array $slots): array
    {
        $parts = [];
        foreach ($slots as $slot => $paths) {
            foreach ((array) $paths as $path) {
                $parts[] = [$slot, $path];
            }
        }
        return json_decode($this->tool('store-assets/start', $parts), true) ?? [];
    }

    public function storeAssetsBatchStatus(string $jobId): array
    {
        return $this->get("/v1/tools/store-assets/{$jobId}");
    }

    public function storeAssetsBatchDownload(string $jobId): string
    {
        return $this->binary('GET', "/v1/tools/store-assets/{$jobId}/download");
    }

    /** Returns a PNG. */
    public function generateLogo(string $companyName, ?string $style = null, ?string $primaryColor = null, ?string $secondaryColor = null, ?int $size = null): string
    {
        $body = array_filter(['company_name' => $companyName, 'style' => $style, 'primary_color' => $primaryColor, 'secondary_color' => $secondaryColor, 'size' => $size], fn ($v) => $v !== null);
        return $this->binary('POST', '/v1/tools/generate-logo', json_encode($body, JSON_UNESCAPED_SLASHES), ['Content-Type: application/json']);
    }

    // --- billing ---
    // Top-ups, API-key management, domain verification and support tickets are
    // dashboard actions (they need a signed-in session, not an API key), so they
    // are not part of this server-side client.


    public function balance(): array
    {
        return $this->get('/v1/balance');
    }

    public function transactions(int $limit = 50, int $offset = 0): array
    {
        return $this->get('/v1/transactions', ['limit' => $limit, 'offset' => $offset]);
    }

    public function usage(array $filters = []): array
    {
        return $this->get('/v1/usage', $filters);
    }

    public function pricing(): array
    {
        return $this->get('/v1/pricing');
    }

    // --- internals ---

    private function headers(?string $idempotencyKey = null): array
    {
        $headers = ['X-IdentifyOrg-Key: ' . $this->apiKey];
        if ($idempotencyKey !== null) {
            $headers[] = 'Idempotency-Key: ' . $idempotencyKey;
        }
        return $headers;
    }

    private function post(string $path, array $body, ?string $idempotencyKey = null): array
    {
        return $this->request('POST', $path, array_filter($body, fn ($v) => $v !== null), $idempotencyKey);
    }

    private function patch(string $path, array $body): array
    {
        return $this->request('PATCH', $path, array_filter($body, fn ($v) => $v !== null));
    }

    private function delete(string $path): void
    {
        $this->request('DELETE', $path);
    }

    private function get(string $path, array $params = []): array
    {
        $params = array_filter($params, fn ($v) => $v !== null);
        $query = $params ? '?' . http_build_query($params) : '';
        return $this->request('GET', $path . $query);
    }

    /** @param array<string, string> $files field name => filesystem path */
    private function postFiles(string $path, array $files, ?string $idempotencyKey = null): array
    {
        $fields = [];
        foreach ($files as $field => $filePath) {
            $fields[$field] = new \CURLFile($filePath);
        }

        // No Content-Type header here — curl sets the multipart boundary
        // itself when CURLOPT_POSTFIELDS is an array (not a JSON string).
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $this->headers($idempotencyKey),
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            throw new IdentifyOrgApiError(0, 'connection_error', $error);
        }
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $data = json_decode($raw, true) ?? [];
        if ($status >= 300) {
            $err = $data['error'] ?? [];
            throw new IdentifyOrgApiError($status, $err['code'] ?? null, $err['message'] ?? "HTTP {$status}");
        }
        return $data;
    }

    /**
     * POSTs multipart to /v1/tools/<slug>. A field name may repeat (several
     * files under one field), which curl's array form cannot do, so the body is
     * assembled by hand.
     *
     * @param array<int, array{0: string, 1: string}> $files [field, path] pairs
     */
    private function tool(string $slug, array $files, array $fields = []): string
    {
        $boundary = '----identifyorg' . bin2hex(random_bytes(12));
        $body = '';
        foreach ($files as [$field, $path]) {
            $content = @file_get_contents($path);
            if ($content === false) {
                throw new \InvalidArgumentException("IdentifyOrg: cannot read file {$path}");
            }
            $body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"{$field}\"; filename=\"" . basename($path) . "\"\r\nContent-Type: application/octet-stream\r\n\r\n{$content}\r\n";
        }
        foreach ($fields as $name => $value) {
            if ($value !== null) {
                $body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"{$name}\"\r\n\r\n{$value}\r\n";
            }
        }
        $body .= "--{$boundary}--\r\n";
        return $this->binary('POST', "/v1/tools/{$slug}", $body, ["Content-Type: multipart/form-data; boundary={$boundary}"]);
    }

    /** Performs a request and returns the raw response body, raising on an error status. */
    private function binary(string $method, string $path, ?string $body = null, array $extraHeaders = []): string
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array_merge($this->headers(), $extraHeaders),
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
        ]);
        $raw = curl_exec($ch);
        if ($raw === false) {
            throw new IdentifyOrgApiError(0, 'connection_error', curl_error($ch));
        }
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($status >= 300) {
            $err = (json_decode($raw, true) ?? [])['error'] ?? [];
            throw new IdentifyOrgApiError($status, $err['code'] ?? null, $err['message'] ?? "HTTP {$status}");
        }
        return (string) $raw;
    }

    private function request(string $method, string $path, ?array $body = null, ?string $idempotencyKey = null): array
    {
        $headers = $this->headers($idempotencyKey);
        $jsonBody = null;
        if ($body !== null) {
            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
            $headers[] = 'Content-Type: application/json';
        }

        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            throw new IdentifyOrgApiError(0, 'connection_error', $error);
        }
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // No curl_close() — CurlHandle is an object since PHP 8.0 and is
        // garbage-collected automatically; calling curl_close() explicitly
        // is a no-op that PHP 8.5+ now emits a deprecation notice for.

        $data = json_decode($raw, true) ?? [];
        if ($status >= 300) {
            $err = $data['error'] ?? [];
            throw new IdentifyOrgApiError($status, $err['code'] ?? null, $err['message'] ?? "HTTP {$status}");
        }
        return $data;
    }
}
