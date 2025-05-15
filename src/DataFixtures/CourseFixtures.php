<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    private static $courses = [
        [
            'name' => 'Курс по программированию на PHP',
            'description' => 'Описание курса по программированию на PHP',
            'code' => 'php',
            'lessons' => [
                ['name' => 'Введение в PHP', 'description' => 'Описание первого урока по PHP'],
                ['name' => 'Основы PHP', 'description' => 'Описание второго урока по PHP'],
                ['name' => 'Работа с массивами в PHP', 'description' => 'Описание третьего урока по PHP'],
            ],
        ],
        [
            'name' => 'Курс по веб-разработке на JavaScript',
            'code' => 'js',
            'description' => 'Описание курса по веб-разработке на JavaScript',
            'lessons' => [
                ['name' => 'Введение в JavaScript', 'description' => 'Описание первого урока по JavaScript'],
                ['name' => 'Основы JavaScript', 'description' => 'Описание второго урока по JavaScript'],
                ['name' => 'Работа с DOM в JavaScript', 'description' => 'Описание третьего урока по JavaScript'],
            ],
        ],
        [
            'name' => 'Курс по разработке мобильных приложений на Swift',
            'code' => 'swift',
            'description' => 'Описание курса по разработке мобильных приложений на Swift',
            'lessons' => [
                ['name' => 'Введение в Swift', 'description' => 'Описание первого урока по Swift'],
                ['name' => 'Основы Swift', 'description' => 'Описание второго урока по Swift'],
                ['name' => 'Работа с графикой в Swift', 'description' => 'Описание третьего урока по Swift'],
                ['name' => 'Работа с сетью в Swift', 'description' => 'Описание четвертого урока по Swift'],
                ['name' => 'Разработка пользовательского интерфейса в Swift', 'description' => 'Описание пятого урока по Swift'],
            ],
        ],
        [
            'name' => 'Курс по разработке на Ruby',
            'code' => 'ruby',
            'description' => 'Описание курса по разработке мобильных приложений на Swift',
            'lessons' => [
                ['name' => 'Введение в Swift', 'description' => 'Описание первого урока по Swift'],
                ['name' => 'Основы Swift', 'description' => 'Описание второго урока по Swift'],
                ['name' => 'Работа с графикой в Swift', 'description' => 'Описание третьего урока по Swift'],
                ['name' => 'Работа с сетью в Swift', 'description' => 'Описание четвертого урока по Swift'],
                ['name' => 'Разработка пользовательского интерфейса в Swift', 'description' => 'Описание пятого урока по Swift'],
            ],
        ],
    ];
    
    public function load(ObjectManager $manager): void
    {
        foreach ($this::$courses as $course) 
        {
            $courseEntity = new Course();
            $courseEntity->setTitle($course['name']);
            $courseEntity->setCode($course['code']);
            $courseEntity->setDescription($course['description']);

            $manager->persist($courseEntity);

            foreach ($course['lessons'] as $key => $lesson) 
            {
                $lessonEntity = new Lesson();
                $lessonEntity->setName($lesson['name']);
                $lessonEntity->setContent($lesson['description']);
                $lessonEntity->setCourse($courseEntity);
                $lessonEntity->setSerialNumber($key + 1);

                $manager->persist($lessonEntity);
            }
        }

        $manager->flush();
    }
}