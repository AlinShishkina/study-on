<?php

namespace App\Tests;

use App\Entity\Course;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use App\DataFixtures\CourseFixtures;

class CourseControllerTest extends WebTestCase
{
    public function testCoursesList(): void
{
    $client = static::createClient();
    $entityManager = $client->getContainer()->get('doctrine')->getManager();

  
    $crawler = $client->request('GET', '/courses/');
    
    $this->assertResponseIsSuccessful();
    
    // Получаем все курсы из базы данных
    $courses = $entityManager->getRepository(Course::class)->findAll();

    // Находим строки в таблице, где отображаются курсы
    $shownCourses = $crawler->filter('table.table tbody tr');

    // Проверка, что количество строк в таблице совпадает с количеством курсов
    $this->assertCount(count($courses), $shownCourses);
}

//  Тест на отображение существующих курсов

public function testShowExistingCourse(): void
{
    $client = static::createClient();
    $entityManager = $client->getContainer()->get('doctrine')->getManager();

    // Создание тестового курса 
    $course = new Course();
    $course->setName('Test Course');
    $course->setCharacterCode('T123'); 
    $entityManager->persist($course);
    $entityManager->flush();

    $crawler = $client->request('GET', '/courses/');
    $this->assertResponseIsSuccessful();
    $firstCourseLink = $crawler->filter('table.table tbody tr td a')->first()->link();
    $crawler = $client->click($firstCourseLink);
    $this->assertResponseIsSuccessful();
}

//  Тест на несуществующий курс

public function testShowNonExistingCourse(): void
    {
        $client = static::createClient();

        $client->request('GET', '/courses/999999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }


    // Тест на загрузку страницы "Создать новый курс"

    public function testNewCourseGet(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses/');
    
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a:contains("Создать курс")');
    
        $link = $crawler->selectLink('Создать курс')->link();
        $crawler = $client->click($link);
    
        // Проверка, что загрузилась страница создания курса
        $this->assertResponseIsSuccessful();
    
        // Проверка наличия полей формы
        $this->assertSelectorExists('input[name="course[name]"]');
        $this->assertSelectorExists('input[name="course[characterCode]"]');
        $this->assertSelectorExists('textarea[name="course[description]"]');
    }
    
 // Тест на то, что новый курс создался и отобразился на фронтенде

 public function testNewCoursePostValidData(): void
{
    $client = static::createClient();
    $entityManager = $client->getContainer()->get('doctrine')->getManager();

    $coursesBeforeCount = count($entityManager->getRepository(Course::class)->findAll());
    $crawler = $client->request('GET', '/courses/');
    $this->assertResponseIsSuccessful();
    $link = $crawler->selectLink('Создать курс')->link();
    $crawler = $client->click($link);
    $this->assertResponseIsSuccessful();
    $form = $crawler->selectButton('Сохранить')->form([
        'course[name]' => 'Тестовый курс',
        'course[characterCode]' => 'test-code-123',
        'course[description]' => 'Тестовое описание',
    ]);

    $client->submit($form);

    // Проверка редиректа (303 See Other)
    $this->assertResponseRedirects('/courses/', Response::HTTP_SEE_OTHER);
    $client->followRedirect();
    $this->assertResponseIsSuccessful();

    // Проверка, что курс добавлен
    $coursesAfter = $entityManager->getRepository(Course::class)->findAll();
    $this->assertCount($coursesBeforeCount + 1, $coursesAfter);

    $newCourse = $entityManager->getRepository(Course::class)->findOneBy([
        'characterCode' => 'test-code-123',
    ]);

    $this->assertNotNull($newCourse);
    $this->assertEquals('Тестовый курс', $newCourse->getName());
    $this->assertEquals('Тестовое описание', $newCourse->getDescription());

    // Проверка, что отображается на странице
    $crawler = $client->request('GET', '/courses/');
    $this->assertResponseIsSuccessful();
    $this->assertSelectorTextContains('body', 'Тестовый курс');
}


// Тест на пустоту кода урока
public function testNewPostEmptyCode(): void
{
    $client = static::createClient();


    $crawler = $client->request('GET', '/courses/');
    $this->assertResponseIsSuccessful();

    $link = $crawler->filter('a:contains("Создать курс")')->link();
    $crawler = $client->click($link);

    $this->assertResponseIsSuccessful();

    $formButton = $crawler->selectButton('Сохранить');

    $form = $formButton->form([
        'course[name]' => 'Тестовый курс',
        'course[characterCode]' => '', 
        'course[description]' => 'Тестовое описание',
    ]);

    $crawler = $client->submit($form);

    echo $client->getResponse()->getContent();

    $this->assertResponseStatusCodeSame(422);
    $content = $client->getResponse()->getContent();
    $this->assertStringContainsString(
        'Код курса не может быть пустым', 
        $content
    );
}

// Тест на то что курс с таким кодом существует 

public function testNewPostNotUniqueCode(): void
{
    $client = static::createClient();
    $entityManager = $client->getContainer()->get('doctrine')->getManager();
    $existingCourse = $entityManager->getRepository(Course::class)->findOneBy([]);
    $crawler = $client->request('GET', '/courses/');
    $this->assertResponseIsSuccessful();
    $link = $crawler->filter('a:contains("Создать курс")')->link();
    $crawler = $client->click($link);
    $this->assertResponseIsSuccessful();
    $formButton = $crawler->selectButton('Сохранить');
    
    $form = $formButton->form([
        'course[name]' => 'Тестовый курс',
        'course[characterCode]' => $existingCourse->getCharacterCode(), 
        'course[description]' => 'Тестовое описание',
    ]);

    $crawler = $client->submit($form);
    $this->assertResponseStatusCodeSame(422);
    $content = $client->getResponse()->getContent();
    $this->assertStringContainsString(
        'Курс с таким кодом уже существует', 
        $content
    );
}


// Тест на то что код написан неправильно

public function testNewPostInvalidCode(): void
{
    $client = static::createClient();
    $entityManager = $client->getContainer()->get('doctrine')->getManager();
    $existingCourse = $entityManager->getRepository(Course::class)->findOneBy([]);
    $crawler = $client->request('GET', '/courses/');
    $this->assertResponseIsSuccessful();
    $link = $crawler->filter('a:contains("Создать курс")')->link();
    $crawler = $client->click($link);

    $this->assertResponseIsSuccessful();

    $formButton = $crawler->selectButton('Сохранить');
    
    $form = $formButton->form([
        'course[name]' => 'Тестовый курс',
        'course[characterCode]' => '1890+#/.!,',
        'course[description]' => 'Тестовое описание',
    ]);

    $crawler = $client->submit($form);

    $this->assertResponseStatusCodeSame(422);

    $content = $client->getResponse()->getContent();
    $this->assertStringContainsString(
        'Код курса может содержать только буквы, цифры, дефисы и подчеркивания', 
        $content
    );
}

// Тест на непустое название курса

public function testNewPostEmptyName(): void
{
    $client = static::createClient();
    $entityManager = $client->getContainer()->get('doctrine')->getManager();
    $existingCourse = $entityManager->getRepository(Course::class)->findOneBy([]);

    $crawler = $client->request('GET', '/courses/');
    $this->assertResponseIsSuccessful();

    $link = $crawler->filter('a:contains("Создать курс")')->link();
    $crawler = $client->click($link);

    $this->assertResponseIsSuccessful();


    $formButton = $crawler->selectButton('Сохранить');
    
    $form = $formButton->form([
        'course[name]' => '', // Пустое название
        'course[characterCode]' => '123',
        'course[description]' => 'тестовое описание',
    ]);

    $crawler = $client->submit($form);

    $this->assertResponseStatusCodeSame(422);

    $content = $client->getResponse()->getContent();
    $this->assertStringContainsString(
        'Название курса не может быть пустым', 
        $content
    );
}

public function testEditPostLongTitle(): void
{
    $client = static::createClient();
    $entityManager = $client->getContainer()->get('doctrine')->getManager();

    $crawler = $client->request('GET', '/courses/');
    $this->assertResponseIsSuccessful();

    $firstCourseLink = $crawler->filter('table.table tbody tr td a')->first()->link();
    $crawler = $client->click($firstCourseLink);
    $this->assertResponseIsSuccessful();

    $link = $crawler->filter('a:contains("Редактировать курс")')->link();
    $crawler = $client->click($link);
    $this->assertResponseIsSuccessful();

    $formButton = $crawler->selectButton('Обновить');
    $form = $formButton->form();

    // Получаем текущие значения для проверки после отправки
    $courseCode = $form['course[characterCode]']->getValue();
    $courseTitleBefore = $form['course[name]']->getValue();

    // Заполняем форму с длинным названием
    $form['course[name]'] = str_repeat('LONG_TITLE_', 100); // >255 символов
    $form['course[characterCode]'] = $courseCode;

    $client->submit($form);

    $this->assertResponseStatusCodeSame(422);

    $content = $client->getResponse()->getContent();
    $this->assertStringContainsString(
        'Название курса не может быть длиннее 255 символов',
        $content
    );



$sameCourse = $entityManager->getRepository(Course::class)->findOneBy(['characterCode' => $courseCode]);
$this->assertEquals($courseTitleBefore, $sameCourse->getName());

}


public function testDelete(): void
{
    $client = static::createClient();
    $this->loadFixtures();

    // Теперь тест будет работать
    $crawler = $client->request('GET', '/courses/');
    $this->assertResponseIsSuccessful();

    $deleteForms = $crawler->filter('form[action^="/courses/"][method="post"]');
    $this->assertGreaterThan(0, $deleteForms->count(), 'Форма удаления не найдена');

    $formNode = $deleteForms->first();
    $action = $formNode->attr('action');
    $csrfToken = $formNode->filter('input[name="_token"]')->attr('value');

    $client->request('POST', $action, [
        '_token' => $csrfToken,
    ]);

    $this->assertResponseRedirects('/courses/');
    $client->followRedirect();
    $this->assertSelectorTextNotContains('body', 'Тестовый курс');
}
}





