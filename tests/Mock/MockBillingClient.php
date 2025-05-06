<?php

namespace App\Tests\Mock;

use App\Service\BillingClient;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class MockBillingClient extends BillingClient
{
    public function __construct()
    {
        // Не вызываем родительский конструктор, если он не нужен
    }
    
    private array $registeredUsers = [
        'user@mail.ru' => 'password',
        'admin@mail.ru' => 'password'
    ];

    private string $userEmail = 'admin@mail.ru';
    private string $adminEmail = 'user@mail.ru';

    // Сигнатура совпадает с базовым классом
    public function authenticate(array $credentials): array
    {
        $username = $credentials['username'] ?? '';
        $password = $credentials['password'] ?? '';

        if (isset($this->registeredUsers[$username]) && $this->registeredUsers[$username] === $password) {
            $roles = ['ROLE_SUPER_ADMIN'];
            //$roles = $username === $this->adminEmail ? ['ROLE_SUPER_ADMIN'] : ['ROLE_USER'];
            $tokenPayload = [
                'email' => $username,
                'iat' => (new \DateTime('now'))->getTimestamp(),
                'exp' => (new \DateTime('+1 hour'))->getTimestamp(),
                'roles' => $roles,
            ];
            $token = base64_encode(json_encode($tokenPayload, JSON_THROW_ON_ERROR));

            return ['token' => "header.$token.verifySignature"];
        }

        throw new CustomUserMessageAuthenticationException('Invalid credentials.');
    }

    public function registration(array $credentials): array
    {
        $username = $credentials['username'] ?? '';

        if (isset($this->registeredUsers[$username])) {
            return [
                'code' => 401,
                'errors' => ['unique' => 'Пользователь с такой электронной почтой уже существует!']
            ];
        }

        $this->registeredUsers[$username] = $credentials['password'];
        $tokenPayload = [
            'email' => $username,
            'iat' => (new \DateTime('now'))->getTimestamp(),
            'exp' => (new \DateTime('+1 hour'))->getTimestamp(),
            'roles' => ['ROLE_USER'],
        ];
        $token = base64_encode(json_encode($tokenPayload, JSON_THROW_ON_ERROR));
        return ['token' => "header.$token.verifySignature"];
    }

    public function getCurrentUser(string $token): array
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                throw new \Exception();
            }

            $payload = json_decode(base64_decode($parts[1]), true, 512, JSON_THROW_ON_ERROR);
            return [
                'balance' => 1000.0,
                'roles' => $payload['roles'],
                'username' => $payload['email'],
                'code' => 200
            ];
        } catch (\Exception $e) {
            return ['code' => 401, 'message' => 'Invalid JWT Token'];
        }
    }
}
