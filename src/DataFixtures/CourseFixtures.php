<?php

namespace App\DataFixtures;

use App\Entity\Lesson;
use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $courses = [
            [
                'name' => 'Основы Python',
                'CharacterCode' => 'python-basics',
                'description' => 'Изучение основ программирования на Python, включая синтаксис, структуры данных и ООП.'
            ],
            [
                'name' => 'Веб-разработка с Django',
                'CharacterCode' => 'django-webdev',
                'description' => 'Создание веб-приложений с использованием Django, SQL и REST API.'
            ],
            [
                'name' => 'Разработка мобильных приложений на Flutter',
                'CharacterCode' => 'flutter-dev',
                'description' => 'Создание кроссплатформенных мобильных приложений с Flutter и Dart.'
            ],
            [
                'name' => 'Алгоритмы и структуры данных',
                'CharacterCode' => 'algorithms-data-structures',
                'description' => 'Разбираем алгоритмы поиска, сортировки, графы, динамическое программирование и другие темы.'
            ],
            [
                'name' => 'Разработка на Vue.js',
                'CharacterCode' => 'vue-js-dev',
                'description' => 'Созданиие фронтенд-приложений с использованием Vue.js, Vue Router и Vuex.'
            ]
        ];

        foreach ($courses as $data) {
            $course = new Course();

            $course->setName($data['name']);
            $course->setCharacterCode($data['CharacterCode']);
            $course->setDescription($data['description']);

            for ($i = 1; $i <= 5; $i++) {
                $lesson = new Lesson();
                $lesson->setNameLesson("Урок $i");
                $lesson->setLessonContent("Контент урока $i для курса {$data['name']}.");
                $lesson->setOrderNumber($i);
                $lesson->setCourse($course);

                $manager->persist($lesson);
            }

            $manager->persist($course);
        }

        $manager->flush();
    }
}
