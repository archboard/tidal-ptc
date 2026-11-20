<?php

use App\Services\BrokerAssertion;

const BROKER_ORIGIN = 'https://district.example.com';

function brokerB64(string $raw): string
{
    return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
}

/**
 * @param  array<string, mixed>  $claims
 * @param  array<string, mixed>  $header
 */
function brokerToken(string $secretKey, array $claims = [], array $header = []): string
{
    $head = brokerB64(json_encode($header + ['alg' => 'EdDSA', 'typ' => 'JWT', 'kid' => 'k1']));
    $body = brokerB64(json_encode($claims + [
        'iss' => 'https://broker.test',
        'aud' => BROKER_ORIGIN,
        'app' => 'tidal-ptc',
        'iat' => now()->getTimestamp(),
        'exp' => now()->getTimestamp() + 60,
        'email' => 'teacher@example.com',
    ]));

    return "$head.$body.".brokerB64(sodium_crypto_sign_detached("$head.$body", $secretKey));
}

beforeEach(function () {
    $pair = sodium_crypto_sign_keypair();
    $this->secretKey = sodium_crypto_sign_secretkey($pair);
    config(['services.auth_broker.public_keys' => ['k1' => base64_encode(sodium_crypto_sign_publickey($pair))]]);
});

it('accepts a valid assertion and returns its claims', function () {
    $claims = (new BrokerAssertion)->verify(brokerToken($this->secretKey), BROKER_ORIGIN);

    expect($claims['email'])->toBe('teacher@example.com');
});

it('rejects a tampered signature', function () {
    $token = brokerToken($this->secretKey);
    $token = substr($token, 0, -2).(str_ends_with($token, 'AA') ? 'BB' : 'AA');

    (new BrokerAssertion)->verify($token, BROKER_ORIGIN);
})->throws(RuntimeException::class);

it('rejects a tampered body', function () {
    [$head, , $sig] = explode('.', brokerToken($this->secretKey));
    $body = brokerB64(json_encode(['aud' => BROKER_ORIGIN, 'app' => 'tidal-ptc', 'iat' => now()->getTimestamp(), 'exp' => now()->getTimestamp() + 60, 'email' => 'admin@example.com']));

    (new BrokerAssertion)->verify("$head.$body.$sig", BROKER_ORIGIN);
})->throws(RuntimeException::class);

it('rejects an unknown kid', function () {
    (new BrokerAssertion)->verify(brokerToken($this->secretKey, header: ['kid' => 'other']), BROKER_ORIGIN);
})->throws(RuntimeException::class, 'Unknown assertion key.');

it('rejects a non-EdDSA alg', function () {
    (new BrokerAssertion)->verify(brokerToken($this->secretKey, header: ['alg' => 'HS256']), BROKER_ORIGIN);
})->throws(RuntimeException::class, 'Unsupported assertion algorithm.');

it('rejects an expired assertion', function () {
    $token = brokerToken($this->secretKey);

    $this->travel(2)->minutes();

    (new BrokerAssertion)->verify($token, BROKER_ORIGIN);
})->throws(RuntimeException::class, 'Assertion expired.');

it('rejects an assertion issued in the future', function () {
    $iat = now()->getTimestamp() + 120;

    (new BrokerAssertion)->verify(brokerToken($this->secretKey, ['iat' => $iat, 'exp' => $iat + 60]), BROKER_ORIGIN);
})->throws(RuntimeException::class, 'Assertion issued in the future.');

it('rejects the wrong audience', function () {
    (new BrokerAssertion)->verify(brokerToken($this->secretKey, ['aud' => 'https://other.example.com']), BROKER_ORIGIN);
})->throws(RuntimeException::class, 'Assertion audience mismatch.');

it('rejects the wrong app', function () {
    (new BrokerAssertion)->verify(brokerToken($this->secretKey, ['app' => 'other-app']), BROKER_ORIGIN);
})->throws(RuntimeException::class, 'Assertion app mismatch.');

it('rejects a malformed token', function () {
    (new BrokerAssertion)->verify('not-a-jwt', BROKER_ORIGIN);
})->throws(RuntimeException::class, 'Malformed assertion.');
