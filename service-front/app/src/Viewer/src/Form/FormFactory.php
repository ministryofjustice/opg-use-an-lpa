<?php

declare(strict_types=1);

namespace Viewer\Form;

use Symfony\Component\Form\FormFactoryInterface;
use Interop\Container\ContainerInterface;
use DI\Definition\FactoryDefinition;

class FormFactory
{
    public function __invoke(ContainerInterface $container, $definition)
    {

        $formFactory = $container->get(FormFactoryInterface::class);

        $class = $definition->getName();

        $type = $class::getType();

       return $formFactory->create(
            $type, null, []
        );
    }
}
