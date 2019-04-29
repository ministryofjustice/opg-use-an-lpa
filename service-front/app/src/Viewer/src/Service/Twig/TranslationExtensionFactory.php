<?php

declare(strict_types=1);

namespace Viewer\Service\Twig;

use Psr\Container\ContainerInterface;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslationExtensionFactory
{

    public function __invoke(ContainerInterface $container): TranslationExtension
    {
        $translator = $container->has(TranslatorInterface::class) ?
            $container->get(TranslatorInterface::class) : null;

        return new TranslationExtension($translator);
    }
}