<?php

namespace App\Tests;

use App\Command\ResetSequencesCommand;
use App\DataFixtures\CourseFixtures;
use App\Tests\Helpers\AuthHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Response;

class ProfileFunctionalTest extends AbstractTest
{
    use AuthHelper;

    protected function getFixtures(): array
    {
        // обнуление сиквансов перед загрузкой фикстур
        $command_reset_seq = new ResetSequencesCommand($this->getEntityManager()->getConnection());
        $input = new ArrayInput([]);
        $output = new NullOutput();
        $command_reset_seq->run($input, $output);

        return [CourseFixtures::class];
    }

    public function testOkProfilePage(): void
    {
        $client = $this->createAuthorizedClient($this->adminEmail, $this->adminEmail);
        $url = "/profile/";

        $client->request('GET', $url);

        // страница доступна
        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertSelectorExists('h1', 'Профиль');
        $this->assertSelectorExists('#balance-field');
        $this->assertSelectorExists('#link-transaction', 'История транзакций');
        $this->assertSelectorExists('#logout-profile-btn', 'Выход');
    }

    public function testOkTransactionsPage(): void
    {
        $client = $this->createAuthorizedClient($this->adminEmail, $this->adminEmail);
        $url = "/profile/transactions";

        $client->request('GET', $url);

        // страница доступна
        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertSelectorExists('h1', 'Транзакции');
        $this->assertSelectorExists('.table');
    }
}