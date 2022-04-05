<?php

namespace EfTech\BookLibrary\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type as FormElement;
use Symfony\Component\Validator\Constraints as Assert;

class LoginForm extends \Symfony\Component\Form\AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setMethod('POST')
            ->add(
                'login',
                FormElement\TextType::class,
                [
                    'required' => true,
                    'label' => 'Логин',
                    'constraints' => [
                        new Assert\Type([
                            'type' => 'string',
                            'message' => 'Логин имеет неверный формат'
                        ])
                    ]
                ]
            )->add(
                'password',
                FormElement\PasswordType::class,
                [
                    'required' => true,
                    'label' => 'Пароль',
                    'constraints' => [
                        new Assert\Type([
                            'type' => 'string',
                            'message' => 'password имеет неверный формат'
                        ])
                    ]
                ]
            )->add(
                'submit',
                FormElement\SubmitType::class,
                [
                    'label' => 'Войти'
                ]
            );
        parent::buildForm($builder, $options);
    }

}