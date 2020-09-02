<?php

declare(strict_types=1);

namespace Common\I18n;

use Acpr\I18n\Translator;
use Acpr\I18n\TranslatorInterface;
use Psr\Container\ContainerInterface;

class TranslatorFactory
{
    public function __invoke(ContainerInterface $container): TranslatorInterface
    {
        $config = $container->get('config');

        if (! isset($config['i18n']['default_locale'])) {
            throw new \RuntimeException('Language file configuration must be specified');
        }

        $gettextTranlsator = $container->get(\Gettext\GettextTranslator::class);
        $gettextTranlsator
            ->setLanguage($config['i18n']['default_locale'])
            ->loadDomain($config['i18n']['default_domain'], $config['i18n']['locale_path']);

        return new Translator($gettextTranlsator);
    }
}
