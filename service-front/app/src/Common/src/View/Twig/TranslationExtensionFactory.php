<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Acpr\I18n\NodeVisitor\TranslationNodeVisitor;
use Acpr\I18n\TranslationExtension;
use Acpr\I18n\TranslatorInterface;
use Psr\Container\ContainerInterface;

class TranslationExtensionFactory
{
    public function __invoke(ContainerInterface $container): TranslationExtension
    {
        return new TranslationExtension(
            $container->get(TranslatorInterface::class),
            $container->get(TranslationNodeVisitor::class)
        );
    }
}
