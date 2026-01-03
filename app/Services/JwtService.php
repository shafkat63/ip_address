<?php

namespace App\Services;

class JwtService
{
    /**
     * Encode a payload into a JWT
     */
    public static function encode(array $payload, int $ttl = 3600): string
    {
        // Add expiration if not set
        if (!isset($payload['exp'])) {
            $payload['exp'] = time() + $ttl;
        }

        // Add unique session ID if not set
        if (!isset($payload['jti'])) {
            $payload['jti'] = uniqid('', true);
        }

        $header = base64_encode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT'
        ]));

        $payload = base64_encode(json_encode($payload));

        $signature = hash_hmac(
            'sha256',
            "$header.$payload",
            config('app.jwt_secret'),
            true
        );

        return "$header.$payload." . base64_encode($signature);
    }

    /**
     * Decode a JWT and return the payload
     */
    public static function decode(string $jwt): array
    {
        [$header, $payload, $signature] = explode('.', $jwt);

        $expected = base64_encode(
            hash_hmac(
                'sha256',
                "$header.$payload",
                config('app.jwt_secret'),
                true
            )
        );

        if (!hash_equals($expected, $signature)) {
            throw new \Exception('Invalid signature');
        }

        $data = json_decode(base64_decode($payload), true);

        // Optional: treat missing exp as invalid
        if (!isset($data['exp']) || $data['exp'] < time()) {
            throw new \Exception('Token expired or missing exp');
        }

        return $data;
    }
}
