<?php

namespace App\Tests;

use App\Command\ResetSequencesCommand;
use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use App\Service\BillingClient;
use App\Tests\Helpers\AuthHelper;
use App\Tests\Mock\BillingClientMock;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Response;

class CourseFunctionaltest extends AbstractTest
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
     * Проверка наличия ссылки для перехода на детальную страницу курса
     */
    public function testHasLinkToDetailCourse(): void
    {
        $client = self::createTestClient();
        $url = '/courses/';

        $crawler = $client->request('GET', $url);
        $link = $crawler->selectLink('Подробнее')->link();

        $crawler = $client->click($link);

        $this->assertResponseOk();
    }
    
    /**
     * Проверка детальной страницы курса и элементов на ней
     * Role: Admin
     */
    public function testOkDetailCoursePageAdmin(): void
    {
        $client = $this->createAuthorizedClient($this->adminEmail, $this->adminEmail);
        $url = '/courses/1';

        $client->request('GET', $url);

        // страница доступна
        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // есть заголовок, список занятий, кнопки
        $this->assertSelectorExists('h1');
        $this->assertSelectorExists('ul.list-group');
        $this->assertSelectorExists('[role=group]');
        $this->assertSelectorExists('a.btn-dark', 'К списку курсов');
        $this->assertSelectorExists('a.btn-secondary', 'Редактировать');
        $this->assertSelectorExists('a.btn-success', 'Добавить урок');
    }
    
    /**
     * Проверка детальной страницы курса и элементов на ней
     * Role: User
     */
    public function testOkDetailCoursePageUser(): void
    {
        $client = $this->createAuthorizedClient($this->userEmail, $this->userEmail);
        $url = '/courses/1';

        $client->request('GET', $url);

        // страница доступна
        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // есть заголовок, список занятий, кнопки
        $this->assertSelectorExists('h1');
        $this->assertSelectorExists('ul.list-group');
        $this->assertSelectorExists('[role=group]');
        $this->assertSelectorExists('a.btn-dark', 'К списку курсов');
        $this->assertSelectorNotExists('a.btn-secondary', 'Редактировать');
        $this->assertSelectorNotExists('a.btn-success', 'Добавить урок');
    }

    /**
     * Проверка заполнения формы создания нового курса
     * Role: Admin
     */
    public function testCreateOkCourseFormAdmin(): void
    {
        $client = $this->createAuthorizedClient($this->adminEmail, $this->adminEmail);

        $crawler = $client->request('GET', '/courses/new');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $form = $crawler->selectButton('Сохранить')->form();
        $form['course[title]'] = 'Название курса';
        $form['course[description]'] = 'Описание курса';
        $form['course[code]'] = 'code1';

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());

        $crawler = $client->followRedirect();

        $this->assertCount(5, $crawler->filter('.card'));
    }

    /**
     * Проверка заполнения формы создания нового курса
     * Role: User
     */
    public function testCreateCourseFormUser(): void
    {
        $client = $this->createAuthorizedClient($this->userEmail, $this->userEmail);

        $client->request('GET', '/courses/new');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // проверка отправки прямого пост-запроса с данными
        $formData = [
            'course' => [
                'title' => 'Название курса',
                'description' => 'Описание курса',
                'code' => 'code1'
            ]
        ];

        $client->request('POST', '/courses/new', $formData);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * Проверка удаления курса
     * Role: Admin
     */
    public function testDeleteCourse(): void
    {
        $client = $this->createAuthorizedClient($this->adminEmail, $this->adminEmail);
        $url = '/courses/1';

        $crawler = $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $form = $crawler->selectButton('Удалить')->form();
        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());

        $crawler = $client->followRedirect();

        $this->assertCount(3, $crawler->filter('.card'));
    }

    /**
     * Проверка удаления курса
     * Role: User
     */
    public function testDeleteCourseUser(): void
    {
        $client = $this->createAuthorizedClient($this->userEmail, $this->userEmail);
        $url = '/courses/1';

        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // прямой запрос на удаление
        $client->request('POST', '/courses/1');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * Проверка редактирования курса
     * Role: Admin
     */
    public function testEditCourseFormAdmin(): void
    {
        $client = $this->createAuthorizedClient($this->adminEmail, $this->adminEmail);
        $url = '/courses/1';

        $crawler = $client->request(
            'GET',
            "{$url}/edit"
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $form = $crawler->selectButton('Обновить')->form();
        $form['course[title]'] = 'Название курса';
        $form['course[description]'] = 'Описание курса';
        $form['course[code]'] = 'code2';

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertResponseRedirects($url);
    }

    /**
     * Проверка редактирования курса
     * Role: User
     */
    public function testEditCourseFormUser(): void
    {
        $client = $this->createAuthorizedClient($this->userEmail, $this->userEmail);
        $url = '/courses/1/edit';

        $client->request('GET', $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // проверка отправки прямого пост-запроса с данными
        $formData = [
            'course' => [
                'title' => 'Название курса',
                'description' => 'Описание курса',
                'code' => 'code1'
            ]
        ];

        $client->request('POST', $url, $formData);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * Проверка перехода на страницу урока с курса
     */
    public function testNavigateToLessonPage(): void
    {
        $client = $this->createAuthorizedClient($this->userEmail, $this->userEmail);
        $url = '/courses/1';

        $crawler = $client->request('GET', $url);

        $lessonLink = $crawler->filter('a.lesson-item')->first()->link();
        $crawler = $client->click($lessonLink);

        $this->assertResponseOk();
    }

    /**
     * Проверка заполнения формы создания нового курса (невалидные данные)
     */
    public function testCreateErrorCourseForm(): void
    {
        $client = $this->createAuthorizedClient($this->adminEmail, $this->adminEmail);
        $crawler = $client->request('GET', '/courses/new');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // неуникальный код
        $form = $crawler->selectButton('Сохранить')->form();
        $form['course[title]'] = 'Название курса';
        $form['course[description]'] = 'Описание курса';
        $form['course[code]'] = 'js';

        $client->submit($form);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li:not(.breadcrumb-item)',
            'Символьный код плохой!'
        );

        // пустой код
        $form = $crawler->selectButton('Сохранить')->form();
        $form['course[title]'] = 'Название курса';
        $form['course[description]'] = 'Описание курса';
        $form['course[code]'] = '';

        $client->submit($form);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li:not(.breadcrumb-item)',
            'Символьный код не может быть пустым'
        );

        // пустое название
        $form = $crawler->selectButton('Сохранить')->form();
        $form['course[title]'] = '';
        $form['course[description]'] = 'Описание курса';
        $form['course[code]'] = 'code1';

        $client->submit($form);
        $this->assertResponseCode(422);

        $this->assertSelectorTextContains(
            'li:not(.breadcrumb-item)',
            'Название курса не может быть пустым'
        );
    }

    // покупка курса
    public function urlProviderBuyCourse(): \Generator
    {
        yield ['/courses/2/buy', 'Доступ к курсу актуален. Оплата не требуется.'];
        yield ['/courses/1/buy', 'Курс бесплатный. Оплата не требуется.'];
        yield ['/courses/3/buy', 'На вашем счету недостаточно средств.'];
    }

    /**
     * Тест на отправкy прямых запросов на покупку курса
     * @dataProvider urlProviderBuyCourse
     * Role: User
     */
    public function testBuyCourse($url, $message)
    {
        $client = $this->createAuthorizedClient($this->userEmail, $this->userEmail);

        $client->request('POST', $url);

        $this->assertResponseRedirect();

        $crawler = $client->followRedirect();
        $this->assertSelectorExists('.alert');
        $this->assertSelectorTextContains('.alert', $message);
    }

    /**
     * Тест на отправкy прямых запросов на покупку курса
     * Role: User
     */
    public function testBuyCourseSuccess()
    {
        $client = $this->createAuthorizedClient($this->userEmail, $this->userEmail);
    
        // Заходим на страницу курса
        $crawler = $client->request('GET', '/courses/4');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    
        // Находим форму из модального окна
        $form = $crawler->filter('form[action="/courses/4/buy"]')->form();
    
        // Отправляем форму (POST запрос покупки)
        $client->submit($form);
    
        // Проверяем редирект после покупки
        $this->assertResponseRedirect();
    
        // Переходим по редиректу
        $crawler = $client->followRedirect();
    
        // Проверяем наличие успешного сообщения
        $this->assertSelectorExists('.alert');
        $this->assertSelectorTextContains('.alert', 'Курс успешно оплачен');
    }
    
}