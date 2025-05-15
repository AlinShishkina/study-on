<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Form\DataTransformer\CourseToEntityTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class LessonType extends AbstractType
{
    private $entityManager;
    private $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Название',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(message: 'Название урока не может быть пустым'),
                ]
            ])
            ->add('content', TextareaType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'label' => 'Содержание урока',
            ])
            ->add('serialNumber', NumberType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Номер урока',
                'constraints' => [
                    new NotBlank(message: 'Номер урока не может быть пустым'),
                    new Range(
                        notInRangeMessage: 'Больше 1 000 и меньше 1 нельзя :(',
                        min: 1,
                        max: 10000,
                    ),
                ]
            ])
            ->add('course', HiddenType::class, [
                'data' => null,
                'disabled' => true
            ])
        ; 
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class
        ]);
    }
}