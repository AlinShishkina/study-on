<?php

namespace App\Tests\Helpers;

use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

trait AuthHelper
{
    private string $userEmail = 'user@email.example';
    private string $adminEmail = 'user_admin@email.example';
    
    private function billingClient()
    {
        $client = self::createTestClient();
        $client->disableReboot();

        $client->getContainer()->set(
            BillingClient::class,
            new BillingClientMock(
                $client->getContainer()->get(TokenStorageInterface::class)
            )
        );
        return $client;
    }
    
    public function createAuthorizedClient($email, $password)
    {
        $client = $this->billingClient();
        $crawler = $client->request('GET', '/login');
        
        $form = $crawler->selectButton('Войти')->form(
            [
                'email' => $email,
                'password' => $password
            ]
        );

        $client->submit($form);
        $client->followRedirect();

        return $client;
    }
}