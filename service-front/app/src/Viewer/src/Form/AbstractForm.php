<?php

declare(strict_types=1);

namespace Viewer\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractForm extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // enable CSRF protection for forms
            'csrf_protection' => true,
            'csrf_message' => "As you have not used this service for over 60 minutes, the page has timed out. We've now refreshed the page - please try to sign in again."
        ]);
    }
}