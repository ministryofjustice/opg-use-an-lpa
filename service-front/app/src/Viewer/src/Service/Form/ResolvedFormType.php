<?php

declare(strict_types=1);

namespace Viewer\Service\Form;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\ButtonBuilder;
use Symfony\Component\Form\ButtonTypeInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\ResolvedFormType as SymfonyResolvedFormType;
use Symfony\Component\Form\SubmitButtonBuilder;
use Symfony\Component\Form\SubmitButtonTypeInterface;

class ResolvedFormType extends SymfonyResolvedFormType
{

    protected function newBuilder($name, $dataClass, FormFactoryInterface $factory, array $options)
    {

        if ($this->getInnerType() instanceof ButtonTypeInterface) {
            return new ButtonBuilder($name, $options);
        }

        if ($this->getInnerType() instanceof SubmitButtonTypeInterface) {
            return new SubmitButtonBuilder($name, $options);
        }

        // Our FormBuilder
        return new FormBuilder($name, $dataClass, new EventDispatcher(), $factory, $options);
    }

}
