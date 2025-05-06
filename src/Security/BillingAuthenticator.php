<?php

namespace App\Security;

use App\Service\BillingClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BillingAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private UrlGeneratorInterface $urlGenerator;
    private BillingClient $billingClient;

    public function __construct(UrlGeneratorInterface $urlGenerator, BillingClient $billingClient)
    {
        $this->urlGenerator = $urlGenerator;
        $this->billingClient = $billingClient;
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $csrfToken = $request->request->get('_csrf_token');

        if (!$email || !$password) {
            throw new CustomUserMessageAuthenticationException('Необходимо ввести email и пароль.');
        }

        $credentials = ['username' => $email, 'password' => $password];

        $userLoader = function ($userIdentifier) use ($credentials) {
            return $this->loadUser($credentials);
        };

        return new SelfValidatingPassport(
            new UserBadge($email, $userLoader)
            // Можно добавить CsrfTokenBadge, если нужно
        );
    }

    private function loadUser(array $credentials): UserInterface
    {
        try {
            $response = $this->billingClient->authenticate($credentials);

            if (isset($response['code']) && $response['code'] !== 200) {
                throw new CustomUserMessageAuthenticationException($response['message'] ?? 'Ошибка аутентификации');
            }

            $token = $response['token'] ?? null;
            if (!$token) {
                throw new CustomUserMessageAuthenticationException('Токен не получен от биллинга');
            }

            $userResponse = $this->billingClient->getCurrentUser($token);

            $user = new User();
            $user->setApiToken($token);
            $user->setRoles($userResponse['roles'] ?? []);
            $user->setBalance($userResponse['balance'] ?? 0);
            $user->setEmail($userResponse['username'] ?? '');

            return $user;
        } catch (\Exception $e) {
            throw new AuthenticationException('Ошибка авторизации: ' . $e->getMessage());
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);

        if ($targetPath) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_profile'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
