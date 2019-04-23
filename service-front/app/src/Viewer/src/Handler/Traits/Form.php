<?php

declare(strict_types=1);

namespace Viewer\Handler\Traits;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

trait Form
{
    /**
     * Creates a form using the passed in FormFactory and specified form class.
     *
     * Optionally binds to a POPO entity representing the form data.
     *
     * @param FormFactoryInterface $formFactory
     * @param string $formType
     * @param null|mixed $entity
     * @return FormInterface
     */
    public function createForm(FormFactoryInterface $formFactory, string $formType, $entity = null) : FormInterface
    {
        return $formFactory->create($formType, $entity);
    }
}
