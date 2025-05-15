<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;

class CourseToEntityTransformer implements DataTransformerInterface
{
    private $entityManager;
    private $courseId;

    // TODO DataTransformer не должен принимать $courseId
    public function __construct(EntityManagerInterface $entityManager, $courseId)
    {
        $this->entityManager = $entityManager;
        $this->courseId = $courseId;
    }

    public function transform($course): ?int
    {
        if (null === $course) {
            return null;
        }

        return $course->getId();

    }

    public function reverseTransform($value): ?Course
    {                
        if ($this->courseId) {
            $course = $this->entityManager
                ->getRepository(Course::class)
                ->find($this->courseId);

            if (null === $course) {
                throw new TransformationFailedException(sprintf(
                    'Курс с id "%s" не существует!',
                    $this->courseId
                ));
            }

            return $course;
        } else {
            return null;
        }
    }
}