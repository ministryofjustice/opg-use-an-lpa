<?php

declare(strict_types=1);

namespace Viewer\Service\Form;

use Symfony\Component\Form\Exception\BadMethodCallException;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder as SymfonyFormBuilder;

class FormBuilder extends SymfonyFormBuilder
{
    private $concreteFormClass = null;

    public function setConcreteFormClass(string $type)
    {
        $this->concreteFormClass = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        if ($this->locked) {
            throw new BadMethodCallException('FormBuilder methods cannot be accessed anymore once the builder is turned into a FormConfigInterface instance.');
        }

        if (isset($this->concreteFormClass)) {
            $form = new $this->concreteFormClass($this->getFormConfig());
        } else {
            $form = new Form($this->getFormConfig());
        }


        foreach ($this->all() as $child) {
            // Automatic initialization is only supported on root forms
            $form->add($child->setAutoInitialize(false)->getForm());
        }

        if ($this->getAutoInitialize()) {
            // Automatically initialize the form if it is configured so
            $form->initialize();
        }

        return $form;
    }
}
