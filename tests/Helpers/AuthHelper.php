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
        self::createTestClient()->disableReboot();

        self::createTestClient()->getContainer()->set(
            BillingClient::class,
            new BillingClientMock(self::getClient()->getContainer()->get(TokenStorageInterface::class))
        );
        return self::createTestClient();
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