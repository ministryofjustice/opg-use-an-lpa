<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Psr\Container\ContainerInterface;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Contracts\Translation\TranslatorInterface;

class SymfonyTranslationExtensionFactory
{
    public function __invoke(ContainerInterface $container): TranslationExtension
    {
        return new TranslationExtension(
            $container->get(TranslatorInterface::class)
        );
    }
}
