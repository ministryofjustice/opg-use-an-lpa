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

        return $formFactory->create(
            $definition->getName(), null, []
        );
    }
}
