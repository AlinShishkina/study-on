<?php

namespace App\Tests;

use App\Command\ResetSequencesCommand;
use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class LessonPagesTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        // обнуление сиквансов перед загрузкой фикстур
        $command_reset_seq = new ResetSequencesCommand($this->getEntityManager()->getConnection());
        $input = new ArrayInput([]);
        $output = new NullOutput();
        $command_reset_seq->run($input, $output);

        return [CourseFixtures::class];
    }
    
    public function urlProviderRedirectToLogin(): \Generator
    {
        yield ['/lessons/new/3'];
        yield ['/lessons/2'];
        yield ["/lessons/2/edit"];
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
        yield ['/lessons/'];
        yield ['/lessons/1000'];
        yield ['/lessons/1000/edit'];
    }

    /**
     * Тест на отсуствие доступа к закрытым / несущ. страницам
     * @dataProvider urlProviderNotFound
     */
    public function testPageNotFound($url): void
    {
        $client = self::createTestClient();
        $client->request('GET', $url);
        $this->assertResponseNotFound();
    }
}