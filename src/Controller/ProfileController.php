<?php

namespace App\Controller;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    public function __construct(
        private BillingClient $billingClient,
    ) {
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'user_role' => in_array('ROLE_SUPER_ADMIN', $user->getRoles()) ? 'Администратор' : 'Пользователь'
        ]);
    }
}