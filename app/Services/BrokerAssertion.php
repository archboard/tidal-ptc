<?php

namespace App\Services;

use RuntimeException;

/**
 * Verifies the short-lived Ed25519 JWT the auth broker signs for an install.
 */
class BrokerAssertion
{
    private const SKEW_SECONDS = 30;

    /**
     * Returns the claims of a valid assertion or throws.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public function verify(string $jwt, string $expectedOrigin): array
    {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            throw new RuntimeException('Malformed assertion.');
        }

        [$head, $body, $signature] = $parts;
        $header = $this->decodeJson($head);

        if (($header['alg'] ?? null) !== 'EdDSA') {
            throw new RuntimeException('Unsupported assertion algorithm.');
        }

        $kid = $header['kid'] ?? null;
        $key = is_string($kid) ? config('services.auth_broker.public_keys.'.$kid) : null;
        $publicKey = is_string($key) ? base64_decode($key, true) : false;

        if ($publicKey === false || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new RuntimeException('Unknown assertion key.');
        }

        $rawSignature = $this->decodeBase64Url($signature);

        if (strlen($rawSignature) !== SODIUM_CRYPTO_SIGN_BYTES
            || ! sodium_crypto_sign_verify_detached($rawSignature, $head.'.'.$body, $publicKey)) {
            throw new RuntimeException('Invalid assertion signature.');
        }

        $claims = $this->decodeJson($body);

        if (! is_string($claims['aud'] ?? null) || ! hash_equals($expectedOrigin, $claims['aud'])) {
            throw new RuntimeException('Assertion audience mismatch.');
        }

        if (! is_string($claims['app'] ?? null) || ! hash_equals((string) config('services.auth_broker.app'), $claims['app'])) {
            throw new RuntimeException('Assertion app mismatch.');
        }

        $now = now()->getTimestamp();

        if (! is_int($claims['exp'] ?? null) || $claims['exp'] < $now - self::SKEW_SECONDS) {
            throw new RuntimeException('Assertion expired.');
        }

        if (! is_int($claims['iat'] ?? null) || $claims['iat'] > $now + self::SKEW_SECONDS) {
            throw new RuntimeException('Assertion issued in the future.');
        }

        return $claims;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $segment): array
    {
        $data = json_decode($this->decodeBase64Url($segment), true);

        if (! is_array($data)) {
            throw new RuntimeException('Malformed assertion.');
        }

        return $data;
    }

    private function decodeBase64Url(string $value): string
    {
        $decoded = preg_match('/^[A-Za-z0-9_-]+$/', $value)
            ? base64_decode(strtr($value, '-_', '+/'), true)
            : false;

        if ($decoded === false) {
            throw new RuntimeException('Malformed assertion.');
        }

        return $decoded;
    }
}
