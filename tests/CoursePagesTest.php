<?php

namespace App\Tests;

use App\Command\ResetSequencesCommand;
use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class CoursePagesTest extends AbstractTest

{
    protected function setUp(): void
    {
        parent::setUp();
        // Подменяем биллинг-клиент на мок
        $this->getClient()->disableReboot();
        $this->getClient()->getContainer()->set(
            \App\Service\BillingClient::class,
            new \App\Tests\Mock\BillingClientMock(
                $this->getClient()->getContainer()->get(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class)
            )
        );
    }
    protected function getFixtures(): array
    {
        // обнуление сиквансов перед загрузкой фикстур
        $command_reset_seq = new ResetSequencesCommand($this->getEntityManager()->getConnection());
        $input = new ArrayInput([]);
        $output = new NullOutput();
        $command_reset_seq->run($input, $output);

        return [CourseFixtures::class];
    }
    
    public function urlProviderSuccessful(): \Generator
    {
        yield ["/courses/1"];
        yield ['/courses/'];
    }
    /**
     * Тест на доступность страниц
     * @dataProvider urlProviderSuccessful
     */
    public function testPageSuccessful($url): void
    {
        $client = static::createTestClient();
        $client->request('GET', $url);
        $this->assertResponseOk();
    }
    
    public function urlProviderRedirectToLogin(): \Generator
    {
        yield ['/courses/new'];
        yield ["/courses/1/edit"];
    }
    /**
     * Тест на доступность страниц
     * @dataProvider urlProviderRedirectToLogin
     */
    public function testPageRedirectToLogin($url): void
    {
        $client = static::createTestClient();
        $client->request('GET', $url);
        $this->assertTrue($client->getResponse()->isRedirect('/login'));
    }

    public function urlProviderNotFound(): \Generator
    {
        yield ['/123/'];
        yield ['/courses/1000'];
        yield ['/courses/1000/edit'];
    }

    /**
     * Тест на отсуствие доступа к закрытым / несущ. страницам
     * @dataProvider urlProviderNotFound
     */
    public function testPageNotFound($url): void
    {
        $client = self::getClient();
        $client->request('GET', $url);
        $this->assertResponseNotFound();
    }

    /**
     * Осуществление переадрессации с главной страницы / на /courses
     */
  
}