<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Электронная почта',
                'attr' => [
                    'class' => 'form-control mb-3',
                    'placeholder' => 'Электронная почта',
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'mapped' => false,
                'options' => ['attr' => ['class' => 'mb-3 w-100']],
                'attr' => ['autocomplete' => 'new-password'],
                'invalid_message' => 'Введенные пароли не совпадают.',
                'first_options'  => [
                    'label' => 'Пароль',
                    'attr' => [
                        'class' => 'form-control mb-3',
                        'placeholder' => 'Минимум 6 символов',
                    ],
                ],
                'second_options' => [
                    'label' => 'Подтвердите пароль',
                    'attr' => [
                        'class' => 'form-control mb-3',
                        'placeholder' => '',
                    ],
                ],
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}