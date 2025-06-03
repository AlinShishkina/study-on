<?php

namespace App\Tests\Mock;

use App\Service\BillingClient;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BillingClientMock extends BillingClient
{
    private string $userEmail = 'user@email.example';
    private string $adminEmail = 'user_admin@email.example';

    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        parent::__construct();
        $this->tokenStorage = $tokenStorage;
    }

    public function authenticate(string $credentials): array
    {
        $arrayedCredentials = json_decode($credentials, true, 512, JSON_THROW_ON_ERROR);

        if (
            ($arrayedCredentials['username'] === $this->adminEmail
            || $arrayedCredentials['username'] === $this->userEmail)
            && $arrayedCredentials['password'] === $arrayedCredentials['username']
        ) {
            $roles = $arrayedCredentials['username'] === $this->adminEmail ? ['ROLE_SUPER_ADMIN'] : ['ROLE_USER'];

            $token = $this->getToken($arrayedCredentials['username'], $roles);
            $response['token'] = "header.$token.verifySignature";
            $response['refresh_token'] = "header.$token.verifySignature";

            return $response;
        }

        return ['code' => 401, "message" => "Invalid credentials."];
    }

    public function registration(string $credentials): array
    {
        $arrayedCredentials = json_decode($credentials, true, 512, JSON_THROW_ON_ERROR);

        if (
            $arrayedCredentials['username'] === $this->adminEmail
            || $arrayedCredentials['username'] === $this->userEmail
        ) {
            $response['code'] = 401;
            $response['errors']['username'] = 'Пользователь с такой электронной почтой уже существует!';
            return $response;
        }

        $token = $this->getToken($arrayedCredentials['username']);

        $response['refresh_token'] = "header.$token.verifySignature";
        $response['token'] = "header.$token.verifySignature";

        return $response;
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
                'balance' => 1005.0,
                'roles' => $payload['roles'],
                'username' => $payload['username'],
                'code' => 200,
            ];
        } catch (\Exception $e) {
            return ['code' => 401, 'message' => 'Invalid JWT Token'];
        }
    }

    private function getToken($username, $roles = ['ROLE_USER']): string
    {
        return base64_encode(json_encode([
            'username' => $username,
            'iat' => (new \DateTime('now'))->getTimestamp(),
            'exp' => (new \DateTime('+1 hour'))->getTimestamp(),
            'roles' => $roles,
        ], JSON_THROW_ON_ERROR));
    }

    public function refresh(string $refreshToken): array
    {
        $user = $this->tokenStorage->getToken()->getUser();

        $token = base64_encode(json_encode([
            'username' => $user->getUserIdentifier(),
            'iat' => (new \DateTime('now'))->getTimestamp(),
            'exp' => (new \DateTime('+1 hour'))->getTimestamp(),
            'roles' => $user->getRoles(),
        ], JSON_THROW_ON_ERROR));

        $response['token'] = "header." . $token . ".verifySignature";
        $response['refresh_token'] = 'refresh_token';
        return $response;
    }

    public function transactions(string $token, ?array $filter = null): array
    {
        $transactions = [
            [
                'id' => 1,
                "create_at" => date('Y-m-d\TH:i:s', time() - 2 * 24 * 60 * 60),
                "type" => "payment",
                "course_code" => "php-gold",
                "amount" => 2500,
                'expires_at' => null,
            ],
            [
                'id' => 2,
                "create_at" => date('Y-m-d\TH:i:s', time()),
                "type" => "payment",
                "course_code" => "js",
                "expires_at" => date('Y-m-d\TH:i:s', time() + 7 * 24 * 60 * 60),
                "amount" => 1000,
            ],
            [
                'id' => 3,
                "create_at" => date('Y-m-d\TH:i:s', time() - 5 * 24 * 60 * 60),
                "type" => "deposit",
                "amount" => 1000000,
            ],
        ];

        if ($filter === null) {
            return $transactions;
        }

        return array_filter($transactions, function ($transaction) use ($filter) {
            if (isset($filter['course_code']) && (!isset($transaction['course_code']) || $transaction['course_code'] !== $filter['course_code'])) {
                return false;
            }

            if (isset($filter['skip_expired']) && isset($transaction['expires_at'])
                && new \DateTime($transaction['expires_at']) <= new \DateTime('now')
            ) {
                return false;
            }

            if (isset($filter['type']) && $transaction['type'] !== $filter['type']) {
                return false;
            }

            return true;
        });
    }

    public function courses(): array
    {
        return [
            [
                'code' => 'php',
                'type' => 'free',
            ],
            [
                'code' => 'js',
                'type' => 'rent',
                'price' => 1000,
            ],
            [
                'code' => 'ruby',
                'type' => 'rent',
                'price' => 250,
            ],
            [
                'code' => 'swift',
                'type' => 'buy',
                'price' => 2500,
            ],
        ];
    }

    public function course($code): array
    {
        $courses = $this->courses();

        foreach ($courses as $course) {
            if ($course['code'] == $code) {
                return $course;
            }
        }

        return [
            'code' => 401,
            'errors' => [
                'course' => 'Не найден курс с данным кодом.',
            ],
        ];
    }

    public function payment(string $token, string $code): array
    {
        $user = $this->getCurrentUser($token);
        $course = $this->course($code);

        if (!isset($course['code'])) {
            return $course;
        }

        if ($course['type'] === 'free') {
            return [
                'code' => 400,
                'errors' => [
                    'course' => 'Курс бесплатный. Оплата не требуется.',
                ],
            ];
        } else {
            $transactions = $this->transactions($token, [
                'course_code' => $code,
                'skip_expired' => true,
            ]);

            if (count($transactions) !== 0) {
                return [
                    'code' => 400,
                    'errors' => [
                        'course' => 'Доступ к курсу актуален. Оплата не требуется.',
                    ],
                ];
            }
            if ($user['balance'] < $course['price']) {
                return [
                    'code' => 406,
                    'errors' => [
                        'payment' => 'На вашем счету недостаточно средств.',
                    ],
                ];
            }

            $result = [
                'id' => 1,
                "create_at" => date('Y-m-d\TH:i:s', time()),
                "type" => "payment",
                "course_code" => $course['code'],
                "amount" => 2500,
                'expires_at' => null,
            ];

            if ($course['type'] == 'rent') {
                $result['expires_at'] = date('Y-m-d\TH:i:s', time() + 432000);
            }

            return $result;
        }
    }
}
