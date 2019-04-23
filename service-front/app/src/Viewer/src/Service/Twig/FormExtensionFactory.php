<?php

declare(strict_types=1);

namespace Viewer\Service\Twig;

use Psr\Container\ContainerInterface;
use Symfony\Bridge\Twig\Extension\FormExtension;

class FormExtensionFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new FormExtension();
    }
}