<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HttpSignatureService
{
    /**
     * Verify an inbound ActivityPub-style HTTP Signature request.
     *
     * @return array{valid: bool, error?: string, actor_uri?: string, key_id?: string}
     */
    public function verifyRequest(Request $request): array
    {
        $signatureHeader = $request->header('Signature');
        if (! is_string($signatureHeader) || trim($signatureHeader) === '') {
            return ['valid' => false, 'error' => 'Missing Signature header'];
        }

        $dateHeader = $request->header('Date');
        if (! is_string($dateHeader) || ! $this->dateHeaderIsFresh($dateHeader)) {
            return ['valid' => false, 'error' => 'Stale or missing Date header'];
        }

        $body = $request->getContent();
        if (! $this->digestMatches($body, $request->header('Digest'))) {
            return ['valid' => false, 'error' => 'Invalid Digest header'];
        }

        $signatureParams = $this->parseSignatureHeader($signatureHeader);
        $keyId = $signatureParams['keyId'] ?? null;
        $signature = $signatureParams['signature'] ?? null;
        $algorithm = strtolower($signatureParams['algorithm'] ?? '');
        $headers = preg_split('/\s+/', trim($signatureParams['headers'] ?? ''));

        if (! is_string($keyId) || trim($keyId) === '' || ! is_string($signature) || trim($signature) === '') {
            return ['valid' => false, 'error' => 'Malformed Signature header'];
        }

        if (! in_array($algorithm, ['rsa-sha256', 'hs2019'], true)) {
            return ['valid' => false, 'error' => 'Unsupported signature algorithm'];
        }

        if (! is_array($headers) || $headers === []) {
            return ['valid' => false, 'error' => 'Signature headers list is required'];
        }

        $publicKey = $this->resolveRemotePublicKey($keyId);
        if (! $publicKey['valid']) {
            return $publicKey;
        }

        $payload = json_decode($body, true);
        $payloadActor = is_array($payload) ? ($payload['actor'] ?? null) : null;
        if (is_string($payloadActor) && ($publicKey['actor_uri'] ?? null) !== $payloadActor) {
            return ['valid' => false, 'error' => 'Signature actor does not match payload actor'];
        }

        $signatureString = $this->buildSignatureString($request, $headers);
        if ($signatureString === null) {
            return ['valid' => false, 'error' => 'Signed header missing from request'];
        }

        $decodedSignature = base64_decode($signature, true);
        if ($decodedSignature === false) {
            return ['valid' => false, 'error' => 'Signature value is not valid base64'];
        }

        $publicKeyResource = openssl_pkey_get_public($publicKey['public_key']);
        if ($publicKeyResource === false) {
            return ['valid' => false, 'error' => 'Remote public key could not be loaded'];
        }

        $verification = openssl_verify($signatureString, $decodedSignature, $publicKeyResource, OPENSSL_ALGO_SHA256);

        if ($verification !== 1) {
            return ['valid' => false, 'error' => 'Signature verification failed'];
        }

        return [
            'valid' => true,
            'actor_uri' => $publicKey['actor_uri'],
            'key_id' => $keyId,
        ];
    }

    /**
     * Build outbound HTTP Signature headers for an ActivityPub request.
     *
     * @return array<string, string>
     */
    public function buildSignedHeaders(
        string $keyId,
        string $privateKeyPem,
        string $method,
        string $url,
        string $body,
        string $contentType = 'application/activity+json'
    ): array {
        $date = now()->toRfc7231String();
        $digest = 'SHA-256='.base64_encode(hash('sha256', $body, true));
        $headers = ['(request-target)', 'host', 'date', 'digest', 'content-type'];
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);

        if (! is_string($host) || $host === '') {
            throw new RuntimeException('Cannot sign request without a valid host.');
        }

        if (is_int($port)) {
            $host .= ":{$port}";
        }

        if (is_string($query) && $query !== '') {
            $path .= "?{$query}";
        }

        $signatureString = $this->buildOutboundSignatureString(
            strtolower($method),
            $path,
            $host,
            $date,
            $digest,
            $contentType,
            $headers
        );

        $privateKey = openssl_pkey_get_private($privateKeyPem);
        if ($privateKey === false || ! openssl_sign($signatureString, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Unable to sign outbound ActivityPub request.');
        }

        return [
            'Accept' => 'application/activity+json, application/ld+json',
            'Content-Type' => $contentType,
            'Date' => $date,
            'Digest' => $digest,
            'Signature' => sprintf(
                'keyId="%s",algorithm="rsa-sha256",headers="%s",signature="%s"',
                $keyId,
                implode(' ', $headers),
                base64_encode($signature)
            ),
        ];
    }

    protected function dateHeaderIsFresh(string $dateHeader): bool
    {
        try {
            $requestDate = Carbon::parse($dateHeader)->utc();
        } catch (\Throwable) {
            return false;
        }

        return abs($requestDate->diffInSeconds(now()->utc(), false)) <= 300;
    }

    protected function digestMatches(string $body, mixed $digestHeader): bool
    {
        if (! is_string($digestHeader) || trim($digestHeader) === '') {
            return false;
        }

        $expectedDigest = base64_encode(hash('sha256', $body, true));

        foreach (explode(',', $digestHeader) as $digestPart) {
            [$algorithm, $value] = array_pad(explode('=', trim($digestPart), 2), 2, null);

            if (strtoupper((string) $algorithm) !== 'SHA-256' || ! is_string($value)) {
                continue;
            }

            return hash_equals($expectedDigest, $value);
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    protected function parseSignatureHeader(string $signatureHeader): array
    {
        preg_match_all('/(\w+)="([^"]*)"/', $signatureHeader, $matches, PREG_SET_ORDER);

        $parsed = [];

        foreach ($matches as $match) {
            $parsed[$match[1]] = $match[2];
        }

        return $parsed;
    }

    /**
     * @param  array<int, string>  $headers
     */
    protected function buildSignatureString(Request $request, array $headers): ?string
    {
        $lines = [];

        foreach ($headers as $headerName) {
            $normalizedHeader = strtolower(trim($headerName));

            if ($normalizedHeader === '') {
                continue;
            }

            $value = match ($normalizedHeader) {
                '(request-target)' => strtolower($request->getMethod()).' '.$request->getRequestUri(),
                'host' => $request->getHttpHost(),
                default => $request->headers->get($normalizedHeader),
            };

            if (! is_string($value) || trim($value) === '') {
                return null;
            }

            $lines[] = $normalizedHeader.': '.$value;
        }

        return $lines === [] ? null : implode("\n", $lines);
    }

    /**
     * @param  array<int, string>  $headers
     */
    protected function buildOutboundSignatureString(
        string $method,
        string $requestUri,
        string $host,
        string $date,
        string $digest,
        string $contentType,
        array $headers
    ): string {
        $headerValues = [
            '(request-target)' => $method.' '.$requestUri,
            'host' => $host,
            'date' => $date,
            'digest' => $digest,
            'content-type' => $contentType,
        ];

        return collect($headers)
            ->map(function (string $header) use ($headerValues): string {
                $normalizedHeader = strtolower(trim($header));

                return $normalizedHeader.': '.$headerValues[$normalizedHeader];
            })
            ->implode("\n");
    }

    /**
     * @return array{valid: bool, error?: string, actor_uri?: string, public_key?: string}
     */
    protected function resolveRemotePublicKey(string $keyId): array
    {
        $documentUrl = strtok($keyId, '#') ?: $keyId;

        $response = Http::accept('application/activity+json')
            ->withHeaders(['Accept' => 'application/activity+json, application/ld+json'])
            ->timeout(5)
            ->get($documentUrl);

        if (! $response->successful()) {
            return ['valid' => false, 'error' => 'Remote actor document could not be fetched'];
        }

        $document = $response->json();
        if (! is_array($document)) {
            return ['valid' => false, 'error' => 'Remote actor document is invalid'];
        }

        $publicKey = $document['publicKey'] ?? null;
        if (! is_array($publicKey)) {
            return ['valid' => false, 'error' => 'Remote actor document has no public key'];
        }

        $publicKeyPem = $publicKey['publicKeyPem'] ?? null;
        $resolvedKeyId = $publicKey['id'] ?? null;
        $actorUri = $publicKey['owner'] ?? ($document['id'] ?? null);

        if (! is_string($publicKeyPem) || trim($publicKeyPem) === '' || ! is_string($actorUri) || trim($actorUri) === '') {
            return ['valid' => false, 'error' => 'Remote actor public key is incomplete'];
        }

        if (is_string($resolvedKeyId) && $resolvedKeyId !== $keyId) {
            return ['valid' => false, 'error' => 'Remote actor key ID mismatch'];
        }

        return [
            'valid' => true,
            'actor_uri' => $actorUri,
            'public_key' => $publicKeyPem,
        ];
    }
}
