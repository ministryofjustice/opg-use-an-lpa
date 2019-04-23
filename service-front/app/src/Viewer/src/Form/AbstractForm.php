<?php

declare(strict_types=1);

namespace Viewer\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

abstract class AbstractForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('__csrf', HiddenType::class, [
                'constraints' => new NotBlank()
            ]);
    }
}