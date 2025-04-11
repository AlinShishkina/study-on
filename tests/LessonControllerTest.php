<?php

namespace App\Tests;

use App\Entity\Course;
use App\Entity\Lesson;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\DataFixtures\CourseFixtures;

class LessonControllerTest extends WebTestCase

{

    protected function getFixtures(): array
    {
        return [CourseFixtures::class, LessonFixtures::class]; // Подгружаем фикстуры для курсов и уроков
    }

    public function testLessonsList(): void
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        $course = $entityManager->getRepository(Course::class)->find(532); // Получаем курс с id = 532
        $this->assertNotNull($course, 'Курс с id 532 не найден');
        $crawler = $client->request('GET', '/courses/' . $course->getId());
        $this->assertResponseIsSuccessful();
    
        // Проверка, что курс отображается
        $this->assertSelectorTextContains('h1', $course->getName());
    
        // Получаем все элементы уроков на странице
        $shownLessons = $crawler->filter('ul#lessons-list li');
    
        // Проверка, что отображаются все 5 уроков
        $this->assertCount(5, $shownLessons, 'Не все уроки отображаются на странице курса');
    
        
    }


    public function testShowExistingLesson(): void
{
    $client = static::createClient();
    $entityManager = $client->getContainer()->get('doctrine')->getManager();

    $course = $entityManager->getRepository(Course::class)->find(532); 

    
    $this->assertNotNull($course, 'Курс с id 532 не найден');

    // Создаем несколько уроков для курса
    for ($i = 1; $i <= 5; $i++) {
        $lesson = new Lesson();
        $lesson->setNameLesson("Lesson $i");
        $lesson->setLessonContent("Content of Lesson $i.");
        $lesson->setCourse($course);
        $entityManager->persist($lesson);
    }
    $entityManager->flush();
    $lesson = $course->getLessons()[0]; // Берем первый урок
    $crawler = $client->request('GET', '/lessons/' . $lesson->getId());
    $this->assertResponseIsSuccessful();
    $this->assertSelectorTextContains('h1', $lesson->getNameLesson());
    $this->assertSelectorTextContains('div.mb-4', $lesson->getLessonContent());  
}

public function testShowNonExistingLesson(): void
{
    $client = static::createClient();
    $nonExistingLessonId = 9999;  

    // Запрашиваем страницу несуществующего урока
    $client->request('GET', "/lessons/{$nonExistingLessonId}");
    $this->assertResponseStatusCodeSame(404);  
}
 


public function testGetActionsResponseOk(): void
{
    // Проверка страниц всех курсов
    $client = static::createClient();  
    $entityManager = $client->getContainer()->get('doctrine')->getManager();  

    $lessons = $entityManager->getRepository(Lesson::class)->findAll();  
    foreach ($lessons as $lesson) {
        // Страница урока
        $client->request('GET', '/lessons/' . $lesson->getId());
        $this->assertResponseIsSuccessful();  // Лучше использовать assertResponseIsSuccessful() вместо assertResponseOk()

        // Страница сздания нового урока
        $client->request('GET', '/lessons/new/' . $lesson->getCourse()->getId());
        $this->assertResponseIsSuccessful();

        // Страница редактирования урока
        $client->request('GET', '/lessons/' . $lesson->getId() . '/edit');
        $this->assertResponseIsSuccessful();
    }
}



     public function testNewLessonGet(): void
    {
        $client = static::createClient();
    
        // Создаём курс для привязки к уроку
        $course = new Course();
        $course->setName('Тестовый курс');
        $course->setCharacterCode('123');  
        
        // Сохраняем курс в базе данных
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($course);
        $entityManager->flush();
        $courseId = $course->getId();
        $crawler = $client->request('GET', "/lessons/new/{$courseId}");
        $this->assertResponseIsSuccessful();
    
        // Проверка, что форма для создания урока существует
        $form = $crawler->filter('form[name="lesson"]');
        $this->assertGreaterThan(0, $form->count(), 'Lesson creation form not found');
    
        $this->assertGreaterThan(0, $crawler->filter('input#lesson_nameLesson')->count(), 'Lesson name input not found');
        $this->assertGreaterThan(0, $crawler->filter('textarea#lesson_lessonContent')->count(), 'Lesson content textarea not found');
        $this->assertGreaterThan(0, $crawler->filter('input#lesson_orderNumber')->count(), 'Lesson order number input not found');
    
        // Отправка формы с данными
        $form = $crawler->selectButton('Сохранить')->form();
        $form['lesson[nameLesson]'] = 'Тестовый урок';
        $form['lesson[lessonContent]'] = 'Содержание тестового урока';
        $form['lesson[orderNumber]'] = 1;

        $client->submit($form);
        $this->assertResponseRedirects('/courses/' . $courseId);
    
        $lesson = $entityManager->getRepository(Lesson::class)->findOneBy(['nameLesson' => 'Тестовый урок']);
        $this->assertNotNull($lesson);
        $this->assertEquals('Тестовый урок', $lesson->getNameLesson());
        $this->assertEquals('Содержание тестового урока', $lesson->getLessonContent());
    }
    


public function testShowLesson(): void
{
    $client = static::createClient();
    $course = new Course();
    $course->setName('Курс для урока');
    $course->setCharacterCode('123');
    $entityManager = self::getContainer()->get('doctrine')->getManager();
    $entityManager->persist($course);
    
    $lesson = new Lesson();
    $lesson->setNameLesson('Урок 1');
    $lesson->setLessonContent('Содержание урока');
    $lesson->setCourse($course);
    $entityManager->persist($lesson);
    $entityManager->flush();

    $courseId = $course->getId();
    $lessonId = $lesson->getId();

    $crawler = $client->request('GET', "/lessons/{$lessonId}");

    $this->assertResponseIsSuccessful();

    $this->assertSelectorTextContains('h1', 'Урок 1');


    $this->assertSelectorTextContains('div.mb-4', 'Содержание урока');
    
    $this->assertSelectorTextContains('a', 'Курс для урока');

    $link = $crawler->filter('a')->link();
    $this->assertStringContainsString('/courses/' . $courseId, $link->getUri());
}


public function testNewLessonPostEmptyTitle(): void
{
    $client = static::createClient();
    $entityManager = self::getContainer()->get('doctrine')->getManager();

    $course = new Course();
    $course->setName('Тестовый курс');
    $course->setCharacterCode('123');  
   
    $entityManager->persist($course);
    $entityManager->flush();

    $courseId = $course->getId();
    $crawler = $client->request('GET', "/lessons/new/{$courseId}");

    $this->assertResponseIsSuccessful();

    $form = $crawler->selectButton('Сохранить')->form();
    $form['lesson[nameLesson]'] = '';  // Пустое название урока
    $form['lesson[lessonContent]'] = 'Содержание тестового урока';
    $form['lesson[orderNumber]'] = 1;

    $client->submit($form);
    $this->assertResponseStatusCodeSame(422);
    $content = $client->getResponse()->getContent();
    $this->assertStringContainsString(
        'Название урока не может быть пустым',
        $content
    );
}


public function testNewLessonPostLongTitle(): void
{
    $client = static::createClient();
    $entityManager = self::getContainer()->get('doctrine')->getManager();

    $course = new Course();
    $course->setName('Тестовый курс');
    $course->setCharacterCode('123'); 

    $entityManager->persist($course);
    $entityManager->flush();

    $courseId = $course->getId();

    $crawler = $client->request('GET', "/lessons/new/{$courseId}");

   
    $this->assertResponseIsSuccessful();

    $form = $crawler->selectButton('Сохранить')->form();
    $form['lesson[nameLesson]'] = str_repeat('TEST', 1000);  // Слишком длинное название
    $form['lesson[lessonContent]'] = 'Содержание тестового урока';
    $form['lesson[orderNumber]'] = 1;

    $client->submit($form);
    $this->assertResponseStatusCodeSame(422);

    $content = $client->getResponse()->getContent();

    $this->assertStringContainsString(
        'Название урока не может быть длиннее 255 символов',
        $content
    );
}

public function testNewLessonPostEmptyContent(): void
{
    $client = static::createClient();
    $entityManager = self::getContainer()->get('doctrine')->getManager();

    $course = new Course();
    $course->setName('Тестовый курс');
    $course->setCharacterCode('123');  

    $entityManager->persist($course);
    $entityManager->flush();

    $courseId = $course->getId();

    $crawler = $client->request('GET', "/lessons/new/{$courseId}");


    $this->assertResponseIsSuccessful();

    $form = $crawler->selectButton('Сохранить')->form();
    $form['lesson[nameLesson]'] = 'Тестовое название';
    $form['lesson[lessonContent]'] = '';  // Пустое содержание
    $form['lesson[orderNumber]'] = 1;


    $client->submit($form);
    $this->assertResponseStatusCodeSame(422);

  
    $content = $client->getResponse()->getContent();

    $this->assertStringContainsString(
        'Содержание урока не может быть пустым',
        $content
    );
}


public function testNewLessonPostEmptyOrderNumber(): void
{
    $client = static::createClient();
    $entityManager = self::getContainer()->get('doctrine')->getManager();

    $course = new Course();
    $course->setName('Тестовый курс');
    $course->setCharacterCode('123');  
    
    $entityManager->persist($course);
    $entityManager->flush();
    $courseId = $course->getId();

    $crawler = $client->request('GET', "/lessons/new/{$courseId}");

    $this->assertResponseIsSuccessful();

    $form = $crawler->selectButton('Сохранить')->form();
    $form['lesson[nameLesson]'] = 'Тестовое название';
    $form['lesson[lessonContent]'] = 'Содержание тестового урока';
    $form['lesson[orderNumber]'] = '';  // Пустой порядковый номер
    $form['lesson[course]'] = $courseId;
    $client->submit($form);
    $this->assertResponseStatusCodeSame(422);


    $content = $client->getResponse()->getContent();
    $this->assertStringContainsString(
        'Порядковый номер не может быть пустым',
        $content
    );
}

public function testNewLessonPostNegativeOrderNumber(): void
{
    $client = static::createClient();
    $entityManager = self::getContainer()->get('doctrine')->getManager();

    $course = new Course();
    $course->setName('Тестовый курс');
    $course->setCharacterCode('123'); 

    $entityManager->persist($course);
    $entityManager->flush();

    $courseId = $course->getId();

  
    $crawler = $client->request('GET', "/lessons/new/{$courseId}");


    $this->assertResponseIsSuccessful();

    $form = $crawler->selectButton('Сохранить')->form();
    $form['lesson[nameLesson]'] = 'Тестовое название';
    $form['lesson[lessonContent]'] = 'Содержание тестового урока';
    $form['lesson[orderNumber]'] = -100;  // Отрицательный порядковый номер
    $form['lesson[course]'] = $courseId;

    $client->submit($form);
    $this->assertResponseStatusCodeSame(422);

    $content = $client->getResponse()->getContent();

    $this->assertStringContainsString(
        'Порядковый номер должен быть от 1 до 10 000',
        $content
    );
}

public function testNewLessonPostLargeOrderNumber(): void
{
    $client = static::createClient();
    $entityManager = self::getContainer()->get('doctrine')->getManager();

    $course = new Course();
    $course->setName('Тестовый курс');
    $course->setCharacterCode('123');  
    
    $entityManager->persist($course);
    $entityManager->flush();

    $courseId = $course->getId();
    $crawler = $client->request('GET', "/lessons/new/{$courseId}");

    $this->assertResponseIsSuccessful();

    $form = $crawler->selectButton('Сохранить')->form();
    $form['lesson[nameLesson]'] = 'Тестовое название';
    $form['lesson[lessonContent]'] = 'Содержание тестового урока';
    $form['lesson[orderNumber]'] = 1000000;  // Слишком большой порядковый номер
    $form['lesson[course]'] = $courseId;
    $client->submit($form);
    $this->assertResponseStatusCodeSame(422);


    $content = $client->getResponse()->getContent();

    $this->assertStringContainsString(
        'Порядковый номер должен быть от 1 до 10 000',
        $content
    );
}


public function testEditLessonGet(): void
{
    $client = static::createClient();
    $entityManager = self::getContainer()->get('doctrine')->getManager();

    $course = new Course();
    $course->setName('Тестовый курс');
    $course->setCharacterCode('123');
    $entityManager->persist($course);

    $lesson = new Lesson();
    $lesson->setNameLesson('Тестовый урок');
    $lesson->setLessonContent('Содержание урока');
    $lesson->setOrderNumber(5);
    $lesson->setCourse($course);
    $entityManager->persist($lesson);

    $entityManager->flush();


    $lessonId = $lesson->getId();

    
    $crawler = $client->request('GET', "/lessons/{$lessonId}/edit");


    $this->assertResponseIsSuccessful();


    $this->assertGreaterThan(0, $crawler->filter('form')->count());
    $this->assertGreaterThan(0, $crawler->filter('input[name="lesson[nameLesson]"]')->count());
    $this->assertGreaterThan(0, $crawler->filter('textarea[name="lesson[lessonContent]"]')->count());
    $this->assertGreaterThan(0, $crawler->filter('input[name="lesson[orderNumber]"]')->count());
}

public function testEditLessonPostValidData(): void
{
    $client = static::createClient();
    $entityManager = self::getContainer()->get('doctrine')->getManager();

    $course = new Course();
    $course->setName('Тестовый курс');
    $course->setCharacterCode('123');
    $entityManager->persist($course);

    $lesson = new Lesson();
    $lesson->setNameLesson('Старое название');
    $lesson->setLessonContent('Старое содержание');
    $lesson->setOrderNumber(1);
    $lesson->setCourse($course);
    $entityManager->persist($lesson);

    $entityManager->flush();

    $lessonId = $lesson->getId();

    $crawler = $client->request('GET', "/lessons/{$lessonId}/edit");
    $this->assertResponseIsSuccessful();

    // Находим форму и заполняем её новыми данными
    $form = $crawler->selectButton('Обновить')->form();
    $form['lesson[nameLesson]'] = 'Новое название';
    $form['lesson[lessonContent]'] = 'Новое содержание';
    $form['lesson[orderNumber]'] = 2;

    $client->submit($form);

    $this->assertResponseRedirects(
        "/lessons/{$lessonId}",
        Response::HTTP_SEE_OTHER
    );


    $entityManager->clear();

    $updatedLesson = $entityManager->getRepository(Lesson::class)->find($lessonId);
    $this->assertEquals('Новое название', $updatedLesson->getNameLesson());
    $this->assertEquals('Новое содержание', $updatedLesson->getLessonContent());
    $this->assertEquals(2, $updatedLesson->getOrderNumber());
}



}
