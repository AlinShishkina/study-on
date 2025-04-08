<?php

namespace App\Tests;

use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use Symfony\Component\HttpFoundation\Response;

class CourseControllerTest extends GlobalTest
{
    protected function getFixtures(): array
    {
        return [CourseFixtures::class];
    }

    // Проверка корректных статусов GET и содержимого ответа
    public function testGetActionsResponseOk(): void
    {
        $client = self::getClient();
        $entityManager = self::getEntityManager();
        $courses = $entityManager->getRepository(Course::class)->findAll();

        // Проверка списка всех курсов
        $client->request('GET', '/courses');
        if ($client->getResponse()->isRedirect()) {
            // Если редирект, проверьте правильность редиректа
            $this->assertResponseRedirect();
            // Дополнительно проверяем редирект на /courses
            $this->assertRedirectsTo('/courses');
        } else {
            $this->assertResponseOk();
        }
        $this->assertCount(count($courses), $client->getCrawler()->filter('.course-item'));

        foreach ($courses as $course) {
            // Проверка страницы конкретного курса
            $client->request('GET', '/courses/' . $course->getId());
            if ($client->getResponse()->isRedirect()) {
                $this->assertResponseRedirect();
                $this->assertRedirectsTo('/courses/' . $course->getId());
            } else {
                $this->assertResponseOk();
            }

            // Проверка количества уроков на странице курса
            $lessons = $entityManager->getRepository(Lesson::class)->findBy(['course' => $course]);
            $this->assertCount(count($lessons), $client->getCrawler()->filter('.lesson-item'));

            // Проверка страницы редактирования курса
            $client->request('GET', '/courses/' . $course->getId() . '/edit');
            if ($client->getResponse()->isRedirect()) {
                $this->assertResponseRedirect();
                $this->assertRedirectsTo('/courses/' . $course->getId() . '/edit');
            } else {
                $this->assertResponseOk();
            }
        }

        // Проверка страницы создания курса
        $client->request('GET', '/courses/new');
        if ($client->getResponse()->isRedirect()) {
            $this->assertResponseRedirect();
            $this->assertRedirectsTo('/courses/new');
        } else {
            $this->assertResponseOk();
        }
    }

    // ✅ Проверка на 404
    public function urlProviderNotFound(): array
    {
        return [
            ['/courses/99999'],  // Курс с ID, которого нет
            ['/lessons/99999'],  // Урок с ID, которого нет
            ['/courses/99999'],
        ];
    }

    /**
     * @dataProvider urlProviderNotFound
     */
    public function testPageNotFound($url): void
    {
        $client = self::getClient();
        $client->request('GET', $url);
        $this->assertResponseNotFound();
    }

    // ✅ Проверка корректных POST-запросов
    public function testPostActionsResponseOk(): void
    {
        $client = self::getClient();
        $entityManager = self::getEntityManager();
        $courses = $entityManager->getRepository(Course::class)->findAll();

        foreach ($courses as $course) {
            // Проверка редактирования курса (POST)
            $client->request('POST', '/courses/' . $course->getId() . '/edit', [
                'name' => 'Updated Name',
                'description' => 'Updated Description'
            ]);
            if ($client->getResponse()->isRedirect()) {
                $this->assertResponseRedirect();
                $this->assertRedirectsTo('/courses/' . $course->getId());
            } else {
                $this->assertResponseOk();
            }
        }

        // Проверка создания нового курса (POST)
        $client->request('POST', '/courses/new', [
            'name' => 'Новый курс',
            'CharacterCode' => 'new-course',
            'description' => 'Описание нового курса'
        ]);
        if ($client->getResponse()->isRedirect()) {
            $this->assertResponseRedirect();
            $this->assertRedirectsTo('/courses');
        } else {
            $this->assertResponseOk();
        }
    }
}
