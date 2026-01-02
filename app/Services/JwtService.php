<?php

namespace App\Services;

class JwtService
{
    public static function encode(array $payload): string
    {
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

        if ($data['exp'] < time()) {
            throw new \Exception('Token expired');
        }

        return $data;
    }
}
