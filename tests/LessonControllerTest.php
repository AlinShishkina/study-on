<?php

namespace App\Tests;

use App\DataFixtures\CourseFixtures;
use App\Entity\Lesson;

class LessonControllerTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [CourseFixtures::class];
    }

    public function testDeleteLesson(): void
    {
        $this->login();

        $client = $this->getClientInstance();
        $lessons = $this->getEntityManager()->getRepository(Lesson::class)->findAll();
        $lesson = reset($lessons);
        $this->assertNotNull($lesson, 'Урок для удаления не найден');

        $token = $this->generateCsrfToken('delete' . $lesson->getId());

        $client->request('POST', '/lessons/' . $lesson->getId() . '/delete', [
            '_token' => $token,
        ]);
        $this->assertResponseRedirect();

        $client->followRedirect();
        $this->assertResponseOk();
    }

    public function testEditLesson(): void
    {
        $this->login();

        $client = $this->getClientInstance();
        $lessons = $this->getEntityManager()->getRepository(Lesson::class)->findAll();
        $lesson = reset($lessons);
        $this->assertNotNull($lesson, 'Урок для редактирования не найден');

        $crawler = $client->request('GET', '/lessons/' . $lesson->getId() . '/edit');
        $this->assertResponseOk();

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[nameLesson]' => 'Updated Lesson Name',
            'lesson[lessonContent]' => 'Updated content',
            'lesson[orderNumber]' => 5,
        ]);
        $client->submit($form);

        $this->assertResponseRedirect();
        $client->followRedirect();
        $this->assertResponseOk();

        $crawler = $client->request('GET', '/lessons/' . $lesson->getId());
        $this->assertResponseOk();
        $this->assertSelectorTextContains('.lesson_name', 'Updated Lesson Name');
    }

    public function testEditLessonError(): void
    {
        $this->login();

        $client = $this->getClientInstance();
        $lessons = $this->getEntityManager()->getRepository(Lesson::class)->findAll();
        $lesson = reset($lessons);
        $this->assertNotNull($lesson, 'Урок для редактирования не найден');

        $crawler = $client->request('GET', '/lessons/' . $lesson->getId() . '/edit');
        $this->assertResponseOk();

        // Отправляем форму с пустым именем, чтобы проверить ошибку валидации
        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[nameLesson]' => '',
            'lesson[lessonContent]' => 'Content',
            'lesson[orderNumber]' => 1,
        ]);
        $client->submit($form);

        $this->assertResponseCode(422);
        $this->assertSelectorExists('.invalid-feedback');
    }
}
