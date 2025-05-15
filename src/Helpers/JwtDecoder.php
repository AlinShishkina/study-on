<?php

namespace App\Helpers;

class JwtDecoder
{
    public static function decode($token)
    {
        $parts = explode('.', $token);
        $payload = json_decode(base64_decode($parts[1]), true);
        
        return [
            'exp' => $payload['exp'],
            'iat' => $payload['iat'],
            'email' => $payload['username'],
            'roles' => $payload['roles']
        ];
    }
}