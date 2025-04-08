<?php

// src/Form/DataTransformer/CourseToIdTransformer.php

namespace App\Form\DataTransformer;

use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CourseToIdTransformer implements DataTransformerInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    // Преобразование объекта в id (для отображения в поле)
    public function transform($course): ?int
    {
        if (!$course instanceof Course) {
            return null;
        }

        return $course->getId();
    }

    // Преобразование id в объект (для использования в сущности)
    public function reverseTransform($courseId): ?Course
    {
        if (!$courseId) {
            return null;
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);

        if (!$course) {
            throw new TransformationFailedException(sprintf('Курс с ID "%s" не найден!', $courseId));
        }

        return $course;
    }
}
