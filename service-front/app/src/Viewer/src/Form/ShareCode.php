<?php

declare(strict_types=1);

namespace Viewer\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ShareCode extends AbstractForm
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('code', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(['value' => 12])
                ]
            ]);
    }
}