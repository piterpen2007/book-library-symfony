<?php

namespace EfTech\BookLibrary\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type as FormElement;
use Symfony\Component\Validator\Constraints as Assert;

class CreateMagazineForm extends CreateBookForm
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('number', FormElement\IntegerType::class, [
            'required' => true,
            'label' => 'Номер журнала',
            'priority' => 150,
            'constraints' => [
                    new Assert\Type(['type' => 'int', 'message' => 'Номер журнала должен быть числом']),
                    new Assert\Positive(
                        ['message' => 'Номер журнала не может быть меньше или равным 0']
                    )
            ]
        ]);
        $builder->get('author_id_list')->setRequired(false);
    }

}