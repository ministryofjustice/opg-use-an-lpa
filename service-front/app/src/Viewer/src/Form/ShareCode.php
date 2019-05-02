<?php

declare(strict_types=1);

namespace Viewer\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Viewer\Service\Form\FormBuilder;

class ShareCode extends AbstractForm
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($builder instanceof FormBuilder) {
            $builder->setConcreteFormClass(ShareCodeForm::class);
        }

        $builder
            ->add('lpa_code', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Regex([
                        'pattern' => '/[\w\d]{4,4}-[\w\d]{4,4}-[\w\d]{4,4}/',
                        'message' => 'Enter an LPA share code in the correct format.'
                    ])
                ]
            ]);
    }
}