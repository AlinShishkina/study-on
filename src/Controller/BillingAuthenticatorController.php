<?php

namespace App\Controller;

use App\Security\BillingAuthenticator;
use App\Security\User;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Form\UserRegistrationType;

class BillingAuthenticatorController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $utils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        $error = $utils->getLastAuthenticationError();
        $lastUsername = $utils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'csrf_token' => $this->container->get('security.csrf.token_manager')->getToken('authenticate')->getValue()
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Logout is handled by Symfony.');
    }

    #[Route('/registration', name: 'app_registration')]
    public function register(
        Request $request,
        UserAuthenticatorInterface $userAuthenticator,
        BillingAuthenticator $billingAuthenticator,
        BillingClient $billingClient
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        $form = $this->createForm(UserRegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->get('password')->getData();
            $confirm = $form->get('password')->get('second')->getData();

            if (strlen($password) < 6) {
                $form->get('password')->addError(new FormError('Пароль должен быть не менее 6 символов.'));
            }

            if ($password !== $confirm) {
                $form->get('password')->get('second')->addError(new FormError('Пароли не совпадают.'));
            }

            if ($form->isValid()) {
                $userData = [
                    'email' => $form->get('email')->getData(),
                    'password' => $password,
                ];

                try {
                    $user = $billingClient->registration(json_encode($userData));

                    if ($user instanceof User) {
                        $userAuthenticator->authenticateUser(
                            $user,
                            $billingAuthenticator,
                            $request
                        );

                        return $this->redirectToRoute('app_profile');
                    } else {
                        foreach ($user as $errorMsg) {
                            $form->addError(new FormError($errorMsg));
                        }
                    }
                } catch (\Throwable $e) {
                    $form->addError(new FormError('Ошибка регистрации: ' . $e->getMessage()));
                }
            }
        }

        return $this->render('security/registration.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
