<?php

declare(strict_types=1);

namespace Common\I18n;

use Laminas\I18n\Translator\Translator;
use Psr\Container\ContainerInterface;

class TranslatorFactory
{
    public function __invoke(ContainerInterface $container): Translator
    {
        $config = $container->get('config');

        if (! isset($config['i18n'])) {
            throw new \RuntimeException('Language file configuration must be specified');
        }

        return Translator::factory($config['i18n']);
    }
}
