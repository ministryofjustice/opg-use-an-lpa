<?php

declare(strict_types=1);

namespace Common\Middleware\I18n;

use Acpr\I18n\Translator;
use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;

/**
 * Configuration for setting a default locale should look like the following:
 *
 * <code>
 * 'i18n' => [
 *     'default_locale' => 'en_GB',
 * ]
 * </code>
 */
class SetLocaleMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): SetLocaleMiddleware
    {
        $config = $container->has('config') ? $container->get('config') : [];

        return new SetLocaleMiddleware(
            $container->get(UrlHelper::class),
            $container->get(Translator::class),
            $config['i18n']['default_locale'] ?? null
        );
    }
}
