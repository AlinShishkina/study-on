<?php

namespace App\Controller;

use App\Exception\BillingUnavailableException;
use App\Form\UserRegistrationType;
use App\Security\BillingAuthenticator;
use App\Security\User;
use App\Service\BillingClient;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class BillingAuthenticatorController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_course_index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException(
            'This method can be blank - it will be intercepted by the logout key on your firewall.'
        );
    }

    #[Route(path: '/registration', name: 'app_registration')]
    public function registration(
        Request $request,
        UserAuthenticatorInterface $authenticator,
        BillingAuthenticator $formAuthenticator
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_course_index');
        }

        $form = $this->createForm(UserRegistrationType::class);
        $form->handleRequest($request);

        $errors = [];

        if ($request->get('errors') || ($form->isSubmitted() && !$form->isValid())) {
            $requestErrors = $request->get('errors', []);

            foreach ($form->getErrors(true, false) as $error) {
                foreach ($error as $formError) {
                    foreach ($formError as $e) {
                        $errors[] = $e->getMessage();
                    }
                }
            }

            return $this->render('security/registration.html.twig', [
                'registrationForm' => $form->createView(),
                'errors' => array_merge(
                    $requestErrors,
                    $errors
                ),
            ]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $credentials = json_encode([
                'username' => $form["email"]->getData(),
                'password' => $form["password"]->getData(),
            ]);

            $billingClient = new BillingClient();

            try {
                $response = $billingClient->registraton($credentials);

                if (isset($response['errors'])) {
                    $errors = $response['errors'];
                    throw new BillingUnavailableException("Невалидные данные.", 1);
                }

                $user = new User();
                $user->setApiToken($response['token']);
                $user->setRefreshToken($response['refresh_token']);

                $userResponse = $billingClient->getCurrentUser($response['token']);

                $user->setRoles($userResponse['roles']);
                $user->setBalance($userResponse['balance']);
                $user->setEmail($userResponse['username']);

                return $authenticator->authenticateUser(
                    $user,
                    $formAuthenticator,
                    $request
                );
            } catch (BillingUnavailableException | JsonException $e) {
                $error = 'Произошла ошибка во время регистрации: ' . $e->getMessage();
            } catch (\Exception $e) {
                $error = 'Произошла непредвиденная ошибка. Подробнее: ' . $e->getMessage();
            } finally {
                if (isset($error)) {
                    $errors[] = $error;
                }
            }
        }

        return $this->render(
            'security/registration.html.twig',
            [
                'registrationForm' => $form->createView(),
                'errors' => $errors
            ]
        );
    }
}