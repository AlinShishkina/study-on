<?php

namespace App\Form;

use App\Entity\Course;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', null, [
                'attr' => ['class' => 'form-control'],
                'label' => 'МногоСимвольный код',
                'constraints' => [
                    new NotBlank(message: 'Символьный код не может быть пустым'),
                ],
            ])
            ->add('title', null, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Название',
                'constraints' => [
                    new NotBlank(message: 'Название курса не может быть пустым'),
                ],

            ])
            ->add('description', TextareaType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Продающее описание',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}