<?php

namespace App\Form;

use App\Entity\Lesson;
use App\Form\DataTransformer\CourseToIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LessonType extends AbstractType
{
    private CourseToIdTransformer $courseToIdTransformer;

    public function __construct(CourseToIdTransformer $courseToIdTransformer)
    {
        $this->courseToIdTransformer = $courseToIdTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nameLesson', TextType::class, [
                'label' => 'Название урока',
                'attr' => [
                    'placeholder' => 'Введите название урока...',
                ],
            ])
            ->add('lessonContent', TextareaType::class, [
                'label' => 'Содержание урока',
                'attr' => [
                    'placeholder' => 'Введите содержание урока...',
                    'rows' => 5,
                ],
            ])
            ->add('orderNumber', IntegerType::class, [
                'label' => 'Цена',
                'attr' => [
                    'placeholder' => 'Введите цену урока...',
                ],
            ])
            ->add('course', HiddenType::class, [
                'label' => false,
                'invalid_message' => 'Некорректный курс',
            ]);

        // Добавляем трансформер после создания поля
        $builder->get('course')
            ->addModelTransformer($this->courseToIdTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
        ]);
    }
}
