<?php

declare(strict_types=1);

namespace Common\I18n;

use Psr\Container\ContainerInterface;
use Symfony\Component\Translation\Loader\MoFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

class SymfonyTranslatorFactory
{
    public function __invoke(ContainerInterface $container): TranslatorInterface
    {
        $config = $container->get('config');

        if (! isset($config['i18n']['default_locale'])) {
            throw new \RuntimeException('Language file configuration must be specified');
        }

        $translator = new Translator($config['i18n']['default_locale']);
        $translator->setFallbackLocales([$config['i18n']['default_locale']]);
        $translator->addLoader('mo', new MoFileLoader());

        // loop our configured languages and push them into the translator
        foreach ($config['i18n']['languages'] as $language) {
            $translator->addResource(
                $language['format'],
                $language['resource'],
                $language['locale'],
            );
        }

        return $translator;
    }
}
