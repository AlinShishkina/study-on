<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => 'Электронная почта',
                'constraints' => [
                    new Email(message: 'Введенный адрес электронной почты невалиден.'),
                    new NotBlank(message: 'Почта не может быть пустой.'),
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'invalid_message' => 'Введенные пароли не совпадают.',
                'first_options'  => [
                    'label' => 'Пароль',
                ],
                'second_options' => [
                    'label' => 'Подтвердите пароль',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Пароль не может быть пустым.',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Пароль должен содержать минимум 6 символов',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
