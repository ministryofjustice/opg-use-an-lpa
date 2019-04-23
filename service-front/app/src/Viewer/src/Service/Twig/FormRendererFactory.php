<?php

declare(strict_types=1);

namespace Viewer\Service\Twig;

use Psr\Container\ContainerInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\FormRendererEngineInterface;

class FormRendererFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new FormRenderer(
            $container->get(FormRendererEngineInterface::class)
        );
    }
}