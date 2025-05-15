<?php

namespace App\Tests;

use App\Command\ResetSequencesCommand;
use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use App\Tests\Helpers\AuthHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Response;

class LessonFunctionaltest extends AbstractTest
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

    /**
     * Проверка состава страницы детальной страницы урока
     * Role: Admin
     */
    public function testStructureLessonPageAdmin()
    {
        $client = $this->createAuthorizedClient($this->adminEmail, $this->adminEmail);

        $url = "/lessons/1";
        $client->request('GET', $url);

        // страница доступна
        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // есть заголовок, контент, кнопки
        $this->assertSelectorExists('h1', 1 .'. '. 'Введение в PHP');
        $this->assertSelectorExists('div.lesson-content');
        $this->assertSelectorExists('a.btn-dark', 'Назад к курсу');
        $this->assertSelectorExists('a.btn-secondary', 'Редактировать');
        $this->assertSelectorExists('button.btn-danger', 'Удалить');
    }

    /**
     * Проверка состава страницы детальной страницы урока
     * Role: User
     */
    public function testStructureLessonPageUser()
    {
        $client = $this->createAuthorizedClient($this->userEmail, $this->userEmail);

        $url = "/lessons/1";
        $client->request('GET', $url);

        // страница доступна
        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // есть заголовок, контент, кнопки
        $this->assertSelectorExists('h1', 1 .'. '. 'Введение в PHP');
        $this->assertSelectorExists('div.lesson-content');
        $this->assertSelectorExists('a.btn-dark', 'Назад к курсу');
        $this->assertSelectorNotExists('a.btn-secondary', 'Редактировать');
        $this->assertSelectorNotExists('button.btn-danger', 'Удалить');
    }

    /**
     * Создание нового урока для курса
     */
    public function testCreateLessonForm()
    {
        $client = $this->createAuthorizedClient($this->adminEmail, $this->adminEmail);

        $url = "/lessons/new/1";
        
        $crawler = $client->request('GET', $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $form = $crawler->selectButton('Сохранить')->form();
        $form['lesson[name]'] = 'Название урока';
        $form['lesson[content]'] = 'Описание курса';
        $form['lesson[serialNumber]'] = 4;

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());

        $crawler = $client->followRedirect();

        $this->assertCount(
            4,
            $crawler->filter('a.lesson-item')
        );
    }

    /**
     * Создание нового урока (невалидные данные)
     */
    public function testFailCreateLessonForm()
    {
        $client = $this->createAuthorizedClient($this->adminEmail, $this->adminEmail);

        $url = "/lessons/new/1";
        
        $crawler = $client->request('GET', $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // пустое название
        $form = $crawler->selectButton('Сохранить')->form();
        $form['lesson[name]'] = '';
        $form['lesson[content]'] = 'Описание курса';
        $form['lesson[serialNumber]'] = 4;

        $client->submit($form);

        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li:not(.breadcrumb-item)',
            'Название урока не может быть пустым'
        );

        // пустой номер урока
        $form = $crawler->selectButton('Сохранить')->form();
        $form['lesson[name]'] = 'Название';
        $form['lesson[content]'] = 'Описание курса';
        $form['lesson[serialNumber]'] = null;

        $client->submit($form);

        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li:not(.breadcrumb-item)',
            'Номер урока не может быть пустым'
        );

        // номер урока больше возможного
        $form = $crawler->selectButton('Сохранить')->form();
        $form['lesson[name]'] = 'Название';
        $form['lesson[content]'] = 'Описание курса';
        $form['lesson[serialNumber]'] = 100500;

        $client->submit($form);

        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li:not(.breadcrumb-item)',
            'Больше 1 000 и меньше 1 нельзя :('
        );
    }
    /**
     * Редактирование урока
     */
    public function testEditLessonForm()
    {
        $client = $this->createAuthorizedClient($this->adminEmail, $this->adminEmail);
        $url = "/lessons/1";
        
        $crawler = $client->request('GET', "$url/edit");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $form = $crawler->selectButton('Обновить')->form();
        $form['lesson[name]'] = 'Название урока';
        $form['lesson[content]'] = 'Описание курса';
        $form['lesson[serialNumber]'] = 3;

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertResponseRedirects("/courses/1");
    }
    /**
     * Удаление урока
     */
    public function testDeleteLesson()
    {
        $client = $this->createAuthorizedClient($this->adminEmail, $this->adminEmail);

        $url = "/lessons/1";
        $crawler = $client->request('GET', $url);

        $form = $crawler->selectButton('Удалить')->form();
        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertResponseRedirects("/courses/1");

        $crawler = $client->followRedirect();

        $this->assertCount(
            2,
            $crawler->filter('a.lesson-item')
        );
    }

    public function urlProviderUserRequests(): \Generator
    {
        
        yield ['/lessons/new/3', Response::HTTP_FORBIDDEN, true];
        yield ['/lessons/2', Response::HTTP_OK];
        yield ["/lessons/2/edit", Response::HTTP_FORBIDDEN, true];
    }
    /**
     * Тест на проверку доступности страниц
     * и возможности отправки прямых запросов на созд/ред
     * @dataProvider urlProviderUserRequests
     * Role: User
     */
    public function testUserRequests($url, $code, $includeFormData = false): void
    {
        $client = $this->createAuthorizedClient($this->userEmail, $this->userEmail);

        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame($code);

        if ($includeFormData) {
            $formData = [
                'lesson' => [
                    'name' => 'Название',
                    'content' => 'Контент',
                    'serialNumber' => 3
                ]
            ];
    
            $client->request('POST', $url, $formData);
        } else { // это запрос на удаление урока
            $client->request('POST', $url);
        }
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}