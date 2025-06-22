<?php

namespace App\Form;

use App\Entity\Lesson;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LessonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Название',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('content', TextareaType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'label' => 'Содержание урока',
            ])
            ->add('serialNumber', NumberType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Номер урока',
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