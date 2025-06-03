<?php

namespace App\Tests;

use App\Command\ResetSequencesCommand;
use App\DataFixtures\CourseFixtures;
use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuthTest extends AbstractTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->getClient()->disableReboot();
        $this->getClient()->getContainer()->set(
            BillingClient::class,
            new BillingClientMock(
                $this->getClient()->getContainer()->get(TokenStorageInterface::class)
            )
        );
    }

    protected function getFixtures(): array
    {
        $command = new ResetSequencesCommand($this->getEntityManager()->getConnection());
        $command->run(new ArrayInput([]), new NullOutput());

        return [CourseFixtures::class];
    }

    public function urlProviderSuccessful(): \Generator
    {
        yield ['/login'];
        yield ['/registration'];
    }

    /** @dataProvider urlProviderSuccessful */
    public function testPageSuccessful(string $url): void
    {
        $this->getClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();
    }

    public function testLoginSuccess(): void
    {
        $client = $this->getClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Войти')->form([
            'email' => 'user@email.example',
            'password' => 'user@email.example'
        ]);

        $client->submit($form);

        // ожидаем редирект на /courses/
        $this->assertResponseRedirects('/courses/');
    }

    public function testLoginFail(): void
    {
        $client = $this->getClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Войти')->form([
            'email' => 'user@email.example',
            'password' => 'wrong_password'
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/login');
    }

    public function testRegisterSuccess(): void
    {
        $client = $this->getClient();
        $crawler = $client->request('GET', '/registration');

        $form = $crawler->selectButton('Зарегистрироваться')->form([
            'user_registration[email]' => 'newuser@example.com',
            'user_registration[password][first]' => 'newpassword',
            'user_registration[password][second]' => 'newpassword',
        ]);

        $client->submit($form);

        $this->assertResponseIsSuccessful();
    }

    public function testRegisterFail(): void
    {
        $client = $this->getClient();
        $crawler = $client->request('GET', '/registration');

        $form = $crawler->selectButton('Зарегистрироваться')->form([
            'user_registration[email]' => 'user@email.example',
            'user_registration[password][first]' => 'password',
            'user_registration[password][second]' => 'password',
        ]);

        $client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-danger', 'Произошла ошибка во время регистрации: Сервис временно недоступен.');
    }
}
