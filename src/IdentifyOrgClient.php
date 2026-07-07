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

    // --- verification ---

    public function verifyBvn(string $bvn, ?string $firstName = null, ?string $lastName = null, ?string $idempotencyKey = null): array
    {
        return $this->post('/v1/verify/bvn', ['bvn' => $bvn, 'first_name' => $firstName, 'last_name' => $lastName], $idempotencyKey);
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

    // --- billing ---

    public function topup(float $amount): array
    {
        return $this->post('/v1/payments/topup', ['amount' => $amount]);
    }

    public function payments(int $limit = 50): array
    {
        return $this->get('/v1/payments', ['limit' => $limit]);
    }

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
